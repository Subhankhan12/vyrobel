<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Assert;

/**
 * ⚠️ THE ONE SELECT ENGINE, EVERYWHERE (2026-10-10, his approval of the profile's select: "I need the same sort of select and same
 * sort of drop down in the entire application, everywhere … test everywhere"). `components/vy-selects.blade.php` is included once
 * from every shell; it skins every native select and opens the Vyrobel panel in place of the operating system's list — with the
 * select itself left in charge of the value, so forms, x-model, the auto-submitting filters and validation are unchanged.
 *
 * ⚠️ WHAT THESE CAN AND CANNOT SEE: that every shell carries the engine, that its rules are intact, and that the old drop-downs are
 * gone. The behaviour is a browser fact: 🖥 walked 2026-10-10 locally across the six realms (28 operator pages, 24 owner, the guest
 * portal, the storefront, signup, 20 admin pages), dialogs included.
 *
 * ⚠️ Messages go through PHPUnit's Assert: Pest's `toContain()` takes MANY needles, so a message passed to it is searched for too.
 */
uses(DatabaseTransactions::class);

/** The engine as the shells include it. */
function selectEngine(): string
{
    return (string) file_get_contents(resource_path('views/components/vy-selects.blade.php'));
}

it('is included by every shell that carries the shared theme — the error pages and Laravel’s unused welcome page aside', function () {
    $shells = collect(glob(resource_path('views').'/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) ?: [])
        ->filter(fn (string $f): bool => str_contains((string) file_get_contents($f), '<x-vy-scrollbar />'))
        ->reject(fn (string $f): bool => str_ends_with($f, 'errors/layout.blade.php') || str_ends_with($f, 'welcome.blade.php'))
        ->values();

    expect($shells->count())->toBeGreaterThanOrEqual(15);   // operator, owner, guest, admin, public, auth, onboarding, billing, oauth …
    foreach ($shells as $file) {
        Assert::assertStringContainsString('<x-vy-selects />', (string) file_get_contents($file), basename($file).' lacks the select engine');
    }
});

it('renders the engine exactly once on a page of each signed-in realm', function () {
    $s = seedScenario(1);

    foreach ([
        [operatorUser($s), '/app/dashboard', null],
        [ownerRoleUser($s), '/owner', null],
        [platformAdmin(), route('admin.customers.index'), 'admin'],
    ] as [$who, $url, $guard]) {
        $html = (string) $this->actingAs($who, $guard)->get($url)->assertOk()->getContent();
        expect(substr_count($html, 'window.VySelect = {'))->toBe(1, "{$url} must carry the engine once");
    }

    // And a page nobody signs in to.
    auth()->logout();
    expect(substr_count((string) $this->get(route('login'))->getContent(), 'window.VySelect = {'))->toBe(1);
});

it('⚠️ leaves the native select in charge: it opens its own panel, and a choice fires input and change on the select', function () {
    $engine = selectEngine();

    expect($engine)
        // A mouse press opens the Vyrobel panel, not the operating system's list — a finger keeps the phone's picker.
        ->toContain("if (eligible(t) && e.button === 0 && pointer === 'mouse') {")
        ->toContain('e.preventDefault();   // no operating-system list')
        // A choice is the select's own: its index set, then input and change — what the native list does.
        ->toContain('s.selectedIndex = r.index;')
        ->toContain("s.dispatchEvent(new Event('input', { bubbles: true }));")
        ->toContain("s.dispatchEvent(new Event('change', { bubbles: true }));")
        // The keyboard: arrows, Enter, Space and typing open it; Esc closes it without closing a dialog behind it.
        ->toContain("if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(e.key) !== -1 || printable) {")
        ->toContain("case 'Enter': e.preventDefault(); choose(st.active); break;")
        ->toContain("case 'Escape': e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); close(true); break;");
});

it('⚠️ places the panel inside a dialog, from a still probe, and centres the choice from layout offsets', function () {
    $engine = selectEngine();

    expect($engine)
        // Inside a dialog: its focus trap and its click-outside see the panel as inside.
        ->toContain('return select.closest(\'[aria-modal="true"], [role="dialog"], dialog\') || D.body;')
        // A dialog with a transform or backdrop-filter becomes the containing block: (0,0) read from a still probe.
        ->toContain('st.home.appendChild(probe); var o = probe.getBoundingClientRect(); probe.remove();')
        // The 98.5% opening scale makes a screen rect short: layout offsets.
        ->toContain('st.list.scrollTop = (row.offsetTop - st.list.offsetTop) - (st.list.clientHeight - row.offsetHeight) / 2;')
        // As wide as its longest choice, up to 420px.
        ->toContain("p.style.width = 'max-content'; p.style.maxWidth = '420px';");
});

it('searches long lists, never a list of plain numbers, and lets a page opt one select out', function () {
    $engine = selectEngine();

    expect($engine)
        ->toContain('return rows.length > 8 && ! rows.every(function (r) { return /^\\d+$/.test(r.label.trim()); });')
        ->toContain("! el.hasAttribute('data-native') && ! el.closest('[data-native-selects]')");

    // ⚠️ Everywhere means everywhere: no view opts a select out today. One that does must say why (and this count moves).
    $optOuts = collect(glob(resource_path('views').'/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) ?: [])
        ->reject(fn (string $f): bool => str_ends_with($f, 'components/vy-selects.blade.php'))
        ->filter(fn (string $f): bool => (bool) preg_match('/<select\b[^>]*\bdata-native\b/s', (string) file_get_contents($f)));
    expect($optOuts->all())->toBe([]);
});

it('skins every closed select with the chevron forced, and gives every select the app’s focus ring', function () {
    $engine = selectEngine();

    expect($engine)
        ->toContain('-webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;')
        ->toContain('background-repeat: no-repeat !important; background-position: right 12px center !important;')
        ->toContain(':where(select:not([data-native])):focus { outline: none;');
});

it('gives the drop-down menus the same panel, and lifts a console card while its list is open', function () {
    $engine = selectEngine();

    expect($engine)
        // The surface rule itself (the selector alone also opens the reduced-motion rule).
        ->toContain(':is(.vy-acct-menu, .vy-gl-menu, .vy-ov-menu, .vy-st-menu, .vy-rep-menu, [data-vy-bell] > [role="menu"]) {'."\n".'        background: rgba(255, 252, 248, .97) !important;')
        ->toContain('.ad-card:has(.ad-combo-trigger[aria-expanded="true"]) { position: relative; z-index: 5; }');
});

it('⚠️ has retired the older drop-down — one engine, no second copy', function () {
    expect(file_exists(resource_path('views/components/operator/combobox.blade.php')))->toBeFalse();

    $views = collect(glob(resource_path('views').'/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) ?: []);
    foreach ($views as $file) {
        $source = (string) file_get_contents($file);
        Assert::assertStringNotContainsString('<x-operator.combobox', $source, basename($file).' still uses the retired drop-down');
        Assert::assertStringNotContainsString('.vy-combo-trigger', $source, basename($file).' still styles the retired drop-down');
    }
});
