<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\Owner\PropertyArchivedNotification;
use App\Support\Billing\BillableUnits;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Property removal — a careful, scheduled ARCHIVE-NOT-DESTROY (task #98 GATE 2 · ⚠️ never destructive).
 *
 * Removal is SCHEDULED, not immediate: the property stays live until the last active reservation checks out
 * ({@see schedule} computes the effective date), then a scheduled pass ({@see processDue}) SOFT-DELETES it and
 * disconnects its channels. It is CANCELLABLE ({@see cancel}) any time before it runs. Reservations, folios and
 * statements are NEVER destroyed — they live in their own tables (a soft-delete doesn't cascade), so they stay
 * queryable/exportable. A hard `delete()` is NEVER used (it would fire `property_user`'s `cascadeOnDelete`).
 *
 * Tenant-scoped: the caller resolves the property under the org scope; every transition is audited.
 *
 * ══ ⚠️⚠️ WHAT AN ARCHIVE ACTUALLY DOES: IT HIDES ONE ROW. THAT IS THE WHOLE DESIGN. ══════════════════
 *
 * ⚠️ Every child table's `property_id` IS declared `cascadeOnDelete` — and a SOFT delete fires NONE of them,
 * because a database cascade only runs on a hard `DELETE`. That is deliberate: a hard delete would destroy
 * reservations, folios and statements that must stay queryable and exportable for years. So after
 * {@see archive()} the property row is hidden by `SoftDeletes` and **all ~20 child tables survive intact** —
 * `units`, `room_types`, `rate_plans`, `inventory_blocks`, `buildings`, `property_policies`, `taxes`, `fees`,
 * `add_ons`, `housekeeping_tasks`, `channels`, `groups`, `reservations`, `booking_quotes`, `shifts`,
 * `overbooking_conflicts`, `property_user`, `invoices` (nullOnDelete). None of them carry `SoftDeletes` of
 * their own, so nothing else hides them.
 *
 * ⚠️⚠️ THE CONSEQUENCE, STATED SO NOBODY HAS TO REDISCOVER IT: **a read is insulated from an archive only if
 * it is bounded by a set derived from `Property::query()`** — which drops the hidden row. Any read that
 * reaches units (or room types, or rate plans) ORG-WIDE, without a property step, still sees an archived
 * property's rows, BY CONSTRUCTION rather than by omission. That is not a bug in this service; it is what
 * "archive, don't destroy" means. It is also why `User::accessiblePropertyIds()` makes every operator screen
 * safe for free, while three owner-facing and operator-facing reads had to be fixed one at a time
 * ({@see BillableUnits::count()} and
 * {@see OwnerPortalService::unitsAtLiveProperties()}).
 *
 * ⚠️ AND TWO THINGS THIS SERVICE DOES NOT DO, recorded because they surprise people:
 *   • **Nothing is ever un-archived.** Removal is cancellable BEFORE it runs ({@see cancel()}) and there is no
 *     restore route, command or method anywhere in the product. After `archive()` it is irreversible in-app.
 *   • **The owner IS told, as of FIX 43 (R-ARCHIVE, F-24).** ⚠️ This entry used to read *"The owner is told
 *     nothing … the silence is a known, open gap"*, and that gap is now closed: {@see archive()} notifies every
 *     owner holding a unit at the property, separately, via {@see PropertyArchivedNotification}. Their earnings
 *     and past stays remain visible and correct — that was always deliberate — and the notice says so rather
 *     than leaving them to infer it from units that stopped appearing.
 */
class PropertyRemovalService
{
    public function __construct(private AuditEventRecorder $audit, private TenantContext $tenant) {}

    /**
     * Schedule removal. Effective date = the last checkout among non-cancelled future/current stays (so the
     * property stays live until then); immediate (today) when there are none. Records who requested it.
     */
    public function schedule(Property $property, string $requestedBy): void
    {
        $today = CarbonImmutable::today();

        $lastCheckout = Reservation::query()
            ->where('property_id', $property->getKey())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereDate('check_out', '>=', $today->toDateString())
            ->max('check_out');

        $effective = $lastCheckout !== null
            ? CarbonImmutable::parse($lastCheckout)->startOfDay()
            : $today;

        $property->forceFill([
            'removal_scheduled_at' => Carbon::now(),
            'removal_effective_at' => $effective->toDateTimeString(),
            'removal_requested_by' => $requestedBy,
        ])->save();

        $this->audit->record('property.removal_scheduled', $property, null, ['fields' => ['removal_effective_at']]);
    }

    /** Cancel a scheduled removal before it runs — clears the schedule; the property is untouched. */
    public function cancel(Property $property): void
    {
        if ($property->removal_scheduled_at === null) {
            return;
        }

        $property->forceFill([
            'removal_scheduled_at' => null,
            'removal_effective_at' => null,
            'removal_requested_by' => null,
        ])->save();

        $this->audit->record('property.removal_cancelled', $property, null, ['fields' => ['removal_scheduled_at']]);
    }

    /**
     * Archive every property whose scheduled effective date has passed (run daily). Returns the count archived.
     * Each: disconnect channels (certified {@see ChannelConnectionService}), then SOFT-DELETE (records retained).
     */
    public function processDue(ChannelConnectionService $channels): int
    {
        // ⚠️⚠️ THIS RAN FROM THE SCHEDULER WITH NO TENANT CONTEXT AND THREW EVERY NIGHT (audit 2026-08-29 · M-8).
        // `Property::query()` is tenant-scoped, and OrganizationScope THROWS with no context — so the daily
        // 02:00 pass raised TenantContextMissingException and archived nothing, silently. CONFIRMED by RUNNING
        // the command as the scheduler runs it, not by reading it.
        //
        // ⚠️ THE SHAPE IS THE SIBLING'S, NOT A NEW ONE (§N.8f) — see GuestDeletionService::processDue():
        //   ① DISCOVER across tenants under an explicit super-admin context, selecting the owning org id;
        //   ② ACT once per tenant inside forOrganization(), so every write, channel disconnect and audit row
        //      happens under that tenant's own scope.
        //
        // ⚠️ NOT `withoutGlobalScope`. Bypassing the scope would convert a fail-closed boundary into a nightly
        // cross-tenant hole — the exact shape the E-1 pass refused.
        $due = $this->tenant->runAsSuperAdmin(fn () => Property::query()
            ->whereNotNull('removal_effective_at')
            ->where('removal_effective_at', '<=', Carbon::now())
            ->get(['id', 'organization_id']));

        $count = 0;

        foreach ($due as $ref) {
            $this->tenant->forOrganization((string) $ref->organization_id, function () use ($ref, $channels, &$count): void {
                $property = Property::query()->find($ref->getKey());

                if ($property instanceof Property) {
                    $this->archive($property, $channels);
                    $count++;
                }
            });
        }

        return $count;
    }

    /** Archive one property NOW (used by processDue and available for an immediate effective date). */
    public function archive(Property $property, ChannelConnectionService $channels): void
    {
        Channel::query()->where('property_id', $property->getKey())->get()
            ->each(fn (Channel $channel) => $channels->disconnect($channel));

        // ⚠️⚠️ CAPTURED BEFORE THE DELETE. After `delete()` the row is hidden by SoftDeletes, so a name read
        //    afterwards — or re-resolved by a queued notification — would find nothing.
        $propertyName = (string) $property->name;
        $owners = $this->ownerUsersFor($property);

        $this->audit->record('property.archived', $property, null, ['fields' => ['deleted_at']]);

        $property->delete();   // SoftDeletes — sets deleted_at; NEVER a hard delete (no FK cascade fires).

        // ⚠️⚠️ R-ARCHIVE (F-24) — AFTER the archive has actually landed, so the notice can never claim an
        //    event that did not happen. ⚠️ AND IT FIRES FROM THE WRITER, NOT A CALLER: `processDue()` and any
        //    future entrance reach the archive through this method, so each inherits the notice for free.
        //    F-18 existed because a guard sat one layer above the write.
        foreach ($owners as $user) {
            $user->notify(new PropertyArchivedNotification($propertyName));
        }
    }

    /**
     * ⚠️ EVERY OWNER HOLDING A UNIT AT THIS PROPERTY, as their linked portal USER — deduplicated.
     *
     * ⚠️ OWNERS ATTACH PER-UNIT (`units.owner_id`), so ONE PROPERTY CAN HAVE SEVERAL, and each is notified
     * SEPARATELY about their own position. A single message naming all of them would tell each owner who the
     * others are, which is a leak.
     *
     * ⚠️ AN OWNER WITH NO LINKED USER CANNOT BE TOLD, and is skipped rather than crashed on: `owners.user_id`
     * is nullable and an owner record can exist before anyone has claimed the portal. 📄 RECORDED as a residual
     * in FIX 43's report — the product has no other channel to reach them.
     *
     * @return list<User>
     */
    private function ownerUsersFor(Property $property): array
    {
        $ownerIds = Unit::query()
            ->where('property_id', $property->getKey())
            ->whereNotNull('owner_id')
            ->distinct()
            ->pluck('owner_id')
            ->all();

        if ($ownerIds === []) {
            return [];
        }

        $userIds = Owner::query()
            ->whereIn('id', $ownerIds)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->all();

        return $userIds === [] ? [] : User::query()->whereIn('id', $userIds)->get()->all();
    }
}
