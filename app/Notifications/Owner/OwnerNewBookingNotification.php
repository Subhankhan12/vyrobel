<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Models\Reservation;
use App\Models\Unit;
use App\Notifications\FeedNotification;
use Illuminate\Support\Carbon;

/**
 * OWNER FIX 2 — a booking was confirmed on one of THIS owner's units.
 *
 * ⚠️ A SEPARATE KEY FROM THE OPERATOR'S `new_booking`, BY NECESSITY. `NotificationPreferences::CATALOG`
 * models `audience` as a SINGLE string, so one key cannot serve both portals; giving the owner the operator's
 * key would have meant flipping `new_booking` to `owner` and stranding every operator who relies on it. The
 * owner gets `owner_new_booking`; the operator's key is untouched.
 *
 * ⚠️ #28 — NO GUEST IDENTITY AT ALL, not even a name. A notification is a NEW surface for a leak, and it is
 * pushed rather than visited, so it takes the STRICTEST owner posture in the build: the unit-detail stays
 * table, which identifies a booking by REFERENCE alone. Unit + dates + reference is everything an owner needs
 * to find the stay in their own portal, and nothing about the person.
 */
class OwnerNewBookingNotification extends FeedNotification
{
    public function __construct(private Reservation $reservation, private Unit $unit) {}

    public function eventKey(): string
    {
        return 'owner_new_booking';
    }

    public function category(): string
    {
        return 'performance';
    }

    public function data(object $notifiable): array
    {
        $label = (string) ($this->unit->code ?: $this->unit->name ?: '');

        // ⚠️ PARSED, NOT METHOD-CALLED ON THE ATTRIBUTE — the same idiom the sibling operator notification
        // uses (NewBookingNotification), and BookingCadence/PerformanceMetrics before it. `check_in` is cast
        // `date`, but static analysis types it as a string here, so calling ->format() on it directly is an
        // L5 error; parsing is correct whichever form the attribute arrives in.
        $in = Carbon::parse((string) $this->reservation->check_in);
        $out = Carbon::parse((string) $this->reservation->check_out);
        $nights = (int) $in->diffInDays($out);

        // I18N-01 — whole sentences: a unit with no code or name says "Your unit" inside its own sentence (never a
        // translated word passed in), and a stay with nights is a plural sentence (tc() stores the count for the feed).
        $params = [
            'unit' => $label,
            'check_in' => $in->translatedFormat('j M Y'),
            'check_out' => $out->translatedFormat('j M Y'),
            'reference' => (string) $this->reservation->reference,
        ];
        $context = match (true) {
            $label !== '' && $nights > 0 => $this->tc('context', 'php/notifications/owner/owner_new_booking_notification.context_nights', $nights, $params),
            $label !== '' => $this->t('context', 'php/notifications/owner/owner_new_booking_notification.context', $params),
            $nights > 0 => $this->tc('context', 'php/notifications/owner/owner_new_booking_notification.context_your_unit_nights', $nights, $params),
            default => $this->t('context', 'php/notifications/owner/owner_new_booking_notification.context_your_unit', $params),
        };

        return [
            'title' => $label !== ''
                ? $this->t('title', 'php/notifications/owner/owner_new_booking_notification.title', ['unit' => $label])
                : $this->t('title', 'php/notifications/owner/owner_new_booking_notification.title_your_unit'),
            'context' => $context,
            'url' => route('owner.bookings', [], false),
            'category' => 'performance',
        ];
    }
}
