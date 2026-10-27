<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Support\I18n\Locales;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;

/**
 * What a PDF needs for its language's script (I18N-01) — read by PdfService, the one renderer every PDF goes through.
 *
 * Every Vyrobel PDF view sets DejaVu Sans (bundled with dompdf: Latin, Cyrillic, Greek, Georgian, Arabic glyphs). That is right
 * for most languages; the rest, by `Locales::pdfGroup()`:
 *  - `cjk` / `hangul` / `thai`: dompdf picks ONE font per text run and has no per-glyph fallback, so DejaVu prints boxes —
 *    the family is swapped for a bundled font that carries the script AND Latin (resources/fonts/pdf, registered on first use);
 *  - Arabic and Persian: dompdf neither joins letters nor lays out right-to-left — ArabicPdfShaper gives contextual forms in
 *    visual order, and the page is aligned to the right;
 *  - Thai: ThaiPdfShaper places the tone marks dompdf would stack on top of the vowels;
 *  - Georgian: CSS uppercase turns it into Mtavruli, which no bundled font has — capitals are switched off;
 *  - `english` (Hindi): dompdf cannot shape Devanagari, so PdfService renders those PDFs' wording in English.
 *
 * Ported from the founder's hrmzy product (UnicodeFontHtml, its fonts and shapers), fitted to Vyrobel's single renderer.
 */
final class PdfFonts
{
    /** pdf group => [dompdf family, [style => file in resources/fonts/pdf]] */
    private const BUNDLED = [
        'cjk' => ['vyrobelcjksans', ['normal' => 'VyrobelCJKSans-Regular.ttf', 'bold' => 'VyrobelCJKSans-Bold.ttf']],
        'hangul' => ['vyrobelhangulsans', ['normal' => 'VyrobelHangulSans-Regular.ttf', 'bold' => 'VyrobelHangulSans-Bold.ttf']],
        'thai' => ['vyrobelthaisans', [
            'normal' => 'VyrobelThaiSans-Regular.ttf', 'bold' => 'VyrobelThaiSans-Bold.ttf',
            'italic' => 'VyrobelThaiSans-Italic.ttf', 'bold_italic' => 'VyrobelThaiSans-BoldItalic.ttf',
        ]],
    ];

    /** The language a document is RENDERED in: Hindi PDFs print English wording (no Devanagari shaping in dompdf). */
    public static function renderLocale(string $locale): string
    {
        return Locales::pdfGroup($locale) === 'english' ? Locales::SOURCE : $locale;
    }

    /** The font family the page footer (and anything not styled) is drawn in. */
    public static function family(string $locale): string
    {
        return self::BUNDLED[Locales::pdfGroup($locale)][0] ?? 'DejaVu Sans';
    }

    /** Rewrite the rendered HTML for the document's script. English and every DejaVu language pass through unchanged. */
    public static function html(string $html, string $locale): string
    {
        $script = Locales::script($locale);
        $group = Locales::pdfGroup($locale);
        $extra = '';

        if ($script === 'Arab') {
            $html = ArabicPdfShaper::shapeHtml($html);
            $extra .= 'body{text-align:right}td,th{text-align:right}';
        }
        if ($script === 'Thai') {
            $html = ThaiPdfShaper::shapeHtml($html);
        }
        if ($script === 'Geor') {
            $extra .= '*{text-transform:none !important}';
        }
        if (isset(self::BUNDLED[$group])) {
            $family = self::BUNDLED[$group][0];
            $html = preg_replace("/'?DejaVu Sans'?/i", $family, $html) ?? $html;
            $extra .= 'html,body{font-family:'.$family.'}';
        }
        if ($extra === '') {
            return $html;
        }

        $style = '<style>'.$extra.'</style>';
        $with = preg_replace('/<\/head>/i', $style.'</head>', $html, 1, $count);

        return ($with !== null && $count > 0) ? $with : $style.$html;
    }

    /**
     * Make sure the document's bundled font is installed in dompdf's font directory (copied once; later calls only look
     * the family up). A failure is reported and the PDF falls back to DejaVu — boxes for that script, never an error page.
     */
    public static function prepare(Dompdf $dompdf, string $locale): void
    {
        $spec = self::BUNDLED[Locales::pdfGroup($locale)] ?? null;
        if ($spec === null) {
            return;
        }
        [$family, $files] = $spec;
        // the bundled fonts weigh up to 5 MB: embed only the glyphs a document uses
        $dompdf->getOptions()->setIsFontSubsettingEnabled(true);

        try {
            $metrics = $dompdf->getFontMetrics();
            if (self::isInstalled($metrics, $family)) {
                return;
            }
            $dir = realpath(resource_path('fonts/pdf')) ?: resource_path('fonts/pdf');
            $options = $dompdf->getOptions();
            $chroot = $options->getChroot();
            $options->setChroot(array_merge($chroot, [$dir]));
            try {
                foreach ($files as $style => $file) {
                    if (! is_file($dir.'/'.$file) || ! $metrics->registerFont([
                        'family' => $family,
                        'style' => str_contains($style, 'italic') ? 'italic' : 'normal',
                        'weight' => str_starts_with($style, 'bold') ? 'bold' : 'normal',
                    ], 'file://'.$dir.'/'.$file)) {
                        return;
                    }
                }
            } finally {
                $options->setChroot($chroot);
            }
            // dompdf asks for the exact style and falls back to Times when one is missing: a family without italics uses
            // its upright files
            $entry = array_map('basename', $metrics->getFontFamilies()[$family]);
            $entry['italic'] ??= $entry['normal'];
            $entry['bold_italic'] ??= $entry['bold'];
            $metrics->setFontFamily($family, $entry);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function isInstalled(FontMetrics $metrics, string $family): bool
    {
        $styles = $metrics->getFontFamilies()[$family] ?? null;
        if ($styles === null) {
            return false;
        }
        foreach (['normal', 'bold', 'italic', 'bold_italic'] as $style) {
            if (! isset($styles[$style]) || ! is_file($styles[$style].'.ttf') || ! is_file($styles[$style].'.ufm')) {
                return false;
            }
        }

        return true;
    }
}
