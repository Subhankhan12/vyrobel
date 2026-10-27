<?php

declare(strict_types=1);

namespace App\Support\I18n;

use App\Models\Organization;
use App\Support\Tenancy\TenantContext;

/**
 * THE ONE DEFINITION of the languages Vyrobel speaks (I18N-01, 2026-10-27).
 *
 * Two lists, never one:
 *  - PLANNED — every code in this table: English plus the founder's 27. A person may SAVE any of them; until the language
 *    is finished their screens stay English, exactly as a saved `pt` did before (the Profile page says so).
 *  - FINISHED — `config('app.available_locales')`: the languages that passed every gate. Only these are ever APPLIED.
 *    Adding a code there is what turns a language on (the brief, §9), so it is a deliberate one-line change per release.
 *
 * Codes are Laravel style (`pt_BR`, `zh_CN`); the HTML tag, the Carbon locale and the PDF font group come from here, so
 * no view or service keeps its own copy.
 */
final class Locales
{
    /**
     * code => [English name, native name, HTML lang tag, direction, Carbon locale, script, PDF font group]
     * PDF groups: `dejavu` (Latin, Cyrillic, Greek, Georgian, Arabic after shaping), `cjk`, `hangul`, `thai`, and `english`
     * (Hindi: dompdf cannot shape Devanagari, so its PDFs print English wording).
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: 'ltr'|'rtl', 4: string, 5: string, 6: string}>
     */
    public const ALL = [
        'en' => ['English', 'English', 'en', 'ltr', 'en', 'Latn', 'dejavu'],
        'ar' => ['Arabic', 'العربية', 'ar', 'rtl', 'ar', 'Arab', 'dejavu'],
        'tr' => ['Turkish', 'Türkçe', 'tr', 'ltr', 'tr', 'Latn', 'dejavu'],
        'de' => ['German', 'Deutsch', 'de', 'ltr', 'de', 'Latn', 'dejavu'],
        'fr' => ['French', 'Français', 'fr', 'ltr', 'fr', 'Latn', 'dejavu'],
        'es' => ['Spanish', 'Español', 'es', 'ltr', 'es', 'Latn', 'dejavu'],
        'pt' => ['Portuguese (Portugal)', 'Português', 'pt-PT', 'ltr', 'pt', 'Latn', 'dejavu'],
        'it' => ['Italian', 'Italiano', 'it', 'ltr', 'it', 'Latn', 'dejavu'],
        'nl' => ['Dutch', 'Nederlands', 'nl', 'ltr', 'nl', 'Latn', 'dejavu'],
        'pt_BR' => ['Portuguese (Brazil)', 'Português (Brasil)', 'pt-BR', 'ltr', 'pt_BR', 'Latn', 'dejavu'],
        'ru' => ['Russian', 'Русский', 'ru', 'ltr', 'ru', 'Cyrl', 'dejavu'],
        'pl' => ['Polish', 'Polski', 'pl', 'ltr', 'pl', 'Latn', 'dejavu'],
        'ro' => ['Romanian', 'Română', 'ro', 'ltr', 'ro', 'Latn', 'dejavu'],
        'id' => ['Indonesian', 'Bahasa Indonesia', 'id', 'ltr', 'id', 'Latn', 'dejavu'],
        'vi' => ['Vietnamese', 'Tiếng Việt', 'vi', 'ltr', 'vi', 'Latn', 'dejavu'],
        'zh_CN' => ['Chinese (Simplified)', '简体中文', 'zh-CN', 'ltr', 'zh_CN', 'Hans', 'cjk'],
        'ja' => ['Japanese', '日本語', 'ja', 'ltr', 'ja', 'Jpan', 'cjk'],
        'ko' => ['Korean', '한국어', 'ko', 'ltr', 'ko', 'Kore', 'hangul'],
        'zh_TW' => ['Chinese (Traditional)', '繁體中文', 'zh-TW', 'ltr', 'zh_TW', 'Hant', 'cjk'],
        'hi' => ['Hindi', 'हिन्दी', 'hi', 'ltr', 'hi', 'Deva', 'english'],
        'th' => ['Thai', 'ไทย', 'th', 'ltr', 'th', 'Thai', 'thai'],
        'fa' => ['Persian', 'فارسی', 'fa', 'rtl', 'fa', 'Arab', 'dejavu'],
        'et' => ['Estonian', 'Eesti', 'et', 'ltr', 'et', 'Latn', 'dejavu'],
        'el' => ['Greek', 'Ελληνικά', 'el', 'ltr', 'el', 'Grek', 'dejavu'],
        'sr' => ['Serbian', 'Српски', 'sr-Cyrl', 'ltr', 'sr_Cyrl', 'Cyrl', 'dejavu'],
        'bg' => ['Bulgarian', 'Български', 'bg', 'ltr', 'bg', 'Cyrl', 'dejavu'],
        'ka' => ['Georgian', 'ქართული', 'ka', 'ltr', 'ka', 'Geor', 'dejavu'],
        'sq' => ['Albanian', 'Shqip', 'sq', 'ltr', 'sq', 'Latn', 'dejavu'],
    ];

    public const SOURCE = 'en';

