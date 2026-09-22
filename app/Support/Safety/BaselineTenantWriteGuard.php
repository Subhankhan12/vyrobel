<?php

declare(strict_types=1);

namespace App\Support\Safety;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * ⚠️⚠️ REFUSES ANY ELOQUENT WRITE AIMED AT THE PINNED BASELINE TENANT. ARMED ONLY WHEN A PROBE SAYS SO.
 *
 * ⚠️⚠️ WHY THIS EXISTS, AND WHY A SENTENCE WAS NOT ENOUGH. "Do not touch tenant one" has been in every brief
 * for weeks, and on 2026-09-22 a probe harness wrote to it anyway. 🔬 The write was caught and reversed and
 * the money digests were unchanged — but by this project's own standard, **a rule that is written down and
 * still fires is a note, not a rule.**
 *
 * ⚠️⚠️ AND THE DIAGNOSIS SAYS WHY A HARNESS CANNOT SIMPLY "BE CAREFUL": a permission sweep runs mostly
 * *refusal* cases, which write nothing and are therefore safe — so the harness looks harmless right up to the
 * one role that is *supposed* to succeed. ⚠️ Worse: **a refused write is still an attempt**, so the day the
 * gate under test is missing is exactly the day the harness writes. **The safety of a refusal test depends on
 * the thing it is testing.** That is a mechanism-shaped problem, not a discipline-shaped one.
 *
 * ⚠️ WHERE IT IS ARMED, AND WHY THERE. It is OFF unless `security.baseline_tenant_guard` names an
 * organisation id. The **probe harness** knows it is probing and turns it on (an env var on the dev server it
 * drives); the **seeder legitimately writes to that tenant** and runs without it, so a blanket always-on
 * guard would fight the thing that builds the baseline in the first place. ⚠️ The arming point is the
 * process, not the code path — which is what makes it hold for a browser-driven probe that never imports
 * this class.
 *
 * ⚠️⚠️ WHAT IT CANNOT CATCH — WRITTEN DOWN BECAUSE A GUARD WHOSE LIMITS ARE UNRECORDED GETS OVER-TRUSTED:
 *   · **Raw query-builder / raw SQL** — `DB::table('guests')->update(...)` fires no Eloquent event. Invisible.
 *   · **A different connection**, or anything reaching the database outside this process.
 *   · **A row with no `organization_id`** that nevertheless belongs to that tenant conceptually — `users`,
 *     `oauth_access_tokens`, `sessions`, the spatie pivots. It guards the tenant column, not the tenant idea.
 *   · **Mass operations** issued as `Model::query()->update(...)`, which bypass per-model events.
 *   · Anything in a **queued job or separate process** that was not started with the flag.
 * ⚠️ So it narrows the blast radius of the realistic mistake — an ordinary `save()`/`create()`/`delete()`
 * through a controller while a probe drives the UI. It is not a sandbox.
 */
final class BaselineTenantWriteGuard
{
    /** Eloquent's wildcard event names. `saving` is deliberately omitted — `creating`/`updating` cover it. */
    private const GUARDED_EVENTS = ['creating', 'updating', 'deleting', 'restoring', 'forceDeleting'];

    public static function armFromConfig(): void
    {
        $orgId = (string) config('security.baseline_tenant_guard', '');

        if ($orgId === '') {
            return; // disarmed — the normal state, including for the seeder
        }

        self::arm($orgId);
    }

    public static function arm(string $organizationId): void
    {
        foreach (self::GUARDED_EVENTS as $event) {
            Event::listen("eloquent.{$event}: *", static function (string $name, array $payload) use ($organizationId): void {
                $model = $payload[0] ?? null;

                if (! $model instanceof Model) {
                    return;
                }

                self::refuseIfBaseline($model, $organizationId, $name);
            });
        }
    }

    private static function refuseIfBaseline(Model $model, string $organizationId, string $eventName): void
    {
        $target = null;

        // The organisation row itself counts — renaming or soft-deleting it is as damaging as touching its data.
        if ($model instanceof Organization && (string) $model->getKey() === $organizationId) {
            $target = 'the organisation row';
        }

        // ⚠️ The ORIGINAL value matters as much as the new one: moving a row OUT of the baseline tenant is
        //    still a write to it. Checking only the current attribute would miss a re-parenting.
        foreach (['getAttribute', 'getOriginal'] as $reader) {
            $value = $model->{$reader}('organization_id');

            if (is_string($value) && $value === $organizationId) {
                $target ??= $model::class;
            }
        }

        if ($target === null) {
            return;
        }

        $verb = str_replace('eloquent.', '', explode(':', $eventName)[0]);

        throw new RuntimeException(
            "⚠️⚠️ BASELINE TENANT WRITE REFUSED — `{$verb}` on {$target} in organisation {$organizationId}. "
            .'That tenant is the pinned money baseline and is read-only to a probe harness. '
            .'If this is the seeder or a legitimate migration, run it WITHOUT security.baseline_tenant_guard set. '
            .'⚠️ See App\\Support\\Safety\\BaselineTenantWriteGuard for what this guard cannot catch.'
        );
    }
}
