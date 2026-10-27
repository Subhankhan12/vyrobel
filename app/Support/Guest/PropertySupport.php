<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Property;

/**
 * The ONE derivation of a property's guest-facing SUPPORT CONTACT (2026-08-12) — the display half of the
 * `DO-G13-1` support backend. Read by the guest surface AND by the operator settings screen that sets it, so the
 * sentence an operator picks is byte-identical to the sentence a guest reads. Two copies of a claim about how
 * fast someone replies is exactly the mock-value shape this repo keeps paying for (§N.13.7).
 *
 * ⚠️ HONEST ABSENCE IS THE RETURN TYPE, not a rule to remember. {@see for()} returns NULL when the property has
 * populated none of the three columns, and each key is null when its column is. There is no "not provided", no
 * placeholder and no empty row to render: a channel that does not exist is simply absent from the page. The
 * relay (guest → property messages) is the only always-present contact and is NOT modelled here, because it is
 * real for every property by construction.
 *
 * ⚠️ THE REPLY NORM IS A NORM BY CONSTRUCTION (§N.8d / `DO-EMAIL-9`). The stored value is a KEY into
 * {@see REPLY_NORMS}; the sentence lives here, always beginning "usually". An operator cannot type "guaranteed
 * within 1 hour" because there is no free-text path — the field's *wording* is not operator-supplied at all,
 * only the bucket. That is a STRUCTURAL refusal (§N.10b), not a helptext asking nicely.
 *
 * ⚠️ NOTHING IS MEASURED. The norm is what the operator says they aim for. It is never derived from real reply
 * times and never presented as a commitment — a measured figure rendered as a promise is the same false claim
 * with better arithmetic behind it.
 *
 * ⚠️ PHONE, NOT WHATSAPP. One number is stored. It renders as a telephone (`tel:`) because nothing in the
 * product verifies that a given number answers on WhatsApp, and drawing a WhatsApp affordance over an unverified
 * number is a fabricated channel (#39). See the entry in MISSING-BACKEND-BY-PAGE.md.
 */
class PropertySupport
{
    /**
     * The fixed catalogue of soft reply norms: stored key => the sentence fragment rendered to the guest.
     *
     * ⚠️ Every phrase begins "usually". Adding an option that does not is how this becomes a guarantee, and a
     * test asserts the property across the whole catalogue.
     *
     * ⚠️ Each phrase is a COMPLETE PREDICATE, because the guest surface renders it as "We {phrase}." The first
     * cut stored bare intervals ("usually within a few hours") and the page read "We usually within a few
     * hours." — caught in the browser, not by the test, which asserted the prefix "We usually" and was satisfied
     * by the broken sentence. The guard now asserts the whole composed sentence.
     *
     * @var array<string, string>
     */
    public const REPLY_NORMS = [
        'few_hours' => 'usually reply within a few hours',
        'same_day' => 'usually reply the same day',
        'one_day' => 'usually reply within a day',
        'two_days' => 'usually reply within two days',
    ];

    /**
     * The property's support contact, or NULL when it has none configured.
     *
     * A key is present-and-null when that channel is unset; the caller renders a row only for non-null values.
     * An unrecognised stored norm (a hand-edited row, or a key retired from the catalogue) yields null rather
     * than the raw key — a guest must never read a database token where a sentence belongs.
     *
     * @return array{property: string, phone: string|null, reply_norm: string|null, hours: string|null}|null
     */
    public static function for(Property $property): ?array
    {
        $phone = self::trimmed($property->support_phone);
        $hours = self::trimmed($property->support_hours);
        $normKey = self::trimmed($property->support_reply_norm);
        $norm = $normKey !== null ? self::replyNorm($normKey) : null;

        if ($phone === null && $hours === null && $norm === null) {
            return null;
        }

        return [
            'property' => (string) $property->name,
            'phone' => $phone,
            'reply_norm' => $norm,
            'hours' => $hours,
        ];
    }

    /**
     * One reply norm's phrase in the reader's language, or null for a key the catalogue does not define. The
     * constant above stays the catalogue of KEYS (and the English a test reads); this is what a person sees.
     */
    public static function replyNorm(string $key): ?string
    {
        return match ($key) {
            'few_hours' => __('php/support/guest/property_support.few_hours'),
            'same_day' => __('php/support/guest/property_support.same_day'),
            'one_day' => __('php/support/guest/property_support.one_day'),
            'two_days' => __('php/support/guest/property_support.two_days'),
            default => null,
        };
    }

    /**
     * The whole catalogue as the reader sees it: stored key => phrase, in the catalogue's order.
     *
     * @return array<string, string>
     */
    public static function replyNorms(): array
    {
        $out = [];
        foreach (array_keys(self::REPLY_NORMS) as $key) {
            $out[$key] = (string) self::replyNorm($key);
        }

        return $out;
    }

    /** True when at least one support channel is populated — the same test {@see for()} applies. */
    public static function hasAny(Property $property): bool
    {
        return self::for($property) !== null;
    }

    /** The stored norm key, only if it is one this catalogue still defines. */
    public static function normKey(Property $property): ?string
    {
        $key = self::trimmed($property->support_reply_norm);

        return $key !== null && array_key_exists($key, self::REPLY_NORMS) ? $key : null;
    }

    /** A whitespace-only column is as unset as a NULL one — it must not draw an empty affordance. */
    private static function trimmed(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
