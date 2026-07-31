<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Models\Reservation;
use Illuminate\Support\Carbon;

/**
 * Add-to-calendar links for a guest booking (#109 · Booking Confirmation email) — Google / Outlook deep-links +
 * an inline `.ics` data-URI, ALL generated from the reservation's REAL data (dates, reference, property name +
 * location). No fabrication: an all-day VEVENT over the half-open stay `[check_in, check_out)`, titled with the
 * real property/unit, described with the real reference + the property's real check-in/out times when set.
 */
final class CalendarLinks
{
    /**
     * @return array{google: string, outlook: string, ics: string}
     */
    public static function for(Reservation $reservation): array
    {
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);
        $property = $reservation->property;

        $title = trim((string) ($property->name ?? 'Your stay'));
        $location = self::location($reservation);
        $times = self::timesNote($reservation);
        $details = 'Booking '.((string) $reservation->reference).($times !== '' ? ' — '.$times : '');

        return [
            'google' => self::google($title, $checkIn, $checkOut, $details, $location),
            'outlook' => self::outlook($title, $checkIn, $checkOut, $details, $location),
            'ics' => self::ics($reservation, $title, $checkIn, $checkOut, $details, $location),
        ];
    }

    private static function google(string $title, Carbon $start, Carbon $end, string $details, string $location): string
    {
        // Google all-day range uses the half-open [start, end) date span directly.
        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $start->format('Ymd').'/'.$end->format('Ymd'),
            'details' => $details,
            'location' => $location,
        ]);
    }

    private static function outlook(string $title, Carbon $start, Carbon $end, string $details, string $location): string
    {
        return 'https://outlook.office.com/calendar/0/deeplink/compose?'.http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $title,
            'startdt' => $start->format('Y-m-d'),
            'enddt' => $end->format('Y-m-d'),
            'body' => $details,
            'location' => $location,
        ]);
    }

    private static function ics(Reservation $reservation, string $title, Carbon $start, Carbon $end, string $details, string $location): string
    {
        $esc = static fn (string $s): string => str_replace([',', ';', "\n"], ['\\,', '\\;', '\\n'], $s);
        $uid = ((string) $reservation->reference).'@vyrobel';
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Vyrobel//Booking//EN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTART;VALUE=DATE:'.$start->format('Ymd'),
            'DTEND;VALUE=DATE:'.$end->format('Ymd'),
            'SUMMARY:'.$esc($title),
            'LOCATION:'.$esc($location),
            'DESCRIPTION:'.$esc($details),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return 'data:text/calendar;charset=utf-8,'.rawurlencode(implode("\r\n", $lines));
    }

    private static function location(Reservation $reservation): string
    {
        $address = $reservation->property?->address;
        if (is_array($address)) {
            $parts = array_filter([
                (string) ($address['city'] ?? ''),
                (string) ($address['country'] ?? ''),
            ], static fn (string $s): bool => trim($s) !== '');
            if ($parts !== []) {
                return implode(', ', $parts);
            }
        }

        return trim((string) ($reservation->property->name ?? ''));
    }

    private static function timesNote(Reservation $reservation): string
    {
        $property = $reservation->property;
        $in = $property?->check_in_time;
        $out = $property?->check_out_time;
        $parts = [];
        if ($in !== null && (string) $in !== '') {
            $parts[] = 'check-in from '.Carbon::parse((string) $in)->format('H:i');
        }
        if ($out !== null && (string) $out !== '') {
            $parts[] = 'check-out by '.Carbon::parse((string) $out)->format('H:i');
        }

        return implode(', ', $parts);
    }
}
