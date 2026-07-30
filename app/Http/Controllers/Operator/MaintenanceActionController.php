<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Unit;
use App\Services\MaintenanceService;
use App\Support\Money\Money;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Operator Maintenance writes — the REAL open + assign actions driven from the styled Maintenance board
 * (design pass · Maintenance UI) via POST → controller → {@see MaintenanceService} → redirect (PRG), the
 * same tenant-safe pattern as the housekeeping/front-desk mutations. A normal POST re-runs the web
 * middleware (SetTenantForWebRequest sets context), so every write is genuinely tenant-scoped, and both
 * `{ticket}`/`unit_id` are resolved through the tenant-scoped global scope AFTER the middleware — so a
 * cross-org id 404s. No schema/guarantee/availability path is touched here; the service does the work
 * (open() may take the unit out of service; assign() is guarded to active tickets only).
 *
 * Only the real `category`/`priority` enum values are accepted — an unknown value is rejected by
 * validation, never invented. A guarded illegal transition surfaces as a clean flash error, not a 500.
 *
 * The lifecycle writes (start/resolve/cancel) are driven from the styled ticket-detail screen and each
 * is offered ONLY when the real current state allows it — but the service is still the authority: an
 * illegal transition is caught and surfaced as a clean flash, never a 500. Assign is shared by the board
 * and the detail; an `origin=detail` hint returns the operator to the detail after the write.
 */
class MaintenanceActionController extends Controller
{
    /** Open a new ticket on one of the org's OWN units. */
    public function store(Request $request, MaintenanceService $maintenance): RedirectResponse
    {
        if ($this->paused()) {
            return redirect()->route('app.maintenance')->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'appliance', 'structural', 'other'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'takes_out_of_service' => ['sometimes', 'boolean'],
        ]);

        // Tenant-scope the unit: a foreign unit id 404s (never opens a ticket on another org's unit).
        $unit = Unit::query()->findOrFail($validated['unit_id']);

        $ticket = $maintenance->open([
            'unit_id' => $unit->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'takes_out_of_service' => (bool) ($validated['takes_out_of_service'] ?? false),
        ]);

