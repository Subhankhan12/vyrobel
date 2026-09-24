<?php

declare(strict_types=1);

namespace App\Support\Platform\Health;

/**
 * Reads WHICH release is live and which are retained for rollback — from the release layout on disk
 * (KNOWN-LIMITS L12k: `releases/rel_<id>/` + one `current` symlink), never from anything typed.
 *
 * ⚠️⚠️ READ-ONLY, AND ONLY READ. There is deliberately no deploy button, no rollback button and no maintenance
 * button anywhere in the console: this reports; it cannot act.
 *
 * ⚠️ The commit is read from each release's own `.release` file and accepted only if it is a 40-character hex
 * hash. Anything else is reported as unreadable, never guessed.
 */
final class ReleaseReader
{
    /**
     * @return array{in_layout: bool, current: ?string, commit: ?string, retained: list<array{name: string, commit: ?string}>}
     */
    public static function read(?string $basePath = null): array
    {
        $base = rtrim($basePath ?? base_path(), '/');
        $releasesDir = dirname($base);
        $inLayout = basename($releasesDir) === 'releases' && str_starts_with(basename($base), 'rel_');

        $retained = [];
        if ($inLayout) {
            foreach (glob($releasesDir.'/rel_*', GLOB_ONLYDIR) ?: [] as $dir) {
                if (basename($dir) === basename($base)) {
                    continue;
                }
                $retained[] = ['name' => basename($dir), 'commit' => self::commitAt($dir)];
            }
            // Newest first by name (rel_B … rel_M sort in order of creation).
            usort($retained, fn (array $a, array $b): int => strcmp($b['name'], $a['name']));
        }

        return [
            'in_layout' => $inLayout,
            'current' => $inLayout ? basename($base) : null,
            'commit' => self::commitAt($base),
            'retained' => $retained,
        ];
    }

    /** A release's commit, from its `.release` file — or null if absent or not a full hash. */
    public static function commitAt(string $dir): ?string
    {
        $file = $dir.'/.release';
        if (! is_file($file) || ! is_readable($file)) {
            return null;
        }

        $raw = trim((string) file_get_contents($file, false, null, 0, 200));

        return preg_match('/^[0-9a-f]{40}$/', $raw) === 1 ? $raw : null;
    }
}
