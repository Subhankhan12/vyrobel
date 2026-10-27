<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\Guest;
use App\Models\User;
use App\Notifications\Operator\TeamMessageNotification;
use App\Support\Platform\BreakGlass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Lang;

/**
 * Maps a notifiable's OWN notifications to the shape the in-app feed renders (Phase 3). Reads only through
 * the per-notifiable `Notifiable` relation — so a feed is ALWAYS the authenticated actor's own; there is no way
 * here to reach another actor's rows. Shared by the operator + owner feed screens so they render identically.
 *
 * ⚠️ WIDENED, NOT FORKED (guest notifications · GATE 1). These signatures were `User`-typed. The guest realm
 * needed the same feed, and a parallel `GuestNotificationFeed` is exactly how two surfaces drift (§N.8f) — so
 * the TYPES widened to `User|Guest` and the BODIES did not change by one character. That is safe precisely
 * because every read here goes through `notifications()` / `unreadNotifications()`, which come from Laravel's
 * `Notifiable` trait — and {@see Guest} already uses it. The `notifications` table is `ulidMorphs('notifiable')`
 * and both models are ULID-keyed, so a guest row needs NO schema change.
 *
 * ⚠️ THIS FILE IS DEPENDED ON BY BOTH SEALED REALMS (operator + owner). The widening is additive by
 * construction — a union type accepts strictly more than the old one — and `NotificationFeedRealmTest` pins
 * the operator + owner behaviour so a future change that breaks them cannot pass silently.
 */
final class NotificationFeed
{
    /** Rows per page — the same size the operator lists use, so the realm feels consistent. */
    public const PER_PAGE = 25;

    /**
     * ⚠️ PAGED (WAVE 3 tail) — this loaded the user's ENTIRE notification history into memory on every
     * render and filtered it in PHP. `items` is now one PAGE, filtered in SQL.
     *
     * ⚠️ THE SHAPE IS DELIBERATE. `items` stays a plain LIST of mapped rows (what both views already
     * iterate) and the paginator is returned SEPARATELY, still typed as a paginator of MODELS — which is
     * exactly what `paginate()` produces. An earlier attempt returned a paginator OF MAPPED ROWS and could
     * not satisfy PHPStan: `LengthAwarePaginator`'s TValue template is INVARIANT, so a re-mapped paginator
     * never matches a declared paginator-of-row-shape, and neither `->through()` nor explicit construction
     * resolves it. Splitting the two removes the conflict instead of suppressing it.
     *
     * The COUNTS stay whole-set on purpose: `unread` is a badge for everything unread, not "unread on this
     * page", and `categories` must offer every filter the user has, not only those on page 1.
     *
     * @return array{items: list<array{id: string, title: string, context: string, url: ?string, category: string, read: bool, when: ?string}>, paginator: LengthAwarePaginator<int, DatabaseNotification>, unread: int, categories: list<string>}
     */
    public static function forUser(User|Guest $user, ?string $category = null, int $perPage = self::PER_PAGE): array
    {
        $filter = ($category === null || $category === '' || $category === 'all') ? null : $category;

        // D29-5 + DO6-4 — dismissed rows leave the feed. ⚠️ FILTERED HERE, in the ONE shared builder, so the
        // full page, the category counts and the bell panel all inherit it from a single place: a dismiss that
        // hid a notification on one surface but not another would be worse than no dismiss at all.
        $query = self::withoutPrivate($user->notifications()->getQuery()->whereNull('dismissed_at'))->orderByDesc('created_at');

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter !== null) {
            // The category lives in the JSON payload; 'general' is the documented default for a row with none.
            $filter === 'general'
                ? $query->where(fn ($q) => $q->whereNull('data->category')->orWhere('data->category', 'general'))
                : $query->where('data->category', $filter);
        }

        /** @var LengthAwarePaginator<int, DatabaseNotification> $page */
        $page = $query->paginate($perPage)->withQueryString();

