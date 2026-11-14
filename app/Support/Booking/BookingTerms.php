<?php

declare(strict_types=1);

namespace App\Support\Booking;

use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Support\I18n\LocalDate;
use App\Support\Reservations\CancellationPolicy;
use Illuminate\Support\Carbon;

/**
 * THE booking terms a guest is shown before they pay (his ruling 2026-11-14, option B — OPEN D-BOOK-NO-TERMS). ONE builder, read by the
 * public checkout, the public terms page, the stored proof of acceptance, the confirmation email and every screen that repeats what the
 * guest accepted — two builders for one concept WILL drift (the anti-drift family: `CancellationPolicy`, `FolioTotals`, `MoneyFormatter`).
 *
 * ⚠️ BUILT ONLY FROM WHAT THE PROPERTY ACTUALLY SET. A line appears only when its value exists; nothing is defaulted on the hotel's behalf.
 *    The cancellation policy stores ONLY a free-cancel deadline (see {@see CancellationPolicy}) — NOT what is charged after it — so the
 *    terms say exactly that and point at the hotel's own words; they never invent a refund percentage or a penalty (#39).
 * ⚠️ The hotel's own text (`property_policies.booking_terms_extra`) is carried as PLAIN TEXT and must be rendered escaped.
 * ⚠️ NO GUEST DATA is ever in here — the result is stored on the reservation as the proof of what was shown.
 *
 * The result is a plain array so the stored copy (a reservation's `terms_snapshot`) and a live build are the SAME shape and render through
 * the SAME component (`<x-booking.terms>`):
 *
 *   version  — a short hash of the FACTS (policy values + the hotel's text + contact), independent of language and of the stay's dates
 *   locale   — the language it was shown in
 *   sections — [{key, title, lines[]}]  who you book with · cancellation · the stay's rules
 *   extra    — the hotel's own words, or null
 */
final class BookingTerms
{
    /** The longest "own terms" text a property may enter (characters). */
    public const MAX_EXTRA = 2000;

    /** Bumped when the wording template changes in a way that should read as a new version. */
    private const TEMPLATE = 1;

    /**
     * @return array{version: string, locale: string, sections: list<array{key: string, title: string, lines: list<string>}>, extra: ?string}
     */
    public static function for(Property $property, ?string $checkIn = null): array
    {
        /** @var PropertyPolicy|null $policy */
        $policy = $property->policies()->first();
        $cancellation = is_array($policy?->cancellation) ? $policy->cancellation : [];
        $days = array_key_exists('free_until_days', $cancellation) && is_numeric($cancellation['free_until_days'])
            ? max(0, (int) $cancellation['free_until_days']) : null;
        $deposit = is_array($policy?->deposit) ? $policy->deposit : [];
        $extra = self::clean($policy?->booking_terms_extra);

        $email = self::text($property->brand_reply_to);
        $phone = self::text($property->support_phone);
        $checkInTime = self::clock($property->check_in_time);
        $checkOutTime = self::clock($property->check_out_time);
        $pet = PropertyPolicy::ruleLabel($policy?->pet);
        $smoking = PropertyPolicy::ruleLabel($policy?->smoking);
        $minStay = $policy?->min_stay !== null ? (int) $policy->min_stay : null;
        $maxStay = $policy?->max_stay !== null ? (int) $policy->max_stay : null;
        $depositRequired = (bool) ($deposit['required'] ?? false);

        // ── who ────────────────────────────────────────────────────────────────────────────────────
        $who = [
            (string) __('php/support/booking/booking_terms.who_seller', ['property' => (string) $property->name]),
            (string) __('php/support/booking/booking_terms.who_system'),
        ];
        $contact = implode(' · ', array_filter([$email, $phone]));
        if ($contact !== '') {
            $who[] = (string) __('php/support/booking/booking_terms.who_contact', ['contact' => $contact]);
        }

        // ── cancellation ────────────────────────────────────────────────────────────────────────────
        $cancel = [];
        if ($days === null) {
            $cancel[] = (string) __('php/support/booking/booking_terms.cancel_none');
        } else {
            $until = $checkIn !== null ? CancellationPolicy::freeUntilForCheckIn((string) $property->getKey(), $checkIn) : null;
            $cancel[] = $until !== null
                ? (string) __('php/support/booking/booking_terms.cancel_until', ['date' => $until->translatedFormat(LocalDate::pattern('j M Y'))])
                : trans_choice('php/support/booking/booking_terms.cancel_days_before', $days, ['count' => $days]);
            $cancel[] = (string) __('php/support/booking/booking_terms.cancel_after');
        }

        // ── the stay's rules (only the ones the property set) ───────────────────────────────────────
        $stay = [];
        if ($checkInTime !== null) {
            $stay[] = (string) __('php/support/booking/booking_terms.stay_check_in', ['time' => $checkInTime]);
        }
        if ($checkOutTime !== null) {
            $stay[] = (string) __('php/support/booking/booking_terms.stay_check_out', ['time' => $checkOutTime]);
        }
        if ($minStay !== null) {
            $stay[] = trans_choice('php/support/booking/booking_terms.stay_min', $minStay, ['count' => $minStay]);
        }
        if ($maxStay !== null) {
            $stay[] = trans_choice('php/support/booking/booking_terms.stay_max', $maxStay, ['count' => $maxStay]);
        }
        if ($pet !== null) {
            $stay[] = (string) __('php/support/booking/booking_terms.stay_pets', ['rule' => $pet]);
        }
        if ($smoking !== null) {
            $stay[] = (string) __('php/support/booking/booking_terms.stay_smoking', ['rule' => $smoking]);
        }
        if ($depositRequired) {
            $stay[] = (string) __('php/support/booking/booking_terms.stay_deposit');
        }

        $sections = [
            ['key' => 'who', 'title' => (string) __('php/support/booking/booking_terms.who_title'), 'lines' => $who],
            ['key' => 'cancellation', 'title' => (string) __('php/support/booking/booking_terms.cancel_title'), 'lines' => $cancel],
        ];
        if ($stay !== []) {
            $sections[] = ['key' => 'stay', 'title' => (string) __('php/support/booking/booking_terms.stay_title'), 'lines' => $stay];
        }

        // The version reads the FACTS, never the localised sentences or the stay's own dates — so the same rules are the same version in
        // every language and for every stay, and any change to a rule (or to the hotel's words) is a new one.
        $facts = [
            'template' => self::TEMPLATE,
            'name' => (string) $property->name,
            'email' => $email,
            'phone' => $phone,
            'cancel_days' => $days,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'min_stay' => $minStay,
            'max_stay' => $maxStay,
            'pet' => is_string($policy?->pet) ? $policy->pet : null,
            'smoking' => is_string($policy?->smoking) ? $policy->smoking : null,
            'deposit' => $depositRequired,
            'extra' => $extra,
        ];

        return [
            'version' => substr(hash('sha256', (string) json_encode($facts, JSON_UNESCAPED_UNICODE)), 0, 16),
            'locale' => (string) app()->getLocale(),
            'sections' => $sections,
            'extra' => $extra,
        ];
    }

    /**
     * The hotel's own text as it is stored: trimmed, line endings normalised, control characters dropped, capped. NULL when empty.
     * ⚠️ Not HTML — it is never stripped of tags here because it is never rendered as HTML; the view escapes it.
     */
    public static function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $text = str_replace(["\r\n", "\r"], "\n", $value);
        $text = (string) preg_replace('/[^\P{C}\n\t]/u', '', $text);
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, self::MAX_EXTRA);
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /** "15:00" from a stored time ("15:00:00"), or null when unset. */
    private static function clock(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }
}
