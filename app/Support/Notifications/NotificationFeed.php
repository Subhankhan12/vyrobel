<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Maps a user's OWN notifications to the shape the in-app feed renders (Phase 3). Reads only through
 * {@see User::notifications()} — the per-user Notifiable relation — so a feed is ALWAYS the authenticated user's
 * own; there is no way here to reach another user's rows. Shared by the operator + owner feed screens so they
 * render identically.
 */
final class NotificationFeed
{
    /**
     * @return array{items: list<array{id: string, title: string, context: string, url: ?string, category: string, read: bool, when: ?string}>, unread: int, categories: list<string>}
     */
    public static function forUser(User $user, ?string $category = null): array
    {
        /** @var Collection<int, DatabaseNotification> $all */
        $all = $user->notifications;   // newest first (Laravel default order)

        $filter = ($category === null || $category === '' || $category === 'all') ? null : $category;

        // #93 — the "Unread" filter is a real read-state filter (not a category); every other value is a category.
        $items = $all
            ->filter(function (DatabaseNotification $n) use ($filter): bool {
                if ($filter === null) {
                    return true;
                }
                if ($filter === 'unread') {
                    return $n->read_at === null;
                }

                return ($n->data['category'] ?? 'general') === $filter;
            })
            ->map(fn (DatabaseNotification $n): array => self::map($n))
            ->values()
            ->all();

        return [
            'items' => $items,
            'unread' => $all->whereNull('read_at')->count(),
            'categories' => $all
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
        $recent = $user->notifications()->limit($limit)->get();   // relation is already newest-first (->latest())

        return [
            'items' => $recent->map(fn (DatabaseNotification $n): array => self::map($n))->all(),
            'unread' => $user->unreadNotifications()->count(),
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
