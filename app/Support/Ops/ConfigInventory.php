<?php

declare(strict_types=1);

namespace App\Support\Ops;

use App\Support\Platform\Health\HealthBoard;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ⚠️⚠️ WHICH CONFIGURATION VALUES ARE EMPTY, AND WHAT DOES EACH EMPTINESS DO?
 *
 * ⚠️ ONE IMPLEMENTATION, TWO CALLERS (ADMIN 01 · Phase 1): `ops/config-inventory.php` (the CLI report) and the
 * console's health board ({@see HealthBoard}). The logic used to live only in the script; a
 * board that re-implemented it would have been a second copy free to drift from the first.
 *
 * ⚠️⚠️ IT ASKS THE FRAMEWORK, IT DOES NOT MODEL IT — values come from Laravel's own resolved config tree
 * (`config()->all()`), never from re-parsing `.env`. 🔬 A detector that reimplements the rule it checks fails in
 * exactly the places the code does.
 *
 * ⚠️⚠️ IT NEVER RETURNS A VALUE — only key paths and verdicts. It is safe to render and safe to paste.
 *
 * THREE VERDICTS, NEVER TWO: SET · EMPTY · UNRESOLVED (a coverage gap, not a pass). And every EMPTY is
 * classified by what its emptiness DOES:
 *   SILENT — the product keeps working and quietly does less. ⚠️⚠️ The dangerous class.
 *   LOUD   — something throws, refuses, or visibly disables itself. Somebody finds out.
 *   INERT  — genuinely optional; empty is a normal, correct state.
 *   UNCLASSIFIED — nobody has decided. Reported separately and never counted as fine.
 */
final class ConfigInventory
{
    /**
     * ⚠️ Keys are Laravel config PATHS, so they survive an env-var rename. A key absent from this map and empty
     * is reported UNCLASSIFIED — deliberately, so the list cannot quietly grow a member nobody thought about.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const CLASSIFY = [
        'mail.default' => ['SILENT', 'a non-delivering mailer (log/array) discards every email; under a log level above debug it is not even recorded'],
        'mail.mailers.postmark.token' => ['SILENT', 'Postmark transport present but unauthenticated — sends fail or vanish depending on the mailer above it'],
        'services.postmark.token' => ['SILENT', 'same token, read from the services config'],
        'mail.from.address' => ['SILENT', 'mail may be built with an empty From and rejected by the provider, after the queue has already reported success'],
        'ops.heartbeat_ping_url' => ['SILENT', 'the external dead-man\'s-switch is NOT ARMED — a skipped cron, a dead worker or a dead host raises nothing at all'],
        'services.google.client_id' => ['LOUD', 'Google sign-in disables itself entirely — no button, no route, no error page. Deliberate and asserted'],
        'services.google.client_secret' => ['LOUD', 'as above; "configured" and "enabled" are the same fact by design'],
        'services.stripe.secret' => ['LOUD', 'a Stripe call throws rather than pretending to charge'],
        'services.stripe.webhook_secret' => ['LOUD', 'signature verification refuses every inbound webhook'],
        'app.key' => ['LOUD', 'the application cannot boot; every encrypted column is unreadable'],
        'database.connections.mysql.password' => ['LOUD', 'the database refuses the connection'],
        'app.debug' => ['INERT', 'false is the correct production value and reads as empty'],
        'services.channex.api_key' => ['INERT', 'the channel integration is account-gated and deliberately not live'],
    ];

    /**
     * Walk the framework's resolved config tree. ⚠️ Returns key PATHS and verdicts only — never a value.
     *
     * @return array{
     *   leaves: int, set: int, empty: int,
     *   buckets: array{SILENT: list<array{0: string, 1: string}>, LOUD: list<array{0: string, 1: string}>, INERT: list<array{0: string, 1: string}>, UNCLASSIFIED: list<array{0: string, 1: string}>},
     *   known: list<string>
     * }
     */
    public static function inventory(): array
    {
        $leaves = [];
        $walk = static function (array $node, string $prefix) use (&$walk, &$leaves): void {
            foreach ($node as $k => $v) {
                $path = $prefix === '' ? (string) $k : $prefix.'.'.$k;
                // ⚠️ A list of scalars (e.g. a channel stack) is a VALUE, not a branch.
                if (is_array($v) && $v !== [] && array_keys($v) !== range(0, count($v) - 1)) {
                    $walk($v, $path);

                    continue;
                }
                // ⚠️ Only emptiness is kept — the value itself never leaves this loop.
                $leaves[$path] = $v === null || $v === '' || $v === [];
            }
        };
        $walk(config()->all(), '');

        $set = $empty = 0;
        $buckets = ['SILENT' => [], 'LOUD' => [], 'INERT' => [], 'UNCLASSIFIED' => []];

        foreach ($leaves as $path => $isEmpty) {
            if (! $isEmpty) {
                $set++;

                continue;
            }
            $empty++;
            [$class, $why] = self::CLASSIFY[$path] ?? ['UNCLASSIFIED', 'nobody has decided what this emptiness costs'];
            $buckets[$class][] = [$path, $why];
        }

        return ['leaves' => count($leaves), 'set' => $set, 'empty' => $empty, 'buckets' => $buckets, 'known' => array_keys($leaves)];
    }

    /**
     * ⚠️⚠️ `env()` CALLED OUTSIDE `config/` — each such call reads NULL, always, once the config is cached (L14).
     * Tokenised, never grepped: a grep reads its own needle in comments and strings.
     *
     * @return list<string> file:line sites
     */
    public static function strayEnvSites(): array
    {
        $sites = [];
        foreach (['app', 'routes', 'database', 'resources', 'bootstrap'] as $root) {
            $dir = base_path($root);
            if (! is_dir($dir)) {
                continue;
            }
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php' || str_contains($file->getPathname(), '/bootstrap/cache/')) {
                    continue;
                }
                $tokens = @token_get_all((string) file_get_contents($file->getPathname()));
                foreach ($tokens as $i => $t) {
                    if (! is_array($t) || $t[0] !== T_STRING || $t[1] !== 'env') {
                        continue;
                    }
                    // `->env(`, `Foo::env(` and `function env(` are different things entirely.
                    $prev = $tokens[$i - 1] ?? null;
                    if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                        continue;
                    }
                    $next = $tokens[$i + 1] ?? null;
                    if ($next !== '(') {
                        continue;
                    }
                    $sites[] = str_replace(base_path().'/', '', $file->getPathname()).':'.$t[2];
                }
            }
        }

        return $sites;
    }

    /**
     * ⚠️⚠️ CALIBRATION — a detector is not trusted until it is seen to be right about answers already known.
     *
     * @param  array{buckets: array<string, list<array{0: string, 1: string}>>, known: list<string>}  $inv
     * @return list<array{0: string, 1: bool}>
     */
    public static function calibration(array $inv): array
    {
        $allFlagged = array_column(array_merge(...array_values($inv['buckets'])), 0);

        return [
            ['app.key must be SET', ! in_array('app.key', $allFlagged, true)],
            ['mail.default must resolve (never UNRESOLVED)', in_array('mail.default', $inv['known'], true)],
            // ⚠️⚠️ FLIPPED BY THE L14 FIX. Before Phase 1 this point asserted the heartbeat URL was NOT a config
            //    key (it was a raw env() — which is how the defect was found). It now must BE one: the instrument
            //    has to see it, empty or set, or the fix did not land.
            ['ops.heartbeat_ping_url IS a config key (L14 fixed — read through config())', in_array('ops.heartbeat_ping_url', $inv['known'], true)],
        ];
    }
}
