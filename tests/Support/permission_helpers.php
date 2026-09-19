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

if (! defined('VY_UNREAD_PERMISSIONS')) {
    /**
     * ⚠️ EVERY ENTRY CARRIES A REASON AND A RULING, never a widened pattern. `remove` / `wire` / `reclassify`
     * are recorded as the OPTION each needs, not as something this pass performed.
     */
    define('VY_UNREAD_PERMISSIONS', [
        // ── The capability IS enforced, under a DIFFERENT permission ────────────────────────────────────
        'taxes.manage' => 'Tax editing exists at POST /app/settings/tax-fees but is gated by `properties.manage`. ⚠️ MEASURED: a Hotel Manager holding `taxes.manage` is refused 403. Needs a ruling — wire the route to this permission, or drop it.',
        'fees.manage' => 'Same as taxes.manage — /app/settings/fees/add is gated by `properties.manage`.',
        'restrictions.manage' => 'Stay restrictions are edited through the rates surface, gated by `rates.manage`.',
        'availability.manage' => 'Availability is written only through AssignmentService, reached via `rates.manage`/`reservations.manage`.',
        'availability.view' => 'Availability is read through the calendar and rack, gated by `rates.view`/`reservations.view`.',
        'housekeeping.view' => 'The housekeeping board (GET /app/housekeeping) carries no gate at all; every WRITE is gated by `housekeeping.manage`. See the ungated-screens section of the doc.',

        // ── The capability does not exist as an operator surface ────────────────────────────────────────
        'invoices.view' => 'No invoice surface exists — invoice numbering is an explicit "Soon" in Settings.',
        'invoices.issue' => 'Same — nothing issues an invoice.',
        'amenities.manage' => 'No amenity management surface exists.',
        'corporate_accounts.manage' => 'No corporate-account surface exists; `reservations.source` carries `corporate` but nothing manages accounts.',
        'guest_documents.manage' => 'Guest ID documents are handled by the guest realm and the retention purge, never by an operator permission.',
        'api.manage' => 'The OAuth/API surface is platform-admin only (`oauth:register-client`), deliberately not self-serve.',
        'organizations.manage' => 'Organisations are created only by TenantProvisioningService; the platform admin realm has its own guard.',
        'addons.manage' => 'Add-ons are a GUEST request flow (POST /guest/reservation/{r}/manage/add-ons); no operator surface consumes this.',

        // ── Enforced by a ROLE allow-list rather than a permission ──────────────────────────────────────
        'owner_portal.view' => 'The owner realm is gated by role (`EnsureBackOfficeAccess` + the External Owner role), not by this permission. Held as the marker of the owner role.',
        'guest_portal.view' => 'The guest realm has its own guard and its own auth guard entirely.',
        'payments.view' => 'Payment reads ride on the folio surfaces, gated by `folios.view`; the only /payments routes are guest-realm.',
    ]);
}

/**
 * ⚠️⚠️ F-44 — EVERY CURRENCY FALLBACK KEYED ON A POSSIBLY-EMPTY COLLECTION (FIX 46).
 *
 * ⚠️⚠️ WHY THIS SHAPE AND NOT "no hardcoded currency anywhere". 🔬 `app/` holds ~39 `?? 'XXX'` literals, and
 * FIX 22 proved FORTY-FIVE of that family UNREACHABLE by reading the schema: every `currency` column is NOT
 * NULL, so the branch cannot fire. That evidence was correct — **and blind to exactly one case**. A fallback
 * written as `$collection->first()->currency ?? 'EUR'` is keyed on an EMPTY SET, not a null column, and no
 * schema can see an empty set. That blind spot IS F-44, and it is the only thing this guard asks about.
 * ⚠️ Forbidding all thirty-nine would need a thirty-nine-entry allow-list, which is how a guard dies quietly.
 *
 * ⚠️⚠️ IT IS VARIABLE-AWARE, BECAUSE A SINGLE-LINE REGEX FINDS ONLY SEVEN OF THE NINE. `OwnerReports` and
 * `OwnerInsights` assign `$first = $units->first();` on one line and coalesce on the next — a guard that
 * missed two of the very sites it exists for would be decorative. Any variable assigned from `->first()`
 * inside a file is tracked, and a later coalesce against it counts.
 *
 * ⚠️ IT REUSES {@see vyPermissionStrip()} — comments, heredocs, `<style>` and `<script>` gone before the scan.
 * A THIRD SCANNER WOULD BE THE DEFECT FIX 42 SPENT A PASS REMOVING, committed while closing a recurrence.
 *
 * ⚠️ ROOTS ARE NAMED: `app/` only. `tests/` and `docs/` are NEVER read, so this guard cannot match its own
 * description — asserted in the test, not claimed in a comment.
 *
 * @return list<string> "path:line  code" for each offending fallback
 */
function vyCurrencyEmptyCollectionFallbacks(): array
{
    $root = __DIR__.'/../../app';
    $base = realpath(__DIR__.'/../../').'/';
    $out = [];

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
            continue;
        }

        $path = str_replace($base, '', (string) $file->getRealPath());
        $source = vyPermissionStrip((string) file_get_contents((string) $file->getRealPath()), false);
        $lines = explode("\n", $source);

        foreach ($lines as $i => $line) {
            if (! preg_match("/\?\?\s*'[A-Z]{3}'/", $line)) {
                continue;
            }

            // ⚠⚠ SAME EXPRESSION, or assigned from ->first() WITHIN A SHORT BACKWARD WINDOW.
            //    ⚠ File-wide tracking was tried first and OVER-MATCHED: eight sites where a `$property`
            //    in one method shares its name with a `$properties->first()` in another. A guard that is red
            //    on arrival needs an allow-list to survive, and an allow-list is how a guard dies quietly.
            //    ⚠ The window is SIX lines because the two multi-line cases this guard exists for
            //    (`OwnerReports`, `OwnerInsights`) assign `$first = $units->first();` two lines above.
            $keyedOnFirst = str_contains($line, '->first(');

            if (! $keyedOnFirst) {
                for ($back = 1; $back <= 6 && ($i - $back) >= 0; $back++) {
                    $prev = $lines[$i - $back];

                    if (! preg_match('/(\$[A-Za-z_]\w*)\s*=\s*[^;]*->first\s*\(/', $prev, $m)) {
                        continue;
                    }

                    if (preg_match('/'.preg_quote($m[1], '/').'\s*\??->/', $line)) {
                        $keyedOnFirst = true;
                        break;
                    }
                }
            }

            if ($keyedOnFirst) {
                $out[] = $path.':'.($i + 1).'  '.trim($line);
            }
        }
    }

    sort($out);

    return $out;
}
