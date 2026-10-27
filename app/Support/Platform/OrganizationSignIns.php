<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\LoginActivity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️ ADMIN 04 (2026-09-26) — SIGN-IN ACTIVITY FOR ONE ORGANISATION, for the founder's console: when its people last
 * signed in, who, from which IP and on which device — and the failed attempts and lockouts against their addresses.
 *
 * ⚠️ WHAT IT READS. `login_activity` — the platform's own security record ({@see LoginActivity}), written on every
 * successful sign-in, failed attempt and lockout, and pruned after {@see LoginActivity::RETENTION_DAYS} days. The
 * Activity page already shows the same rows platform-wide, IP included; this narrows them to one organisation.
 * ⚠️ WHOSE. The organisation's ACCOUNT HOLDERS — its staff and owners (`organization_user`). A sign-in has no
 * organisation of its own, so a person who belongs to two organisations appears on both — the panel says so.
 * ⚠️ ONLY THE STAFF AND OWNERS' OWN REALM (`web`) — never a guest's, never the platform console's. The tenant login
 *    records no per-attempt failure (it validates without firing one); what it records is the LOCKOUT after repeated
 *    wrong passwords, and that is what this panel lists beside the sign-ins.
 */
final class OrganizationSignIns
{
    public const LIMIT = 25;

    /** The label of a stored event code, in the request's language — an unknown code is shown as itself. */
    private static function eventLabel(string $event): string
    {
        return match ($event) {
            'login' => __('php/support/platform/organization_sign_ins.event_login'),
            'failed' => __('php/support/platform/organization_sign_ins.event_failed'),
            'lockout' => __('php/support/platform/organization_sign_ins.event_lockout'),
            default => ucfirst($event),
        };
    }

    /**
     * @return array{last: ?array{event: string, label: string, at: ?CarbonImmutable, name: string, email: string, ip: ?string, device: string}, rows: list<array{event: string, label: string, at: ?CarbonImmutable, name: string, email: string, ip: ?string, device: string}>, window_days: int}
     */
    public function for(string $organizationId): array
    {
        $members = DB::table('organization_user')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->where('organization_user.organization_id', $organizationId)
            ->get(['users.id', 'users.name', 'users.email']);

        if ($members->isEmpty()) {
            return ['last' => null, 'rows' => [], 'window_days' => LoginActivity::RETENTION_DAYS];
        }

        $byId = $members->keyBy(fn ($m): string => (string) $m->id);
        $byEmail = $members->keyBy(fn ($m): string => strtolower((string) $m->email));
        $since = now()->subDays(LoginActivity::RETENTION_DAYS);

        // ⚠️ THE STAFF AND OWNERS' OWN REALM ONLY (`web`). A wrong password typed at the PLATFORM CONSOLE's login with a
        //    member's address is an attempt on the console, not on this organisation — it never appears here. And the
        //    window the panel names is applied HERE, not left to the nightly prune.
        $inRealm = fn () => LoginActivity::query()->where('guard', 'web')->where('occurred_at', '>=', $since);

        $toRow = function (LoginActivity $row) use ($byId, $byEmail): array {
            $member = $row->event === 'login' ? $byId->get((string) $row->subject_id) : $byEmail->get(strtolower((string) $row->identifier));

            return [
                'event' => (string) $row->event,
                'label' => self::eventLabel((string) $row->event),
                'at' => $row->occurred_at instanceof \DateTimeInterface ? CarbonImmutable::instance($row->occurred_at) : null,
                'name' => (string) ($member->name ?? '—'),
                'email' => (string) ($member->email ?? ''),
                'ip' => $row->ip !== null ? (string) $row->ip : null,
                'device' => DeviceLabel::from($row->user_agent),
            ];
        };

        $rows = $inRealm()
            ->where(function ($q) use ($byId, $byEmail): void {
                // A SUCCESS names its account by id; a failure or a lockout only has the address that was typed —
                // matched regardless of case by the column's case-insensitive collation (measured: a LOWER() here
                // changed nothing, so it was dropped); the row is then attributed by the lower-cased key.
                $q->where(fn ($s) => $s->where('event', 'login')->where('subject_type', 'user')->whereIn('subject_id', $byId->keys()->all()))
                    ->orWhere(fn ($f) => $f->whereIn('event', ['failed', 'lockout'])->whereIn('identifier', $byEmail->keys()->all()));
            })
            ->orderByDesc('occurred_at')
            ->limit(self::LIMIT)
            ->get()
            ->map($toRow)
            ->values()
            ->all();

        // ⚠️ THE LAST SIGN-IN HAS ITS OWN QUERY. Taken from the 25 rows above, a burst of lockouts — which anyone who
        //    knows a member's address can cause from the login page — would push every real sign-in out of view.
        $lastRow = $inRealm()->where('event', 'login')->where('subject_type', 'user')
            ->whereIn('subject_id', $byId->keys()->all())->orderByDesc('occurred_at')->first();

        return ['last' => $lastRow !== null ? $toRow($lastRow) : null, 'rows' => $rows, 'window_days' => LoginActivity::RETENTION_DAYS];
    }
}
