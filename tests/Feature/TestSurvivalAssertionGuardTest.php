<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Assert;
use Symfony\Component\Finder\Finder;

/**
 * ⚠️⚠️ THE SHAPE THAT HAS NOW BITTEN TWICE — A SURVIVAL ASSERTION THAT CANNOT SEE A REMOVAL.
 *
 * {@see SoftDeleteScopeGuardTest} closed the class in `app/`. It could not close it in `tests/`, where
 * **156** bare `withoutGlobalScopes()` calls live and MOST ARE CORRECT — a test deliberately reading across
 * tenants is doing its job, and a test asserting a row WAS trashed must see trashed rows. A blanket ban
 * would need ~152 allow-list entries, which is a list nobody reads.
 *
 * ⚠️⚠️ SO THIS GUARDS THE DEFECT, NOT THE CALL. The dangerous combination is narrow and nameable:
 *
 *      a bare `withoutGlobalScopes()` on a SOFT-DELETING model
 *      + a test that PROVES SURVIVAL with `not->toBeNull()`
 *      + a REMOVAL attempted in that same test
 *      + and the word `trashed()` never appearing
 *
 * `withoutGlobalScopes()` drops `SoftDeletingScope`, so `find()` returns a REMOVED row exactly as it returns
 * a live one. Every one of those tests passes whether the removal was refused or went through — and it is
 * counted as proof that it was refused.
 *
 * ⚠️ MEASURED, NOT ASSUMED. Against the text as it stood before the fixes this rule flagged exactly the two
 *    defects that were demonstrated by planting (four lines), and NOTHING else out of 156 sites. Against the
 *    tree today it flags nothing. **The allow-list is empty, and there has never been an entry in it.**
 *
 * ⚠️ THE NARROWER RULES WERE TRIED FIRST AND REJECTED, both measured:
 *      • "no bare call on a soft-deleting model asserting `toBeNull()`"      → 10 hits, 0 real
 *      • "…asserting `not->toBeNull()`"                                      →  4 hits, 0 real
 *    Both fired on tests that assert a row was CREATED, where nothing is removed and a trashed row could
 *    not arise. Requiring a removal in the same test is what makes the rule honest rather than noisy.
 */
uses(DatabaseTransactions::class);

/**
 * Scan one directory for the dangerous combination.
 *
 * ⚠️⚠️ TOKENISED, NOT GREPPED — the tenth instance of that trap here. The docblock above contains the very
 * call it forbids, so a grep-shaped guard fires on its own explanation and the next person "fixes" it by
 * loosening the pattern. `token_get_all()` sees what RUNS.
 *
 * @return list<string>
 */
function tsagOffenders(string $directory): array
{
    // Models carrying SoftDeletingScope — read from the models themselves, never a hand-kept list, so a
    // model that GAINS soft-deletion is covered on the day it does.
    $soft = [];
    foreach (Finder::create()->files()->in(app_path('Models'))->name('*.php') as $model) {
        $class = 'App\\Models\\'.$model->getFilenameWithoutExtension();
        if (! class_exists($class)) {
            continue;
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
            continue;
        }
        foreach (array_keys((new $class)->getGlobalScopes()) as $scope) {
            if (class_basename($scope) === 'SoftDeletingScope') {
                $soft[] = class_basename($class);
            }
        }
    }

    $offenders = [];

    /** @var SplFileInfo $file */
    foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
        $source = (string) file_get_contents($file->getRealPath());

        // Strip comments so the guard cannot read its own needle, keeping line numbers intact.
        $stripped = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                $stripped .= in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)
                    ? str_repeat("\n", substr_count($token[1], "\n"))
                    : $token[1];

                continue;
            }
            $stripped .= $token;
        }

        $lines = explode("\n", $stripped);

        // Walk each it()/test() closure by brace balance.
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/^\s*(it|test)\s*\(/', $lines[$i]) !== 1) {
                continue;
            }
            $depth = 0;
            $body = '';
            for ($j = $i; $j < count($lines); $j++) {
                $depth += substr_count($lines[$j], '{') - substr_count($lines[$j], '}');
                $body .= $lines[$j]."\n";
                if ($j > $i && $depth <= 0) {
                    break;
                }
            }

            if (! str_contains($body, 'withoutGlobalScopes()')) {
                continue;
            }
            if (! preg_match('/not->toBeNull\(\)/', $body)) {
                continue;   // not a survival proof
            }
            if (preg_match('/trashed\(\)|withTrashed\(\)|onlyTrashed\(\)/', $body) === 1) {
                continue;   // says what it means — this is the correct form
            }
            if (preg_match('/remove|archive|withdraw|destroy|->delete\(|Removal|revoke/i', $body) !== 1) {
                continue;   // nothing is removed here, so no trashed row can arise
            }
            // …and only if the receiver is a model that actually soft-deletes.
            foreach ($soft as $model) {
                if (preg_match('/\b'.preg_quote($model, '/').'::[^;]*withoutGlobalScopes\(\)/', $body) === 1) {
                    $offenders[] = $file->getRelativePathname().':'.($i + 1).' ('.$model.')';
                    break;
                }
            }
        }
    }

    sort($offenders);

    return $offenders;
}

