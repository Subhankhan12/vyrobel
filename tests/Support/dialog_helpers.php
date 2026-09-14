<?php

/**
 * THE ONE DIALOG CENSUS (§N.8f) — discovery, not a hand-kept list.
 *
 * ⚠️⚠️ WHY DISCOVERY. The D2 pass named four dialogs in a constant. By the time this pass ran there were
 * **thirty-seven**, and a hand-kept list had silently stopped describing the codebase: two `portfolio`
 * dialogs carried `role`, a name and Escape but **no `x-trap` and no `tabindex`** — they looked done in
 * source and were not, which is precisely the shape a list cannot catch. A census that walks the views
 * cannot fall behind them.
 *
 * ⚠️ THE CONTRACT APPLIES TO MODAL DIALOGS, NOT TO EVERY OVERLAY. A backdrop that covers the viewport is
 * what makes a thing modal; a dropdown or an inline disclosure is not one, and forcing `x-trap.noscroll`
 * onto it would lock the page behind a panel that was never modal.
 *
 * @return list<array{file:string,show:string,panel:string,backdrop:string}>
 */
function vyDialogCensus(): array
{
    $out = [];

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

    foreach ($rii as $file) {
        if (! $file->isFile() || ! str_ends_with((string) $file->getFilename(), '.blade.php')) {
            continue;
        }

        // ⚠️ BLADE COMMENTS OUT BEFORE ANY SCAN. Several of these dialogs now carry comments that quote the
        //    very attributes being counted; an unstripped scan reads its own documentation.
        $src = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file->getRealPath()));

        if (! preg_match_all('/<div([^>]*)>/', $src, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            continue;
        }

        foreach ($m as $tag) {
            $attrs = $tag[1][0];
            $style = preg_match('/style="([^"]*)"/', $attrs, $sm) ? $sm[1] : '';

            $isBackdrop = str_contains($attrs, 'vy-modal-backdrop')
                || str_contains($attrs, 'vyp-scrim')
                || str_contains($attrs, 'vy-fdm-overlay')
                || (str_contains($style, 'position: fixed') && str_contains($style, 'z-index') && str_contains($style, 'inset'));

            if (! $isBackdrop || ! preg_match('/x-show="([^"]*)"/', $attrs, $show)) {
                continue;
            }

            $after = substr($src, $tag[0][1] + strlen($tag[0][0]));
            $panel = preg_match('/<div([^>]*)>/', $after, $pm) ? $pm[1] : '';

            $out[] = [
                'file' => str_replace(resource_path('views').'/', '', (string) $file->getRealPath()),
                'show' => $show[1],
                'backdrop' => $attrs,
                'panel' => $panel,
            ];
        }
    }

    usort($out, fn (array $a, array $b): int => [$a['file'], $a['show']] <=> [$b['file'], $b['show']]);

    return $out;
}
