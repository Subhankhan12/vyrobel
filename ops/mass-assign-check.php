<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;

/**
 * ⚠️⚠️ THE SILENT-DROP DETECTOR — bought by a real bug, 2026-09-23.
 *
 * 🔬 `OwnerInviteService::accept()` passed `email_verified_at` to `update()`. The column is not in
 * `User::$fillable`, so **Eloquent discarded it silently** — no exception, no log line. The code read
 * correctly and did nothing, and nobody noticed for as long as nothing READ the column back.
 *
 * ⚠️⚠️ A WRITE IS PROVEN BY READING THE VALUE BACK, NEVER BY THE LINE THAT MAKES IT. If that happened on the
 * single most important column in the application, assume it happened elsewhere — and assume it is equally
 * invisible.
 *
 * ⚠️ TOKENISED, NOT GREPPED. A grep finds literals; this question is about **which keys reach which model**,
 * so the file is parsed and the receiver resolved.
 *
 * ⚠️⚠️ THREE VERDICTS, NEVER TWO. A call whose array is built at runtime (`$data`, a merge, a spread) cannot
 * be resolved statically — those are **UNMEASURABLE**, not clean. The exposure battery counted `503` as a
 * pass and that is exactly this mistake.
 *
 * ⚠️ `forceFill`/`forceCreate` are NOT flagged: they bypass the protection on purpose and are the correct fix.
 * ⚠️ A model with `$guarded = []` has nothing to drop — reported as such rather than scored.
 *
 * USAGE:  php ops/mass-assign-check.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** Resolve a model class name to its fillable/guarded contract, or null if it is not a model. */
function contractFor(string $class): ?array
{
    if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
        return null;
    }

    // ⚠️⚠️ ASK THE MODEL, NEVER RE-DERIVE ITS RULE. 🔬 The first version of this detector reimplemented
    //    Laravel's logic and reported **91 false drops**, because it treated the DEFAULT `$guarded = ['*']`
    //    as "everything is guarded" — when Laravel's actual rule is that a key in a NON-EMPTY `$fillable`
    //    is fillable regardless of `$guarded`. ⚠️ One definition per concept: `isFillable()` is the
    //    authoritative answer and it lives on the model.
    return ['model' => new $class];
}

/**
 * ⚠️⚠️ THE KEYS OF THE OUTERMOST ARRAY LITERAL ONLY (SESSIONS 01 · Part C, 2026-09-25). 🔬 The first version ran one regex
 * over the whole argument, so a key INSIDE a nested array — the contents of a JSON column — was scored as a column
 * the model silently drops. All five of its "DROPPED" findings were exactly that (`address.city`,
 * `requested[].room_type_id…`, `metadata.authorization_id`, `meta.encrypted/cipher`): the data WAS stored, and "remove
 * the dead key" would have destroyed it. Depth 1 = directly inside the outer `[`; strings are skipped whole.
 *
 * @return list<string>
 */
function topLevelKeys(string $arg): array
{
    $keys = [];
    $depth = 0;
    $len = strlen($arg);
    for ($i = 0; $i < $len; $i++) {
        $c = $arg[$i];
        if ($c === "'" || $c === '"') {
            $j = $i + 1;
            $buf = '';
            while ($j < $len && $arg[$j] !== $c) {
                if ($arg[$j] === '\\') {
                    $buf .= $arg[$j + 1] ?? '';
                    $j += 2;

                    continue;
                }
                $buf .= $arg[$j];
                $j++;
            }
            if ($depth === 1 && preg_match('/^\s*=>/', substr($arg, $j + 1)) === 1 && preg_match('/^[a-z0-9_]+$/i', $buf) === 1) {
                $keys[] = $buf;
            }
            $i = $j;

            continue;
        }
        if ($c === '[' || $c === '(') {
            $depth++;
        } elseif ($c === ']' || $c === ')') {
            $depth--;
        }
    }

    return $keys;
}

/** Map a variable/expression to a model class using `use` imports and common idioms. */
function guessModel(string $receiver, array $uses): ?string
{
    $receiver = trim($receiver);
    // Model::query()->... / Model::create(...)
    if (preg_match('/^([A-Z][A-Za-z0-9_]*)$/', $receiver, $m)) {
        return $uses[$m[1]] ?? ('App\\Models\\'.$m[1]);
    }

    return null;
}

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app'));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $files[] = $f->getPathname();
    }
}
sort($files);

$clean = $dropped = $unmeasurable = $noGuard = 0;
$drops = [];
$unmeasured = [];