        return redirect()->route('app.maintenance')
            ->with('mt_notice', "Ticket “{$ticket->title}” opened on unit {$unit->code}.");
    }

    /** Assign (or unassign) a ticket to an active member of the org. */
    public function assign(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'string'],
        ]);

        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return $this->backTo($request, $model)->with('mt_error', $this->pausedMessage());
        }

        $userId = $validated['user_id'] ?? null;
        if ($userId !== null && $userId !== '') {
            // The assignee must be an active member of THIS org — a foreign/unknown user is rejected.
            $orgId = app(TenantContext::class)->id();
            $isMember = Membership::query()
                ->where('organization_id', $orgId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists();

            if (! $isMember) {
                abort(422, 'That user is not a member of this organization.');
            }
        } else {
            $userId = null;   // "— Unassigned —"
        }

        try {
            $maintenance->assign($model, $userId);
        } catch (RuntimeException) {
            // e.g. the ticket is already resolved/cancelled — guarded, surfaced cleanly, not a 500.
            return $this->backTo($request, $model)->with('mt_error', 'That ticket can’t be reassigned — it’s already closed.');
        }

        return $this->backTo($request, $model)->with('mt_notice', 'Ticket assignment updated.');
    }

    /** Start work on an OPEN ticket (guarded: illegal from any other state → clean flash). */
    public function start(MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        try {
            $maintenance->start($model);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)
                ->with('mt_error', 'That ticket can’t be started — it’s no longer open.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', 'Work started on this ticket.');
    }

    /**
     * Resolve an IN-PROGRESS ticket with an optional cost. On an OWNED unit a cost posts a deductible
     * owner expense through the certified path (the service decides — this controller only passes Money);
     * on an operator-owned unit no owner expense is created. Cost is captured in the property currency.
     */
    public function resolve(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'labor' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'parts' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $model = MaintenanceTicket::query()->with('unit.property')->findOrFail($ticket);   // tenant-scoped → 404

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        $currency = (string) $model->unit->property->currency;
        // #89 — the REAL labor/parts split (labor+parts=total). A legacy single `cost` still works.
        $labor = $this->money($validated['labor'] ?? null, $currency);
        $parts = $this->money($validated['parts'] ?? null, $currency);
        $cost = ($labor === null && $parts === null) ? $this->money($validated['cost'] ?? null, $currency) : null;

        try {
            $maintenance->resolve($model, $cost, $labor, $parts);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)
                ->with('mt_error', 'That ticket can’t be resolved — it isn’t in progress.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', 'Ticket resolved.');
    }

    /** Cancel an ACTIVE ticket (guarded: illegal from a terminal state → clean flash). */
    public function cancel(MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        try {
            $maintenance->cancel($model);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)
                ->with('mt_error', 'That ticket can’t be cancelled — it’s already closed.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', 'Ticket cancelled.');
    }

    /** Flag / clear the "waiting on a part" state on an ACTIVE ticket (#89 — the Parts-on-order panel). */
    public function setParts(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'awaiting_parts' => ['sometimes', 'boolean'],
            'part_name' => ['nullable', 'string', 'max:160'],
            'part_eta' => ['nullable', 'date'],
        ]);

        $awaiting = $request->boolean('awaiting_parts');
        try {
            $maintenance->setAwaitingParts($model, $awaiting, $validated['part_name'] ?? null, $validated['part_eta'] ?? null);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', 'That ticket is closed — parts can’t be updated.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', $awaiting ? 'Marked as awaiting parts.' : 'Parts marked as arrived.');
    }

    /**
     * Attach a proof/issue PHOTO to a ticket (#89) — the app's file-upload security discipline (as
     * {@see RoomTypePhotoController}): validate real image CONTENT, a generated filename, the `public` disk, a
     * `Media` row on the ticket (`collection='mt_photo'`). Tenant-scoped; read-only respected.
     */
    public function uploadPhoto(Request $request, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('photo');
        $ext = $file->extension() ?: 'jpg';                      // from the VALIDATED file, never the client name
        $name = (string) Str::ulid().'.'.$ext;
        $path = $file->storeAs('maintenance/'.$model->getKey(), $name, 'public');

        Media::create([
            'mediable_type' => $model->getMorphClass(),
            'mediable_id' => $model->getKey(),
            'collection' => 'mt_photo',
            'disk' => 'public',
            'path' => $path,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'alt' => 'Maintenance photo · '.(string) $model->title,
        ]);

        return redirect()->route('app.maintenance.show', $model->id)->with('mt_notice', 'Photo added.');
    }

    /** Remove a ticket photo — deletes the stored bytes + the row. Tenant + ticket scoped; read-only respected. */
    public function deletePhoto(Request $request, string $ticket, string $media): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        $row = Media::query()
            ->where('mediable_type', $model->getMorphClass())
            ->where('mediable_id', $model->getKey())
            ->findOrFail($media);

        Storage::disk((string) $row->disk)->delete((string) $row->path);
        $row->delete();

        return redirect()->route('app.maintenance.show', $model->id)->with('mt_notice', 'Photo removed.');
    }

    /** Where an assign returns to: the ticket detail when it came from there, else the board. */
    private function backTo(Request $request, MaintenanceTicket $model): RedirectResponse
    {
        return $request->input('origin') === 'detail'
            ? redirect()->route('app.maintenance.show', $model->id)
            : redirect()->route('app.maintenance');
    }

    /** Parse a numeric major-unit form value into Money, or null when blank/zero. */
    private function money(mixed $input, string $currency): ?Money
    {
        if ($input === null || $input === '' || (float) $input <= 0) {
            return null;
        }

        return Money::of((int) round(((float) $input) * 100), $currency);
    }

    private function paused(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function pausedMessage(): string
    {
        return 'Your workspace is paused (read-only) — maintenance changes are off until you resume.';
    }
}
