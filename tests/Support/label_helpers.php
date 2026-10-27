<?php

/**
 * THE ONE ACCESSIBLE-NAME CENSUS (§N.8f) — discovery, beside `heading_helpers` and `dialog_helpers`.
 *
 * ⚠️⚠️ IT RECOGNISES ALL FOUR VALID ASSOCIATIONS, and the fourth is why earlier counts were wrong:
 * `for`/`id` · `aria-label` · `aria-labelledby` · **a WRAPPING `<label>`**, which associates correctly with
 * neither `for` nor `aria-label`. **155 controls in this codebase are named that way.** A scan looking only
 * for the first three would report every one of them as broken.
 *
 * ⚠️ WHAT IT CANNOT PROVE: what a screen reader announces. It proves an association EXISTS by one of the
 * four means the accessible-name computation actually uses — not that the resulting name is a good one.
 *
 * @return array{excluded: list<array>, named: list<array>, unnamed: list<array>}
 */
function vyControlCensus(): array
{
    $excluded = $named = $unnamed = [];

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

    foreach ($rii as $file) {
        if (! $file->isFile() || ! str_ends_with((string) $file->getFilename(), '.blade.php')) {
            continue;
        }

        // ⚠️ Comments, style and script out first — the needle-in-own-comment trap, ten sightings.
        $s = (string) preg_replace([
            '/\{\{--.*?--\}\}/s', '/<!--.*?-->/s',
            '/<style\b[^>]*>.*?<\/style>/is', '/<script\b[^>]*>.*?<\/script>/is',
        ], '', (string) file_get_contents($file->getRealPath()));

        // ⚠️ I18N-01 — a `>` INSIDE a Blade echo (`aria-label="{{ __('k', ['n' => $n]) }}"`) is not the end of the tag; the
        //    pattern below stops at the first `>`, so it cut the tag short and lost every attribute after it. Neutralised
        //    in echoes only — ids and labels keep their own text, so no two controls start to look alike.
        $s = (string) preg_replace_callback('/\{\{.*?\}\}|\{!!.*?!!\}/s', fn (array $e): string => str_replace('>', '›', $e[0]), $s);

        preg_match_all('/\bid="([^"]+)"/', $s, $idm);
        $ids = array_flip($idm[1]);
        $rel = str_replace(resource_path('views').'/', '', (string) $file->getRealPath());

        preg_match_all('/<(input|select|textarea)\b([^>]*)>/i', $s, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($m as $c) {
            $tag = strtolower($c[1][0]);
            $a = $c[2][0];
            $get = fn (string $k): ?string => preg_match('/'.$k.'="([^"]*)"/i', $a, $x) ? $x[1] : null;
            $type = strtolower($get('type') ?? ($tag === 'input' ? 'text' : ''));

            // ── EXCLUSIONS, each with its reason. Never a widened pattern.
            $why = match (true) {
                in_array($type, ['hidden', 'submit', 'button', 'image', 'reset'], true) => "type={$type} — submits no user value / is named by its own text",
                str_contains($a, 'aria-hidden="true"') => 'aria-hidden — the documented password-manager decoy; naming it would defeat it',
                (bool) preg_match('/tabindex="-1"/', $a) && (bool) preg_match('/honeypot|hp_|_gotcha/i', $a) => 'honeypot — a named field is one a bot can read',
                default => null,
            };

            if ($why !== null) {
                $excluded[] = ['file' => $rel, 'why' => $why];

                continue;
            }

            $id = $get('id');
            $how = match (true) {
                $get('aria-label') !== null => 'aria-label',
                $get('aria-labelledby') !== null => isset($ids[$get('aria-labelledby')]) ? 'aria-labelledby' : 'aria-labelledby ⚠️DANGLING',
                $id !== null && preg_match('/<label\b[^>]*\bfor="'.preg_quote($id, '/').'"/', $s) === 1 => 'label[for]',
                // ⚠️ WRAPPING LABEL — an unclosed <label> before this control.
                preg_match_all('/<label\b/i', substr($s, 0, $c[0][1])) > preg_match_all('/<\/label>/i', substr($s, 0, $c[0][1])) => 'wrapping label',
                default => null,
            };

            if ($how !== null && ! str_contains($how, 'DANGLING')) {
                $named[] = ['file' => $rel, 'how' => $how];
            } else {
                $unnamed[] = ['file' => $rel, 'tag' => $tag, 'type' => $type,
                    'name' => $get('name'), 'placeholder' => $get('placeholder'), 'how' => $how];
            }
        }
    }

    return ['excluded' => $excluded, 'named' => $named, 'unnamed' => $unnamed];
}
