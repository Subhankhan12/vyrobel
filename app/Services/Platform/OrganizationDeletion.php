<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\CheckoutSession;
use App\Models\Organization;
use App\Models\PaymentDispute;
use App\Support\I18n\LocalDate;
use App\Support\Platform\TenantLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * ⚠️⚠️ ADMIN 05 (2026-09-26) — PERMANENTLY DELETE AN ORGANISATION AND EVERYTHING IT OWNS.
 *
 * THE RULING. The founder ruled "delete everything, always" for the console's explicit Delete (2026-09-26), replacing
 * — for this one, deliberate, Owner-only action — the standing "no hard delete" ruling. Archive stays the reversible
 * option. The platform's OWN records about the organisation are kept: the console ledger (append-only — it records this
 * deletion), break-glass sessions and privacy requests. Everything the organisation owns goes: its bookings, guests,
 * money records, owners, audit trail, uploaded files, and the accounts of people who belong to no other organisation.
 *
 * HOW, AND WHY IT CANNOT HALF-HAPPEN. Everything runs in ONE database transaction:
 *   0. the organisation's own row is LOCKED first — an insert into any table with a foreign key to it now waits, a second
 *      deletion waits and then finds nothing — and the refusal, the people and the files are all read UNDER the lock;
 *   1. rows reachable only THROUGH a parent (no foreign key) are removed first, while the parent still says whose they
 *      are: amenity links, a guest's notifications, sessions, security-log rows and reset tokens — and the notifications
 *      of people who are KEPT, where they are about this organisation;
 *   2. every table with an `organization_id` column — READ from the schema at run time, never a typed list — is
 *      cleared for this organisation, then the organisation row itself goes (its foreign keys cascade the rest);
 *   3. people whose ONLY link was this organisation are deleted with their own no-foreign-key rows; anyone who also
 *      belongs elsewhere — or whom another organisation's records still name — keeps their account and simply loses
 *      this membership;
 *   4. a POST-CONDITION re-reads every organisation-owned table and REFUSES (rolls everything back) if a single row for
 *      this organisation survives.
 * ⚠️ AFTER THE COMMIT the deletion HAS happened, so nothing there may throw — a throw would be reported as a rollback and
 * written to the ledger as a failure. The permission cache, the files and a re-scan each REPORT what they could not do.
 *
 * WHAT MAKES IT REFUSE ({@see self::refusal()}, and {@see OrganizationDeletionRefused}): ⚠️ ADMIN 06 — LIVE-mode money
 * records (a hold pending the founder's lawyer, never a policy); money that can still move at Stripe or is still owed
 * (live Stripe billing, an open Checkout page [B19] or a live one Stripe has not reported on, a card payment, refund or
 * hold still open, a dispute not finished, a payout in transit or still owed, a payout run in progress, a webhook being
 * processed); a row of ANOTHER organisation pointing into
 * this one — a cascade would delete it; a cascade chain deeper than the count can follow; or a schema read that cannot
 * see the tables every real schema has. ⚠️ It NEVER calls Stripe: closing anything there is the founder's, by hand.
 *
 * WHAT THE CONFIRMATION SAYS ({@see self::preview()}) is the count of the SAME plan the deletion runs ({@see self::plan()})
 * — every row it removes, cascades and accounts included.
 * ⚠️ The console checks the lock, the suspension, the password, the typed name and the reason before calling this.
 */
final class OrganizationDeletion
{
    /** Morph names a polymorphic amenity link can carry for an organisation's own records. */
    private const AMENITY_PARENTS = ['property' => 'properties', 'room_type' => 'room_types', 'unit' => 'units', 'unit_request' => 'unit_requests'];

    /**
     * Columns that name a PERSON but carry no foreign key — so the schema cannot list them. They are read together with
     * the foreign keys when deciding whether another organisation still names someone (then they keep their account).
     * ⚠️ A drift test sweeps the schema for person-shaped columns without a key and fails on one missing from here.
     */
    public const UNKEYED_USER_COLUMNS = [
        ['audit_events', 'actor_id'],
        ['folios', 'billed_by_user_id'],
        ['ip_allow_rules', 'created_by'],
        ['open_shifts', 'created_by'],
        ['properties', 'removal_requested_by'],
        ['reservation_unit_moves', 'stated_by_user_id'],
        ['schedule_publications', 'published_by'],
    ];

    /**
     * ⚠️ ADMIN 06 — the tables the plan clears DIRECTLY in phases 1 and 3 (no organisation column; reached through a
     * parent, or an account's own rows). A cascade FROM one of these would remove rows the count cannot see, so
     * {@see self::uncountedCascades()} refuses one. A drift test holds the plan's own phase-1/3 tables to this list.
     */
    public const REACHED_THROUGH_A_PARENT = [
        'amenitiables', 'announcement_dismissals', 'guest_password_reset_tokens', 'llm_usage', 'login_activity', 'notifications',
        'oauth_access_tokens', 'oauth_auth_codes', 'oauth_device_codes', 'oauth_refresh_tokens', 'password_reset_tokens', 'sessions',
    ];

    /** Tables every real schema has. A schema read that misses one is wrong — and would make the post-condition blind. */
    private const MUST_SEE = ['reservations', 'guests', 'properties', 'units', 'payments', 'organization_user', 'audit_events', 'media'];

    /** How long money in motion may sit before a refusal calls it stuck rather than asking to wait. */
    private const STALE_MINUTES = 30;

    /** A bound list is cut into pieces this size — a statement allows 65,535 placeholders, and a big tenant has more rows. */
    private const CHUNK = 1000;

    /** @var list<string>|null the schema read, once per request */
    private ?array $tables = null;

    /** @var array<string, list<array{name: string, columns: list<string>, foreign_table: string, foreign_columns: list<string>}>>|null */
    private ?array $foreignKeys = null;

    /** @var list<array{0: string, 1: string, 2: string, 3: string}>|null the cascading keys, once per request */
    private ?array $cascades = null;

    /**
     * What stops a deletion, in plain words — or null when it may proceed. Read-only.
     * ⚠️ ADMIN 06: the order puts the reasons that WAITING cannot fix first — nobody should wait out a payment page only
     * to be refused by the hold.
     */
    public function refusal(string $organizationId): ?string
    {
        // ⚠️⚠️ #2 — A HOLD PENDING THE FOUNDER'S LAWYER, NOT A POLICY (docs/DECISIONS.md, ADMIN 06). Live-mode money
        // records are never deleted until the ruling on how long the law requires them kept. Test-mode ones never block.
        if (($live = $this->liveMoneyRecords($organizationId)) !== []) {
            return __('php/services/platform/organization_deletion.live_money_records', ['records' => implode(', ', $live)]);
        }
        if (($deeper = self::uncountedCascades($this->cascadeEdges(), $this->organizationTables(), $this->nullableOrganizationTables(), self::REACHED_THROUGH_A_PARENT)) !== []) {
            return __('php/services/platform/organization_deletion.uncounted_cascades', ['links' => implode(', ', array_slice($deeper, 0, 3))]);
        }
        // any subscription Stripe knows about that is not cancelled — an unknown status refuses too, never the reverse
        $live = DB::table('subscriptions')->where('organization_id', $organizationId)
            ->where(fn ($q) => $q->where('status', 'incomplete')
                ->orWhere(fn ($s) => $s->whereNotNull('stripe_subscription_id')->where('status', '!=', 'canceled')))
            ->exists();
        if ($live) {
            return __('php/services/platform/organization_deletion.stripe_billing_live');
        }
        // B19 (KNOWN-LIMITS L19) — a payment page that can still be paid
        $page = CheckoutSession::whereOpen(DB::table('stripe_checkout_sessions')->where('organization_id', $organizationId))
            ->orderBy('created_at')->first(['mode', 'created_at', 'expires_at']);
        if ($page !== null) {
            $closes = $page->expires_at ?? CarbonImmutable::parse((string) $page->created_at)->addHours(CheckoutSession::LONGEST_LIFETIME_HOURS);

            return $page->mode === 'subscription'
                ? __('php/services/platform/organization_deletion.checkout_open_subscription', ['opened' => $this->when($page->created_at), 'closes' => $this->when($closes)])
                : __('php/services/platform/organization_deletion.checkout_open_card', ['opened' => $this->when($page->created_at), 'closes' => $this->when($closes)]);
        }
        // ⚠️ REVIEW 2 (D-R2-2) — a LIVE page past its expiry that Stripe has not reported on. It may have been paid in its
        //    last minute with the report still in Stripe's retries, and deleting now would leave a live subscription
        //    charging a customer nobody here can see. A test-mode page moves no real money, so its expiry is enough.
        $unreported = CheckoutSession::whereUnreported(DB::table('stripe_checkout_sessions')->where('organization_id', $organizationId))
            ->orderBy('created_at')->first(['mode', 'created_at', 'expires_at']);
        if ($unreported !== null) {
            $closed = $unreported->expires_at ?? CarbonImmutable::parse((string) $unreported->created_at)->addHours(CheckoutSession::LONGEST_LIFETIME_HOURS);

            return $unreported->mode === 'subscription'
                ? __('php/services/platform/organization_deletion.checkout_unreported_subscription', ['opened' => $this->when($unreported->created_at), 'closed' => $this->when($closed)])
                : __('php/services/platform/organization_deletion.checkout_unreported_card', ['opened' => $this->when($unreported->created_at), 'closed' => $this->when($closed)]);
        }
        $open = DB::table('payments')->where('organization_id', $organizationId)->where('gateway', 'stripe')->whereIn('status', ['pending', 'authorized']);
        if ((clone $open)->where('type', 'refund')->exists()) {
            return __('php/services/platform/organization_deletion.refund_processing');
        }
        if ($open->exists() || DB::table('payment_authorizations')->where('organization_id', $organizationId)->where('status', 'authorized')->exists()) {
            return __('php/services/platform/organization_deletion.card_payment_open');
        }
        // any status Stripe does not call finished — an unknown one included (REVIEW 2, D-R2-7)
        $disputes = DB::table('payment_disputes')->where('organization_id', $organizationId)->whereNotIn('status', PaymentDispute::CLOSED_STATUSES);
        if ($disputes->exists()) {
            // ⚠️ REVIEW 2 (D-R2-8) — a dispute can stay open for months, so there is no "stuck" clock; the way out is named
            return __('php/services/platform/organization_deletion.dispute_open', ['since' => $this->when($disputes->min('created_at'))]);
        }
        $transit = DB::table('payouts')->where('organization_id', $organizationId)->where('status', 'processing');
        if (($n = (clone $transit)->count()) > 0) {
            $since = (clone $transit)->min('updated_at');

            return trans_choice('php/services/platform/organization_deletion.payouts_in_transit', $n, ['count' => $n, 'since' => $this->when($since)]).' '
                .($this->stale($since) ? __('php/services/platform/organization_deletion.payout_transit_stale') : __('php/services/platform/organization_deletion.payout_transit_wait'));
        }
        // money OWED to an owner and not yet sent — deleting would leave them unpaid, and nothing here would remember it
        if (($owed = DB::table('payouts')->where('organization_id', $organizationId)->whereIn('status', ['pending', 'held'])->count()) > 0) {
            return trans_choice('php/services/platform/organization_deletion.payouts_owed', $owed, ['count' => $owed]);
        }
        $run = DB::table('payout_runs')->where('status', 'running')->orderBy('created_at')->first(['started_at', 'created_at']);
        if ($run !== null) {
            $since = $run->started_at ?? $run->created_at;

            return __('php/services/platform/organization_deletion.payout_run_running', ['since' => $this->when($since)]).' '
                .($this->stale($since) ? __('php/services/platform/organization_deletion.looks_stuck') : __('php/services/platform/organization_deletion.payout_run_wait'));
        }
        $hook = DB::table('webhook_events')->where('organization_id', $organizationId)->whereIn('status', ['received', 'processing'])
            ->orderBy('created_at')->first(['source', 'received_at', 'created_at']);
        if ($hook !== null) {
            $since = $hook->received_at ?? $hook->created_at;

            return __('php/services/platform/organization_deletion.webhook_unprocessed', ['source' => str_replace('_', ' ', ucfirst((string) $hook->source)), 'received' => $this->when($since)]).' '
                .($this->stale($since) ? __('php/services/platform/organization_deletion.looks_stuck') : __('php/services/platform/organization_deletion.webhook_wait'));
        }
        if (($crossing = $this->crossTenantReferences($organizationId)) !== []) {
            return __('php/services/platform/organization_deletion.cross_tenant_references', ['links' => implode(', ', array_slice($crossing, 0, 3))]);
        }

        return null;
    }

    /**
     * What a deletion would remove — for the confirmation screen. ⚠️⚠️ ADMIN 06 (#3b): EVERY row it removes, in every
     * table — the organisation's own, the rows a foreign key cascades away (a role's 212 permissions), the rows removed
     * through a parent, and the accounts that belong nowhere else with theirs. 🔬 Before this it counted only the tables
     * with an organisation column: 17 of the 232 rows a signup's deletion removes (production, 27 September). It is
     * the SAME plan {@see self::delete()} runs and reports, so the two cannot disagree.
     *
     * @return array{rows: int, tables: int, own: int, linked: int, accounts: int, per_table: array<string, int>, reservations: int, guests: int, payments: int, invoices: int, users_deleted: int, users_kept: int, files: int}
     */
    public function preview(string $organizationId): array
    {
        $tables = $this->organizationTables();
        [$sole, $shared] = $this->members($organizationId, $tables);
        $tally = $this->tally($this->plan($organizationId, $tables, $sole, $shared));

        return $tally + [
            'reservations' => $this->own($organizationId, 'reservations'),
            'guests' => $this->own($organizationId, 'guests'),
            'payments' => $this->own($organizationId, 'payments'),
            'invoices' => $this->own($organizationId, 'invoices'),
            'users_deleted' => count($sole),
            'users_kept' => count($shared),
            'files' => count($this->files($organizationId, $sole)),
        ];
    }

    /**
     * DELETE. Call inside PlatformQuery::mutate() (lifted scopes, the ledger). Throws {@see OrganizationDeletionRefused}
     * — with nothing deleted — when it must not proceed; after the commit it never throws.
     *
     * @return array{records: int, tables: int, own: int, linked: int, accounts: int, per_table: array<string, int>, users_deleted: int, users_kept: int, files_deleted: int, files_missing: int, files_shared: int, files_failed: list<string>, stripe: array{customers: list<string>, subscriptions: list<string>, connect_accounts: list<string>}, survivors: array<string, int>, warnings: list<string>}
     */
    public function delete(Organization $org): array
    {
        $id = (string) $org->getKey();
        $tables = $this->organizationTables();
        if (($unseen = array_values(array_diff(self::MUST_SEE, $tables))) !== []) {
            throw new OrganizationDeletionRefused(__('php/services/platform/organization_deletion.schema_unreadable', ['tables' => implode(', ', $unseen)]));
        }

        $done = DB::transaction(function () use ($id, $tables): array {
            // ⓪ The lock — then everything is read under it.
            if (DB::table('organizations')->where('id', $id)->lockForUpdate()->first(['id']) === null) {
                throw new OrganizationDeletionRefused(__('php/services/platform/organization_deletion.already_deleted'));
            }
            // the service holds its own bounds (never only the console): a protected organisation, or one not suspended
            // or archived, is refused here too — re-read under the lock, so a reactivation racing the delete is seen
            $current = Organization::withTrashed()->whereKey($id)->firstOrFail();
            if (TenantLifecycle::lockFor($current) !== null) {
                throw new OrganizationDeletionRefused(__('php/services/platform/organization_deletion.protected'));
            }
            if (! $current->trashed() && $current->status !== TenantLifecycle::SUSPENDED) {
                throw new OrganizationDeletionRefused(__('php/services/platform/organization_deletion.not_suspended'));
            }
            // a payout being paid holds its row while the transfer is at Stripe, and says `processing` only when it
            // commits — so the open payouts are LOCKED before the refusal reads them: one mid-transfer is waited for and
            // then seen; one not yet started waits for this deletion, and finds nothing to pay
            DB::table('payouts')->where('organization_id', $id)->whereIn('status', ['pending', 'held', 'processing'])->lockForUpdate()->pluck('id');
            if (($refusal = $this->refusal($id)) !== null) {
                throw new OrganizationDeletionRefused($refusal);
            }
            // the people are LOCKED before anyone is judged sole: someone accepting another organisation's invite now
            // waits for this deletion, instead of joining it a moment before their account — and that membership — go
            $candidates = $this->candidates($id);
            foreach (array_chunk($candidates, self::CHUNK) as $chunk) {
                DB::table('users')->whereIn('id', $chunk)->lockForUpdate()->pluck('id');
            }
            [$sole, $shared] = $this->members($id, $tables, $candidates);
            $files = $this->files($id, $sole);
            $stripe = $this->stripeHandles($id);
            // ⚠️ THE PLAN, counted under the lock BEFORE anything goes — the same plan the preview counted. The count is
            // what this deletion reports; the post-condition below proves the organisation's own rows are all gone.
            $plan = $this->plan($id, $tables, $sole, $shared);
            $tally = $this->tally($plan);

            // ① Rows reachable only through a parent — removed while the parent still says whose they are.
            // ② Every table that carries this organisation's id — read from the schema, never a typed list — then its row.
            foreach ([1, 2] as $phase) {
                foreach ($plan as $step) {
                    if ($step['phase'] === $phase && $step['table'] !== 'organizations') {
                        DB::table($step['table'])->where($step['where'])->delete();
                    }
                }
            }
            // the organisation's own row, last of phase 2 — a LITERAL statement: the guard that there is one way to erase
            // an organisation, and only one, reads for exactly this (PlatformTenantLifecycleTest)
            DB::table('organizations')->where('id', $id)->delete();

            // The platform's notices may name it among their chosen audience — the dangling id is removed (a change, not
            // a removal: the notice stays).
            DB::table('platform_announcements')->whereJsonContains('organization_ids', $id)->get(['id', 'organization_ids'])
                ->each(function ($a) use ($id): void {
                    $ids = array_values(array_filter((array) json_decode((string) $a->organization_ids, true), fn ($v) => $v !== $id));
                    DB::table('platform_announcements')->where('id', $a->id)->update(['organization_ids' => json_encode($ids)]);
                });

            // ③ People whose ONLY link was this organisation — and their rows no foreign key would reach; the account last.
            foreach ($plan as $step) {
                if ($step['phase'] === 3) {
                    DB::table($step['table'])->where($step['where'])->delete();
                }
            }

            // ④ POST-CONDITION — nothing of this organisation may survive, or everything above is rolled back.
            if (($left = $this->rowsPerTable($id, $tables)) !== []) {
                throw new OrganizationDeletionRefused(__('php/services/platform/organization_deletion.record_survived', ['tables' => implode(', ', array_keys($left))]));
            }
            $soleLeft = collect(array_chunk($sole, self::CHUNK))->contains(fn (array $chunk): bool => DB::table('users')->whereIn('id', $chunk)->exists());
            if ($soleLeft || DB::table('organizations')->where('id', $id)->exists()) {
                throw new OrganizationDeletionRefused(__('php/services/platform/organization_deletion.organization_survived'));
            }

            return ['tally' => $tally, 'sole' => $sole, 'shared' => $shared, 'files' => $files, 'stripe' => $stripe];
        });

        // ⑤ AFTER THE COMMIT — the deletion has happened. Nothing below may throw; each step reports instead.
        $warnings = [];
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            report($e);
            $warnings[] = 'The permission cache could not be cleared (it expires on its own).';
        }
        $removed = $this->removeFiles($id, $done['files']);
        $survivors = [];
        try {
            // a write that raced past the post-condition — a queued job, a table no foreign key guards
            if (($survivors = $this->rowsPerTable($id, $tables)) !== []) {
                Log::error('ADMIN 05 — rows of a deleted organisation appeared after its deletion; remove them by hand.', ['organization_id' => $id, 'tables' => $survivors]);
                $warnings[] = array_sum($survivors).' '.(array_sum($survivors) === 1 ? 'record was' : 'records were').' written for it while it was being deleted ('.implode(', ', array_keys($survivors)).') — see the log.';
            }
        } catch (Throwable $e) {
            report($e);
            $warnings[] = 'The check for records written during the deletion could not run — see the log.';
        }

        return [
            'records' => $done['tally']['rows'],
            'tables' => $done['tally']['tables'],
            'own' => $done['tally']['own'],
            'linked' => $done['tally']['linked'],
            'accounts' => $done['tally']['accounts'],
            'per_table' => $done['tally']['per_table'],
            'users_deleted' => count($done['sole']),
            'users_kept' => count($done['shared']),
            'files_deleted' => $removed['deleted'],
            'files_missing' => $removed['missing'],
            'files_shared' => $removed['shared'],
            'files_failed' => $removed['failed'],
            'stripe' => $done['stripe'],
            'survivors' => $survivors,
            'warnings' => $warnings,
        ];
    }

    /**
     * ⚠️⚠️ ADMIN 06 (#3b) — THE ONE PLAN of what a deletion removes, row set by row set: counted by the preview, counted
     * AND run by the deletion. Each step is a table, what goes from it, and when:
     *   phase 1 — rows reachable only through a parent (no foreign key), removed while the parent still says whose;
     *   phase 2 — every row carrying this organisation's id, then the organisation's own row;
     *   phase 3 — the accounts that belong nowhere else, with their rows no foreign key reaches, the account last;
     *   null    — rows a foreign key CASCADES away when a phase-2 or phase-3 row goes: the database removes them, the
     *             plan only counts them (the organisation's roles' 212 permissions, an account's Google/X sign-ins).
     * ⚠️ The steps are DISJOINT by construction, so the count is their sum: a cascade into an organisation table counts
     * only rows NOT carrying this organisation's id (those are already phase 2's), and a person is sole or kept, never both.
     * ⚠️ It follows ONE level of cascade — exact while no cascaded row is itself the parent of a cascade, which
     * {@see self::uncountedCascades()} proves of the live schema, and a refusal enforces.
     *
     * @param  list<string>  $tables
     * @param  list<string>  $sole
     * @param  list<string>  $shared
     * @return list<array{table: string, kind: string, phase: int|null, where: \Closure}>
     */
    private function plan(string $id, array $tables, array $sole, array $shared): array
    {
        $steps = [];
        $add = function (string $table, string $kind, ?int $phase, \Closure $where) use (&$steps): void {
            $steps[] = ['table' => $table, 'kind' => $kind, 'phase' => $phase, 'where' => $where];
        };

        // ① SUBQUERIES, never a list of every guest bound into the statement: a statement allows 65,535 placeholders,
        //   and a tenant with more guests than that could otherwise never be deleted.
        $guestIds = fn ($q) => $q->select('id')->from('guests')->where('organization_id', $id);
        foreach (self::AMENITY_PARENTS as $type => $parent) {
            $add('amenitiables', 'linked', 1, fn ($q) => $q->where('amenitiable_type', $type)
                ->whereIn('amenitiable_id', fn ($p) => $p->select('id')->from($parent)->where('organization_id', $id)));
        }
        $add('notifications', 'linked', 1, fn ($q) => $q->where('notifiable_type', 'guest')->whereIn('notifiable_id', $guestIds));
        $add('sessions', 'linked', 1, fn ($q) => $q->whereIn('user_id', $guestIds));
        $add('login_activity', 'linked', 1, fn ($q) => $q->where('subject_type', 'guest')->whereIn('subject_id', $guestIds));
        // a reset token is keyed by the address's blind index — one another organisation's guest shares, stays
        $add('guest_password_reset_tokens', 'linked', 1, fn ($q) => $q
            ->whereIn('email', fn ($g) => $g->select('email_hash')->from('guests')->where('organization_id', $id)->whereNotNull('email_hash'))
            ->whereNotIn('email', fn ($g) => $g->select('email_hash')->from('guests')->where('organization_id', '!=', $id)->whereNotNull('email_hash')));
        // people who are KEPT still read their feed elsewhere: only the notifications about THIS organisation go
        foreach (array_chunk($this->notificationsAbout($id, $shared, $tables), self::CHUNK) as $chunk) {
            $add('notifications', 'linked', 1, fn ($q) => $q->whereIn('id', $chunk));
        }

        // ②
        foreach ($tables as $table) {
            $add($table, 'own', 2, fn ($q) => $q->where('organization_id', $id));
        }
        $add('organizations', 'own', 2, fn ($q) => $q->where('id', $id));

        // ③ chunked: a statement allows 65,535 placeholders
        foreach (array_chunk($sole, self::CHUNK) as $chunk) {
            $emails = DB::table('users')->whereIn('id', $chunk)->pluck('email')->map(fn ($v): string => strtolower((string) $v))->all();
            $add('oauth_refresh_tokens', 'accounts', 3, fn ($q) => $q
                ->whereIn('access_token_id', fn ($t) => $t->select('id')->from('oauth_access_tokens')->whereIn('user_id', $chunk)));
            foreach (['oauth_access_tokens', 'oauth_auth_codes', 'oauth_device_codes'] as $table) {
                $add($table, 'accounts', 3, fn ($q) => $q->whereIn('user_id', $chunk));
            }
            $add('sessions', 'accounts', 3, fn ($q) => $q->whereIn('user_id', $chunk));
            $add('llm_usage', 'accounts', 3, fn ($q) => $q->whereIn('owner_id', $chunk));
            $add('announcement_dismissals', 'accounts', 3, fn ($q) => $q->whereIn('user_id', $chunk));
            $add('notifications', 'accounts', 3, fn ($q) => $q->where('notifiable_type', 'user')->whereIn('notifiable_id', $chunk));
            $add('password_reset_tokens', 'accounts', 3, fn ($q) => $q->whereIn('email', $emails));
            // their own sign-ins, and the failed attempts and lockouts at the TENANT sign-in with their address —
            // ⚠️ never a platform admin's (guard `admin`) console history, even at the same address
            $add('login_activity', 'accounts', 3, fn ($q) => $q
                ->where(fn ($w) => $w->where('subject_type', 'user')->whereIn('subject_id', $chunk))
                ->orWhere(fn ($w) => $w->where('guard', 'web')->whereNull('subject_type')->whereIn('identifier', $emails)));
            $add('users', 'accounts', 3, fn ($q) => $q->whereIn('id', $chunk));
        }

        // the cascades — read from the schema, never a typed list
        $withOrg = array_flip($tables);
        foreach ($this->cascadeEdges() as [$child, $column, $parent, $parentColumn]) {
            if ($parent === 'organizations' && $column === 'organization_id') {
                continue;   // these rows carry this organisation's id: phase 2 has them
            }
            $parents = match (true) {
                $parent === 'organizations' => [fn ($p) => $p->select($parentColumn)->from('organizations')->where('id', $id)],
                $parent === 'users' => array_map(fn (array $chunk): \Closure => fn ($p) => $p->select($parentColumn)->from('users')->whereIn('id', $chunk), array_chunk($sole, self::CHUNK)),
                isset($withOrg[$parent]) => [fn ($p) => $p->select($parentColumn)->from($parent)->where('organization_id', $id)],
                default => [],   // a parent no deletion reaches directly (amenities, plans, permissions) — see uncountedCascades()
            };
            foreach ($parents as $parentRows) {
                $add($child, $parent === 'users' ? 'accounts' : 'linked', null, fn ($q) => $q->whereIn($column, $parentRows)
                    ->when(isset($withOrg[$child]), fn ($w) => $w->where(fn ($n) => $n->whereNull('organization_id')->orWhere('organization_id', '!=', $id)))
                    ->when($child === 'users', fn ($w) => $w->whereNotIn('id', $sole))
                    ->when($child === 'organizations', fn ($w) => $w->where('id', '!=', $id)));
            }
        }

        return $steps;
    }

    /**
     * Count a plan — ONE statement per step, before anything is removed.
     *
     * @param  list<array{table: string, kind: string, phase: int|null, where: \Closure}>  $plan
     * @return array{rows: int, tables: int, own: int, linked: int, accounts: int, per_table: array<string, int>}
     */
    private function tally(array $plan): array
    {
        $perTable = [];
        $kinds = ['own' => 0, 'linked' => 0, 'accounts' => 0];
        foreach ($plan as $step) {
            $n = DB::table($step['table'])->where($step['where'])->count();
            if ($n > 0) {
                $perTable[$step['table']] = ($perTable[$step['table']] ?? 0) + $n;
                $kinds[$step['kind']] += $n;
            }
        }
        arsort($perTable);

        return ['rows' => array_sum($perTable), 'tables' => count($perTable)] + $kinds + ['per_table' => $perTable];
    }

    private function own(string $organizationId, string $table): int
    {
        return DB::table($table)->where('organization_id', $organizationId)->count();
    }

    /**
     * Every single-column foreign key whose rows go when their parent goes: [child, column, parent, parent column].
     * Read in TWO bulk queries — the delete rule lives in `REFERENTIAL_CONSTRAINTS`, which MariaDB rebuilds on every
     * read (see {@see self::foreignKeysOf()}): 🔬 7 ms read ONCE on production, 1.1 s read once per table.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function cascadeEdges(): array
    {
        if ($this->cascades === null) {
            $rules = [];
            foreach (DB::select('SELECT TABLE_NAME AS child, CONSTRAINT_NAME AS name, DELETE_RULE AS rule FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()') as $row) {
                $rules[(string) $row->child.' '.(string) $row->name] = strtoupper((string) $row->rule);
            }
            $this->foreignKeysOf('organizations');   // loads every table's keys in its one query
            $this->cascades = [];
            foreach ($this->foreignKeys ?? [] as $child => $keys) {
                foreach ($keys as $fk) {
                    if (count($fk['columns']) === 1 && ($rules[$child.' '.$fk['name']] ?? null) === 'CASCADE') {
                        $this->cascades[] = [(string) $child, $fk['columns'][0], $fk['foreign_table'], $fk['foreign_columns'][0]];
                    }
                }
            }
        }

        return $this->cascades;
    }

    /**
     * Organisation tables whose `organization_id` may be NULL — rows a cascade can remove without carrying the id.
     *
     * @return list<string>
     */
    private function nullableOrganizationTables(): array
    {
        return array_map(fn ($r): string => (string) $r->t, DB::select(
            "SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'organization_id' AND IS_NULLABLE = 'YES'"
        ));
    }

    /**
     * ⚠️ THE PREMISE THE PLAN'S COUNT RESTS ON, checked against the live schema. The plan counts one row set per step and
     * sums them, following ONE level of cascade. That is exact while four things hold, and this returns each place
     * one does not (empty when all hold):
     *   1. no row a cascade removes (in a table without an organisation column, or one whose column may be null) is
     *      itself the PARENT of another cascade — "grandparent → parent → child";
     *   2. no table the plan clears DIRECTLY in phase 1 or 3 is the parent of a cascade — "parent (cleared directly) →
     *      child";
     *   3. no such table is the child of TWO cascades from parents the deletion reaches — two parents, or two keys to
     *      the same one — one row would be counted twice and removed once — "child ← parent, parent";
     *   4. no table the plan clears DIRECTLY is also the child of a cascade from a parent the deletion reaches — the
     *      step and the cascade would both count it — "child (cleared directly) ← parent" (REVIEW 2, D-R2-10).
     * 🔬 All four hold on the schema of 2026-09-29. Pure — a test feeds it graphs that break each one.
     *
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $edges  [child, column, parent, parent column]
     * @param  list<string>  $organizationTables
     * @param  list<string>  $nullable
     * @param  list<string>  $clearedDirectly
     * @return list<string>
     */
    public static function uncountedCascades(array $edges, array $organizationTables, array $nullable, array $clearedDirectly = []): array
    {
        $withOrg = array_flip($organizationTables);
        $mayBeNull = array_flip($nullable);
        $direct = array_flip($clearedDirectly);
        $cascadedInto = [];
        foreach ($edges as [$child, $column, $parent]) {
            if ($parent === 'organizations' && $column === 'organization_id') {
                continue;
            }
            // a cascaded row the plan cannot see by an organisation id: no column, or one that may be null
            if (! isset($withOrg[$child]) || isset($mayBeNull[$child])) {
                $cascadedInto[$child][] = $parent;
            }
        }
        $reached = fn (string $table): bool => isset($withOrg[$table]) || isset($direct[$table]) || isset($cascadedInto[$table])
            || $table === 'users' || $table === 'organizations';
        $problems = [];
        foreach ($edges as [$child, $column, $parent]) {
            foreach ($cascadedInto[$parent] ?? [] as $grandparent) {
                $problems[] = $grandparent.' → '.$parent.' → '.$child;
            }
            if (isset($direct[$parent])) {
                $problems[] = $parent.' (cleared directly) → '.$child;
            }
        }
        foreach ($cascadedInto as $child => $parents) {
            // ⚠️ REVIEW 3 — count KEYS, not parents: two keys to the same reached parent are two plan steps, and one row
            //    they both reach would be counted twice
            $reachedKeys = array_values(array_filter($parents, $reached));
            $counted = array_values(array_unique($reachedKeys));
            if (count($reachedKeys) > 1) {
                $problems[] = $child.' ← '.implode(', ', $counted);
            }
            // ⚠️ REVIEW 2 (D-R2-10) — a table the plan clears DIRECTLY that a cascade from a reached parent also removes:
            //    the direct step and the cascade would each count the same row
            if (isset($direct[$child]) && $counted !== []) {
                $problems[] = $child.' (cleared directly) ← '.implode(', ', $counted);
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * ⚠️⚠️ #2 — the organisation's LIVE-mode money records, in plain words; empty when there are none. Whether a record is
     * live comes from Stripe's own `livemode`, stored when it was written — test-mode (false) and non-Stripe (null)
     * records never count. A HOLD pending the founder's lawyer (docs/DECISIONS.md, ADMIN 06), not a policy.
     *
     * @return list<string>
     */
    private function liveMoneyRecords(string $organizationId): array
    {
        $live = fn (string $table) => DB::table($table)->where('organization_id', $organizationId)->where('livemode', true);
        $found = [];
        if (($n = $live('payments')->where('type', '!=', 'refund')->count()) > 0) {
            $found[] = trans_choice('php/services/platform/organization_deletion.live_card_payments', $n, ['count' => $n]);
        }
        if (($n = $live('payments')->where('type', 'refund')->count()) > 0) {
            $found[] = trans_choice('php/services/platform/organization_deletion.live_refunds', $n, ['count' => $n]);
        }
        if (($n = $live('payouts')->count()) > 0) {
            $found[] = trans_choice('php/services/platform/organization_deletion.live_payouts', $n, ['count' => $n]);
        }
        // the subscription's invoices live at Stripe; a live billing relationship is what says they exist
        if ($live('subscriptions')->exists()) {
            $found[] = __('php/services/platform/organization_deletion.live_stripe_billing');
        }

        return $found;
    }

    /**
     * Every table with an `organization_id` column, except the organisations table itself — READ from the schema through
     * Laravel's own schema API (engine-neutral: MySQL 8 upper-cases information_schema column labels, and a raw read
     * keyed on `table_name` silently finds nothing there — which would make the post-condition pass while checking
     * nothing).
     * ⚠️ SCOPED TO THIS CONNECTION'S DATABASE: with no argument, `getTables()` lists every database the user can see —
     * 225 names for 100 tables on the dev machine — and the confirmation screen counted each table once per copy.
     *
     * @return list<string>
     */
    public function organizationTables(): array
    {
        return $this->tables ??= collect(Schema::getTables(Schema::getCurrentSchemaName()))
            ->map(fn (array $t): string => (string) $t['name'])
            ->reject(fn (string $t): bool => $t === 'organizations')
            ->filter(fn (string $t): bool => in_array('organization_id', Schema::getColumnListing($t), true))
            ->sort()->values()->all();
    }

    /**
     * A table's foreign keys — the same shape `Schema::getForeignKeys()` returns (name, columns, foreign_table,
     * foreign_columns), read for the WHOLE schema in ONE query.
     * ⚠️⚠️ 🔬 Measured on production (MariaDB 11.8.9, 2026-09-26): one `Schema::getForeignKeys()` takes 1,110 ms there —
     * it joins `REFERENTIAL_CONSTRAINTS`, which MariaDB rebuilds on every read — so a preview that read a hundred tables'
     * keys one at a time took 118 SECONDS; this read takes 2 ms. Public so a test can hold it equal to the framework's
     * own answer, table for table.
     *
     * @return list<array{name: string, columns: list<string>, foreign_table: string, foreign_columns: list<string>}>
     */
    public function foreignKeysOf(string $table): array
    {
        if ($this->foreignKeys === null) {
            $this->foreignKeys = [];
            // aliased: MySQL 8 returns information_schema labels in UPPER case, and an unaliased key would read as nothing
            $rows = DB::select(
                'SELECT TABLE_NAME AS child, CONSTRAINT_NAME AS name, COLUMN_NAME AS col, REFERENCED_TABLE_NAME AS parent, REFERENCED_COLUMN_NAME AS parent_col'
                .' FROM information_schema.KEY_COLUMN_USAGE'
                .' WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL'
                .' ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION'
            );
            $keys = [];
            foreach ($rows as $row) {
                $at = (string) $row->child.' '.(string) $row->name;
                $keys[$at] ??= ['child' => (string) $row->child, 'name' => (string) $row->name, 'columns' => [], 'foreign_table' => (string) $row->parent, 'foreign_columns' => []];
                $keys[$at]['columns'][] = (string) $row->col;
                $keys[$at]['foreign_columns'][] = (string) $row->parent_col;
            }
            foreach ($keys as $key) {
                $this->foreignKeys[$key['child']][] = ['name' => $key['name'], 'columns' => $key['columns'], 'foreign_table' => $key['foreign_table'], 'foreign_columns' => $key['foreign_columns']];
            }
        }

        return $this->foreignKeys[$table] ?? [];
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, int> table => this organisation's rows, the non-empty ones
     */
    private function rowsPerTable(string $organizationId, array $tables): array
    {
        $rows = [];
        foreach ($tables as $table) {
            $n = DB::table($table)->where('organization_id', $organizationId)->count();
            if ($n > 0) {
                $rows[$table] = $n;
            }
        }

        return $rows;
    }

    /**
     * [people whose only link is this organisation, people who also belong — or are named — elsewhere].
     *
     * @param  list<string>  $tables
     * @return array{0: list<string>, 1: list<string>}
     */
    private function members(string $organizationId, array $tables, ?array $candidates = null): array
    {
        $ids = collect($candidates ?? $this->candidates($organizationId));
        if ($ids->isEmpty()) {
            return [[], []];
        }

        // ⚠️⚠️ Membership is not the whole test. Anyone ANOTHER organisation's records still name — a former member's
        // shifts, leave, signed amendment acknowledgements, audit rows — keeps their account: five foreign keys into
        // `users` CASCADE and the rest set null, so deleting them would delete, or strip the "who" from, that
        // organisation's history. Every foreign key into `users` (read from the schema), plus the columns that name a
        // person without one.
        $elsewhere = collect();
        foreach ([...$this->userReferences($tables), ...self::UNKEYED_USER_COLUMNS] as [$table, $column]) {
            if (! in_array($table, $tables, true)) {
                continue;
            }
            foreach ($ids->chunk(self::CHUNK) as $chunk) {
                $elsewhere = $elsewhere->merge(DB::table($table)->where('organization_id', '!=', $organizationId)->whereIn($column, $chunk->values()->all())->pluck($column));
            }
        }
        $elsewhere = $elsewhere->map(fn ($v): string => (string) $v)->unique();

        return [$ids->diff($elsewhere)->values()->all(), $ids->intersect($elsewhere)->values()->all()];
    }

    /**
     * Everyone this organisation links to an account: its staff, its owners' people, and owner parties with a login.
     *
     * @return list<string>
     */
    private function candidates(string $organizationId): array
    {
        return collect()
            ->merge(DB::table('organization_user')->where('organization_id', $organizationId)->pluck('user_id'))
            ->merge(DB::table('owner_users')->where('organization_id', $organizationId)->pluck('user_id'))
            ->merge(DB::table('owners')->where('organization_id', $organizationId)->whereNotNull('user_id')->pluck('user_id'))
            ->map(fn ($v): string => (string) $v)->unique()->values()->all();
    }

    /**
     * Every foreign key from an organisation table into `users` — [table, column]. The membership tables are among them.
     *
     * @param  list<string>  $tables
     * @return list<array{0: string, 1: string}>
     */
    private function userReferences(array $tables): array
    {
        $refs = [];
        foreach ($tables as $table) {
            foreach ($this->foreignKeysOf($table) as $fk) {
                if ((string) $fk['foreign_table'] === 'users' && count($fk['columns']) === 1) {
                    $refs[] = [$table, (string) $fk['columns'][0]];
                }
            }
        }

        return $refs;
    }

    /**
     * The notifications of KEPT people that are about this organisation — a payload that names it (stamped since
     * ADMIN 05) or links to one of its records by id. Their other notifications are left alone. Their ids — the plan
     * removes them.
     *
     * @param  list<string>  $kept
     * @param  list<string>  $tables
     * @return list<string>
     */
    private function notificationsAbout(string $organizationId, array $kept, array $tables): array
    {
        if ($kept === []) {
            return [];
        }
        $mentions = [];
        foreach (array_chunk($kept, self::CHUNK) as $people) {
            foreach (DB::table('notifications')->where('notifiable_type', 'user')->whereIn('notifiable_id', $people)->get(['id', 'data']) as $row) {
                preg_match_all('/\b[0-7][0-9a-hjkmnp-tv-z]{25}\b/i', (string) $row->data, $m);
                if ($m[0] !== []) {
                    $mentions[(string) $row->id] = array_values(array_unique(array_map('strtolower', $m[0])));
                }
            }
        }
        if ($mentions === []) {
            return [];
        }

        $all = array_values(array_unique(array_merge(...array_values($mentions))));
        $ours = [strtolower($organizationId) => true];
        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'id')) {
                continue;
            }
            foreach (array_chunk($all, 500) as $chunk) {
                foreach (DB::table($table)->where('organization_id', $organizationId)->whereIn('id', $chunk)->pluck('id') as $hit) {
                    $ours[strtolower((string) $hit)] = true;
                }
            }
        }

        return array_map('strval', array_keys(array_filter($mentions, fn (array $ids): bool => array_intersect_key(array_flip($ids), $ours) !== [])));
    }

    /**
     * Files the organisation's rows point at: every media upload (guests' ID scans among them), brand logos, and the
     * avatars of its sole users.
     *
     * @param  list<string>  $soleUsers
     * @return list<array{0: string, 1: string}> [disk, path]
     */
    private function files(string $organizationId, array $soleUsers): array
    {
        $files = DB::table('media')->where('organization_id', $organizationId)->whereNotNull('path')->get(['disk', 'path'])
            ->map(fn ($m): array => [(string) ($m->disk ?: 'local'), (string) $m->path]);
        $files = $files->merge(DB::table('properties')->where('organization_id', $organizationId)->whereNotNull('brand_logo_path')->pluck('brand_logo_path')
            ->map(fn ($p): array => ['public', (string) $p]));
        if ($soleUsers !== []) {
            $files = $files->merge(DB::table('users')->whereIn('id', $soleUsers)->whereNotNull('avatar_path')->pluck('avatar_path')->map(fn ($p): array => ['public', (string) $p]));
        }

        return $files->filter(fn (array $f): bool => $f[1] !== '')->unique(fn (array $f): string => $f[0].':'.$f[1])->values()->all();
    }

    /**
     * Remove the files — after the commit, never throwing. A file already gone is counted as MISSING (not as removed);
     * one that stays is logged with its path (the only record of it once the rows are gone) and returned.
     *
     * @param  list<array{0: string, 1: string}>  $files
     * @return array{deleted: int, missing: int, shared: int, failed: list<string>}
     */
    private function removeFiles(string $organizationId, array $files): array
    {
        [$deleted, $missing, $shared, $failed] = [0, 0, 0, []];
        foreach ($files as [$disk, $path]) {
            try {
                // a file ANOTHER row still points at (two tenants seeded from the same demo photos) is theirs too
                if ($this->stillReferenced($disk, $path)) {
                    $shared++;

                    continue;
                }
                $store = Storage::disk($disk);
                if (! $store->exists($path)) {
                    $missing++;

                    continue;
                }
                // delete it, then confirm it is gone: a disk that refuses reports false, or leaves the file behind
                if ($store->delete($path) && $store->missing($path)) {
                    $deleted++;

                    continue;
                }
                $failed[] = $disk.':'.$path;
                $this->logFile($organizationId, $disk, $path, 'the disk did not remove it');
            } catch (Throwable $e) {
                $failed[] = $disk.':'.$path;
                $this->logFile($organizationId, $disk, $path, $e::class.': '.$e->getMessage());
            }
        }

        return ['deleted' => $deleted, 'missing' => $missing, 'shared' => $shared, 'failed' => $failed];
    }

    private function stillReferenced(string $disk, string $path): bool
    {
        return DB::table('media')->where('disk', $disk)->where('path', $path)->exists()
            || ($disk === 'public' && (DB::table('properties')->where('brand_logo_path', $path)->exists() || DB::table('users')->where('avatar_path', $path)->exists()));
    }

    private function logFile(string $organizationId, string $disk, string $path, string $why): void
    {
        try {
            Log::error('ADMIN 05 — a file of a deleted organisation could not be removed; remove it by hand.', [
                'organization_id' => $organizationId, 'disk' => $disk, 'path' => $path, 'why' => $why,
            ]);
        } catch (Throwable) {
            // the log itself is unavailable — the path is still returned, and the console writes it to its ledger
        }
    }

    /**
     * What Stripe still holds for this organisation once its rows are gone — ids to close there by hand. Not secrets.
     *
     * @return array{customers: list<string>, subscriptions: list<string>, connect_accounts: list<string>}
     */
    private function stripeHandles(string $organizationId): array
    {
        $ids = fn ($values): array => collect($values)->filter()->map(fn ($v): string => (string) $v)->unique()->values()->all();

        return [
            'customers' => $ids(DB::table('subscriptions')->where('organization_id', $organizationId)->pluck('stripe_customer_id')),
            'subscriptions' => $ids(DB::table('subscriptions')->where('organization_id', $organizationId)->pluck('stripe_subscription_id')),
            'connect_accounts' => $ids(collect()
                ->merge(DB::table('organizations')->where('id', $organizationId)->pluck('stripe_connect_account_id'))
                ->merge(DB::table('owners')->where('organization_id', $organizationId)->pluck('stripe_connect_account_id'))
                ->merge(DB::table('payouts')->where('organization_id', $organizationId)->pluck('connect_account_id'))
                ->merge(DB::table('payments')->where('organization_id', $organizationId)->pluck('stripe_account_id'))),
        ];
    }

    private function when(mixed $at): string
    {
        return $at === null
            ? __('php/services/platform/organization_deletion.unknown_time')
            : __('php/services/platform/organization_deletion.time_utc', ['time' => CarbonImmutable::parse((string) $at)->utc()->translatedFormat(LocalDate::pattern('j M Y, H:i'))]);
    }

    private function stale(mixed $at): bool
    {
        return $at === null || CarbonImmutable::parse((string) $at)->lt(now()->subMinutes(self::STALE_MINUTES));
    }

    /**
     * Rows of ANOTHER organisation that point at this one's through a foreign key — a cascade would take them too.
     *
     * @return list<string> "child_table.column" for each crossing found
     */
    private function crossTenantReferences(string $organizationId): array
    {
        $withOrg = array_flip([...$this->organizationTables(), 'organizations']);
        $crossing = [];
        foreach (array_keys($withOrg) as $child) {
            if ($child === 'organizations') {
                continue;
            }
            foreach ($this->foreignKeysOf($child) as $fk) {
                $parent = (string) $fk['foreign_table'];
                if ($parent === 'organizations' || ! isset($withOrg[$parent]) || count($fk['columns']) !== 1) {
                    continue;
                }
                $n = DB::table($child.' as c')
                    ->join($parent.' as p', 'c.'.$fk['columns'][0], '=', 'p.'.$fk['foreign_columns'][0])
                    ->where('p.organization_id', $organizationId)
                    ->where(fn ($q) => $q->whereNull('c.organization_id')->orWhere('c.organization_id', '!=', $organizationId))
                    ->count();
                if ($n > 0) {
                    $crossing[] = $child.'.'.$fk['columns'][0];
                }
            }
        }

        return $crossing;
    }
}