        return [
            'items' => array_map(static fn (DatabaseNotification $n): array => self::map($n), $page->items()),
            'paginator' => $page,
            // ⚠️ The badge counts what the feed SHOWS. A dismissed-but-unread row would otherwise keep the
            // bell lit pointing at something the user has explicitly taken off their list.
            'unread' => self::unreadCount($user),
            'categories' => self::withoutPrivate($user->notifications()->getQuery()->whereNull('dismissed_at'))->get(['data'])
                ->map(fn (DatabaseNotification $n): string => (string) ($n->data['category'] ?? 'general'))
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * The CAPPED read behind the top-bar bell PANEL (Phase-3 notification rebuild) — the most recent $limit
     * notifications + the unread count, WITHOUT loading the user's whole history into every page render (unlike
     * {@see forUser()}, which the full feed screen wants). Same per-user Notifiable relation, so it is the SAME
     * self-only guarantee (#28/#26) and the SAME row shape via {@see map()} — the panel and the page can never
     * drift. The bell reuses `unread` from here (one path for the badge + the panel), no separate count query.
     *
     * @return array{items: list<array{id: string, title: string, context: string, url: ?string, category: string, read: bool, when: ?string}>, unread: int}
     */
    public static function recentForUser(User|Guest $user, int $limit = 6): array
    {
        /** @var Collection<int, DatabaseNotification> $recent */
        // ⚠️ The panel filters dismissed too — same rule, same builder, so the bell and the page agree.
        $recent = self::withoutPrivate($user->notifications()->getQuery()->whereNull('dismissed_at'))->limit($limit)->get();   // already newest-first

        return [
            'items' => $recent->map(fn (DatabaseNotification $n): array => self::map($n))->all(),
            'unread' => self::unreadCount($user),
        ];
    }

    /** The bell's count — what the feed shows, unread (the pulse and team chat read it too, so they always agree). */
    public static function unreadCount(User|Guest $user): int
    {
        return self::withoutPrivate($user->notifications()->getQuery()->whereNull('dismissed_at')->whereNull('read_at'))->count();
    }

    /**
     * While a Vyrobel support (break-glass) session borrows this person's view, their private team-chat entries stay out
     * of every read here — they name the person's conversations (2026-10-06, the independent review: team chat itself
     * refuses the session; its bell entries must too).
     *
     * @param  Builder<DatabaseNotification>  $query
     * @return Builder<DatabaseNotification>
     */
    private static function withoutPrivate(Builder $query): Builder
    {
        return $query->when(BreakGlass::hasStash(request()), fn (Builder $q) => $q->where('type', '!=', TeamMessageNotification::class));
    }

    /**
     * The ONE row-shape mapper — a stored {@see DatabaseNotification} → the array both the feed page and the bell
     * panel render. Kept private + shared so the two surfaces are byte-identical in shape.
     *
     * @return array{id: string, title: string, context: string, url: ?string, category: string, read: bool, when: ?string}
     */
    /**
     * I18N-01 — a row written since the translation work also stores each text's KEY and parameters (`data.i18n`, see
     * FeedNotification::t()), so the bell and the feed show it in the READER's language now, not the one it was sent in.
     * Older rows keep the sentence they stored. A key that no longer exists falls back to the stored sentence.
     *
     * @param  array<string, mixed>  $data
     */
    private static function text(array $data, string $field): ?string
    {
        $spec = $data['i18n'][$field] ?? null;
        if (! is_array($spec) || ! is_string($spec[0] ?? null) || ! Lang::has($spec[0])) {
            return null;
        }

        $params = is_array($spec[1] ?? null) ? $spec[1] : [];

        // a plural row (FeedNotification::tc) stored its count — pick the form in the reader's language
        return is_int($spec[2] ?? null) ? (string) trans_choice($spec[0], $spec[2], $params) : (string) __($spec[0], $params);
    }

    /**
     * The words for a notification category (the bell's tag, the feed pages' filters and chips) — ONE map, in
     * lang/en/components/notifications/bell.php; a category with no entry keeps its code, humanised, as it always printed.
     */
    public static function categoryLabel(string $category): string
    {
        $labels = (array) __('components/notifications/bell.categories');

        return is_string($labels[$category] ?? null) ? $labels[$category] : ucwords(str_replace('_', ' ', $category));
    }

    private static function map(DatabaseNotification $n): array
    {
        return [
            'id' => (string) $n->id,
            'title' => self::text($n->data, 'title') ?? (string) ($n->data['title'] ?? __('php/support/notifications/notification_feed.fallback_title')),
            'context' => self::text($n->data, 'context') ?? (string) ($n->data['context'] ?? ''),
            'url' => is_string($n->data['url'] ?? null) ? (string) $n->data['url'] : null,
            'category' => (string) ($n->data['category'] ?? 'general'),
            'read' => $n->read_at !== null,
            'when' => $n->created_at?->diffForHumans(),
        ];
    }
}
