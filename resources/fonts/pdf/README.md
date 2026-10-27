# Bundled PDF fonts (I18N-01)

dompdf has no per-glyph font fallback, so every font here carries Latin, digits and punctuation together with its script.
`App\Support\Pdf\PdfFonts` registers them into dompdf's font directory the first time a Chinese, Japanese, Korean or Thai PDF is
built. Everything else prints in DejaVu Sans (bundled with dompdf), which covers Latin, Cyrillic, Greek, Georgian and — after
`App\Support\Pdf\ArabicPdfShaper` — Arabic and Persian.

Ported 2026-10-27 from the founder's hrmzy product (the same builds), with every name record renamed "Vyrobel …" (fontTools;
modified versions may not keep the original names, which these never did).

| File | Contents | Licence |
|---|---|---|
| VyrobelCJKSans-Regular.ttf, VyrobelCJKSans-Bold.ttf | DejaVu Sans / Bold (Latin, Cyrillic, Greek, Vietnamese …) merged with Droid Sans Fallback (Han, kana); hinting and OpenType layout removed, Droid rescaled to 2048 units/em. The bold file pairs DejaVu Bold Latin with regular-weight CJK. | DejaVu fonts licence (Bitstream Vera + public-domain changes) and Apache License 2.0 (Droid Sans Fallback, © Google) |
| VyrobelHangulSans-Regular.ttf, VyrobelHangulSans-Bold.ttf | Nanum Gothic / Bold (Hangul, Latin, Cyrillic, Greek) plus DejaVu Sans glyphs for what Nanum lacks (U+2212, Vietnamese, symbols), DejaVu rescaled to 1000 units/em. | SIL Open Font License 1.1 (Nanum Gothic, © NAVER Corp.) and the DejaVu fonts licence |
| VyrobelThaiSans-Regular/Bold/Italic/BoldItalic.ttf | TH Sarabun New (Thai + Latin, with the Windows Thai PUA variants U+F700–F71D that `ThaiPdfShaper` substitutes) plus DejaVu Sans glyphs for what it lacks. | SIL Open Font License 1.1 (TH Sarabun New) and the DejaVu fonts licence |

Hindi has no font or shaping here: dompdf cannot shape Devanagari, so Hindi PDFs print their wording in English
(`App\Support\I18n\Locales` font group `english`).
