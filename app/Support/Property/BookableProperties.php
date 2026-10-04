<?php

declare(strict_types=1);

namespace App\Support\Property;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

/**
 * The properties this person may book into — the `property_user` allow-list, or the whole organisation ({@see PropertyContext},
 * #38) — each with its units and how many of them can take a booking, so a property with no rooms shows it before it is chosen.
 *
 * ⚠️ ONE list for every booking surface that offers "book into another property": the New reservation page (2026-10-04, `rel_AV`)
 * and the Calendar's dialog and empty state (2026-10-04, the founder's next report — his working property has no rooms and the
 * Calendar named only "Go to Units" while his other property had three).
 */
final class BookableProperties
{
    /**
     * @return list<array{id: string, name: string, rooms: int, rooms_all: int}>
     */
    public static function all(): array
    {
        $ids = app(PropertyContext::class)->accessibleIds();
        if ($ids === []) {
            return [];
        }

        $count = fn (Builder $q) => $q->whereIn('property_id', $ids)->selectRaw('property_id, COUNT(*) AS n')->groupBy('property_id')->pluck('n', 'property_id');
        $all = $count(Unit::query());
        $open = $count(Unit::query()->bookable());

        return Property::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Property $p): array => ['id' => (string) $p->getKey(), 'name' => (string) $p->name,
                'rooms' => (int) ($open[(string) $p->getKey()] ?? 0), 'rooms_all' => (int) ($all[(string) $p->getKey()] ?? 0)])
            ->values()->all();
    }

    /**
     * The ones that can take a booking now, other than the one named — the way out of a property with no rooms.
     *
     * @return list<array{id: string, name: string, rooms: int, rooms_all: int}>
     */
    public static function elsewhere(?string $exceptId): array
    {
        return array_values(array_filter(self::all(), fn (array $p): bool => $p['rooms'] > 0 && $p['id'] !== $exceptId));
    }
}
