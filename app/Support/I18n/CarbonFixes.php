<?php

declare(strict_types=1);

namespace App\Support\I18n;

use Carbon\Translator;

/**
 * Corrections to Carbon's own language data, applied when a language is switched on (I18N-01).
 *
 *  · Georgian: Carbon's `months_standalone` holds the DATIVE forms ("ნოემბერს") — so a month with no day before it
 *    ("F Y") printed "ნოემბერს 2026" — and its `weekdays` hold the dative ("ხუთშაბათს") where a date line wants the
 *    nominative ("ხუთშაბათი, 5 ნოემბერი 2026"). Both now carry the nominative names Georgian writes in a date.
 */
final class CarbonFixes
{
    public static function apply(string $carbonLocale): void
    {
        // idempotent on purpose (no "already applied" flag): Carbon's translator can be reset between two calls in one
        // process, and a flag would then keep the dative names for good
        if ($carbonLocale !== 'ka') {
            return;
        }

        Translator::get('ka')->setTranslations([
            'months_standalone' => ['იანვარი', 'თებერვალი', 'მარტი', 'აპრილი', 'მაისი', 'ივნისი', 'ივლისი', 'აგვისტო', 'სექტემბერი', 'ოქტომბერი', 'ნოემბერი', 'დეკემბერი'],
            'weekdays' => ['კვირა', 'ორშაბათი', 'სამშაბათი', 'ოთხშაბათი', 'ხუთშაბათი', 'პარასკევი', 'შაბათი'],
        ]);
    }
}
