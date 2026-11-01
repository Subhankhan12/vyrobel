<?php

declare(strict_types=1);

namespace App\Support\I18n;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

/**
 * I18N-01 — a sentence the SYSTEM wrote into a row in English before (or without) keys: a bell notification from before the
 * translation work, an owner-ledger line ("Management commission 18%"). The row is a record and is never rewritten; this says
 * it in the READER's language when it is exactly one of a known group's English texts, with the parts that were filled in
 * (a name, a percentage, a date) carried over as stored.
 *
 * The group is a lang/en file whose values ARE the sentences the code writes, with `:name` where a value was filled in. A
 * stored sentence that matches none of them (the English moved since, or a person typed it) gives null — show it as stored.
 * An English reader always gets null: what they see is the stored sentence, byte for byte.
 */
final class StoredEnglish
{
    /**
     * @param  array<string, callable(string): string>  $filled  how to say a filled-in part in the reader's language (e.g. a
     *                                                           nested stored sentence, or a category code); others stay as stored
     */
    public static function say(string $group, mixed $stored, array $filled = []): ?string
    {
        if (! is_string($stored) || $stored === '' || App::getLocale() === 'en') {
            return null;
        }
        $english = Lang::get($group, [], 'en', false);
        if (! is_array($english)) {
            return null;
        }
        // the MOST specific sentence first: a shorter one (":property · :check_in – :check_out") also matches a longer stored
        // sentence, its last part swallowing the rest ("8 Oct 2026 · 3 nights") — so more fixed text is tried before less
        uasort($english, fn ($a, $b): int => strlen((string) preg_replace('/:[a-z_]+/i', '', is_string($b) ? $b : ''))
            <=> strlen((string) preg_replace('/:[a-z_]+/i', '', is_string($a) ? $a : '')));
        foreach ($english as $item => $text) {
            if (! is_string($text)) {
                continue;
            }
            if (! str_contains($text, '|')) {
                $params = self::match($text, $stored, $filled);
                if ($params !== null) {
                    return (string) __($group.'.'.$item, $params);
                }

                continue;
            }
            // a plural value ("{1} New message from :name|[2,*] :count new messages from :name"): each form is tried; the count
            // is the stored :count, or the form's own when it names one number ("{1}") — the reader's form is picked from it
            foreach (explode('|', $text) as $form) {
                $range = preg_match('/^\s*(\{[^}]*\}|\[[^\]]*\])\s*/', $form, $r) === 1 ? $r[1] : '';
                $params = self::match(substr($form, strlen($r[0] ?? '')), $stored, $filled);
                if ($params === null) {
                    continue;
                }
                $count = isset($params['count']) && ctype_digit((string) $params['count']) ? (int) $params['count']
                    : (preg_match('/^\{(\d+)\}$/', $range, $one) === 1 ? (int) $one[1] : null);
                if ($count !== null) {
                    return (string) trans_choice($group.'.'.$item, $count, ['count' => $count] + $params);
                }
            }
        }

        return null;
    }

    /**
     * The filled-in parts when $stored is exactly $text with each `:name` filled, else null.
     *
     * @param  array<string, callable(string): string>  $filled
     * @return array<string, string>|null
     */
    private static function match(string $text, string $stored, array $filled): ?array
    {
        $names = [];
        $pattern = preg_replace_callback('/:([a-z_]+)|[^:]+|:/i', function (array $m) use (&$names): string {
            if (($m[1] ?? '') !== '') {
                $names[] = $m[1];

                return '(.+?)';
            }

            return preg_quote($m[0], '~');
        }, $text);
        if (preg_match('~^'.$pattern.'$~su', $stored, $found) !== 1) {
            return null;
        }
        $params = [];
        foreach ($names as $i => $name) {
            $value = $found[$i + 1];
            $params[$name] = isset($filled[$name]) ? $filled[$name]($value) : $value;
        }

        return $params;
    }
}
