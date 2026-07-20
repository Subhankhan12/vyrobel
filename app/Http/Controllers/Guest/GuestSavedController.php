<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\GuestWaitlistEntry;
use App\Models\GuestWishlistItem;
use App\Models\RoomType;
use App\Services\GuestWishlistService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guest Saved (Phase 4c · Sub-gate 4) — the guest's WISHLIST (saved room types) + WAITLIST (join for sold-out
 * dates), on one page. Real, guest-owned stores.
 *
 * 🔒 ISOLATION: every item/entry is resolved through the authed guest's `guest_id` ({@see ownedItem()} /
 * {@see ownedEntry()}) — never route-model binding. A foreign item/entry → 404 (a guest can only see/modify their
 * own). The waitlist notify trigger + auto-hold are deferred honestly (see {@see GuestWishlistService}).
 */
class GuestSavedController extends Controller
{
    use ResolvesGuest;

    public function __construct(private readonly GuestWishlistService $saved) {}

    public function show(): View
    {
        $guest = $this->guest();
        $now = CarbonImmutable::now();

        return view('guest.saved', [
            'wishlist' => $this->saved->wishlist($guest)->map(fn (GuestWishlistItem $i): array => [
                'id' => (string) $i->getKey(),
                'room' => (string) ($i->roomType->name ?? 'Room'),
                'property' => (string) ($i->roomType->property->name ?? ''),
            ])->all(),
            'waitlist' => $this->saved->waitlist($guest)->map(fn (GuestWaitlistEntry $e): array => [
                'id' => (string) $e->getKey(),
                'room' => (string) ($e->roomType->name ?? 'Room'),
                'property' => (string) ($e->roomType->property->name ?? ''),
                'dates' => $e->flexible_month !== null
                    ? 'Anytime in '.CarbonImmutable::parse($e->flexible_month.'-01')->format('F Y')
                    : ($e->check_in !== null && $e->check_out !== null
                        ? CarbonImmutable::parse($e->check_in)->format('j M').' – '.CarbonImmutable::parse($e->check_out)->format('j M Y')
                        : 'Flexible'),
                'notify_similar' => (bool) $e->notify_similar,
                'expired' => $e->expires_at !== null && $e->expires_at->lessThanOrEqualTo($now),
                'expires' => $e->expires_at !== null ? $e->expires_at->format('j M Y') : null,
            ])->all(),
            // The guest's own org room types — options for the waitlist join form.
            'roomTypes' => RoomType::query()->with('property')->orderBy('name')->get()
                ->map(fn (RoomType $rt): array => ['id' => (string) $rt->getKey(), 'label' => trim(((string) ($rt->property->name ?? '')).' · '.((string) $rt->name))])->all(),
        ]);
    }

    public function addWishlist(Request $request): RedirectResponse
    {
        $data = $request->validate(['room_type_id' => ['required', 'string', Rule::exists('room_types', 'id')]]);
        $this->saved->saveRoom($this->guest(), RoomType::query()->findOrFail($data['room_type_id']));

        return back()->with('status', 'Saved to your wishlist.');
    }

    public function removeWishlist(string $item): RedirectResponse
    {
        $this->saved->removeItem($this->ownedItem($item));

        return redirect()->route('guest.saved')->with('status', 'Removed from your wishlist.');
    }

    public function joinWaitlist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'string', Rule::exists('room_types', 'id')],
            'mode' => ['required', Rule::in(['dates', 'month'])],
            'check_in' => ['nullable', 'required_if:mode,dates', 'date', 'after_or_equal:today'],
            'check_out' => ['nullable', 'required_if:mode,dates', 'date', 'after:check_in'],
            'flexible_month' => ['nullable', 'required_if:mode,month', 'date_format:Y-m'],
            'notify_similar' => ['sometimes', 'boolean'],
        ]);

        $this->saved->joinWaitlist($this->guest(), RoomType::query()->findOrFail($data['room_type_id']), [
            'check_in' => $data['mode'] === 'dates' ? (string) $data['check_in'] : null,
            'check_out' => $data['mode'] === 'dates' ? (string) $data['check_out'] : null,
            'flexible_month' => $data['mode'] === 'month' ? (string) $data['flexible_month'] : null,
            'notify_similar' => $request->boolean('notify_similar'),
        ]);

        return redirect()->route('guest.saved')->with('status', 'You’re on the waitlist — we’ll email you if a spot opens.');
    }

    public function leaveWaitlist(string $entry): RedirectResponse
    {
        $this->saved->leaveWaitlist($this->ownedEntry($entry));

        return redirect()->route('guest.saved')->with('status', 'Removed from the waitlist.');
    }

    public function reconfirmWaitlist(string $entry): RedirectResponse
    {
        $this->saved->reconfirmWaitlist($this->ownedEntry($entry));

        return redirect()->route('guest.saved')->with('status', 'Waitlist place re-confirmed.');
    }

    /** The authed guest's OWN wishlist item, or 404 — resolved through their `guest_id` (never a request id). */
    private function ownedItem(string $item): GuestWishlistItem
    {
        return GuestWishlistItem::query()->where('guest_id', $this->guest()->getKey())->findOrFail($item);
    }

    /** The authed guest's OWN waitlist entry, or 404 — resolved through their `guest_id` (never a request id). */
    private function ownedEntry(string $entry): GuestWaitlistEntry
    {
        return GuestWaitlistEntry::query()->where('guest_id', $this->guest()->getKey())->findOrFail($entry);
    }
}
