<?php

declare(strict_types=1);

/**
 * THE KEYBOARD-REACHABILITY CONTRACT — the ONE definition of "an element with a click handler that no
 * keyboard can activate" (docs/KEYBOARD-SURFACES.md).
 *
 * ⚠️ THE CLASS, NOT THE TWO INSTANCES. A maintenance work order and the owner action centre were each
 * unreachable by keyboard, and the second holds rate proposals and expense approvals — money decisions
 * behind a control that only answered a mouse. A guard naming those two would catch nothing next time.
 *
 * ⚠️ WHAT IS LEGITIMATE, AND WHY THE ALLOW-LIST CARRIES REASONS. Not every click handler on a `<div>` is a
 * defect. A BACKDROP DISMISSAL (`@click.self`, `@click.outside`) is a mouse shortcut for something Escape
 * already does, and demanding a keyboard path for it would add a focus stop that does nothing. A handler on
 * an element that is ALREADY interactive (`<button>`, `<a href>`, `<summary>`, a form control) needs
 * nothing. Everything else must carry its own keyboard path — a `keydown`/`keyup` binding, `tabindex` with
 * `role`, or a real control inside it that does the same job.
 *
 * ⚠️ IT MUST READ WHAT RUNS, NOT WHAT IS WRITTEN ABOUT IT. `<style>`, `<script>` and both comment dialects
 * are stripped before the scan — the trap of a source guard matching its own explanatory prose has fired
 * repeatedly in this project, and this file's own docblock says `@click` several times.
 *
 * ⚠️ AND IT DOES NOT PARSE HTML WITH ONE REGEX. Blade attribute values legitimately contain `>` and `=>`
 * (`{{ $a > $b }}`, `fn () => …`), so a `<tag[^>]*>` match ends in the wrong place. The walker below finds
 * the handler first, steps BACK to the tag that owns it, then forward through the attribute list tracking
 * quote state to find the real tag end.
 */
const VY_KEYBOARD_INTERACTIVE_TAGS = ['button', 'a', 'input', 'select', 'textarea', 'summary', 'label', 'details', 'option'];

/** Modifiers that make a click handler a dismissal/pass-through rather than a control. */
const VY_KEYBOARD_DISMISSAL_MODIFIERS = ['.self', '.outside', '.away'];

/**
 * Every click handler in the views that no keyboard can reach.
 *
 * @return list<array{file: string, line: int, tag: string, handler: string, snippet: string}>
 */
function vyUnreachableClickHandlers(): array
{
    $root = __DIR__.'/../../resources/views';
    $base = realpath(__DIR__.'/../../').'/';
    $found = [];

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $path = str_replace($base, '', (string) $file->getRealPath());
        $raw = (string) file_get_contents((string) $file->getRealPath());

        // ⚠️ STRIP FIRST — and replace with newlines so reported line numbers stay true.
        $code = vyKeyboardStrip($raw);

        foreach (vyKeyboardClickTags($code) as $tag) {
            if (in_array($tag['tag'], VY_KEYBOARD_INTERACTIVE_TAGS, true)) {
                continue;   // already operable by keyboard as itself
            }

            if ($tag['dismissal']) {
                continue;   // a backdrop/outside dismissal — Escape is the keyboard path
            }

            if ($tag['hasKeyboardPath']) {
                continue;   // carries its own keydown/keyup, or role+tabindex
            }

            $found[] = [
                'file' => $path,
                'line' => $tag['line'],
                'tag' => $tag['tag'],
                'handler' => $tag['handler'],
                'snippet' => $tag['snippet'],
            ];
        }
    }

    return $found;
}

/** Remove `<style>`, `<script>`, HTML comments and Blade comments, preserving line numbering. */
function vyKeyboardStrip(string $source): string
{
    $blank = static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n"));

    foreach ([
        '#<style\b[^>]*>.*?</style>#is',
        '#<script\b[^>]*>.*?</script>#is',
        '#<!--.*?-->#s',
        '#\{\{--.*?--\}\}#s',
        '#/\*.*?\*/#s',
    ] as $pattern) {
        $source = preg_replace_callback($pattern, $blank, $source) ?? $source;
    }

    return $source;
}