it('⚠️⚠️ no test proves a row SURVIVED a removal through a scope that cannot see the removal', function () {
    $offenders = tsagOffenders(base_path('tests'));

    Assert::assertSame([], $offenders, implode("\n", [
        '⚠️⚠️ A SURVIVAL ASSERTION THAT CANNOT SEE A REMOVAL — it passes whether the removal was refused',
        '   or went through, and it is counted as proof that it was refused.',
        '',
        '   The bare no-argument form drops SoftDeletingScope, so find() returns a REMOVED row as it',
        '   returns a live one. Assert the STATE instead:',
        '',
        '       $row = Model::'.'withoutGlobalScopes'.'()->withTrashed()->find($id);',
        '       expect($row->trashed())->toBeFalse();',
        '',
        '   Offending tests:',
        '     '.implode("\n     ", $offenders),
    ]));
});

it('⚠️ the scanner is NOT vacuous — it fires on the exact text that bit', function () {
    // ⚠️⚠️ A GUARD THAT FINDS NOTHING LOOKS IDENTICAL TO A GUARD THAT CANNOT FIND ANYTHING. This drives the
    //    same scanner over a fixture reproducing the two real defects, so an empty result above means "the
    //    tree is clean" rather than "the rule matches nothing".
    $dir = sys_get_temp_dir().'/tsag_'.bin2hex(random_bytes(6));
    mkdir($dir);

    // ⚠️⚠️ THE NEEDLE IS ASSEMBLED, NEVER WRITTEN. My first draft put these fixtures in heredocs — and the
    //    guard flagged ITS OWN FILE, because a heredoc is a STRING and comment-stripping does not touch it.
    //    That is the tenth instance of this project's recurring trap, hit while writing the guard against
    //    it. Concatenating the call means the literal never appears in this file, so the guard needs no
    //    self-exclusion and no allow-list: it genuinely has nothing to skip.
    $bare = 'withoutGlobalScopes'.'()';

    // The first sighting's shape, verbatim in structure: a removal, then survival proved by existence.
    file_put_contents($dir.'/ProbeTest.php', implode("\n", [
        '<?php',
        "it('cannot remove the primary owner', function () {",
        "    \$this->delete(route('owner.team.members.remove', \$c['id']))->assertNotFound();",
        "    expect(Owner::{$bare}->find(\$c['ownerId'])->user_id)->not->toBeNull();",
        '});',
    ]));

    // And a NEAR MISS that must NOT fire: the same call and the same assertion, but the correct form.
    file_put_contents($dir.'/CleanTest.php', implode("\n", [
        '<?php',
        "it('cannot remove the primary owner', function () {",
        "    \$this->delete(route('owner.team.members.remove', \$c['id']))->assertNotFound();",
        "    \$row = Owner::{$bare}->withTrashed()->find(\$c['ownerId']);",
        '    expect($row->trashed())->toBeFalse()->and($row->user_id)->not->toBeNull();',
        '});',
    ]));

    $offenders = tsagOffenders($dir);

    array_map('unlink', glob($dir.'/*.php'));
    rmdir($dir);

    expect($offenders)->toHaveCount(1)
        ->and($offenders[0])->toContain('ProbeTest.php')
        ->and($offenders[0])->toContain('Owner');
});