    /** Every planned code, English first. @return list<string> */
    public static function planned(): array
    {
        return array_keys(self::ALL);
    }

    /** The finished languages — the only ones ever applied. English always. @return list<string> */
    public static function finished(): array
    {
        $configured = (array) config('app.available_locales', [self::SOURCE]);

        return array_values(array_unique([self::SOURCE, ...array_values(array_filter(
            $configured,
            fn ($c): bool => is_string($c) && isset(self::ALL[$c]),
        ))]));
    }

    /**
     * The languages a person may PICK today: the finished ones plus the three offered before I18N-01 (`pt`, `es`, `fr` —
     * kept so nobody's saved choice or form changes before its language is done). Grows as each language finishes.
     *
     * @return list<string>
     */
    public static function offered(): array
    {
        return array_values(array_unique([...self::finished(), ...self::OFFERED_BEFORE_I18N]));
    }

    /** What the Profile, owner Account and console offered before I18N-01. */
    public const OFFERED_BEFORE_I18N = ['en', 'pt', 'es', 'fr'];

    public static function isPlanned(?string $code): bool
    {
        return is_string($code) && isset(self::ALL[$code]);
    }

    public static function isFinished(?string $code): bool
    {
        return is_string($code) && in_array($code, self::finished(), true);
    }

    /** The first FINISHED candidate, else English. A saved planned-but-unfinished choice falls through, as before. */
    public static function resolve(?string ...$candidates): string
    {
        foreach ($candidates as $c) {
            if (self::isFinished($c)) {
                return (string) $c;
            }
        }

        return self::SOURCE;
    }

    public static function tag(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()][2] ?? self::SOURCE;
    }

    /** @return 'ltr'|'rtl' */
    public static function dir(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()][3] ?? 'ltr';
    }

    public static function isRtl(?string $code = null): bool
    {
        return self::dir($code) === 'rtl';
    }

    /**
     * The <html> tag's language attributes: `lang="en"` in English (byte-for-byte what every page printed before), plus
     * `dir="rtl"` for Arabic and Persian. One definition for every shell, email and PDF.
     */
    public static function htmlAttributes(?string $code = null): string
    {
        return 'lang="'.e(self::tag($code)).'"'.(self::isRtl($code) ? ' dir="rtl"' : '');
    }

    public static function carbon(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()][4] ?? self::SOURCE;
    }

    public static function script(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()][5] ?? 'Latn';
    }

    public static function pdfGroup(?string $code = null): string
    {
        return self::ALL[$code ?? app()->getLocale()][6] ?? 'dejavu';
    }

    public static function nativeName(string $code): string
    {
        return self::ALL[$code][1] ?? $code;
    }

    /**
     * The languages a person may choose on their profile ({@see offered()}), as code => the language's own name.
     *
     * @return array<string, string>
     */
    public static function offeredOptions(): array
    {
        $out = [];
        foreach (self::offered() as $code) {
            $out[$code] = self::nativeName($code);
        }

        return $out;
    }

    /**
     * The same languages for the Vyrobel select: each in its own name, with its name in the READER's language beside it
     * (ICU's, so "Portuguese" for an English reader, "Portugiesisch" for a German one) — omitted where the two are the same.
     *
     * @return list<array{value: string, label: string, hint?: string}>
     */
    public static function pickerOptions(): array
    {
        $reader = str_replace('_', '-', self::tag((string) app()->getLocale()));
        $out = [];
        foreach (self::offered() as $code) {
            $label = self::nativeName($code);
            // a region only where our CODE carries one (pt_BR, zh_CN, zh_TW) — plain "pt" reads "Portuguese", as it always has
            $hint = (string) (str_contains($code, '_')
                ? \Locale::getDisplayName(str_replace('_', '-', $code), $reader)
                : \Locale::getDisplayLanguage($code, $reader));
            $out[] = $hint === '' || $hint === $label ? ['value' => $code, 'label' => $label] : ['value' => $code, 'label' => $label, 'hint' => $hint];
        }

        return $out;
    }

    /**
     * A label placed INSIDE a sentence ("… by bank transfer", "the Channels feature"). English lowercases it, as it always
     * has; German keeps the label as written — its nouns are capitalised, and lowercasing one is a spelling error
     * ("banküberweisung"). The ONE place that decides; never lowercase a translated label any other way.
     */
    public static function inSentence(string $label): string
    {
        return in_array((string) app()->getLocale(), ['de'], true) ? $label : mb_strtolower($label);
    }

    /** Finished languages as code => native name, for a language picker. @return array<string, string> */
    public static function finishedOptions(): array
    {
        $out = [];
        foreach (self::finished() as $code) {
            $out[$code] = self::nativeName($code);
        }

        return $out;
    }

    /** An organisation's default language, read without a tenant scope (it is the organisation's own row). */
    public static function organizationDefault(?string $organizationId): ?string
    {
        if ($organizationId === null || $organizationId === '') {
            return null;
        }

        $value = app(TenantContext::class)->runAsSuperAdmin(
            fn () => Organization::query()->whereKey($organizationId)->value('default_locale'),
        );

        return is_string($value) ? $value : null;
    }
}
