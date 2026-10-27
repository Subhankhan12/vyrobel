<?php

declare(strict_types=1);

namespace App\Support\Pdf;

/**
 * Prepares Arabic / Persian text for dompdf, which can neither join Arabic letters nor lay out
 * right-to-left text: without this an Arabic folio prints isolated letters in reverse order.
 *
 * shape():     contextual letter forms (Unicode Presentation Forms A/B) + lam-alef ligatures,
 *              then a simplified bidi pass for an RTL paragraph — Arabic runs are reversed into
 *              visual order, Latin words and numbers ("Ali Khan", "12,500.00") stay readable,
 *              brackets are mirrored.
 * shapeHtml(): applies shape() to text nodes only (never tags, attributes, <style>, <script>).
 *
 * Limitation: visual order is computed per text node, so a long Arabic paragraph that dompdf
 * wraps onto several lines would list its lines bottom-up. PDF table cells are short labels and
 * values, which is what the folio, statements and receipts mostly are. Pair with a font that has the glyphs (DejaVu Sans).
 * Ported 2026-10-27 (I18N-01) from the founder's hrmzy product, where it shipped first; used by PdfService for ar/fa.
 */
class ArabicPdfShaper
{
    /** code point => [isolated, final, initial, medial]; two entries = right-joining only. */
    private const FORMS = [
        0x0621 => [0xFE80],
        0x0622 => [0xFE81, 0xFE82], 0x0623 => [0xFE83, 0xFE84], 0x0624 => [0xFE85, 0xFE86],
        0x0625 => [0xFE87, 0xFE88], 0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C], 0x0627 => [0xFE8D, 0xFE8E],
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92], 0x0629 => [0xFE93, 0xFE94],
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98], 0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0], 0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8], 0x062F => [0xFEA9, 0xFEAA], 0x0630 => [0xFEAB, 0xFEAC],
        0x0631 => [0xFEAD, 0xFEAE], 0x0632 => [0xFEAF, 0xFEB0],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4], 0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC], 0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4], 0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC], 0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4], 0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC], 0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4], 0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC], 0x0648 => [0xFEED, 0xFEEE], 0x0649 => [0xFEEF, 0xFEF0],
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
        // Persian
        0x067E => [0xFB56, 0xFB57, 0xFB58, 0xFB59], 0x0686 => [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D],
        0x0698 => [0xFB8A, 0xFB8B], 0x06A9 => [0xFB8E, 0xFB8F, 0xFB90, 0xFB91],
        0x06AF => [0xFB92, 0xFB93, 0xFB94, 0xFB95], 0x06CC => [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF],
    ];

    /** lam + (alef variant) => [isolated, final] ligature. */
    private const LAM_ALEF = [0x0622 => [0xFEF5, 0xFEF6], 0x0623 => [0xFEF7, 0xFEF8], 0x0625 => [0xFEF9, 0xFEFA], 0x0627 => [0xFEFB, 0xFEFC]];

    private const TATWEEL = 0x0640;

    private const MIRROR = ['(' => ')', ')' => '(', '[' => ']', ']' => '[', '{' => '}', '}' => '{', '<' => '>', '>' => '<', '«' => '»', '»' => '«'];

    public static function shape(string $text): string
    {
        if ($text === '' || ! preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        return self::reorder(self::join(mb_str_split($text, 1, 'UTF-8')));
    }

    public static function shapeHtml(string $html): string
    {
        return preg_replace_callback(
            '#(<(script|style)\b.*?</\2>)|(<[^>]*>)|([^<]+)#is',
            function ($m) {
                if (! empty($m[1]) || ! empty($m[3])) {
                    return $m[0];                       // script/style block or a tag: untouched
                }
                $text = html_entity_decode($m[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return htmlspecialchars(self::shape($text), ENT_NOQUOTES, 'UTF-8', false);
            },
            $html
        );
    }

    /** Contextual forms in logical order. */
    private static function join(array $chars): array
    {
        $cp = array_map(fn ($c) => mb_ord($c, 'UTF-8'), $chars);
        $n = count($cp);
        $out = [];
        $isMark = fn ($c) => ($c >= 0x064B && $c <= 0x065F) || $c === 0x0670;
        $joinsForward = fn ($c) => $c === self::TATWEEL || (isset(self::FORMS[$c]) && count(self::FORMS[$c]) === 4);
        $joinsBack = fn ($c) => $c === self::TATWEEL || (isset(self::FORMS[$c]) && count(self::FORMS[$c]) >= 2);

        for ($i = 0; $i < $n; $i++) {
            $c = $cp[$i];
            if (! isset(self::FORMS[$c])) {
                $out[] = $chars[$i];

                continue;
            }
            // Neighbours, skipping diacritics.
            $p = $i - 1;
            while ($p >= 0 && $isMark($cp[$p])) {
                $p--;
            }
            $x = $i + 1;
            while ($x < $n && $isMark($cp[$x])) {
                $x++;
            }
            $prev = $p >= 0 && $joinsForward($cp[$p]) && ! self::endsLigature($cp, $p);
            $next = $x < $n && $joinsBack($cp[$x]);

            if ($c === 0x0644 && $x < $n && isset(self::LAM_ALEF[$cp[$x]])) {
                $out[] = mb_chr(self::LAM_ALEF[$cp[$x]][$prev ? 1 : 0], 'UTF-8');
                for ($k = $i + 1; $k < $x; $k++) {
                    $out[] = $chars[$k];
                }   // keep marks between
                $i = $x;

                continue;
            }

            $f = self::FORMS[$c];
            $form = count($f) === 4
                ? ($prev && $next ? $f[3] : ($prev ? $f[1] : ($next ? $f[2] : $f[0])))
                : ($prev && count($f) > 1 ? $f[1] : $f[0]);
            $out[] = mb_chr($form, 'UTF-8');
        }

        return $out;
    }

    /** True when $cp[$i] is an alef consumed by a preceding lam (the ligature never joins forward). */
    private static function endsLigature(array $cp, int $i): bool
    {
        return isset(self::LAM_ALEF[$cp[$i]]) && $i > 0 && $cp[$i - 1] === 0x0644;
    }

    /** Simplified UBA for an RTL paragraph: returns visual order. */
    private static function reorder(array $chars): string
    {
        $type = [];
        foreach ($chars as $i => $ch) {
            $c = mb_ord($ch, 'UTF-8');
            $type[$i] = ($c >= 0x0590 && $c <= 0x08FF) || ($c >= 0xFB1D && $c <= 0xFDFF) || ($c >= 0xFE70 && $c <= 0xFEFF)
                ? 'R'
                : (preg_match('/[\p{L}\p{N}]/u', $ch) ? 'L' : 'N');
        }
        // Neutrals take L only when both strong neighbours are L; otherwise the paragraph direction (R).
        $n = count($chars);
        for ($i = 0; $i < $n; $i++) {
            if ($type[$i] !== 'N') {
                continue;
            }
            $j = $i;
            while ($j < $n && $type[$j] === 'N') {
                $j++;
            }
            $before = $i > 0 ? $type[$i - 1] : 'R';
            $after = $j < $n ? $type[$j] : 'R';
            $t = ($before === 'L' && $after === 'L') ? 'L' : 'R';
            for ($k = $i; $k < $j; $k++) {
                $type[$k] = $t;
            }
            $i = $j - 1;
        }
        // Runs, reversed; R runs also reversed internally with mirrored brackets.
        $runs = [];
        foreach ($chars as $i => $ch) {
            if ($runs && end($runs)[0] === $type[$i]) {
                $runs[count($runs) - 1][1][] = $ch;
            } else {
                $runs[] = [$type[$i], [$ch]];
            }
        }
        $out = '';
        foreach (array_reverse($runs) as [$t, $cs]) {
            $out .= $t === 'L' ? implode('', $cs) : implode('', array_map(fn ($c) => self::MIRROR[$c] ?? $c, array_reverse($cs)));
        }

        return $out;
    }
}
