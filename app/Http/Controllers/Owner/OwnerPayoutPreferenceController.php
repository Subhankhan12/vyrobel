<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Support\Events\AuditEventRecorder;
use App\Support\Money\MoneyFormatter;
use App\Support\Money\MoneyInput;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Retention\RetentionPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Owner Payout PREFERENCES — the owner's STATED preference for how earnings settle (frequency · business-day ·
 * minimum · pause). A WRITE on a controller ROUTE (invariant #20): a sensitive owner write never rides a Livewire
 * action (that would bypass {@see SetOwnerScopeForPortal} + the capability gate).
 *
 * GATED `CAP_PAYOUT` (co-owner only, #28/#42 — the same money-security gate as the encrypted IBAN): an accountant
 * or view-only sub-user may SEE the preference but not change where/when the money moves. OWNER-SCOPED: the row is
 * written under the resolved {@see OwnerContext} owner id (never a request id); a non-linked user has no owner id
 * → nothing is written. Idempotent upsert on `owner_id`.
 *
 * ⚠️ HONESTY: this stores a PREFERENCE, not a schedule. It NEVER moves money or promises a date — the actual
 * cadence + execution are confirmed with the operator + the live payout rail (#39). Money is integer minor (#17).
 */
class OwnerPayoutPreferenceController extends Controller
{
    public function save(Request $request, OwnerContext $ownerContext): RedirectResponse
    {
        // 🔒 P1-1: payout preferences are a CAP_PAYOUT write — resolve the holder from the owners the acting user
        // has PAYOUT on (per-owner), never the union. Fail closed when none.
        $ownerIds = $ownerContext->ownerIdsWithCapability(OwnerTeamRole::CAP_PAYOUT);
        if ($ownerIds === []) {
            abort(404);
        }

        $validated = $request->validate([
            // ⚠️ RE-AUTH (2026-08-31). Pausing payouts stops the money without touching any destination — a
            // DENIAL rather than a redirect, and one an owner may not notice for a whole cycle. So is a
            // €1,000,000 minimum, which is a pause by another name.
            // ⚠️ THE BARE RULE, RE-VERIFIED not inherited: the guards are web/guest/admin/api — there is no
            // `owner` guard, and `/owner` runs on plain `auth`. `current_password:owner` would match nothing.
            // ⚠️ ORDER: the CAP_PAYOUT resolution above already 404s first, so capability and ownership answer
            // before identity and a caller with no payout owner learns nothing from the password field.
            'current_password' => ['required', 'current_password'],
            'frequency' => ['required', Rule::in(OwnerPayoutPreference::FREQUENCIES)],
            'payout_day' => ['required', 'integer', 'min:1', 'max:5'],   // Nth business day of the month
            'minimum' => ['required', 'string', 'max:20'],
        ]);

        $ownerId = $ownerIds[0];

        // ⚠️ THE OWNER IS RESOLVED BEFORE THE PARSE so the refusal can state the bound in the owner's OWN
        // currency. It used to say "between €1 and €1,000,000" to an owner paid in pounds. Rewording it to a
        // bare "between 1 and 1,000,000" was ALSO wrong and `MoneyBoundRefusesTest` caught it: that test exists
        // because an absurd minimum once stored silently as "none", and its whole point is that the refusal
        // NAMES THE LIMIT. Naming it correctly satisfies both — the bound is stated, and stated in real money.
        $minimumMinor = $this->minimumMinor((string) $validated['minimum'], $this->payoutCurrency($ownerId));
        $after = [
            'frequency' => $validated['frequency'],
            'payout_day' => (int) $validated['payout_day'],
            'minimum_minor' => $minimumMinor,
            'paused' => $request->boolean('paused'),
        ];

        $existing = OwnerPayoutPreference::query()->where('owner_id', $ownerId)->first();
        $before = $existing === null ? null : [
            'frequency' => (string) $existing->frequency,
            'payout_day' => (int) $existing->payout_day,
            'minimum_minor' => (int) $existing->minimum_minor,
            'paused' => (bool) $existing->paused,
        ];

        OwnerPayoutPreference::query()->updateOrCreate(
            ['owner_id' => $ownerId],   // organization_id is auto-filled by BelongsToOrganization
            $after,
        );

        $this->recordChange($ownerId, $before, $after);

        return back()->with('payout_pref_notice', __('php/http/controllers/owner/owner_payout_preference_controller.updated'));
    }

    /** The largest minimum that means anything — above this a payout could never clear. */
    private const MAX_MINIMUM_MINOR = 100_000_000;   // €1,000,000

    /**
     * ⚠️⚠️ THE OWNER'S OWN CHANGE TO THEIR PAYOUT SETTINGS WAS INVISIBLE — added 2026-08-31.
     *
     * ⚠️ THE ASYMMETRY THIS CLOSES. `paused` lives in ONE column, and the OPERATOR path already recorded it:
     * `OwnerManageController::pausePayouts()` writes `owner.payouts_paused`. The owner changing the same field
     * here wrote nothing — so the same state change had two different histories depending on who made it, and
     * "why did this owner stop being paid?" was answerable only when the operator did it.
     *
     * ⚠️ RECORDED THE WAY THE SIBLING DOES: subject = the Owner, through the shared {@see AuditEventRecorder}.
     * It is written from the controller rather than a service because there is no service here and the closest
     * sibling — the operator's pause, on this very column — is itself a controller-side record. Extracting a
     * service for one upsert would be a larger change than the gap warrants; noted rather than done.
     *
     * ⚠️ WHAT CHANGED, NOT THAT SOMETHING DID. Complete before/after per field, so a reader can reconstruct
     * the settings at any past moment. `before` is NULL only when no preference row existed at all — which is
     * itself the fact worth recording, not an absence to paper over.
     *
     * ⚠️ VALUES, NOT FIELD NAMES — and that is a deliberate departure from the TAX path, which records names
     * only. A tax id and a VAT number are encrypted identifiers about a person (#28); a payout cadence, a
     * business-day number, a threshold in minor units and a boolean are the owner's own operational settings,
     * held in plain columns, and the operator's row for this same field already records its value. Recording
     * the numbers is what makes the row reconstructable at all.
     *
     * ⚠️ A NO-OP RECORDS NOTHING — the permission-matrix precedent. Re-saving identical preferences is not an
     * event, and `audit_events` is NEVER PRUNED ({@see RetentionPolicy}), so a row
     * written here is kept forever. That is a reason for care about what goes in, not for recording less.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    private function recordChange(string $ownerId, ?array $before, array $after): void
    {
        if ($before === $after) {
            return;
        }

        $owner = Owner::query()->find($ownerId);

        if ($owner === null) {
            return;
        }

        app(AuditEventRecorder::class)->record('owner.payout_preferences_updated', $owner, $before, $after);
    }

    /** The owner's own payout currency — the money this bound is actually expressed in. */
    private function payoutCurrency(string $ownerId): string
    {
        $owner = Owner::query()->find($ownerId);

        // `owners.payout_currency` is NOT NULL, so the fallback covers only an owner that vanished between
        // the capability resolution and here — not a missing currency.
        return $owner === null
            ? (string) config('billing.default_currency', 'EUR')
            : (string) $owner->payout_currency;
    }

    /**
     * ⚠️⚠️ THE BOUND MUST REFUSE, NOT DEFAULT. This read `MoneyInput::toMinor($input, 100_000_000) ?? 0`, and
     * `toMinor()` returns NULL for anything unparseable OR above its cap — so `?? 0` turned "€50,000,000" into
     * **no minimum at all**. The owner was told "Payout preferences updated" and the bound they thought they
     * had set did not exist. ⚠️ `?? 0` on a money input is the worst default there is, because ZERO IS A LEGAL
     * AMOUNT: nothing downstream can tell an intended "none" from a silently-dropped figure.
     *
     * ⚠️ AND "0 = NONE" IS REAL AND MUST SURVIVE — the Payout Method field says so in as many words, and the
     * Payouts page offers 0 as a radio option. So zero is accepted EXPLICITLY here rather than falling out of a
     * failed parse, which is exactly the conflation that hid this.
     *
     * ⚠️ BOTH PATHS REFUSE IDENTICALLY. The Payouts page's bounded radio set could never trip this; the Payout
     * Method page's free-text field is where an absurd figure was reachable — and a forged POST from either now
     * meets the same rule.
     */
    private function minimumMinor(string $input, string $currency): int
    {
        $raw = trim($input);

        // An explicit "none": blank, or a zero written any of the ways a person writes zero.
        if ($raw === '' || preg_match('/^0(\.0{1,2})?$/', $raw) === 1) {
            return 0;
        }

        $minor = MoneyInput::toMinor($raw, self::MAX_MINIMUM_MINOR);

        if ($minor === null) {
            throw ValidationException::withMessages([
                'minimum' => __('php/http/controllers/owner/owner_payout_preference_controller.invalid_minimum', [
                    'lowest' => MoneyFormatter::display(100, $currency),
                    'highest' => MoneyFormatter::display(self::MAX_MINIMUM_MINOR, $currency),
                ]),
            ]);
        }

        return $minor;
    }
}
