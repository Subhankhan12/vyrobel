<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestWaitlistEntry;
use App\Models\GuestWishlistItem;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * The guest's SAVED lists (Phase 4c · Sub-gate 4) — the wishlist (saved room types) + the waitlist (join for
 * sold-out dates). Real, guest-owned stores; the caller passes the authed guest, so every write is self-only.
 *
 * The waitlist store + re-confirm + expiry are real. The NOTIFY side ("a spot opens → you hear first") and
 * AUTO-HOLD are deferred honestly: a production trigger would listen on a cancellation that frees a waitlisted
 * date and email matching entries via the Phase-3 mail path (SMS is account-gated); auto-holding inventory has
 * real availability implications and would go through AssignmentService. Neither is faked here.
 */
class GuestWishlistService
{
    /** How long a waitlist entry stays live before the guest must re-confirm to keep their place. */
    public const WAITLIST_TTL_DAYS = 30;

    // ── Wishlist ──────────────────────────────────────────────────────────────────────────────────

    /**
     * @return Collection<int, GuestWishlistItem>
     */
    public function wishlist(Guest $guest): Collection
    {
        return GuestWishlistItem::query()
            ->where('guest_id', $guest->getKey())
            ->with('roomType.property')
            ->latest()
            ->get();
    }

    /** Save a room type — idempotent (saving the same room twice is one item). */
    public function saveRoom(Guest $guest, RoomType $roomType): GuestWishlistItem
    {
        return GuestWishlistItem::query()->firstOrCreate([
            'guest_id' => $guest->getKey(),
            'room_type_id' => $roomType->getKey(),
        ]);
    }

    public function removeItem(GuestWishlistItem $item): void
    {
        $item->delete();
    }

    // ── Waitlist ──────────────────────────────────────────────────────────────────────────────────

    /**
     * @return Collection<int, GuestWaitlistEntry>
     */
    public function waitlist(Guest $guest): Collection
    {
        return GuestWaitlistEntry::query()
            ->where('guest_id', $guest->getKey())
            ->where('status', '!=', GuestWaitlistEntry::STATUS_CANCELLED)
            ->with('roomType.property')
            ->latest()
            ->get();
    }

    /**
     * Join the waitlist for a room type — exact dates OR a flexible month. Real store; the notify/hold are deferred.
     *
     * @param  array{check_in?: ?string, check_out?: ?string, flexible_month?: ?string, notify_similar?: bool}  $data
     */
    public function joinWaitlist(Guest $guest, RoomType $roomType, array $data, ?CarbonImmutable $now = null): GuestWaitlistEntry
    {
        $now ??= CarbonImmutable::now();

        return GuestWaitlistEntry::query()->create([
            'guest_id' => $guest->getKey(),
            'room_type_id' => $roomType->getKey(),
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'flexible_month' => $data['flexible_month'] ?? null,
            'notify_similar' => (bool) ($data['notify_similar'] ?? false),
            'status' => GuestWaitlistEntry::STATUS_ACTIVE,
            'confirmed_at' => $now,
            'expires_at' => $now->addDays(self::WAITLIST_TTL_DAYS),
        ]);
    }

    public function leaveWaitlist(GuestWaitlistEntry $entry): void
    {
        $entry->delete();
    }

    /** Re-confirm to keep the place — extends the expiry window and re-activates a lapsed entry. */
    public function reconfirmWaitlist(GuestWaitlistEntry $entry, ?CarbonImmutable $now = null): GuestWaitlistEntry
    {
        $now ??= CarbonImmutable::now();
        $entry->forceFill([
            'status' => GuestWaitlistEntry::STATUS_ACTIVE,
            'confirmed_at' => $now,
            'expires_at' => $now->addDays(self::WAITLIST_TTL_DAYS),
        ])->save();

        return $entry;
    }
}
