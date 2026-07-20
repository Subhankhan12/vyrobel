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

        $items = $all
            ->filter(fn (DatabaseNotification $n): bool => $filter === null || (($n->data['category'] ?? 'general') === $filter))
            ->map(fn (DatabaseNotification $n): array => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Notification'),
                'context' => (string) ($n->data['context'] ?? ''),
                'url' => is_string($n->data['url'] ?? null) ? (string) $n->data['url'] : null,
                'category' => (string) ($n->data['category'] ?? 'general'),
                'read' => $n->read_at !== null,
                'when' => $n->created_at?->diffForHumans(),
            ])
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
}
