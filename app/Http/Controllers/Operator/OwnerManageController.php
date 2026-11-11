<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Owner\StatementSealedException;
use App\Http\Controllers\Controller;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerUser;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerStatementService;
use App\Support\Events\AuditEventRecorder;
use App\Support\I18n\LocalDate;
use App\Support\I18n\LocalNumber;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Owner\CommissionChange;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerListParams;
use App\Support\Owner\OwnerLoginLifecycle;
use App\Support\Owner\OwnerPortalAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
/**
 * Operator OWNER management writes (#93) — Edit owner (commission / portal), Pause payouts, Remove owner (a careful
 * ARCHIVE-not-destroy), and Add note. All PRG (POST → controller → redirect), tenant-scoped (`{owner}` resolves via
 * the global scope → cross-org 404), gated `can:users.manage` (the same owner-management privilege as the invite),
 * read-only-respecting (#20), and audited. NEVER a destructive cascade — the financial + booking records are kept.
 */
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class OwnerManageController extends Controller
{
    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * Change the owner's COMMISSION — a new effective-dated {@see ManagementAgreement} from the first of next month, by the ONE
     * rule in {@see CommissionChange} (the latest change wins; every other term carries forward; history is never rewritten).
     *
     * ⚠️⚠️ PORTAL ACCESS IS NO LONGER WRITTEN HERE (2026-10-03). The dialog's "Owner portal access" checkbox posted
     * `portal_active`, and this method set the linked login's status from it: 🔬 an unticked box posts nothing, so the box
     * could never switch access off — while a hand-made `portal_active=0` switched ANY linked login off (bypassing
     * {@see OwnerLoginLifecycle}, which spares a person who also works here), and `portal_active=1` opened an invited login
     * nobody had set a password for. The field is now ignored. ⚠️ DEC-o (2026-11-12) answered it a different way: portal access is switched
     * off and on by {@see portalAccess()} — one date on the owner, never a login.
     */
    public function update(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);
        LocalNumber::mergeInto($request, ['commission_pct']);   // D-MONEY-COMMA: "12,5" read in the page's language before `numeric`
        $validated = $request->validate([
            'commission_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'commission_pct.required' => __('php/http/controllers/operator/owner_manage_controller.commission_required'),
            'commission_pct.numeric' => __('php/http/controllers/operator/owner_manage_controller.commission_range'),
            'commission_pct.min' => __('php/http/controllers/operator/owner_manage_controller.commission_range'),
            'commission_pct.max' => __('php/http/controllers/operator/owner_manage_controller.commission_range'),
        ]);

        $pct = round((float) $validated['commission_pct'], 2);
        $display = OwnerCommissionRate::display($pct) ?? $pct.'%';
        $ownerId = (string) $model->getKey();

        // What the save will do, read BEFORE it: once an agreement exists today, a later save starts next month instead.
        $today = CommissionChange::governing($ownerId, CarbonImmutable::today());
        $startsOn = CommissionChange::startsOn($ownerId);
        $waiting = CommissionChange::governing($ownerId, $startsOn);
        $wasWaiting = $today !== null && $waiting !== null && (string) $waiting->getKey() !== (string) $today->getKey();

        if (! CommissionChange::apply($model, $pct)) {
            return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.commission_unchanged', ['rate' => $display, 'name' => $model->name, 'date' => $startsOn->translatedFormat(LocalDate::pattern('j M Y'))]));
        }

        // ⚠️ A field list, never the figures — an audit row is not a second copy of the money.
        $this->audit->record('owner.updated', $model, null, ['fields' => ['commission_pct']]);

        if ($today === null) {
            return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.commission_from_today', ['name' => $model->name, 'rate' => $display]));
        }

        // back to today's rate while a change was waiting: the waiting change is what moved, so that is what the notice says
        if ($wasWaiting && $today->commission_pct !== null && round((float) $today->commission_pct, 2) === $pct) {
            return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.commission_waiting_cancelled', ['name' => $model->name, 'rate' => $display, 'date' => $startsOn->translatedFormat(LocalDate::pattern('j M Y'))]));
        }

        // ⚠️ "Worked out", not "booked": earnings are worked out when a booking is made — or later, for one imported without
        //    them, and again when a booking moves to another owner's unit (the review) — so that is the honest unit.
        return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.commission_from_date', ['name' => $model->name, 'rate' => $display, 'date' => $startsOn->translatedFormat(LocalDate::pattern('j M Y'))]));
    }

    /**
     * Pause / resume payouts — REAL via {@see OwnerPayoutPreference::paused}, which the certified payout engine
     * already respects (a paused owner is skipped; the amount stays in the ledger and carries via the statement).
     */
    public function pausePayouts(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage(), $request);
        }

        $model = Owner::query()->findOrFail($owner);
        $paused = $request->boolean('paused');

        OwnerPayoutPreference::query()->updateOrCreate(['owner_id' => $model->id], ['paused' => $paused]);
        $this->audit->record('owner.payouts_paused', $model, null, ['paused' => $paused]);

        return $this->back($owner, 'owners_notice', $paused
            // DEC-n (2026-10-02) — a pause stops every payout, by the automatic run or by hand on the payout run page
            ? __('php/http/controllers/operator/owner_manage_controller.payouts_paused', ['name' => $model->name])
            : __('php/http/controllers/operator/owner_manage_controller.payouts_resumed', ['name' => $model->name]), $request);
    }

    /**
     * ⚠️⚠️ DEC-o (2026-11-12) — SWITCH AN OWNER'S PORTAL ACCESS OFF OR ON. The founder's ruling: the owner's TEAM goes with them (and
     * comes back with them); a person who ALSO works for the operator keeps their login; it is reversible and non-destructive.
     *
     * The whole state is `owners.portal_disabled_at` ({@see OwnerPortalAccess}) — NOTHING else is written: no `users.status`, no
     * `owner_users` row, no Membership, so there is nothing to "restore" and nobody's other login can be cut off. Payouts, statements
     * and the ledger never read it. ⚠️ It is NOT {@see OwnerLoginLifecycle}, which decides whether a login should still EXIST once an owner
     * link is taken away — this takes nobody's link away.
     *
     *   OFF  — a typed reason (5–300 characters), kept ONLY in the audit row; only for an owner whose login is active (nothing to switch
     *          off otherwise). The dialog is the confirmation.
     *   ON   — your current password, as sending an invite asks (it gives access back).
     *
     * ⚠️ NO EMAIL IS SENT EITHER WAY — the notice says so, and says why while email isn't being sent from Vyrobel at all
     * ({@see MailSafetyGuard::sendingNow()}). ⚠️ The audit row records the switch and the team's size, and the reason an operator typed — never
     * the owner's contact details (#28).
     */
    public function portalAccess(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage(), $request);
        }

        // tenant-scoped: another organisation's owner is a 404 BEFORE anything is validated (no existence oracle)
        $model = Owner::query()->findOrFail($owner);

        $request->validate(['portal' => ['required', Rule::in(['on', 'off'])]], [
            'portal.required' => __('php/http/controllers/operator/owner_manage_controller.portal_choice_invalid'),
            'portal.in' => __('php/http/controllers/operator/owner_manage_controller.portal_choice_invalid'),
        ]);
        $switchOff = $request->input('portal') === 'off';
        $team = OwnerUser::query()->where('owner_id', $model->id)->where('status', 'active')->count();

        if ($switchOff) {
            $request->merge(['reason' => trim((string) $request->input('reason'))]);
            $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:300']], [
                'reason.required' => __('php/http/controllers/operator/owner_manage_controller.portal_reason_required'),
                'reason.min' => __('php/http/controllers/operator/owner_manage_controller.portal_reason_min'),
                'reason.max' => __('php/http/controllers/operator/owner_manage_controller.portal_reason_max'),
            ]);

            if ($model->portalIsOff()) {
                return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.portal_already_off', ['name' => $model->name]), $request);
            }
            // ⚠️ only for an owner who can sign in today — the same rule the page uses to offer the button (a hand-made request meets it too)
            if (! ($model->user instanceof User) || (string) $model->user->status !== User::SIGN_IN_STATUS) {
                return $this->back($owner, 'owners_error', __('php/http/controllers/operator/owner_manage_controller.portal_login_not_active', ['name' => $model->name]), $request)
                    ->withInput($request->only('_form', 'reason'));
            }

            DB::transaction(function () use ($model, $team, $validated): void {
                $model->forceFill(['portal_disabled_at' => now()])->save();
                // the reason lives here and nowhere else; the owner's name, email and phone are never copied into an audit row (#28)
                $this->audit->record('owner.portal_access_changed', $model, ['portal' => 'on'], ['portal' => 'off', 'team' => $team, 'reason' => (string) $validated['reason']]);
            });

            return $this->back($owner, 'owners_notice', $this->portalNotice('portal_switched_off', $model, $team), $request);
        }

        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.required' => __('php/http/controllers/operator/owner_manage_controller.portal_password_required'),
            'current_password.current_password' => __('php/http/controllers/operator/owner_manage_controller.password_wrong'),
        ]);

        if (! $model->portalIsOff()) {
            return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.portal_already_on', ['name' => $model->name]), $request);
        }

        DB::transaction(function () use ($model, $team): void {
            $model->forceFill(['portal_disabled_at' => null])->save();
            $this->audit->record('owner.portal_access_changed', $model, ['portal' => 'off'], ['portal' => 'on', 'team' => $team]);
        });

        return $this->back($owner, 'owners_notice', $this->portalNotice('portal_switched_on', $model, $team), $request);
    }

    /** The notice after a switch: what happened (with the team's size), then what did NOT happen — nobody is emailed. */
    private function portalNotice(string $key, Owner $owner, int $team): string
    {
        return trans_choice('php/http/controllers/operator/owner_manage_controller.'.$key, $team, ['name' => $owner->name, 'count' => $team]).' '
            .__('php/http/controllers/operator/owner_manage_controller.'.(MailSafetyGuard::sendingNow() ? 'portal_no_email_sent' : 'portal_no_email_not_sent'));
    }

    /**
     * Add a note to the owner's operator-internal timeline (#93) — a real authored {@see Note}, never shown in the
     * owner portal.
     */
    /**
     * ⚠️⚠️ CLOSE A PERIOD — the FIRST REAL CALLER `OwnerStatementService::finalize()` has ever had.
     *
     * ⚠️ `generateDraft()` and `finalize()` were reachable only from seeders and tests: the twelve-entity
     * seeder-only class again, and a decisive one, because **the payout engine selects ONLY finalized
     * statements**. A real deployment therefore had nothing to pay, ever. The demo looked complete because
     * `DemoSeeder` finalises statements the application could not.
     *
     * ⚠️ SAFE ONLY BECAUSE THE COLLECTION GATE LANDED WITH IT. Before {@see OwnerCollection}, giving
     * finalisation a caller would have started paying owners against revenue never collected — the two
     * defects were cancelling. That ordering is proven by `OwnerCollectionGateTest`.
     *
     * ⛔ **THE TRIGGER IS A PRODUCT DECISION AND IS SURFACED, NOT PICKED.** This ships the MECHANISM as an
     * explicit operator action, which is the only option that needs no ruling — a human closes a period when
     * they judge it closed. The founder still has to decide:
     *   (a) operator action, as built here — deliberate, auditable, and someone must remember;
     *   (b) a scheduled period close (a monthly command) — reliable, but it closes over late arrivals;
     *   (c) automatic on period end — no forgetting, and no chance to review before money moves.
     * ⚠️ And two questions no code here answers: a booking that lands AFTER its period closes (today it
     * flows to the next statement as a prior-period adjustment — the existing straggler path), and whether a
     * finalised statement may ever be REOPENED, and by whom. Nothing reopens one today; `finalize()` locks.
     */
    public function closePeriod(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ], [
            'period_start.required' => __('php/http/controllers/operator/owner_manage_controller.period_start_required'),
            'period_end.required' => __('php/http/controllers/operator/owner_manage_controller.period_end_required'),
            'period_end.after_or_equal' => __('php/http/controllers/operator/owner_manage_controller.period_end_after_start'),
        ]);

        $model = Owner::query()->findOrFail($owner);
        $statements = app(OwnerStatementService::class);

        // ⚠️ AUDIT H-2 — the service REFUSES an already-sealed period ("Cannot regenerate a finalized/locked
        // statement."). Before this control existed nothing could reach that refusal, so it surfaced as a raw
        // 500 the moment a real operator picked a closed period. Presented here the same way the paused()
        // guard already is; finalize()'s own behaviour is unchanged — it still throws.
        try {
            $statement = $statements->finalize(
                $statements->generateDraft($model->id, $data['period_start'], $data['period_end'])
            );
        } catch (RuntimeException $e) {
            // the dates come back with the refusal, so the dialog reopens with them and says why (2026-10-03)
            // ⚠️ the reason is a translated key chosen by the exception's TYPE — an exception's own message is English and
            //    for the log, never for a person (D-I18N-STATEMENT-REFUSAL)
            $reason = $e instanceof StatementSealedException
                ? __('php/http/controllers/operator/owner_manage_controller.period_sealed')
                : __('php/http/controllers/operator/owner_manage_controller.period_not_closed_other');

            return $this->back($owner, 'owners_error',
                __('php/http/controllers/operator/owner_manage_controller.period_not_closed', ['reason' => $reason]))->withInput($request->only('_form', 'period_start', 'period_end'));
        }

        // ⚠️ Recorded as a field list, never the figures — an audit row is not a second copy of the money.
        $this->audit->record('owner.statement_finalized', $model, null, [
            'fields' => ['statement_id', 'period_start', 'period_end'],
            'statement_id' => (string) $statement->id,
        ]);

        // ⚠️ `currency` is the PAYOUT currency and `net_payable_minor` the converted figure — there is no
        // `payout_currency` column and no `reference` column on owner_statements. Both were assumed in a
        // first draft and corrected by reading the live schema.
        $net = MoneyFormatter::display((int) $statement->net_payable_minor, (string) $statement->currency);

        return $this->back($owner, 'owners_notice',
            __('php/http/controllers/operator/owner_manage_controller.period_closed', ['number' => $statement->sequence_no, 'amount' => $net]));
    }

    public function addNote(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']], [
            'body.required' => __('php/http/controllers/operator/owner_manage_controller.note_required'),
            'body.max' => __('php/http/controllers/operator/owner_manage_controller.note_max'),
        ]);

        $model->noteEntries()->create([
            'body' => $validated['body'],
            'visibility' => 'internal',
            'author_id' => $request->user()?->getAuthIdentifier(),
        ]);
        $this->audit->record('owner.note_added', $model);

        return $this->back($owner, 'owners_notice', __('php/http/controllers/operator/owner_manage_controller.note_added'));
    }

    /**
     * REMOVE OWNER — a careful ARCHIVE-not-destroy. Confirmed by typing the owner's exact name. Units become
     * UNASSIGNED (bookings stay intact — `units.owner_id` → null); the active agreement is ENDED; portal access
     * ends (the linked user is deactivated + pending invites revoked); the owner is SOFT-DELETED so all statements
     * / payouts / ledger / bookings are RETAINED for accounting (the FK cascades fire only on a hard delete). NEVER
     * a destructive cascade.
     */
    public function remove(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);

        // ⚠️ RE-AUTH (G2, 2026-08-31). This was DELIBERATELY EXCLUDED when the re-auth family closed, on the
        // strength of its mitigations — and re-examining them is what justified adding it:
        //   · the type-the-name confirm is SERVER-SIDE (below), so a blind POST really is refused — but a
        //     HIJACKED session can read the owner's name straight off this page, so it does not stop the
        //     threat the password is for;
        //   · `can:users.manage` is real (a Housekeeping session cannot reach the route at all);
        //   · the audit row is DETECTION, not prevention.
        // So the password closes the one case the confirm cannot. ⚠️ BARE rule, verified not inherited:
        // `/app` runs on plain `auth` and AUTH_GUARD is `web`; `current_password:admin` would validate NOTHING.
        // ⚠️ ORDER: it sits AFTER `findOrFail()`, so a foreign owner id 404s whether or not a password is sent.
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_name' => ['required', 'string'],
        ], [
            'current_password.required' => __('php/http/controllers/operator/owner_manage_controller.remove_password_required'),
            'current_password.current_password' => __('php/http/controllers/operator/owner_manage_controller.password_wrong'),
            'confirm_name.required' => __('php/http/controllers/operator/owner_manage_controller.confirm_name_required'),
        ]);

        if (trim((string) $validated['confirm_name']) !== trim((string) $model->name)) {
            // what was typed comes back with the refusal, so the dialog reopens with it (2026-10-03)
            return $this->back($owner, 'owners_error', __('php/http/controllers/operator/owner_manage_controller.confirm_name_mismatch'))
                ->withInput($request->only('_form', 'confirm_name'));
        }

        DB::transaction(function () use ($model): void {
            // Units → unassigned (bookings untouched).
            // ⚠️ DEC-p: the day the owner took a unit over goes with the owner — a stale `owner_since` would cut the NEXT owner of the unit
            Unit::query()->where('owner_id', $model->id)->update(['owner_id' => null, 'owner_since' => null]);
            // End the active management agreements.
            ManagementAgreement::query()->where('owner_id', $model->id)->where('status', 'active')
                ->update(['status' => 'ended', 'effective_to' => CarbonImmutable::today()->toDateString()]);
            // Portal access ends: revoke pending invites. ⚠️ THE LINKED USER'S LOGIN IS DECIDED BELOW,
            // AFTER the owner is soft-deleted — see the note on that call.
            OwnerInvite::query()->where('owner_id', $model->id)->where('status', 'pending')->update(['status' => 'revoked']);

            // ⚠️⚠️ AND THE OWNER'S SUB-USERS — added 2026-08-31, because until then this line's own promise
            // ("Portal access ends immediately") was FALSE for any owner with a team. MEASURED before the fix:
            // the sub-user's `owner_users` row stayed `active`, `GET /owner` stayed 200, and a payout-preference
            // write LANDED (0 → 1 rows) on the removed owner.
            //
            // ⚠️ WHY A CASCADE IS THE RIGHT ANSWER HERE, AND NOT AN ASSUMPTION. Sub-users have exactly ONE
            // creator: {@see \App\Services\OwnerTeamInviteService} — reachable only through
            // `/owner/team/invite`, which sits behind `EnsurePrimaryOwner`. They are the OWNER'S OWN people,
            // invited by the owner. No operator path writes `owner_users` at all. And the only route that can
            // remove one is ALSO primary-owner-only — so once the owner's login is deactivated, NOBODY could
            // close this access: not the operator, who has no route, and not the owner, who is locked out.
            // "Require a second explicit action" was therefore not an available option.
            //
            // ⚠️ THE MECHANISM IT CLOSES: `OwnerContext` resolves sub-memberships from `owner_users` alone
            // (OwnerContext:247), with no join to `owners`, so a row pointing at a SOFT-DELETED owner still
            // resolved. Reads happened to come back empty because the data models' scope excludes the trashed
            // owner — but a write needing only the owner ID went straight through.
            //
            // ⚠️ Soft-delete, matching how an owner removes a member themselves ("the owner scope no longer
            // resolves them"), and the shared {@see OwnerLoginLifecycle} decides the login — so a person who
            // also holds a back-office role keeps their account. The cascade removes an owner link; it never
            // silently ends an unrelated working login.
            $subUserIds = OwnerUser::query()->where('owner_id', $model->id)->pluck('user_id')
                ->map(fn ($id): string => (string) $id)->all();

            OwnerUser::query()->where('owner_id', $model->id)->get()->each(fn (OwnerUser $m) => $m->delete());

            foreach (array_unique($subUserIds) as $subUserId) {
                OwnerLoginLifecycle::disableIfOrphaned($subUserId);
            }
            // Archive the owner (soft-delete) + mark inactive. Statements/payouts/ledger are RETAINED (no cascade).
            $model->forceFill(['status' => 'inactive'])->save();
            $model->delete();

            // ⚠️⚠️ THE PRIMARY OWNER'S OWN LOGIN — THE SAME RULE AS THEIR TEAM'S, AND IT USED NOT TO BE.
            //
            // This line replaced an UNCONDITIONAL `$model->user?->forceFill(['status' => 'inactive'])`, which
            // sat ten lines above the cascade that asks {@see OwnerLoginLifecycle} the identical question for
            // sub-users. Two answers to one question in one transaction — and the docblock above already
            // described the lifecycle's answer as though it governed both.
            //
            // ⚠️ WHAT THAT COST, REPRODUCED: an actor holding `users.manage` but NOT `roles.manage` could
            // remove the owner an organisation's SOLE role-manager was linked to, deactivate that login, and
            // permanently strip the org of role management — a LESSER permission producing the exact effect
            // the greater one is guarded against. Measured after the fact: the matrix write 403s, and the
            // escalation guard refuses to re-grant a wildcard role ("You can't assign a role with more access
            // than your own"), so nothing inside the product can climb back out. Recovery is the console
            // procedure in docs/GO-LIVE-RUNBOOK.md.
            //
            // ⚠️ NOT A NEW GUARD, AND DELIBERATELY NOT THE FLOOR. The floor ({@see RolePermissions::roleManagersAfter})
            // is correct and is consulted by the two paths that CHANGE roles. This path changes a LOGIN, and the
            // rule for "should this login still exist once an owner link is taken away?" already existed and is
            // broader: the lifecycle spares anyone still holding a back-office role, which covers the last
            // role-manager and every other employed staff member alike. Reused, never re-implemented (§N.8f).
            //
            // ⚠️⚠️ ORDER IS LOAD-BEARING: it runs AFTER `$model->delete()`. The lifecycle asks "is this person
            // a primary owner?" against a non-trashed `Owner`, so calling it earlier would find THIS owner,
            // treat every removal as still-owning, and spare every login — silently un-doing the removal's
            // whole promise. `OwnerRemovalSparesTheLastRoleManagerTest` pins both directions.
            if ($model->user_id !== null) {
                OwnerLoginLifecycle::disableIfOrphaned((string) $model->user_id);
            }
        });

        $this->audit->record('owner.archived', $model, null, ['archived' => true]);

        return redirect()->route('app.owners')->with('owners_notice', __('php/http/controllers/operator/owner_manage_controller.owner_removed', ['name' => $model->name]));
    }

    private function paused(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function pausedMessage(): string
    {
        // ⚠️ 2026-10-01 — with no resume date on record this ended "until ." (the same fix as the rate-plan writes)
        $until = app(OperatorWorkspace::class)->resumeLabel();

        return $until !== null && $until !== ''
            ? __('php/http/controllers/operator/owner_manage_controller.workspace_paused_until', ['date' => $until])
            : __('php/http/controllers/operator/owner_manage_controller.workspace_paused_for_now');
    }

    /** Back to the owner — or to the Owners list as the operator had it, when the form was started there (2026-10-01). */
    private function back(string $owner, string $key, string $message, ?Request $request = null): RedirectResponse
    {
        $list = $request !== null ? OwnerListParams::redirect($request) : null;

        return ($list ?? redirect()->route('app.owners.show', $owner))->with($key, $message);
    }
}
