<?php

declare(strict_types=1);

namespace App\Support\I18n;

use Illuminate\Support\Facades\App;

/**
 * A list of WORDS shown to a person, joined with the page language's own separator (I18N-01, D-I18N-LIST-JOIN).
 * `implode(', ')` is right for English and wrong for Arabic and Persian ("،"), Chinese and Japanese ("、") and Thai (a space).
 * English is byte for byte what `implode(', ', …)` always printed. Machine output (SQL, CSS, ids, logs, CSV) keeps `implode`.
 */
final class ListJoin
{
    /** Anything not listed writes ", " exactly as English does. */
    private const SEPARATORS = [
        'ar' => '، ',
        'fa' => '، ',
        'zh_CN' => '、',
        'zh_TW' => '、',
        'ja' => '、',
        'th' => ' ',
    ];

    public static function separator(?string $locale = null): string
    {
        $locale ??= (string) App::getLocale();

        return self::SEPARATORS[$locale] ?? ', ';
    }

    /** @param  iterable<mixed>  $items */
    public static function join(iterable $items, ?string $locale = null): string
    {
        $words = [];
        foreach ($items as $item) {
            $words[] = (string) $item;
        }

        return implode(self::separator($locale), $words);
    }
}
