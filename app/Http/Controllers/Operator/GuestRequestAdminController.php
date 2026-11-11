<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\GuestChangeRequest;
use App\Services\GuestChangeRequestService;
use App\Support\Guest\GuestName;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\I18n\Locales;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Operator view of guest change REQUESTS (Phase 4b · Sub-gate 3) — the confirm/decline side of the
 * operator-confirmed-request pattern. This is where a guest request is ACTED on; the guest can never do this
 * (realm separation — /app is the web guard; a guest is refused).
 *
 * ⚠️ SCOPE (stated): confirm/decline record the DECISION (status only). They do NOT auto-execute the real change
 * — the operator runs the actual date-change / cancel-and-refund / folio charge with their certified tools (front
 * desk, reservations, folio), where the real inventory/money guards live. That execute-on-confirm is deferred to
 * the operator's existing flows, honestly. Behind the back-office gate + `can:reservations.manage`.
 *
 * 🪤 The route param is a STRING id resolved INSIDE the action via the scoped `GuestChangeRequest::findOrFail`
 * (the established operator pattern), NEVER a type-hinted route-model binding: `SetTenantForWebRequest` is
 * APPENDED to the web group, so it runs AFTER `SubstituteBindings` — a bound scoped model resolves with no
 * tenant context and throws. Resolving in the action (context already set) keeps the org scope: cross-org → 404.
 */
class GuestRequestAdminController extends Controller
{
    public function __construct(private readonly GuestChangeRequestService $requests) {}

    public function index(): Response
    {
        $pending = GuestChangeRequest::query()
            ->where('status', 'pending')
            ->with(['reservation', 'guest'])
            ->latest()
            ->get()
            ->map(fn (GuestChangeRequest $r): array => [
                'id' => (string) $r->getKey(),
                'guest' => GuestName::display($r->guest),   // NAME only (no contact PII · #28)
                'reference' => (string) ($r->reservation->reference ?? ''),
                'type' => $this->typeLabel((string) $r->type),
                'detail' => $this->detail($r),
                'at' => CarbonImmutable::parse($r->created_at)->translatedFormat(LocalDate::pattern('j M Y · H:i')),
            ]);

        return response()->view('operator.guest-requests', ['requests' => $pending->all()]);
    }

    public function confirm(string $guestRequest): RedirectResponse
    {
        $this->requests->confirm($this->owned($guestRequest));

        return back()->with('status', __('php/http/controllers/operator/guest_request_admin_controller.request_confirmed'));
    }

    public function decline(string $guestRequest): RedirectResponse
    {
        $this->requests->decline($this->owned($guestRequest));

        return back()->with('status', __('php/http/controllers/operator/guest_request_admin_controller.request_declined'));
    }

    /** This org's request, or 404 — resolved in-action so the tenant scope is set (cross-org → not found). */
    private function owned(string $id): GuestChangeRequest
    {
        return GuestChangeRequest::query()->findOrFail($id);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            GuestChangeRequest::TYPE_DATE_CHANGE => __('php/http/controllers/operator/guest_request_admin_controller.type_date_change'),
            GuestChangeRequest::TYPE_ADD_ON => __('php/http/controllers/operator/guest_request_admin_controller.type_add_ons'),
            GuestChangeRequest::TYPE_CANCELLATION => __('php/http/controllers/operator/guest_request_admin_controller.type_cancellation'),
            default => __('php/http/controllers/operator/guest_request_admin_controller.type_change'),
        };
    }

    private function detail(GuestChangeRequest $request): string
    {
        $payload = $request->payload;

        return match ((string) $request->type) {
            GuestChangeRequest::TYPE_DATE_CHANGE => (string) ($payload['new_check_in'] ?? '').' '.Locales::forward().' '.(string) ($payload['new_check_out'] ?? ''),
            GuestChangeRequest::TYPE_ADD_ON => ListJoin::join((array) ($payload['add_ons'] ?? [])),
            GuestChangeRequest::TYPE_CANCELLATION => (string) ($payload['reason'] ?? ''),
        };
    }
}
