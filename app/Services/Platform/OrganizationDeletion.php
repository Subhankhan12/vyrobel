<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\Organization;
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
 * WHAT MAKES IT REFUSE ({@see self::refusal()}, and {@see OrganizationDeletionRefused}): money actively moving at a third
 * party (live Stripe billing, a card payment or hold at Stripe, a payout in transit, a payout run in progress, a webhook
 * being processed), a row of ANOTHER organisation pointing into this one — a cascade would delete it — or a schema read
 * that cannot see the tables every real schema has.
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

    /** Tables every real schema has. A schema read that misses one is wrong — and would make the post-condition blind. */
    private const MUST_SEE = ['reservations', 'guests', 'properties', 'units', 'payments', 'organization_user', 'audit_events', 'media'];

    /** How long money in motion may sit before a refusal calls it stuck rather than asking to wait. */
    private const STALE_MINUTES = 30;

    /** A bound list is cut into pieces this size — a statement allows 65,535 placeholders, and a big tenant has more rows. */
    private const CHUNK = 1000;

    /** @var list<string>|null the schema read, once per request */
    private ?array $tables = null;

    /** @var array<string, list<array<string, mixed>>> foreign keys per table, read once per request */
    private array $foreignKeys = [];

    /**
     * What stops a deletion, in plain words — or null when it may proceed. Read-only.
     */
    public function refusal(string $organizationId): ?string
    {
        $live = DB::table('subscriptions')->where('organization_id', $organizationId)
            ->where(fn ($q) => $q->where('status', 'incomplete')
                ->orWhere(fn ($s) => $s->whereNotNull('stripe_subscription_id')->whereIn('status', ['trialing', 'active', 'past_due', 'paused'])))
            ->exists();
        if ($live) {
            return 'Stripe billing is live for this organisation. Cancel the subscription in Stripe first, or it keeps charging a customer that no longer exists.';
        }
        if (DB::table('payments')->where('organization_id', $organizationId)->where('gateway', 'stripe')->whereIn('status', ['pending', 'authorized'])->exists()
            || DB::table('payment_authorizations')->where('organization_id', $organizationId)->where('status', 'authorized')->exists()) {
            return 'A card payment or card hold is still open. Let it settle, or release it, first.';
        }
        $transit = DB::table('payouts')->where('organization_id', $organizationId)->where('status', 'processing');
        if (($n = (clone $transit)->count()) > 0) {
            $since = (clone $transit)->min('updated_at');

            return ($n === 1 ? 'A payout to an owner is' : $n.' payouts to owners are').' in transit at Stripe (since '.$this->when($since).'). '
                .($this->stale($since) ? 'That is longer than it should take — resolve it with Stripe by hand first.' : 'Wait until Stripe confirms, then try again.');
        }
        $run = DB::table('payout_runs')->where('status', 'running')->orderBy('created_at')->first(['started_at', 'created_at']);
        if ($run !== null) {
            $since = $run->started_at ?? $run->created_at;

            return 'A payout run has been running since '.$this->when($since).'. '
                .($this->stale($since) ? 'It looks stuck — resolve it by hand before deleting.' : 'Try again when it has finished.');
        }
        $hook = DB::table('webhook_events')->where('organization_id', $organizationId)->whereIn('status', ['received', 'processing'])
            ->orderBy('created_at')->first(['source', 'received_at', 'created_at']);
        if ($hook !== null) {
            $since = $hook->received_at ?? $hook->created_at;

            return 'A '.str_replace('_', ' ', ucfirst((string) $hook->source)).' notification for this organisation, received '.$this->when($since).', has not been processed yet. '
                .($this->stale($since) ? 'It looks stuck — resolve it by hand before deleting.' : 'Try again in a few minutes.');
        }
        if (($crossing = $this->crossTenantReferences($organizationId)) !== []) {
            return 'Another organisation has records that point into this one ('.implode(', ', array_slice($crossing, 0, 3)).'). Deleting would delete them too.';
        }

        return null;
    }

    /**
     * What a deletion would remove — counts only, for the confirmation screen. The same count the deletion reports.
     *
     * @return array{rows: int, tables: int, reservations: int, guests: int, payments: int, invoices: int, users_deleted: int, users_kept: int, files: int}
     */
    public function preview(string $organizationId): array
    {
        $tables = $this->organizationTables();
        $before = $this->rowsPerTable($organizationId, $tables);
        [$sole, $shared] = $this->members($organizationId, $tables);

        return [
            'rows' => array_sum($before),
            'tables' => count($before),
            'reservations' => $before['reservations'] ?? 0,
            'guests' => $before['guests'] ?? 0,
            'payments' => $before['payments'] ?? 0,
            'invoices' => $before['invoices'] ?? 0,
            'users_deleted' => count($sole),
            'users_kept' => count($shared),
            'files' => count($this->files($organizationId, $sole)),
        ];
    }

    /**
     * DELETE. Call inside PlatformQuery::mutate() (lifted scopes, the ledger). Throws {@see OrganizationDeletionRefused}
     * — with nothing deleted — when it must not proceed; after the commit it never throws.
     *
     * @return array{records: int, tables: int, linked: int, users_deleted: int, users_kept: int, files_deleted: int, files_missing: int, files_shared: int, files_failed: list<string>, stripe: array{customers: list<string>, subscriptions: list<string>, connect_accounts: list<string>}, survivors: array<string, int>, warnings: list<string>}
     */
    public function delete(Organization $org): array
    {
        $id = (string) $org->getKey();
        $tables = $this->organizationTables();
        if (($unseen = array_values(array_diff(self::MUST_SEE, $tables))) !== []) {
            throw new OrganizationDeletionRefused('The database schema could not be read in full ('.implode(', ', $unseen).' not found).');
        }

        $done = DB::transaction(function () use ($id, $tables): array {
            // ⓪ The lock — then everything is read under it.
            if (DB::table('organizations')->where('id', $id)->lockForUpdate()->first(['id']) === null) {
                throw new OrganizationDeletionRefused('This organisation no longer exists — it has already been deleted.');
            }
            // the service holds its own bounds (never only the console): a protected organisation, or one not suspended
            // or archived, is refused here too — re-read under the lock, so a reactivation racing the delete is seen
            $current = Organization::withTrashed()->whereKey($id)->firstOrFail();
            if (TenantLifecycle::lockFor($current) !== null) {
                throw new OrganizationDeletionRefused('This organisation is protected and can never be deleted.');
            }
            if (! $current->trashed() && $current->status !== TenantLifecycle::SUSPENDED) {
                throw new OrganizationDeletionRefused('Suspend it first — only a suspended or archived organisation can be deleted.');
            }
            // a payout being paid holds its row while the transfer is at Stripe, and says `processing` only when it
            // commits — so the open payouts are LOCKED before the refusal reads them: one mid-transfer is waited for and
            // then seen; one not yet started waits for this deletion, and finds nothing to pay
            DB::table('payouts')->where('organization_id', $id)->whereIn('status', ['pending', 'processing'])->lockForUpdate()->pluck('id');
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
            $before = $this->rowsPerTable($id, $tables);   // every one of these goes — the post-condition below proves it
            $linked = 0;

            // ① Rows reachable only through a parent — removed while the parent still says whose they are. SUBQUERIES,
            //    never a list of every guest bound into the statement: a statement allows 65,535 placeholders, and a
            //    tenant with more guests than that could otherwise never be deleted.
            $guestIds = fn ($q) => $q->select('id')->from('guests')->where('organization_id', $id);
            foreach (self::AMENITY_PARENTS as $type => $table) {
                $linked += DB::table('amenitiables')->where('amenitiable_type', $type)
                    ->whereIn('amenitiable_id', fn ($q) => $q->select('id')->from($table)->where('organization_id', $id))->delete();
            }
            $linked += DB::table('notifications')->where('notifiable_type', 'guest')->whereIn('notifiable_id', $guestIds)->delete();
            $linked += DB::table('sessions')->whereIn('user_id', $guestIds)->delete();
            $linked += DB::table('login_activity')->where('subject_type', 'guest')->whereIn('subject_id', $guestIds)->delete();
            // a reset token is keyed by the address's blind index — one another organisation's guest shares, stays
            $linked += DB::table('guest_password_reset_tokens')
                ->whereIn('email', fn ($q) => $q->select('email_hash')->from('guests')->where('organization_id', $id)->whereNotNull('email_hash'))
                ->whereNotIn('email', fn ($q) => $q->select('email_hash')->from('guests')->where('organization_id', '!=', $id)->whereNotNull('email_hash'))
                ->delete();
            // people who are KEPT still read their feed elsewhere: only the notifications about THIS organisation go
            $linked += $this->deleteNotificationsAbout($id, $shared, $tables);

            // ② Every table that carries this organisation's id — read from the schema, never a typed list.
            foreach ($tables as $table) {
                DB::table($table)->where('organization_id', $id)->delete();
            }
            DB::table('organizations')->where('id', $id)->delete();

            // The platform's notices may name it among their chosen audience — the dangling id is removed.
            DB::table('platform_announcements')->whereJsonContains('organization_ids', $id)->get(['id', 'organization_ids'])
                ->each(function ($a) use ($id): void {
                    $ids = array_values(array_filter((array) json_decode((string) $a->organization_ids, true), fn ($v) => $v !== $id));
                    DB::table('platform_announcements')->where('id', $a->id)->update(['organization_ids' => json_encode($ids)]);
                });

            // ③ People whose ONLY link was this organisation — and their rows no foreign key would reach.
            foreach (array_chunk($sole, self::CHUNK) as $chunk) {
                $emails = DB::table('users')->whereIn('id', $chunk)->pluck('email')->map(fn ($v): string => strtolower((string) $v))->all();
                $linked += DB::table('oauth_refresh_tokens')
                    ->whereIn('access_token_id', fn ($q) => $q->select('id')->from('oauth_access_tokens')->whereIn('user_id', $chunk))->delete();
                foreach (['oauth_access_tokens', 'oauth_auth_codes', 'oauth_device_codes'] as $table) {
                    $linked += DB::table($table)->whereIn('user_id', $chunk)->delete();
                }
                $linked += DB::table('sessions')->whereIn('user_id', $chunk)->delete();
                $linked += DB::table('llm_usage')->whereIn('owner_id', $chunk)->delete();
                $linked += DB::table('announcement_dismissals')->whereIn('user_id', $chunk)->delete();
                $linked += DB::table('notifications')->where('notifiable_type', 'user')->whereIn('notifiable_id', $chunk)->delete();
                $linked += DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
                // their own sign-ins, and the failed attempts and lockouts at the TENANT sign-in with their address —
                // ⚠️ never a platform admin's (guard `admin`) console history, even at the same address
                $linked += DB::table('login_activity')
                    ->where(fn ($q) => $q->where('subject_type', 'user')->whereIn('subject_id', $chunk))
                    ->orWhere(fn ($q) => $q->where('guard', 'web')->whereNull('subject_type')->whereIn('identifier', $emails))
                    ->delete();
                DB::table('users')->whereIn('id', $chunk)->delete();
            }

            // ④ POST-CONDITION — nothing of this organisation may survive, or everything above is rolled back.
            if (($left = $this->rowsPerTable($id, $tables)) !== []) {
                throw new OrganizationDeletionRefused('A record of this organisation survived the deletion (in '.implode(', ', array_keys($left)).'), so everything was rolled back.');
            }
            $soleLeft = collect(array_chunk($sole, self::CHUNK))->contains(fn (array $chunk): bool => DB::table('users')->whereIn('id', $chunk)->exists());
            if ($soleLeft || DB::table('organizations')->where('id', $id)->exists()) {
                throw new OrganizationDeletionRefused('The organisation or one of its people survived the deletion, so everything was rolled back.');
            }

            return ['before' => $before, 'linked' => $linked, 'sole' => $sole, 'shared' => $shared, 'files' => $files, 'stripe' => $stripe];
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
            'records' => array_sum($done['before']),
            'tables' => count($done['before']),
            'linked' => $done['linked'],
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

    /** @return list<array<string, mixed>> */
    private function foreignKeys(string $table): array
    {
        return $this->foreignKeys[$table] ??= Schema::getForeignKeys($table);
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
            foreach ($this->foreignKeys($table) as $fk) {
                if ((string) $fk['foreign_table'] === 'users' && count($fk['columns']) === 1) {
                    $refs[] = [$table, (string) $fk['columns'][0]];
                }
            }
        }

        return $refs;
    }

    /**
     * The notifications of KEPT people that are about this organisation — a payload that names it (stamped since
     * ADMIN 05) or links to one of its records by id. Their other notifications are left alone.
     *
     * @param  list<string>  $kept
     * @param  list<string>  $tables
     */
    private function deleteNotificationsAbout(string $organizationId, array $kept, array $tables): int
    {
        if ($kept === []) {
            return 0;
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
            return 0;
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
        $about = array_keys(array_filter($mentions, fn (array $ids): bool => array_intersect_key(array_flip($ids), $ours) !== []));

        $n = 0;
        foreach (array_chunk($about, self::CHUNK) as $chunk) {
            $n += DB::table('notifications')->whereIn('id', $chunk)->delete();
        }

        return $n;
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
        return $at === null ? 'an unknown time' : CarbonImmutable::parse((string) $at)->utc()->format('j M Y, H:i').' UTC';
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
            foreach ($this->foreignKeys($child) as $fk) {
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
