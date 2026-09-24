<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Models\Organization;
use App\Services\AssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * ADMIN 01 · PHASE 2 — THE ONE DEFINITION of a tenant's platform lifecycle: who is refused, who is protected, and
 * what a suspension would touch. Read by the access gate ({@see RefuseSuspendedOrganization}), by the console's
 * lifecycle actions, and by the pages that warn before them — so they cannot disagree.
 *
 * ⚠️⚠️ WHAT SUSPENSION IS: the organisation's own people (staff at /app, owners at /owner and the owner API) are
 * refused, and the public storefront is closed (it already was — R-SUSPEND, `ResolvePublicProperty`).
 * ⚠️⚠️ WHAT IT IS NOT: it touches no money and no reservation — no refund, no cancellation, no folio write — and a
 * guest keeps their own booking pages. Suspension is between Vyrobel and the operator; the guest is not the lever.
 *
 * ⚠️ ARCHIVE = soft delete (`deleted_at`), reversible, every row retained. An archived organisation is refused
 * exactly as a suspended one is, and it can only be archived once it is suspended, so it keeps `status =
 * suspended` underneath — which is what closes its storefront even on a lookup that forgets the trashed rows.
 * ⚠️ There is NO hard delete and NO prune. Both are the founder's ruling, tied to a retention policy he has not set.
 */
final class TenantLifecycle
{
    public const SUSPENDED = 'suspended';

    /** Every console write is refused — the pinned baseline tenant. */
    public const LOCK_BASELINE = 'baseline';

    /** A lifecycle action needs the organisation's exact name typed as a second confirmation. */
    public const LOCK_CONFIRM = 'confirm';

    /** How an organisation was created. NULL is shown as "Unknown" — never guessed. */
    public const SIGNUP_SOURCES = ['email' => 'Email', 'google' => 'Google', 'twitter' => 'X'];

    /**
     * ⚠️⚠️ THE ACCESS RULE: suspended, archived, or not there at all → the organisation's people are refused.
     * A missing row fails CLOSED — an organisation that cannot be read is not one to let people work in.
     */
    public static function refusesAccess(?Organization $organization): bool
    {
        return $organization === null
            || $organization->trashed()
            || $organization->status === self::SUSPENDED;
    }

    /**
     * The organisation's lifecycle lock, or null. ⚠️ The baseline guard's organisation is locked even if its
     * column were cleared: when `security.baseline_tenant_guard` names it, the console refuses it too.
     */
    public static function lockFor(Organization $organization): ?string
    {
        $baseline = (string) config('security.baseline_tenant_guard', '');
        if ($baseline !== '' && $baseline === (string) $organization->getKey()) {
            return self::LOCK_BASELINE;
        }

        return in_array($organization->lifecycle_lock, [self::LOCK_BASELINE, self::LOCK_CONFIRM], true)
            ? $organization->lifecycle_lock
            : null;
    }

    public static function sourceLabel(?string $source): string
    {
        return self::SIGNUP_SOURCES[$source] ?? 'Unknown';
    }

    /**
     * What a suspension would reach — COUNTS ONLY, never a row (guests are the hotel's data, not the platform's).
     *   · in_house — checked in right now.
     *   · upcoming — not terminal, not checked in, and the stay has not ended yet (today in UTC).
     *
     * ⚠️ Must run inside {@see PlatformQuery::read()} — it reads another tenant's table.
     *
     * @return array{in_house: int, upcoming: int}
     */
    public static function reservationImpact(string $organizationId, ?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::now('UTC'))->toDateString();
        $base = fn () => DB::table('reservations')->where('organization_id', $organizationId);

        return [
            'in_house' => (int) $base()->whereIn('status', AssignmentService::IN_PROGRESS_STATUSES)->count(),
            'upcoming' => (int) $base()
                ->whereNotIn('status', [...AssignmentService::TERMINAL_STATUSES, ...AssignmentService::IN_PROGRESS_STATUSES])
                ->where('check_out', '>', $today)
                ->count(),
        ];
    }
}
