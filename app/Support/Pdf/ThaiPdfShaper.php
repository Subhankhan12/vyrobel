<?php

declare(strict_types=1);

namespace App\Support\Pdf;

/**
 * Prepares Thai text for dompdf, which has no OpenType layout: without it tone marks that sit on top of an upper
 * vowel (ที่ ซึ่ง สิ่ง น้ำ) land on the same spot as the vowel and vanish, and marks above tall consonants (ป ฝ ฟ)
 * collide with the ascender.
 *
 * The bundled Thai font (THSarabunNew, resources/fonts/pdf) carries the Windows Thai "PUA" variants U+F700–F71D that
 * were made for exactly this situation. shape() substitutes them using the classic rules:
 *
 *   tone mark   on a plain consonant              -> low        (F70A–F70E)
 *               on a tall consonant ป ฝ ฟ         -> low, left  (F705–F709)
 *               above an upper vowel              -> default (high) glyph
 *               above an upper vowel, tall base   -> high, left (F713–F717)
 *   upper vowel on a tall consonant               -> shifted left (ิ ี ึ ื = F701–F704, ั = F710, ํ = F711, ็ = F712)
 *   ญ ฐ        before a lower vowel               -> the variant without the tail (F70F, F700)
 *   ฎ ฏ        before a lower vowel               -> lowered vowel (ุ ู ฺ = F718–F71A)
 *
 * shapeHtml() applies shape() to text nodes only (never tags, attributes, <style>, <script>). Text without Thai
 * letters is returned untouched. Ported 2026-10-27 (I18N-01) from hrmzy; used by PdfService for th.
 */
class ThaiPdfShaper
{
    private const TALL = [0x0E1B, 0x0E1D, 0x0E1F];                       // ป ฝ ฟ

    private const UPPER_VOWELS = [0x0E31, 0x0E34, 0x0E35, 0x0E36, 0x0E37, 0x0E47, 0x0E4D]; // ั ิ ี ึ ื ็ ํ

    private const LOWER_VOWELS = [0x0E38, 0x0E39, 0x0E3A];               // ุ ู ฺ

    private const TONES = [0x0E48, 0x0E49, 0x0E4A, 0x0E4B, 0x0E4C];      // ่ ้ ๊ ๋ ์

    private const SARA_AM = 0x0E33;

    /** upper vowel on a tall consonant => shifted-left variant. */
    private const UPPER_LEFT = [
        0x0E34 => 0xF701, 0x0E35 => 0xF702, 0x0E36 => 0xF703, 0x0E37 => 0xF704,
        0x0E31 => 0xF710, 0x0E4D => 0xF711, 0x0E47 => 0xF712,
    ];

    /** lower vowel under ฎ ฏ => lowered variant. */
    private const LOWER_LOW = [0x0E38 => 0xF718, 0x0E39 => 0xF719, 0x0E3A => 0xF71A];

    public static function shape(string $text): string
    {
        if ($text === '' || ! preg_match('/[\x{0E31}-\x{0E4E}]/u', $text)) {
            return $text;
        }

        $cp = array_map(fn (string $c) => mb_ord($c, 'UTF-8'), mb_str_split($text, 1, 'UTF-8'));
        $out = $cp;
        $n = count($cp);

        for ($i = 0; $i < $n; $i++) {
            $c = $cp[$i];

            // Lower vowels: tail removal (ญ ฐ) or lowered vowel (ฎ ฏ) on the preceding consonant.
            if (in_array($c, self::LOWER_VOWELS, true) && $i > 0) {
                $base = $i - 1;
                if ($cp[$base] === 0x0E0D) {
                    $out[$base] = 0xF70F;
                } elseif ($cp[$base] === 0x0E10) {
                    $out[$base] = 0xF700;
                } elseif ($cp[$base] === 0x0E0E || $cp[$base] === 0x0E0F) {
                    $out[$i] = self::LOWER_LOW[$c];
                }

                continue;
            }

            $isUpper = in_array($c, self::UPPER_VOWELS, true);
            $isTone = in_array($c, self::TONES, true);
            if (! $isUpper && ! $isTone) {
                continue;
            }

            // The consonant this mark belongs to: skip back over an upper vowel / lower vowel.
            $b = $i - 1;
            while ($b >= 0 && (in_array($cp[$b], self::UPPER_VOWELS, true) || in_array($cp[$b], self::LOWER_VOWELS, true) || in_array($cp[$b], self::TONES, true))) {
                $b--;
            }
            $tall = $b >= 0 && in_array($cp[$b], self::TALL, true);

            if ($isUpper) {
                if ($tall && $i - 1 === $b) {
                    $out[$i] = self::UPPER_LEFT[$c];
                }

                continue;
            }

            // Tone mark: stacked on an upper vowel (or on sara am, which contains a nikhahit)?
            $stacked = $i > 0 && in_array($cp[$i - 1], self::UPPER_VOWELS, true);
            $stacked = $stacked || (($cp[$i + 1] ?? null) === self::SARA_AM);
            $k = $c - 0x0E48;                                              // 0..4

            if ($stacked) {
                $out[$i] = $tall ? 0xF713 + $k : $c;                       // high-left : default (high)
            } else {
                $out[$i] = $tall ? 0xF705 + $k : 0xF70A + $k;              // low-left : low
            }
        }

        return implode('', array_map(fn (int $c) => mb_chr($c, 'UTF-8'), $out));
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
        ) ?? $html;
    }
}
