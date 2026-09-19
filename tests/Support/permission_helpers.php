<?php

declare(strict_types=1);

/**
 * THE PERMISSION-ENFORCEMENT CENSUS — the ONE definition of "a permission the matrix offers that nothing
 * reads" (docs/PHANTOM-PERMISSIONS.md).
 *
 * ⚠️⚠️ WHY IT EXISTS. The roles matrix presents a switch per permission. A switch that nothing enforces is
 * not a display defect — it is a FALSE STATEMENT ABOUT ACCESS, made on the screen where access is decided
 * and believed by the person deciding it. Measured: of 51 permissions, 34 are read by a real enforcement
 * site and 17 are read by nothing but the catalogue that declares them.
 *
 * ⚠️ THE CLASS, NOT TODAY'S SEVENTEEN. A guard naming the current set catches nothing when the
 * fifty-second permission is added, which is exactly when it matters.
 *
 * ⚠️ IT MUST READ WHAT RUNS. `<style>`, `<script>`, and both comment dialects are stripped before the scan
 * — this file and its test both name the permissions they discuss in prose, and a guard that matched its
 * own explanation would report perfect enforcement while enforcing nothing. That trap has fired repeatedly
 * in this project.
 *
 * ⚠️ `config/authorization.php` IS DELIBERATELY NOT AN ENFORCEMENT SITE. It is where permissions are
 * DECLARED and GRANTED to roles; counting it would make every permission look enforced by virtue of
 * existing, which is the precise illusion being measured.
 */
function vyPermissionEnforcement(): array
{
    $roots = [__DIR__.'/../../app', __DIR__.'/../../routes', __DIR__.'/../../resources/views'];
    $base = realpath(__DIR__.'/../../').'/';
    $sources = [];

    foreach ($roots as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $path = str_replace($base, '', (string) $file->getRealPath());

            // ⚠️ The declaration file is not an enforcement site — see the class docblock.
            if (str_starts_with($path, 'config/')) {
                continue;
            }

            $sources[$path] = vyPermissionStrip(
                (string) file_get_contents((string) $file->getRealPath()),
                str_ends_with($file->getFilename(), '.blade.php'),
            );
        }
    }

    $out = [];

    foreach (vyPermissionCatalogue() as $permission) {
        $quoted = preg_quote($permission, '/');
        $sites = ['route' => [], 'view' => [], 'code' => []];

        foreach ($sources as $path => $source) {
            if (! str_contains($source, $permission)) {
                continue;
            }

            if (preg_match('/[\'"]can:'.$quoted.'[\'"]/', $source)) {
                $sites['route'][] = $path;
            }

            if (str_ends_with($path, '.blade.php') && preg_match('/@can\w*\(\s*[\'"]'.$quoted.'[\'"]/', $source)) {
                $sites['view'][] = $path;
            }

            if (preg_match('/(->can|->cannot|Gate::(allows|denies|authorize|check)|authorize|hasPermissionTo)\s*\(\s*[\'"]'.$quoted.'[\'"]/', $source)) {
                $sites['code'][] = $path;
            }
        }

        $out[$permission] = $sites;
    }

    return $out;
}

/** The permissions the roles matrix offers — read from the app's own catalogue, never re-typed. */
function vyPermissionCatalogue(): array
{
    $catalogue = [];

    foreach ((array) config('authorization.permissions', []) as $key => $value) {
        $catalogue[] = is_string($value) ? $value : (string) $key;
    }

    sort($catalogue);

    return array_values(array_unique(array_filter($catalogue)));
}

/** Permissions read by a real enforcement site — a route gate, a policy/service check, or a view directive. */
function vyEnforcedPermissions(): array
{
    return array_keys(array_filter(
        vyPermissionEnforcement(),
        fn (array $s): bool => $s['route'] !== [] || $s['code'] !== [] || $s['view'] !== [],
    ));
}

/** ⚠️ Permissions NOTHING reads — the switch flips, the row saves, and no code consults it. */
function vyUnreadPermissions(): array
{
    return array_keys(array_filter(
        vyPermissionEnforcement(),
        fn (array $s): bool => $s['route'] === [] && $s['code'] === [] && $s['view'] === [],
    ));
}

function vyPermissionStrip(string $source, bool $blade): string
{
    $blank = static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n"));

    foreach ([
        '#<style\b[^>]*>.*?</style>#is',
        '#<script\b[^>]*>.*?</script>#is',
        '#<!--.*?-->#s',
        '#\{\{--.*?--\}\}#s',
        '#/\*.*?\*/#s',
        '#^[ \t]*//.*$#m',
        // ⚠️ A HEREDOC IS A STRING, AND COMMENT-STRIPPING DOES NOT TOUCH ONE. Anything call-shaped inside
        //    one would read as an enforcement site while enforcing nothing — the same class as the prose
        //    match above, one syntax over. 🔬 ADDED FIX 41 AND PROVEN INERT: the scanned roots contain
        //    exactly one heredoc (app/Support/Llm/Prompts.php) and it names no permission, so the census
        //    is byte-identical with and without this line. It is here for the fifty-second permission.
        '#<<<([\x27"]?)([A-Za-z_][A-Za-z0-9_]*)\1\R.*?\R\s*\2\b#s',
    ] as $pattern) {
        $source = preg_replace_callback($pattern, $blank, $source) ?? $source;
    }

    return $source;
}
