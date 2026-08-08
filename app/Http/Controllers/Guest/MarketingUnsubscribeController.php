<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Support\Guest\GuestMarketing;
use App\Support\Guest\MarketingUnsubscribe;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * MARKETING unsubscribe — public, no login (wireframe-parity sweep · HIGH-5).
 *
 * Reached only through an HMAC-`signed` link minted by {@see MarketingUnsubscribe}, so the guest cannot be forged
 * or swapped and no session is required — a guest who never claimed a portal account can still opt out, which was
 * the exposure. The only opt-out before this lived inside a CLAIMED guest account.
 *
 * TENANCY. `guests` is tenant-scoped and this route is deliberately outside any tenant middleware (the visitor is
 * not logged in and has no organization context), so the lookup runs in an explicit cross-tenant read and the
 * WRITE re-enters the guest's OWN organization. The signature is what authorises the request.
 *
 * PRIVACY (#28). The page shows NO guest data — no name, no email address, no booking. An unsubscribe link can be
 * forwarded or land in a shared inbox, so it must not turn into a disclosure. An anonymised guest is treated as
 * already unsubscribed.
 *
 * ⚠️ Opting out stops MARKETING mail only ({@see GuestMarketing::canReceive()}). Transactional
 * mail — booking confirmations, receipts, pre-arrival — is a service message the guest is owed regardless of
 * consent; the page says so plainly rather than implying they have silenced their booking confirmations.
 */
class MarketingUnsubscribeController extends Controller
{
    /** The confirmation page (GET). Changes NOTHING — a scanner prefetching the link must not opt a guest out. */
    public function show(string $guest): Response
    {
        $model = $this->resolve($guest);

        return response()->view('guest.marketing-unsubscribe', [
            'done' => $model === null || ! (bool) $model->marketing_consent,
            'confirmUrl' => $model === null ? null : MarketingUnsubscribe::oneClickUrl($model),
        ]);
    }

    /** The opt-out itself (POST) — also the RFC 8058 one-click target an inbox provider calls directly. */
    public function update(Request $request, string $guest): Response
    {
        $model = $this->resolve($guest);

        if ($model !== null && (bool) $model->marketing_consent) {
            app(TenantContext::class)->forOrganization((string) $model->organization_id, function () use ($model): void {
                $model->forceFill(['marketing_consent' => false])->save();
            });
        }

        // A provider's one-click POST is not a browser — it wants a bare 200 it can read, not a redirect.
        if ($request->expectsJson() || ! $request->acceptsHtml()) {
            return response('Unsubscribed.', 200)->header('Content-Type', 'text/plain');
        }

        // Rendered directly rather than PRG: the action is idempotent, and a redirect would need a second signed
        // URL for a guest who may no longer resolve.
        return response()->view('guest.marketing-unsubscribe', ['done' => true, 'confirmUrl' => null]);
    }

    /** Resolve the guest cross-tenant; null when they no longer exist or have been anonymised. */
    private function resolve(string $guest): ?Guest
    {
        /** @var ?Guest $model */
        $model = app(TenantContext::class)->runAsSuperAdmin(
            fn (): ?Guest => Guest::query()->find($guest),
        );

        if ($model === null || $model->isAnonymized()) {
            return null;   // an erased guest is already beyond marketing (erasure withdraws consent)
        }

        return $model;
    }
}
