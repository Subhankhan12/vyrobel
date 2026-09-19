<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/**
 * ⚠️⚠️ R-PHANTOM (founder ruling, 2026-09-19) — KEEP THE SEVENTEEN, DOCUMENT THEM, PIN THE EXACT REGISTER.
 *
 * ⚠️ WHAT WAS ALREADY HERE, AND WHAT IT DID NOT DO. {@see PermissionEnforcementContractTest} holds the
 * seventeen as keys of `VY_UNREAD_PERMISSIONS` and asserts two PROPERTIES of them — every unread permission
 * is recorded there, and no recorded entry is enforced. Those are good and they stay. 🔬 But together they
 * only pin the set WHILE EACH NAME REMAINS IN THE CATALOGUE: delete `invoices.view` from
 * `config/authorization.php` and it leaves BOTH computed sets, so the first assertion still sees nothing
 * unexplained and the second still sees nothing enforced. Both stay green and a permission has vanished.
 *
 * ⚠️⚠️ AND A COUNT WOULD NOT HAVE HELPED — SEVENTEEN DIFFERENT NAMES ALSO COUNT SEVENTEEN. This asserts the
 * NAMES, so a rename reddens it too.
 *
 * ⚠️⚠️ THE RIGHT-HAND SIDE IS COMPUTED, THE LEFT IS WRITTEN DOWN — that is what makes this a pin rather than
 * a tautology. `vyUnreadPermissions()` walks `app/`, `routes/` and `resources/views/` and reports which
 * catalogue entries no enforcement site reads; the lists below are typed out by hand from the founder's
 * ruling. Nothing propagates from one to the other.
 *
 * ⚠️⚠️ THIS IS A NEGATIVE CLAIM PROVEN BY SCANNING SOURCE — the needle-reads-its-own-list trap, which has
 * fired about ten times in this project. It is shipped only because both halves of the defence hold:
 *
 *   (a) THE SCAN CANNOT SEE ITSELF, structurally rather than by pattern. Its roots are `app/`, `routes/`
 *       and `resources/views/` — so `tests/` (this file, and the register it pins) and `docs/` (where
 *       PHANTOM-PERMISSIONS.md names all seventeen in prose) are not scanned AT ALL, and `config/` is
 *       skipped by an explicit branch because the file that DECLARES a permission must never count as one
 *       that READS it. Comment dialects, `<style>`, `<script>` and — since FIX 41 — heredocs are stripped
 *       from what remains.
 *   (b) IT FINDS A READER PLANTED ON PURPOSE. 🔬 MUTATION-PROVEN in FIX 41: a real `->can('invoices.view')`
 *       added to an operator controller moves that permission out of the unread set and REDS this test.
 *       An undetectable negative would be worse than no test, so (a) alone was never going to be enough.
 *
 * ⚠️ NOTHING IS GRANTED, WIRED OR REMOVED HERE. The ruling was to KEEP them; this records exactly which.
 */

// ── THE REGISTER, IN THE THREE SHAPES THE RULING KEPT THEM IN ───────────────────────────────────────

/** A · The capability IS enforced — under a DIFFERENT permission. Six. */
const VYP_SHAPE_A_ENFORCED_ELSEWHERE = [
    'availability.manage',
    'availability.view',
    'fees.manage',
    'housekeeping.view',
    'restrictions.manage',
    'taxes.manage',
];

/** B · The capability does not exist as an operator surface. Eight. */
const VYP_SHAPE_B_NO_SURFACE = [
    'addons.manage',
    'amenities.manage',
    'api.manage',
    'corporate_accounts.manage',
    'guest_documents.manage',
    'invoices.issue',
    'invoices.view',
    'organizations.manage',
];

/** C · Enforced by a ROLE allow-list rather than by a permission. Three. */
const VYP_SHAPE_C_ROLE_GATED = [
    'guest_portal.view',
    'owner_portal.view',
    'payments.view',
];

// ── 1. ⚠️⚠️ THE EXACT SEVENTEEN, BY NAME ────────────────────────────────────────────────────────────
/**
 * ⚠️⚠️ THE PIN. Adding a permission that nothing reads, removing one of these from the catalogue, renaming
 * one, or wiring one to a real check — every one of those changes this set, and every one must be a
 * deliberate act with a ruling behind it rather than a side effect of a tidy-up.
 */
