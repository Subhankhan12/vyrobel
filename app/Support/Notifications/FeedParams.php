<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Support\I18n\LocalDate;
use App\Support\I18n\StoredEnglish;
use App\Support\Reports\OperatorRevenueBreakdown;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

/**
 * I18N-01 — the parameters a bell notification stores beside its key (`data.i18n`), said in the READER's language when the row
 * is shown. A plain value (a name, a code, an amount) is stored as is. A DATE is stored as a date with its pattern and formatted
 * when read; a booking SOURCE as its code and worded when read — so neither stays in the language it was sent in.
 * Older rows stored only an English sentence: {@see legacyFilled()} re-reads their English dates and source words.
 */
final class FeedParams
{
    /** @return array{'@date': string, '@format': string} */
    public static function date(CarbonInterface|string $date, string $format): array
    {
        return ['@date' => CarbonImmutable::parse($date)->toIso8601String(), '@format' => $format];
    }

    /** @return array{'@source': string} */
    public static function source(string $code): array
    {
        return ['@source' => $code];
    }

    /**
     * Every stored parameter as the text it says now, in the current language.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, scalar|null>
     */
    public static function say(array $params): array
    {
        $out = [];
        foreach ($params as $name => $value) {
            $out[$name] = match (true) {
                is_array($value) && isset($value['@date'], $value['@format']) => CarbonImmutable::parse((string) $value['@date'])->translatedFormat(LocalDate::pattern((string) $value['@format'])),
                is_array($value) && isset($value['@source']) => OperatorRevenueBreakdown::sourceLabel((string) $value['@source']),
                is_scalar($value) || $value === null => $value,
                default => '',
            };
        }

        return $out;
    }

    /**
     * For an OLDER row's English sentence ({@see StoredEnglish}): its dates were printed in English and its
     * source as a capitalised code ("Ota", "Walk_in"). Re-read each in the reader's language; anything else stays as stored.
     *
     * @return array<string, callable(string): string>
     */
    public static function legacyFilled(): array
    {
        $date = fn (string $v): string => self::englishDate($v) ?? $v;

        return [
            'check_in' => $date, 'check_out' => $date, 'from' => $date, 'period' => $date, 'date' => $date,
            'source' => fn (string $v): string => OperatorRevenueBreakdown::sourceLabel(strtolower($v)),
        ];
    }

    /** "26 Oct", "14 Oct 2026", "1 Sep 2026, 09:30", "April 2026" printed in English → the same pattern in the current language. */
    private static function englishDate(string $text): ?string
    {
        foreach (['j M Y, H:i', 'j M Y', 'j M', 'F Y'] as $format) {
            try {
                $d = CarbonImmutable::createFromFormat('!'.$format, $text);
            } catch (Throwable) {
                continue;
            }
            if ($d !== null && $d->locale('en')->translatedFormat($format) === $text) {
                return $d->locale(Carbon::getLocale())->translatedFormat(LocalDate::pattern($format));   // createFromFormat() does not take the current locale
            }
        }

        return null;
    }
}
