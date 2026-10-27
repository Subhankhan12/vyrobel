<?php

declare(strict_types=1);

// ops/i18n/check.php — I18N-01: validate one language's translations before they are applied.
//
//   php ops/i18n/check.php <lang> [batch]        (lang = a Laravel code: de, ar, pt_BR, zh_CN …)
//
// Reads batches/NNN.json and out/<lang>/NNN.json ({"id": "translation"}). One line per problem: <batch> <id> <CODE> <detail>.
// ERRORS (exit 1): MISSING · EMPTY · PLACEHOLDER (a :name added, dropped or renamed) · HTML (a tag or attribute added/dropped)
// · ATTR (an attribute value changed — classes and :url must stay as English wrote them) · PLURAL (a plural became plain text)
// · NO_SCRIPT (a non-Latin language without its own letters) · UNCHANGED (identical to English, not declared in
// out/<lang>/same/NNN.json) · JSQUOTE (a straight " or ' the English did not have — it can end a JS/HTML string) ·
// ENGLISH_WORDS (common English words left inside a Latin-script translation).
// WARNINGS: LONG (a short label more than 1.7× the English — check it fits) · LATIN (English-looking words in a non-Latin text).

require __DIR__.'/lib.php';

$lang = $argv[1] ?? '';
$only = $argv[2] ?? null;
if ($lang === '' || $lang === 'en') {
    fwrite(STDERR, "usage: php ops/i18n/check.php <lang> [batch]\n");
    exit(2);
}
$work = VY_I18N_WORK;
$brands = ['Vyrobel', 'Google', 'Stripe', 'Booking.com', 'Airbnb', 'Expedia', 'Channex', 'PDF', 'CSV', 'XLSX', 'IBAN', 'BIC', 'SWIFT', 'VAT',
    'OTA', 'ADR', 'RevPAR', 'GDPR', 'URL', 'ID', 'OK', 'Apple Pay', 'Google Pay', 'WhatsApp', 'SMS', 'TOTP', '2FA', 'API', 'OAuth', 'X'];
$englishWords = '/\b(the|and|your|you|with|for|this|that|from|will|have|been|are|was|not|can|which|when|there|their|please|booking|guest|room)\b/i';
// words of the target language spelled like one of the English words above — never "English left behind"
$nativeAlso = ['de' => ['was', 'will', 'die'], 'nl' => ['was', 'have', 'room'], 'it' => ['can'], 'ro' => ['are', 'care'], 'fr' => ['are'], 'es' => ['are'], 'pt' => ['are'], 'pt_BR' => ['are'], 'sq' => ['for', 'are'], 'et' => ['are']];
$scriptRe = vyI18nScriptRegex($lang);
$errors = 0;
$warnings = 0;
$batches = glob("$work/batches/*.json") ?: [];
foreach ($batches as $bf) {
    $b = basename($bf, '.json');
    if ($only !== null && $b !== sprintf('%03d', (int) $only)) {
        continue;
    }
    $src = vyI18nJson($bf);
    $out = vyI18nJson("$work/out/$lang/$b.json");
    $same = vyI18nJson("$work/out/$lang/same/$b.json");
    if ($out === [] && $only === null) {
        continue;   // a batch not started for this language
    }
    foreach ($src as $row) {
        $id = (string) $row['id'];
        $en = (string) $row['en'];
        $t = $out[$id] ?? null;
        $err = function (string $code, string $detail = '') use ($b, $id, &$errors) {
            echo "$b $id $code $detail\n";
            $errors++;
        };
        if ($t === null) {
            $err('MISSING');

            continue;
        }
        if (trim((string) $t) === '' && trim($en) !== '') {
            $err('EMPTY');

            continue;
        }
        $t = (string) $t;
        $key = (string) ($row['key'] ?? '');
        if (vyI18nPlaceholders($en, $key) !== vyI18nPlaceholders($t, $key)) {
            $err('PLACEHOLDER', 'en '.implode(',', vyI18nPlaceholders($en, $key)).' ≠ '.implode(',', vyI18nPlaceholders($t, $key)));
        }
        if (vyI18nIsPlural($en) && vyI18nIsPlural($t)) {
            if (! vyI18nPluralMarkupMatches($en, $t)) {   // per form: a language with more plural forms repeats the markup
                $err('HTML', 'a plural form carries markup no English form has');
            }
        } elseif (vyI18nTags($en) !== vyI18nTags($t)) {
            $err('HTML', implode(' ', vyI18nTags($en)).' ≠ '.implode(' ', vyI18nTags($t)));
        } elseif (vyI18nTagValues($en) !== vyI18nTagValues($t)) {
            $err('ATTR', 'an attribute value differs from the English');
        }
        if (vyI18nIsPlural($en) && ! vyI18nIsPlural($t)) {
            $err('PLURAL', 'the English is a plural ({1} …|[0,*] …); keep the form');
        }
        $plain = trim(strip_tags(preg_replace('/(?<![\w:]):[a-zA-Z_]\w*/', '', $t) ?? $t));
        $isBrandOnly = in_array(trim(strip_tags($en)), $brands, true) || preg_match('/^[\p{P}\p{S}\d\s:·—–\-\/]*$/u', $plain);
        if ($t === $en && ! isset($same[$id]) && ! $isBrandOnly) {
            $err('UNCHANGED', mb_strimwidth($en, 0, 60, '…'));
        }
        if ($scriptRe !== null && $plain !== '' && ! $isBrandOnly && ! preg_match($scriptRe, $plain) && ! isset($same[$id])) {
            $err('NO_SCRIPT', mb_strimwidth($t, 0, 60, '…'));
        }
        foreach (['"', "'"] as $q) {
            if (str_contains(strip_tags($t), $q) && ! str_contains(strip_tags($en), $q)) {
                $err('JSQUOTE', "a straight $q — use the language's typographic quotes or ’");
            }
        }
        // the words, not the placeholders (":from" is a placeholder, not the English "from") and not the markup; a word that is
        // ALSO a word of this language (German "was", "will"; Dutch "was") is not English left behind
        $plain = strip_tags(preg_replace('/(?<![\w:]):[a-zA-Z_]\w*/', '', $t) ?? $t);
        $ownWords = $nativeAlso[$lang] ?? [];
        if ($scriptRe === null && $t !== $en && ! isset($same[$id]) && preg_match_all($englishWords, $plain, $ew)
            && count($found = array_values(array_filter($ew[0], fn (string $w): bool => ! in_array(strtolower($w), $ownWords, true)))) >= 2
            && ! in_array($lang, ['id', 'vi'], true)) {
            $err('ENGLISH_WORDS', implode(',', array_unique($found)));
        }
        if ($scriptRe !== null && preg_match_all('/\b[a-z]{4,}\b/', strip_tags(preg_replace('/(?<![\w:]):[a-zA-Z_]\w*/', '', $t) ?? ''), $lw) && ! isset($same[$id])) {
            $words = array_diff($lw[0], array_map('strtolower', $brands));
            if ($words !== []) {
                echo "$b $id WARN_LATIN ".implode(',', array_slice($words, 0, 4))."\n";
                $warnings++;
            }
        }
        if (mb_strlen($en) <= 24 && mb_strlen($t) > max(12, (int) (mb_strlen($en) * 1.7))) {
            echo "$b $id WARN_LONG ".mb_strlen($en).' → '.mb_strlen($t).": $t\n";
            $warnings++;
        }
    }
}
echo "$lang: errors $errors · warnings $warnings\n";
exit($errors > 0 ? 1 : 0);