it('⚠️⚠️ R-PHANTOM — the seventeen unread permissions are EXACTLY these, by name', function () {
    $expected = [...VYP_SHAPE_A_ENFORCED_ELSEWHERE, ...VYP_SHAPE_B_NO_SURFACE, ...VYP_SHAPE_C_ROLE_GATED];
    sort($expected);

    $actual = vyUnreadPermissions();
    sort($actual);

    expect($actual)->toBe($expected,
        "The set of permissions NOTHING reads has changed.\n"
        .'  appeared: '.implode(', ', array_diff($actual, $expected))."\n"
        .'  gone:     '.implode(', ', array_diff($expected, $actual))."\n"
        .'R-PHANTOM ruled these seventeen are KEPT and DOCUMENTED. If one is now enforced, that is good — '
        .'move it out of docs/PHANTOM-PERMISSIONS.md and this register. If one vanished from the catalogue, '
        .'that moves role_has_permissions and needs a ruling.');
});

// ── 2. ⚠️ THE THREE SHAPES, EACH WITH ITS OWN SIZE ──────────────────────────────────────────────────
/**
 * ⚠️ THE SHAPES ARE THE REASONING, NOT DECORATION — each needs a different answer if it is ever revisited:
 * A could be wired or dropped, B is dead weight until a surface exists, C is correctly enforced by role.
 * Keeping them separate means a later pass cannot quietly move a permission between categories.
 */
it('⚠️ keeps the three shapes distinct — six, eight and three', function () {
    expect(VYP_SHAPE_A_ENFORCED_ELSEWHERE)->toHaveCount(6);
    expect(VYP_SHAPE_B_NO_SURFACE)->toHaveCount(8);
    expect(VYP_SHAPE_C_ROLE_GATED)->toHaveCount(3);

    // ⚠️ No permission may sit in two shapes — that would make the total right and the reasoning wrong.
    $all = [...VYP_SHAPE_A_ENFORCED_ELSEWHERE, ...VYP_SHAPE_B_NO_SURFACE, ...VYP_SHAPE_C_ROLE_GATED];
    expect(array_unique($all))->toHaveCount(17, 'a permission appears in more than one shape');
});

// ── 3. ⚠️⚠️ THE SCAN EXCLUDES ITSELF — ASSERTED, NOT CLAIMED IN A COMMENT ───────────────────────────
/**
 * ⚠️⚠️ THE DEFENCE IS ONLY WORTH THE ASSERTION BEHIND IT. This file, the allow-list register and
 * docs/PHANTOM-PERMISSIONS.md all name the seventeen in plain text. If the scan ever widened to read them,
 * every phantom would look enforced and the census would report perfect enforcement while enforcing nothing.
 */
it('⚠️⚠️ cannot read its own register — the roots exclude tests, docs and the catalogue', function () {
    $sites = vyPermissionEnforcement();

    $everyPath = [];
    foreach ($sites as $s) {
        $everyPath = [...$everyPath, ...$s['route'], ...$s['view'], ...$s['code']];
    }

    foreach (array_unique($everyPath) as $path) {
        expect(str_starts_with($path, 'tests/'))->toBeFalse("the census read a TEST file: {$path}");
        expect(str_starts_with($path, 'docs/'))->toBeFalse("the census read a DOC: {$path}");
        expect(str_starts_with($path, 'config/'))->toBeFalse("the census read the CATALOGUE: {$path}");
    }

    // ⚠️ AND IT IS NOT VACUOUS — it really did find enforcement sites to check.
    expect(count($everyPath))->toBeGreaterThan(25, 'the census found almost nothing; it is not walking');
});

// ── 4. ⚠️ A COMMENT IS NOT AN ENFORCEMENT SITE, AND NEITHER IS A HEREDOC ────────────────────────────
/**
 * ⚠️ Both strips are asserted directly on the stripper rather than inferred from the census, so a
 * regression in either is named for what it is instead of surfacing as a mysterious count change.
 */
it('⚠️ strips comments and heredocs before deciding what reads a permission', function () {
    $commented = <<<'PHP'
    <?php
    // $user->can('invoices.view');
    /* Gate::allows('invoices.issue'); */
    PHP;

    expect(trim(vyPermissionStrip($commented, false)))->not->toContain('invoices.view');
    expect(trim(vyPermissionStrip($commented, false)))->not->toContain('invoices.issue');

    // ⚠️ A HEREDOC IS A STRING — comment-stripping leaves it entirely intact, which is the whole point.
    $withHeredoc = "<?php\n\$prompt = <<<'TXT'\n\$user->can('amenities.manage');\nTXT;\n";

    expect(vyPermissionStrip($withHeredoc, false))->not->toContain('amenities.manage');
});
