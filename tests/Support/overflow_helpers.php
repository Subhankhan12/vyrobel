<?php

declare(strict_types=1);

/**
 * The horizontal-overflow contract — the ONE definition of the defect that made twelve screens scroll
 * sideways on a phone (docs/REMAINING-OVERFLOW.md).
 *
 * ⚠️ WHAT IT GUARDS, AND WHY IT IS A SOURCE RULE AND NOT A MEASUREMENT. Overflow is a RENDERED property:
 * proving a page does not scroll sideways needs a real browser at a real viewport, which the suite has no
 * way to do. What the suite CAN hold is the defect CLASS underneath it — a bare `1fr` grid track.
 *
 * `1fr` is `minmax(auto, 1fr)`, and that `auto` is a MIN-CONTENT FLOOR the track cannot shrink below. It
 * reads as "one flexible fraction" and behaves as "never narrower than my widest word". That single
 * misreading was eight of the twelve screens, and it is the third time it has been found and fixed in this
 * codebase (the marketing footer grid, then `.vy-shell`, then this).
 *
 * ⚠️ THIS GUARD MUST NOT READ ITS OWN NEEDLE. It is the fourth source-scanning guard in this project and
 * the previous three were each caught matching the prose written ABOUT the rule rather than the CSS that
 * runs. So every comment — CSS `/* … *\/` and Blade `{{-- … --}}` alike — is stripped BEFORE the scan, and
 * `tests/` is never scanned at all. Mutate a comment and this stays green; mutate a declaration and it goes
 * red. That is the whole difference between a guard and a decoration.
 */
function vyBareFrTracks(?string $onlyFile = null): array
{
    $roots = [__DIR__.'/../../resources/views'];
    $offenders = [];

    foreach ($roots as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $path = str_replace(realpath(__DIR__.'/../../').'/', '', (string) $file->getRealPath());

            if ($onlyFile !== null && $path !== $onlyFile) {
                continue;
            }

            $source = (string) file_get_contents((string) $file->getRealPath());

            // ⚠️ COMMENTS OUT FIRST — both dialects — so the scan reads only what the browser will.
            $code = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
            $code = preg_replace('#\{\{--.*?--\}\}#s', '', $code) ?? $code;

            // Every `grid-template-columns` declaration, wherever it is written: a stylesheet rule, an
            // inline `style` attribute, or a Blade-interpolated one.
            if (! preg_match_all('/grid-template-columns\s*:\s*([^;"\'\}]+)/i', $code, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[1] as $hit) {
                [$value, $offset] = $hit;

                // A track list is safe when every flexible track carries an explicit floor. Strip the
                // `minmax(...)` groups, then ask whether any bare `fr` survives.
                $withoutMinmax = preg_replace('/minmax\s*\([^)]*\)/i', '', $value) ?? $value;

                if (! preg_match('/(^|[\s,\(])[\d.]*fr\b/i', $withoutMinmax)) {
                    continue;
                }

                $offenders[] = [
                    'file' => $path,
                    'line' => substr_count(substr($code, 0, $offset), "\n") + 1,
                    'value' => trim(preg_replace('/\s+/', ' ', $value) ?? $value),
                ];
            }
        }
    }

    return $offenders;
}