foreach ($files as $path) {
    $src = file_get_contents($path);
    $tokens = token_get_all($src);

    // collect `use` imports
    $uses = [];
    foreach ($tokens as $i => $t) {
        if (is_array($t) && $t[0] === T_USE && isset($tokens[$i + 2])) {
            $fq = '';
            for ($j = $i + 1; $j < count($tokens); $j++) {
                if ($tokens[$j] === ';') {
                    break;
                }
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                    $fq .= $tokens[$j][1];
                }
            }
            if ($fq !== '' && str_contains($fq, '\\')) {
                $uses[substr($fq, strrpos($fq, '\\') + 1)] = $fq;
            }
        }
    }

    // find ->update([ / ->fill([ / ::create([ / ->create([
    foreach (['update', 'fill', 'create'] as $method) {
        $offset = 0;
        while (($pos = strpos($src, $method.'(', $offset)) !== false) {
            $offset = $pos + 1;
            $before = substr($src, max(0, $pos - 60), min(60, $pos));
            // ⚠️ forceFill / forceCreate bypass the protection ON PURPOSE — never flagged.
            if (preg_match('/force(Fill|Create)\s*$/i', $before.$method)) {
                continue;
            }
            if (! preg_match('/(->|::)\s*$/', $before)) {
                continue;
            }

            // the argument text, balanced to the closing paren
            $depth = 0;
            $argStart = $pos + strlen($method) + 1;
            $end = $argStart;
            for ($k = $argStart; $k < strlen($src); $k++) {
                if ($src[$k] === '(' || $src[$k] === '[') {
                    $depth++;
                }
                if ($src[$k] === ')' || $src[$k] === ']') {
                    if ($depth === 0 && $src[$k] === ')') {
                        $end = $k;
                        break;
                    }
                    $depth--;
                }
            }
            $arg = substr($src, $argStart, $end - $argStart);
            $line = substr_count(substr($src, 0, $pos), "\n") + 1;
            $rel = str_replace(dirname(__DIR__).'/', '', $path);

            // ⚠️ THIRD VERDICT: an array we cannot read statically is UNMEASURABLE, not clean.
            if (! preg_match('/^\s*\[/', $arg) || topLevelKeys($arg) === []) {
                $unmeasurable++;
                $unmeasured[] = "{$rel}:{$line}  {$method}(".trim(substr($arg, 0, 34)).'…)';

                continue;
            }
            $keys = topLevelKeys($arg);

            // resolve the receiver
            if (! preg_match('/([A-Za-z0-9_\\\\]+)\s*(->|::)\s*$/', $before, $rm)) {
                $unmeasurable++;
                $unmeasured[] = "{$rel}:{$line}  receiver unresolved";

                continue;
            }
            $model = guessModel($rm[1], $uses);
            $contract = $model !== null ? contractFor($model) : null;
            if ($contract === null) {
                $unmeasurable++;
                $unmeasured[] = "{$rel}:{$line}  {$rm[1]}->{$method}() — receiver is not a resolvable model";

                continue;
            }

            $modelInstance = $contract['model'];

            if ($modelInstance->getFillable() === [] && $modelInstance->getGuarded() === []) {
                $noGuard++;

                continue;
            }

            $bad = [];
            foreach ($keys as $key) {
                if (! $modelInstance->isFillable($key)) {
                    $bad[] = $key;
                }
            }
            if ($bad !== []) {
                $dropped++;
                $drops[] = "{$rel}:{$line}  ".class_basename($model)."::{$method}()  DROPS: ".implode(', ', $bad);
            } else {
                $clean++;
            }
        }
    }
}

echo "\n  ⚠️⚠️ SILENT-DROP SWEEP — every update()/fill()/create() in app/\n";
echo '  '.str_repeat('─', 76)."\n";
printf("  files scanned      : %d\n", count($files));
printf("  ✅ CLEAN            : %d  (every key reaches the model)\n", $clean);
printf("  ⚪ no mass-assign guard : %d  (\$guarded = [] — nothing to drop)\n", $noGuard);
printf("  🔴 DROPPED          : %d\n", $dropped);
printf("  ⚠️  UNMEASURABLE     : %d  (runtime-built array or unresolvable receiver — NOT clean)\n", $unmeasurable);
echo '  '.str_repeat('─', 76)."\n";
foreach ($drops as $d) {
    echo "  🔴 {$d}\n";
}
if ($drops === []) {
    echo "  ✅ no statically-provable silent drop remains.\n";
}
echo "\n  ⚠️ UNMEASURABLE are listed in full below — they are a coverage gap, not a pass:\n";
foreach (array_slice($unmeasured, 0, 25) as $u) {
    echo "     {$u}\n";
}
if (count($unmeasured) > 25) {
    printf("     … and %d more\n", count($unmeasured) - 25);
}
echo "\n";
