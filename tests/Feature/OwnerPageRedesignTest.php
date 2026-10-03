<?php

declare(strict_types=1);

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE OWNER'S PAGE — HOW IT LOOKS AND WHAT IT OFFERS (2026-10-03, the founder: "modal design and buttons are mess …
// confirm with your password and its box is not well aligned"). What the writes DO is OwnerPageFixesTest. These read the
// rendered page; that each dialog opens centred and every box lines up was measured in a browser (docs/memory/HISTORY.md).

function odrOwner(array $s): Owner
{
    $owner = owpOwner($s, 'Inês Carvalho');
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 18.0,
        'effective_from' => '2026-01-01', 'effective_to' => null]);

    return $owner;
}

function odrXPath(User $actor, Owner $owner): DOMXPath
{
    $html = (string) test()->actingAs($actor)->get(route('app.owners.show', $owner->id))->assertOk()->getContent();
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function odrCls(string $class): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
}

function odrText(?DOMNode $n): string
{
    return $n === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $n->textContent));
}

/** The dialog named by a title id, checked against the shared contract; returns it. */
function odrDialog(DOMXPath $xp, string $titleId): DOMElement
{
    $dialog = $xp->query('//*[@role="dialog" and @aria-labelledby="'.$titleId.'"]')->item(0);
    expect($dialog)->toBeInstanceOf(DOMElement::class, "no dialog titled #{$titleId}")
        ->and($xp->query('//*[@id="'.$titleId.'"]')->length)->toBe(1)
        ->and(preg_split('/\s+/', $dialog->parentNode->getAttribute('class')))->toContain('vy-modal-backdrop')
        ->and(preg_split('/\s+/', $dialog->getAttribute('class')))->toContain('vy-modal')->toContain('vy-modal-card')->toContain('vyo-dlg')
        ->and($dialog->getAttribute('aria-modal'))->toBe('true')
        ->and($dialog->getAttribute('tabindex'))->toBe('-1')
        ->and($dialog->getAttribute('x-trap.noscroll'))->not->toBe('');

    return $dialog;
}

/** @return list<string> the foot's buttons, in order */
function odrFoot(DOMXPath $xp, DOMElement $dialog): array
{
    $foot = $xp->query('.//*['.odrCls('vy-modal-foot').']', $dialog)->item(0);

    return array_map(fn ($b) => odrText($b), iterator_to_array($xp->query('.//button', $foot)));
}

it('⚠️⚠️ asks for the password on its own row in Remove owner — Keep owner and Remove owner together below it', function () {
    $s = seedScenario(1);
    $owner = odrOwner($s);
    $xp = odrXPath(owpAdmin($s), $owner);

    $dialog = odrDialog($xp, 'vyod-remove-t');
    // 🖥 before: a borderless password box under a shouted label, and the two buttons at two different sizes
    $password = $xp->query('.//input[@type="password"]', $dialog)->item(0);
    expect($password)->not->toBeNull()
        ->and($xp->query('ancestor::*['.odrCls('vy-modal-foot').']', $password)->length)->toBe(0)
        ->and($xp->query('ancestor::*['.odrCls('vyo-confirm').']', $password)->length)->toBe(1)
        ->and(odrText($xp->query('.//label[@for="'.$password->getAttribute('id').'"]', $dialog)->item(0)))->toBe('Confirm it’s you')
        ->and(odrFoot($xp, $dialog))->toBe(['Keep owner', 'Remove owner']);

    // the name to type is named by a visible label
    $name = $xp->query('.//input[@name="confirm_name"]', $dialog)->item(0);
    expect(odrText($xp->query('.//label[@for="'.$name->getAttribute('id').'"]', $dialog)->item(0)))->toBe('Type Inês Carvalho to confirm');
});

it('⚠️ opens every write on the page as the shared centred dialog, each with its two buttons at one height', function () {
    $s = seedScenario(1);
    $owner = odrOwner($s);
    $xp = odrXPath(owpAdmin($s), $owner);

    expect(odrFoot($xp, odrDialog($xp, 'vyod-commission-t')))->toBe(['Cancel', 'Save commission'])
        ->and(odrFoot($xp, odrDialog($xp, 'vyod-pause-t')))->toBe(['Cancel', 'Pause payouts'])
        ->and(odrFoot($xp, odrDialog($xp, 'vyod-close-t')))->toBe(['Cancel', 'Close period'])
        ->and(odrFoot($xp, odrDialog($xp, 'vyod-propose-t')))->toBe(['Cancel', 'Send proposal']);

    // the two dates of a period sit side by side, one height
    $close = odrDialog($xp, 'vyod-close-t');
    foreach (['period_start', 'period_end'] as $field) {
        $input = $xp->query('.//input[@name="'.$field.'"]', $close)->item(0);
        expect($xp->query('ancestor::*['.odrCls('vyo-pair').']', $input)->length)->toBe(1);
    }
});

it('puts the actions on the owner in one bar under their name — each only for a role that may use it', function () {
    $s = seedScenario(1);
    $owner = odrOwner($s);

    $actions = fn (User $u): array => array_map(fn ($b) => odrText($b),
        iterator_to_array(($xp = odrXPath($u, $owner))->query('//*['.odrCls('vyo-hero-actions').']//button')));

    expect($actions(owpAdmin($s)))->toBe(['Change commission', 'Pause payouts', 'Close a period', 'Remove owner'])
        // Hotel Manager reads owners and manages neither users nor payouts
        ->and($actions(operatorUser($s, [], 'Hotel Manager')))->toBe([])
        // an Accountant closes periods (payouts.manage) and manages no one (users.manage)
        ->and($actions(operatorUser($s, [], 'Accountant')))->toBe(['Close a period']);

    // and the other way round — a role given users.manage without payouts.manage gets every action but closing a period
    $manager = operatorUser($s, [], 'Hotel Manager');
    app(PermissionRegistrar::class)->setPermissionsTeamId($s['org']->getKey());
    Role::query()->where('name', 'Hotel Manager')->where('organization_id', $s['org']->id)->firstOrFail()->givePermissionTo('users.manage');
    expect($actions($manager->fresh()))->toBe(['Change commission', 'Pause payouts', 'Remove owner']);
});

it('⚠️ uses no inline style and no page-only dialog panel — every display is a class', function () {
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/owner-detail.blade.php')));

    expect(substr_count($source, 'style="'))->toBe(0)
        ->and($source)->not->toContain('vyo-scrim')
        ->and($source)->not->toContain('vyo-modal"');
});

it('⚠️⚠️ gives every class the owner’s page uses a rule in the stylesheet', function () {
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/owner-detail.blade.php')));
    preg_match_all('/<style>(.*?)<\/style>/s', $source, $styles);
    $css .= implode("\n", $styles[1]);

    preg_match_all('/\sclass="([^"]*)"/', $source, $m);
    $missing = [];
    foreach ($m[1] as $attr) {
        $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
        foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (str_ends_with($token, '-')) {
                continue;
            }
            if (preg_match('/^vyo?-[a-z0-9-]+$/', $token) && preg_match('/\.'.preg_quote($token, '/').'(?![a-zA-Z0-9_-])/', $css) !== 1) {
                $missing[] = $token;
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
