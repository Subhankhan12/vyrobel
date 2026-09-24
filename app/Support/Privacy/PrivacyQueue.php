<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use App\Services\GuestDeletionService;
use App\Support\Platform\PlatformQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️ ADMIN 01 · PHASE 4 — THE LIVE GDPR QUEUE, read from the EXISTING mechanism, never re-implemented:
 * a guest who deletes their account is scheduled ({@see GuestDeletionService::schedule()} — `guests.scheduled_deletion_at`,
 * {@see GuestDeletionService::GRACE_DAYS} days, reversible) and the daily `guests:process-deletions` job erases them.
 *
 * ⚠️⚠️ COUNTS AND ORGANISATION NAMES ONLY — never a guest. The hotel is the controller of its guests' data; the
 * console only watches that Vyrobel's machinery keeps its promise.
 * ⚠️ OVERDUE IS THE ALARM: a request past its due date and not yet erased means the daily job did not run — a
 * silent failure, exactly the class this console exists to make visible.
 *
 * Must run inside {@see PlatformQuery::read()}.
 */
final class PrivacyQueue
{
    /**
     * @return array{pending: int, overdue: int, erased_30d: int, next_due: ?CarbonImmutable, by_org: list<array{organization: string, internal: bool, pending: int, overdue: int}>}
     */
    public function compute(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $open = fn () => DB::table('guests')->whereNotNull('scheduled_deletion_at')->whereNull('anonymized_at');

        $byOrg = $open()
            ->join('organizations', 'organizations.id', '=', 'guests.organization_id')
            ->groupBy('organizations.id', 'organizations.name', 'organizations.is_internal')
            ->selectRaw('organizations.name as organization, organizations.is_internal as internal, COUNT(*) as pending, SUM(CASE WHEN guests.scheduled_deletion_at < ? THEN 1 ELSE 0 END) as overdue', [$now])
            ->orderByDesc('overdue')->orderBy('organizations.name')
            ->get()
            ->map(fn (object $r): array => ['organization' => (string) $r->organization, 'internal' => (bool) $r->internal, 'pending' => (int) $r->pending, 'overdue' => (int) $r->overdue])
            ->all();

        $next = $open()->where('scheduled_deletion_at', '>=', $now)->min('scheduled_deletion_at');

        return [
            'pending' => $open()->count(),
            'overdue' => $open()->where('scheduled_deletion_at', '<', $now)->count(),
            'erased_30d' => DB::table('guests')->where('anonymized_at', '>=', $now->subDays(30))->count(),
            'next_due' => $next !== null ? CarbonImmutable::parse((string) $next, 'UTC') : null,
            'by_org' => $byOrg,
        ];
    }
}
