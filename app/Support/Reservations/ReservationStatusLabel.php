<?php

declare(strict_types=1);

namespace App\Support\Reservations;

use App\Livewire\Operator\Concerns\FormatsOperatorData;

/**
 * A reservation's status as a WORD a person reads inside a sentence — "checked in", "no-show" — never the stored code.
 *
 * ⚠️ D-REFUSAL-STATUS-CODE (2026-11-11, his "do all"): front-desk and lifecycle refusals printed the raw code ("this booking is
 * checked_in", "cannot be marked no_show") in English AND in every language. They now say the status the way the rest of the app
 * does. ONE words map: this reads the same keys {@see FormatsOperatorData::reservationStatusMeta()} reads, so a status is never
 * worded two ways — only lower-cased, because it sits mid-sentence ("This reservation is already checked out …").
 *
 * An unknown code falls back to its words with the underscores removed — still a word, never `foo_bar`.
 */
final class ReservationStatusLabel
{
    private const KEYS = [
        'confirmed' => 'reservation_confirmed',
        'checked_in' => 'reservation_checked_in',
        'checked_out' => 'reservation_checked_out',
        'tentative' => 'reservation_tentative',
        'enquiry' => 'reservation_enquiry',
        'no_show' => 'reservation_no_show',
        'cancelled' => 'reservation_cancelled',
    ];

    public static function for(string $status): string
    {
        $key = self::KEYS[$status] ?? null;

        return mb_strtolower($key === null
            ? str_replace('_', ' ', $status)
            : __('php/livewire/operator/concerns/formats_operator_data.'.$key));
    }
}
