<?php

declare(strict_types=1);

use App\Models\Owner;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the WAVE 3 tail: Notifications pagination (BOTH realms).
 *
 * `NotificationFeed::forUser()` loaded the user's ENTIRE notification history into memory on every render
 * and filtered it in PHP.
 *
 * ⚠️ BOTH REALMS TOGETHER. The feed is shared by the operator AND owner notification pages, so paginating
 * one without the other would hide that realm's older notifications with no way to reach them — worse than
 * no pagination at all.
 *
 * ⚠️ THE SHAPE. `items` is a plain LIST of mapped rows and the paginator is returned SEPARATELY, still a
 * paginator of MODELS. An earlier attempt returned a paginator OF MAPPED ROWS and could not satisfy PHPStan
 * (LengthAwarePaginator's TValue is INVARIANT). Splitting them removed the conflict — no cast, no widened
 * type, no baseline entry.
 */
function seedNotifications(string $userId, int $count, string $category = 'general'): void
{
    foreach (range(1, $count) as $i) {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test',
            // ⚠️ The app registers a MORPH MAP, so notifiable_type is the alias ('user'), not the FQCN —
            // seeding the class name writes rows the relation will never find.
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => $userId,
            'data' => json_encode(['title' => 'Notice '.$i, 'context' => 'ctx', 'category' => $category]),
            'read_at' => null,
            'created_at' => now()->subMinutes($i),
            'updated_at' => now()->subMinutes($i),
        ]);
    }
}

it('bounds the feed to one page instead of loading every notification', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    seedNotifications((string) $user->getKey(), NotificationFeed::PER_PAGE + 6);

    $feed = NotificationFeed::forUser($user->fresh());

    expect($feed['items'])->toHaveCount(NotificationFeed::PER_PAGE)          // the PAGE…
        ->and($feed['paginator'])->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($feed['paginator']->total())->toBe(NotificationFeed::PER_PAGE + 6);   // …of a larger whole
});

it('returns the NEXT notifications on page 2, not a repeat of page 1', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    seedNotifications((string) $user->getKey(), NotificationFeed::PER_PAGE + 6);

    $page1 = collect(NotificationFeed::forUser($user->fresh())['items'])->pluck('id')->all();

    request()->merge(['page' => 2]);
    $page2 = collect(NotificationFeed::forUser($user->fresh())['items'])->pluck('id')->all();

    expect($page2)->toHaveCount(6)
        ->and(array_intersect($page1, $page2))->toBe([]);
});

it('keeps the unread badge and category list whole-set, not page-set', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    seedNotifications((string) $user->getKey(), NotificationFeed::PER_PAGE + 6, 'billing');
    seedNotifications((string) $user->getKey(), 3, 'maintenance');

    $feed = NotificationFeed::forUser($user->fresh());

    // A badge counting only the current page would be wrong the moment you paged.
    expect($feed['unread'])->toBe(NotificationFeed::PER_PAGE + 9)
        ->and($feed['categories'])->toContain('billing', 'maintenance');
});

it('filters by category in SQL and paginates the filtered set', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    seedNotifications((string) $user->getKey(), 4, 'billing');
    seedNotifications((string) $user->getKey(), 2, 'maintenance');

    $billing = NotificationFeed::forUser($user->fresh(), 'billing');

    expect($billing['paginator']->total())->toBe(4)
        ->and(collect($billing['items'])->pluck('category')->unique()->all())->toBe(['billing']);
});

it('paginates the OWNER page too — the shared feed cannot bound one realm and not the other', function () {
    $s = seedScenario(1);
    $ownerUser = ownerRoleUser($s);

    seedNotifications((string) $ownerUser->getKey(), NotificationFeed::PER_PAGE + 4);

    // Both notification views render the shared pager off the same feed.
    foreach (['operator/notifications', 'owner/notifications'] as $view) {
        expect(file_get_contents(resource_path("views/livewire/{$view}.blade.php")))
            ->toContain("\$feed['paginator']");
    }

    expect(Owner::query()->where('user_id', $ownerUser->id)->exists())->toBeTrue()
        ->and(NotificationFeed::forUser($ownerUser->fresh())['items'])->toHaveCount(NotificationFeed::PER_PAGE);
});