/**
 * Find every tag carrying a click binding.
 *
 * ⚠️⚠️ A FORWARD TOKENISER, AND THE FIRST ATTEMPT WAS WRONG IN A WAY WORTH KEEPING WRITTEN DOWN. It found
 * each `@click` and walked BACKWARD to the `<` that owns it, stopping at any `>`. That breaks on
 * `<div title="{{ $a > $b ? … }}" @click="go()">`: the `>` inside the quoted Blade expression sits between
 * the handler and its own tag, so the walk gave up and the element was never scanned — a guard silently
 * blind to exactly the markup this codebase writes most. Walking FORWARD from each `<` with quote state
 * tracked is the only version that cannot be fooled that way, and there is a probe for it in the test.
 *
 * @return list<array{tag: string, line: int, handler: string, dismissal: bool, hasKeyboardPath: bool, snippet: string}>
 */
function vyKeyboardClickTags(string $code): array
{
    $out = [];
    $length = strlen($code);
    $i = 0;

    while ($i < $length) {
        $lt = strpos($code, '<', $i);

        if ($lt === false) {
            break;
        }

        if ($lt + 1 >= $length || preg_match('/[a-zA-Z]/', $code[$lt + 1]) !== 1) {
            $i = $lt + 1;

            continue;
        }

        // Forward to the real tag end, respecting quoted attribute values.
        $quote = null;
        $tagEnd = null;

        for ($j = $lt; $j < $length; $j++) {
            $ch = $code[$j];

            if ($quote !== null) {
                if ($ch === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;

                continue;
            }

            if ($ch === '>') {
                $tagEnd = $j;
                break;
            }
        }

        if ($tagEnd === null) {
            break;
        }

        $tagSource = substr($code, $lt, $tagEnd - $lt + 1);
        $i = $tagEnd + 1;

        // ⚠️ READ THE BINDINGS THAT ACTUALLY EXIST ON THIS TAG, and read them properly.
        //
        // ⚠️ A MODIFIER-ONLY BINDING IS NOT A HANDLER. `@click.stop` with NO value does not DO anything —
        //    it stops a click reaching an ancestor, which is how a dialog panel keeps a backdrop from
        //    dismissing it. Three `<form>` elements carry exactly that, and reading them as mouse-only
        //    controls would demand a keyboard path for a thing that performs no action at all.
        // ⚠️ A DISMISSAL MODIFIER (`.self`/`.outside`/`.away`) is a backdrop shortcut, not a control.
        if (! preg_match_all('/(?:@|x-on:)click((?:\.[a-z]+)*)\s*(=)?/i', $tagSource, $bindings, PREG_SET_ORDER)) {
            continue;
        }

        $dismissal = false;
        $hasExpression = false;

        foreach ($bindings as $binding) {
            $modifiers = $binding[1] ?? '';

            foreach (VY_KEYBOARD_DISMISSAL_MODIFIERS as $modifier) {
                if (str_contains($modifiers, $modifier)) {
                    $dismissal = true;
                }
            }

            if (($binding[2] ?? '') === '=') {
                $hasExpression = true;
            }
        }

        if (! $hasExpression) {
            continue;   // modifier-only: performs nothing, so there is nothing to reach
        }

        preg_match('/^<([a-zA-Z][a-zA-Z0-9-]*)/', $tagSource, $nameMatch);

        $out[] = [
            'tag' => strtolower($nameMatch[1] ?? '?'),
            'line' => substr_count(substr($code, 0, $lt), "\n") + 1,
            'handler' => '@click',
            'dismissal' => $dismissal,
            'hasKeyboardPath' => (bool) preg_match('/(@|x-on:)key(down|up|press)/i', $tagSource)
                || (preg_match('/\btabindex\s*=/i', $tagSource) && preg_match('/\brole\s*=/i', $tagSource)),
            'snippet' => trim(preg_replace('/\s+/', ' ', substr($tagSource, 0, 150)) ?? ''),
        ];
    }

    return $out;
}
