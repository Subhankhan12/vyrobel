<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\Operator\MaintenanceReassignedNotification;
use App\Notifications\Operator\MaintenanceResolvedNotification;
use App\Services\MaintenanceService;
use App\Support\Export\CsvSafe;
use App\Support\Maintenance\MaintenanceListParams;
use App\Support\Money\Money;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    /** The real category and priority enums — the only values a work order may carry. */
    private const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /** Open a new ticket on one of the org's OWN units. */
    public function store(Request $request, MaintenanceService $maintenance): RedirectResponse
    {
        if ($this->paused()) {
            return $this->listReturn($request, redirect()->route('app.maintenance'))->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
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

        return $this->listReturn($request, redirect()->route('app.maintenance'))
            ->with('mt_notice', "Ticket “{$ticket->title}” opened on unit {$unit->code}.");
    }

    /** Assign (or unassign) a ticket to an active member of the org. */
    public function assign(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'string'],
            // #108 reassign flow — an optional real reason (recorded on the audit/domain payload) + notify-both.
            'reason' => ['nullable', 'string', 'max:80'],
            'notify' => ['sometimes', 'boolean'],
        ]);

        $model = MaintenanceTicket::query()->with('unit', 'assignedTo')->findOrFail($ticket);   // tenant-scoped → cross-org 404
        $previousAssignee = $model->assignedTo;

        if ($this->paused()) {
            return $this->backTo($request, $model)->with('mt_error', $this->pausedMessage());
        }

        $userId = $validated['user_id'] ?? null;
        if ($userId !== null && $userId !== '') {
            // ⚠️ The assignee must be real STAFF of THIS org — not merely an active member. A Membership is
            // NOT operator access: External Owners hold one, so the old membership-only check ACCEPTED a
            // POSTed owner id even after the picker stopped offering it. Fixing the picker alone would have
            // been cosmetic; this is the guard that makes it real. See {@see StaffRoster::staffUserIds()}.
            if (! in_array((string) $userId, app(StaffRoster::class)->staffUserIds(), true)) {
                abort(422, 'That user is not assignable staff in this organization.');
            }
        } else {
            $userId = null;   // "— Unassigned —"
        }

        try {
            $maintenance->assign($model, $userId, $validated['reason'] ?? null);
        } catch (RuntimeException) {
            // e.g. the ticket is already resolved/cancelled — guarded, surfaced cleanly, not a 500.
            return $this->backTo($request, $model)->with('mt_error', 'That ticket can’t be reassigned — it’s already closed.');
        }

        // "Notify both technicians" — real, preference-gated pings: the new assignee (it's now theirs) and the
        // prior assignee (they're off it). Only when a real user changed + the toggle was kept on (#39).
        if ($request->boolean('notify')) {
            $unit = (string) ($model->unit->code ?? '');
            $newAssignee = $userId !== null ? User::query()->find($userId) : null;
            if ($newAssignee !== null) {
                $newAssignee->notify(new MaintenanceReassignedNotification((string) $model->id, (string) $model->title, $unit, true));
            }
            if ($previousAssignee !== null && (string) $previousAssignee->id !== (string) $userId) {
                $previousAssignee->notify(new MaintenanceReassignedNotification((string) $model->id, (string) $model->title, $unit, false));
            }
        }

        return $this->backTo($request, $model)->with('mt_notice', 'Ticket assignment updated.');
    }

    /**
     * Edit an OPEN or IN-PROGRESS work order's details (2026-09-29, the board's Edit): title, description, category and
     * priority, through {@see MaintenanceService::updateDetails()}, which refuses a closed ticket (its title is already on
     * the owner's expense line). Gated `can:maintenance.manage` on the route like every maintenance write; the ticket is
     * resolved tenant-scoped (cross-org 404) AND through the viewer's property allow-list (#38) — a manager scoped to one
     * property cannot edit another's. `validate()` because the board renders `$errors` and reopens the dialog on what
     * was typed.
     */
    public function update(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = $this->accessibleTicket($ticket);
        $default = redirect()->route('app.maintenance.show', $model->id);

        if ($this->paused()) {
            return $this->listReturn($request, $default)->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
        ]);

        try {
            $maintenance->updateDetails($model, $validated);
        } catch (RuntimeException) {
            return $this->listReturn($request, $default)->with('mt_error', 'That work order is closed — its details can’t be changed.');
        }

        return $this->listReturn($request, $default)->with('mt_notice', 'Work order updated.');
    }

    /**
     * BULK ASSIGN (#GATE 6.2) — assign (or unassign) the SELECTED tickets to one member, each through the
     * certified {@see MaintenanceService::assign} (never a raw update). Tenant-scoped: a foreign id simply
     * does not resolve (fail-safe isolation — it is never touched). A ticket that can't be reassigned (closed)
     * is SKIPPED, not a 500; the flash reports done/skipped honestly.
     */
    public function bulkAssign(Request $request, MaintenanceService $maintenance): RedirectResponse
    {
        if ($this->paused()) {
            return $this->listReturn($request, redirect()->route('app.maintenance'))->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1', 'max:500'],
            'ticket_ids.*' => ['string'],
            'user_id' => ['nullable', 'string'],
        ]);

        $userId = is_string($validated['user_id'] ?? null) && $validated['user_id'] !== '' ? (string) $validated['user_id'] : null;
        if ($userId !== null) {
            // Staff-only, same reasoning as the single assign above (bulk path).
            if (! in_array((string) $userId, app(StaffRoster::class)->staffUserIds(), true)) {
                abort(422, 'That user is not assignable staff in this organization.');
            }
        }

        // Tenant-scoped whereIn — a foreign/unknown id is silently absent (never acted on).
        $tickets = MaintenanceTicket::query()->whereIn('id', $validated['ticket_ids'])->get();
        [$done, $skipped] = [0, 0];
        foreach ($tickets as $t) {
            try {
                $maintenance->assign($t, $userId, null);
                $done++;
            } catch (RuntimeException) {
                $skipped++;   // already closed — skipped, not fatal
            }
        }

        return $this->listReturn($request, redirect()->route('app.maintenance'))->with('mt_notice', $this->bulkNotice('Assigned', $done, $skipped));
    }

    /**
     * BULK RESOLVE (#GATE 6.2) — resolve the SELECTED active tickets through the certified service (no cost
     * captured in bulk — per-ticket cost stays on the detail). An OPEN ticket is started first, then resolved;
     * an already-resolved/cancelled ticket is skipped. Returns each unit to sale by default (lift OOO). No raw
     * writes; each transition is the guarded service. Tenant-scoped; done/skipped reported honestly.
     */
    public function bulkResolve(Request $request, MaintenanceService $maintenance): RedirectResponse
    {
        if ($this->paused()) {
            return $this->listReturn($request, redirect()->route('app.maintenance'))->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1', 'max:500'],
            'ticket_ids.*' => ['string'],
        ]);

        $tickets = MaintenanceTicket::query()->whereIn('id', $validated['ticket_ids'])->with('unit.property')->get();
        [$done, $skipped] = [0, 0];
        foreach ($tickets as $t) {
            try {
                if ($t->status === 'open') {
                    $maintenance->start($t);
                    $t->refresh();
                }
                if ($t->status === 'in_progress') {
                    $maintenance->resolve($t, null, null, null, null, null, false);   // no cost; return to sale
                    $done++;
                } else {
                    $skipped++;   // resolved / cancelled — nothing to do
                }
            } catch (RuntimeException) {
                $skipped++;
            }
        }

        return $this->listReturn($request, redirect()->route('app.maintenance'))->with('mt_notice', $this->bulkNotice('Resolved', $done, $skipped));
    }

    /**
     * BULK EXPORT (#GATE 6.2) — stream a CSV of the SELECTED tickets (a READ; no mutation). Tenant-scoped, so a
     * foreign id is silently absent. Maintenance tickets carry no guest PII (#28) — unit code + work fields only.
     */
    public function bulkExport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1', 'max:500'],
            'ticket_ids.*' => ['string'],
        ]);

        $tickets = MaintenanceTicket::query()->whereIn('id', $validated['ticket_ids'])
            ->with('unit.property', 'assignedTo')->orderByDesc('opened_at')->get();

        return response()->streamDownload(function () use ($tickets): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, CsvSafe::row(['Title', 'Unit', 'Category', 'Priority', 'Status', 'Assignee', 'Reported', 'Cost']));
            foreach ($tickets as $t) {
                $currency = (string) ($t->unit->property->currency ?? 'EUR');
                fputcsv($out, CsvSafe::row([
                    // Free-text (user-controllable) columns pass through the ONE CSV formula-injection guard
                    // (App\Support\Export\CsvSafe — the same guard the costs export uses; never a raw =/+/-/@).
                    (string) $t->title,
                    (string) ($t->unit->code ?? '—'),
                    (string) $t->category,   // enum — safe
                    (string) $t->priority,   // enum — safe
                    (string) $t->status,     // enum — safe
                    (string) ($t->assignedTo->name ?? '—'),
                    CarbonImmutable::parse($t->opened_at)->toDateString(),
                    $t->cost_minor !== null ? number_format(((int) $t->cost_minor) / 100, 2).' '.$currency : '',
                ]));
            }
            fclose($out);
        }, 'work-orders-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** "Assigned 3 · 1 skipped (closed)" — an honest bulk result line. */
    private function bulkNotice(string $verb, int $done, int $skipped): string
    {
        $msg = $verb.' '.$done.' '.Str::plural('work order', $done);

        return $skipped > 0 ? $msg.' · '.$skipped.' skipped (already closed)' : $msg.'.';
    }

    /** Start work on an OPEN ticket (guarded: illegal from any other state → clean flash). */
    public function start(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))->with('mt_error', $this->pausedMessage());
        }

        try {
            $maintenance->start($model);
        } catch (RuntimeException) {
            return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))
                ->with('mt_error', 'That ticket can’t be started — it’s no longer open.');
        }

        return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))
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
            // #108 Mark-resolved modal — Time spent (hours, informational), What was done, the toggles.
            'time_spent' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'return_to_sale' => ['sometimes', 'boolean'],
            'notify' => ['sometimes', 'boolean'],
        ]);

        $model = MaintenanceTicket::query()->with('unit.property', 'reportedBy')->findOrFail($ticket);   // tenant-scoped → 404

        if ($this->paused()) {
            return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))->with('mt_error', $this->pausedMessage());
        }

        $currency = (string) $model->unit->property->currency;
        // #89 — the REAL labor/parts split (labor+parts=total). A legacy single `cost` still works.
        $labor = $this->money($validated['labor'] ?? null, $currency);
        $parts = $this->money($validated['parts'] ?? null, $currency);
        $cost = ($labor === null && $parts === null) ? $this->money($validated['cost'] ?? null, $currency) : null;

        // Return to sale is the default; toggling it OFF keeps the unit out of order (lift later from the rack).
        $returnToSale = $request->has('return_to_sale') ? $request->boolean('return_to_sale') : true;
        $laborHours = isset($validated['time_spent']) && $validated['time_spent'] !== '' ? (float) $validated['time_spent'] : null;

        try {
            $maintenance->resolve($model, $cost, $labor, $parts, $laborHours, $validated['resolution'] ?? null, ! $returnToSale);
        } catch (RuntimeException) {
            return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))
                ->with('mt_error', 'That work order can’t be resolved — it’s already closed.');   // open and in progress both resolve (2026-10-05)
        }

        // "Notify the front desk" — a real, preference-gated ping to the reporter (never a dead toggle, #39).
        if ($request->boolean('notify') && $model->reportedBy !== null) {
            $model->reportedBy->notify(new MaintenanceResolvedNotification((string) $model->id, (string) $model->title, (string) $model->unit->code, $returnToSale));
        }

        return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))
            ->with('mt_notice', 'Ticket resolved.');
    }

    /** Cancel an ACTIVE ticket (guarded: illegal from a terminal state → clean flash). */
    public function cancel(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))->with('mt_error', $this->pausedMessage());
        }

        try {
            $maintenance->cancel($model);
        } catch (RuntimeException) {
            return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))
                ->with('mt_error', 'That ticket can’t be cancelled — it’s already closed.');
        }

        return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))
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
     * #108 — post a real Activity update (Note / Progress / Part-ordered) to the ticket's timeline, through the
     * certified {@see MaintenanceService::addNote} (shared polymorphic notes). Tenant + ticket scoped; read-only
     * respected; `can:maintenance.manage` at the route (#20/#30).
     */
    public function addNote(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return redirect()->route('app.maintenance.show', $model->id)->with('mt_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'kind' => ['nullable', Rule::in(MaintenanceService::NOTE_KINDS)],
            'part_name' => ['nullable', 'string', 'max:160'],
            'part_eta' => ['nullable', 'date'],
        ]);

        $kind = $validated['kind'] ?? 'note';
        $maintenance->addNote($model, $validated['body'], $kind);

        // ⚠️ 2026-10-05 — "Part ordered" DOES what it says: the work order is marked as waiting on that part, so the board's
        //    "Awaiting parts" tag, its figure and the Parts-on-order panel follow. It only wrote a note before — nothing on the board
        //    changed. The certified setAwaitingParts() (active work orders only; a closed one keeps just the note).
        if ($kind === 'part' && $model->isActive()) {
            $maintenance->setAwaitingParts($model, true, $validated['part_name'] ?? null, $validated['part_eta'] ?? null);

            return redirect()->route('app.maintenance.show', $model->id)->with('mt_notice', 'Update posted — marked as waiting on the part.');
        }

        return redirect()->route('app.maintenance.show', $model->id)->with('mt_notice', 'Update posted.');
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

        // the board's drawer sends return=list: back to the board where the photo was added (2026-10-05)
        return $this->listReturn($request, redirect()->route('app.maintenance.show', $model->id))->with('mt_notice', 'Photo added.');
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
        return $this->listReturn($request, $request->input('origin') === 'detail'
            ? redirect()->route('app.maintenance.show', $model->id)
            : redirect()->route('app.maintenance'));
    }

    /**
     * Where a write started from the BOARD comes back to (2026-09-29): the board as the operator had it — its filter,
     * sort, page size and page — rebuilt from an allow-list by {@see MaintenanceListParams::returnUrl()}, never taken
     * from the request as a URL. Anything else keeps the action's own destination.
     */
    private function listReturn(Request $request, RedirectResponse $default): RedirectResponse
    {
        return $request->input('return') === 'list'
            ? redirect()->to(MaintenanceListParams::returnUrl($request->input('return_query')))
            : $default;
    }

    /** A ticket the viewer may act on: tenant-scoped (a cross-org id 404s) and on a property they can access (#38). */
    private function accessibleTicket(string $ticket): MaintenanceTicket
    {
        $model = MaintenanceTicket::query()->with('unit')->findOrFail($ticket);
        abort_unless(in_array((string) $model->unit?->property_id, app(PropertyContext::class)->accessibleIds(), true), 404);

        return $model;
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
