<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Maps a user's OWN notifications to the shape the in-app feed renders (Phase 3). Reads only through
 * {@see User::notifications()} — the per-user Notifiable relation — so a feed is ALWAYS the authenticated user's
 * own; there is no way here to reach another user's rows. Shared by the operator + owner feed screens so they
 * render identically.
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
    public static function forUser(User $user, ?string $category = null, int $perPage = self::PER_PAGE): array
    {
        $filter = ($category === null || $category === '' || $category === 'all') ? null : $category;

        // D29-5 + DO6-4 — dismissed rows leave the feed. ⚠️ FILTERED HERE, in the ONE shared builder, so the
        // full page, the category counts and the bell panel all inherit it from a single place: a dismiss that
        // hid a notification on one surface but not another would be worse than no dismiss at all.
        $query = $user->notifications()->getQuery()->whereNull('dismissed_at')->orderByDesc('created_at');

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
            'unread' => $user->notifications()->whereNull('dismissed_at')->whereNull('read_at')->count(),
            'categories' => $user->notifications()->whereNull('dismissed_at')->get(['data'])
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
    public static function recentForUser(User $user, int $limit = 6): array
    {
        /** @var Collection<int, DatabaseNotification> $recent */
        // ⚠️ The panel filters dismissed too — same rule, same builder, so the bell and the page agree.
        $recent = $user->notifications()->whereNull('dismissed_at')->limit($limit)->get();   // already newest-first

        return [
            'items' => $recent->map(fn (DatabaseNotification $n): array => self::map($n))->all(),
            'unread' => $user->unreadNotifications()->whereNull('dismissed_at')->count(),
        ];
    }

    /**
     * The ONE row-shape mapper — a stored {@see DatabaseNotification} → the array both the feed page and the bell
     * panel render. Kept private + shared so the two surfaces are byte-identical in shape.
     *
     * @return array{id: string, title: string, context: string, url: ?string, category: string, read: bool, when: ?string}
     */
    private static function map(DatabaseNotification $n): array
    {
        return [
            'id' => (string) $n->id,
            'title' => (string) ($n->data['title'] ?? 'Notification'),
            'context' => (string) ($n->data['context'] ?? ''),
            'url' => is_string($n->data['url'] ?? null) ? (string) $n->data['url'] : null,
            'category' => (string) ($n->data['category'] ?? 'general'),
            'read' => $n->read_at !== null,
            'when' => $n->created_at?->diffForHumans(),
        ];
    }
}
