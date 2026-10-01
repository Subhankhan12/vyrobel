<?php

declare(strict_types=1);

use App\Models\OwnerInvite;
use App\Services\OwnerInviteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE OWNERS PAGE — HOW IT LOOKS AND WHAT IT OFFERS (2026-10-01, the founder: "on Add owner page, modal design and buttons
// are mess, cancel and send invite buttons look bad, confirm with your password and its box is not well aligned"). What the
// writes DO is OwnersPageFixesTest. These read the rendered page; that the dialog opens centred and every box lines up was
// measured in a browser (docs/memory/HISTORY.md, "OWNERS").

function ordPage($test, $user, string $query = ''): string
{
    return $test->actingAs($user)->get(route('app.owners').$query)->assertOk()->getContent();
}

function ordXPath(string $html): DOMXPath
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function ordCls(string $class): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
}

function ordText(?DOMNode $n): string
{
    return $n === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $n->textContent));
}

/** @return list<string> */
function ordTokens(?DOMNode $n): array
{
    return $n instanceof DOMElement ? preg_split('/\s+/', trim($n->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) : [];
}

// ── The Add owner dialog — the founder's report ──────────────────────────────────────────────────

it('⚠️⚠️ opens Add owner as the centred shared dialog — the password on its own row, Cancel and Send invite together below it', function () {
    $s = seedScenario(1);
    $xp = ordXPath(ordPage($this, owpAdmin($s)));

    $dialog = $xp->query('//*[@role="dialog" and @aria-labelledby="vy-invite-title"]')->item(0);
    expect($dialog)->not->toBeNull()
        ->and(ordTokens($dialog->parentNode))->toContain('vy-modal-backdrop')
        ->and(ordTokens($dialog))->toContain('vy-modal')->toContain('vy-modal-card')
        ->and($dialog->getAttribute('tabindex'))->toBe('-1')
        ->and($dialog->getAttribute('x-trap.noscroll'))->not->toBe('');

    // 🖥 before: the label, a 68 px password box and both buttons shared ONE row — Cancel at 47 px, "Send invite" wrapped
    $password = $xp->query('.//input[@id="invite_pw"]', $dialog)->item(0);
    expect($password)->not->toBeNull()
        ->and($xp->query('ancestor::*['.ordCls('vy-modal-foot').']', $password)->length)->toBe(0)
        ->and($xp->query('ancestor::*['.ordCls('vyo-confirm').']', $password)->length)->toBe(1)
        ->and(ordText($xp->query('.//label[@for="invite_pw"]', $dialog)->item(0)))->toBe('Confirm it’s you');

    $foot = $xp->query('.//*['.ordCls('vy-modal-foot').']', $dialog)->item(0);
    $buttons = array_map(fn ($b) => ordText($b), iterator_to_array($xp->query('.//button', $foot)));
    expect($buttons)->toBe(['Cancel', 'Send invite']);

    // every field is named by a visible label
    foreach (['vy-lbl-owners-name' => 'Name', 'vy-lbl-owners-email' => 'Email'] as $id => $label) {
        expect(ordText($xp->query('.//label[@for="'.$id.'"]', $dialog)->item(0)))->toBe($label);
    }
});

it('⚠️⚠️ offers a unit only while it has no owner — a taken unit says whose it is and cannot be picked', function () {
    $s = seedScenario(2);
    $amara = owpOwner($s);
    $s['units'][0]->update(['owner_id' => $amara->id]);

    $xp = ordXPath(ordPage($this, owpAdmin($s)));
    $taken = $xp->query('//*[@role="dialog"]//input[@name="unit_ids[]" and @value="'.$s['units'][0]->id.'"]')->item(0);
    $free = $xp->query('//*[@role="dialog"]//input[@name="unit_ids[]" and @value="'.$s['units'][1]->id.'"]')->item(0);

    expect($taken)->not->toBeNull()->and($taken->hasAttribute('disabled'))->toBeTrue()
        ->and(ordText($taken->parentNode))->toContain('Owned by Amara Okafor')
        ->and($free)->not->toBeNull()->and($free->hasAttribute('disabled'))->toBeFalse();
});

// ── The page ─────────────────────────────────────────────────────────────────────────────────────

it('shows four figures that agree with the rows — owners, ready to pay, on hold and needs review', function () {
    $s = seedScenario(1);
    $ready = owpOwner($s, 'Ready Owner');
    owpStatement($ready, '2026-09', 30000);
    $held = owpOwner($s, 'Held Owner', ['tax_id' => null]);
    owpStatement($held, '2026-09', 44280);

    $xp = ordXPath(ordPage($this, owpAdmin($s)));
    $kpi = fn (string $label): string => ordText($xp->query('//*['.ordCls('vy-dash-kpi').'][.//*['.ordCls('vy-dk-label').' and normalize-space(.)="'.$label.'"]]//*['.ordCls('vy-dk-val').']')->item(0));

    expect($kpi('Owners'))->toBe('2')
        ->and($kpi('Ready to pay'))->toBe('€300.00')
        ->and($kpi('On hold'))->toBe('1')
        ->and($kpi('Needs review'))->toBe('0')
        ->and(ordText($xp->query('//*['.ordCls('vy-dash-kpi').'][.//*[normalize-space(.)="On hold"]]//*['.ordCls('vy-dk-sub').']')->item(0)))->toContain('€442.80');
});

it('lists owners in a sortable table, with chips that count what clicking them shows and the reason beside each hold', function () {
    $s = seedScenario(1);
    $held = owpOwner($s, 'Held Owner', ['tax_id' => null]);
    owpStatement($held, '2026-09', 44280);
    owpOwner($s, 'Quiet Owner');

    $xp = ordXPath(ordPage($this, owpAdmin($s)));

    $chips = [];
    foreach ($xp->query('//*['.ordCls('vy-gl-chips').']//a['.ordCls('vy-gl-chip').']') as $chip) {
        $chips[trim(str_replace(ordText($xp->query('.//*['.ordCls('vy-gl-count').']', $chip)->item(0)), '', ordText($chip)))] = ordText($xp->query('.//*['.ordCls('vy-gl-count').']', $chip)->item(0));
    }
    expect($chips)->toBe(['All' => '2', 'Ready to pay' => '0', 'On hold' => '1', 'Needs review' => '0', 'Payouts paused' => '0', 'Invited' => '0']);

    $sorts = array_map(fn ($a) => ordText($a), iterator_to_array($xp->query('//table['.ordCls('vy-gl-table').']//a['.ordCls('vy-gl-sort').']')));
    expect($sorts)->toBe(['Owner', 'Units', 'Net payable', 'Status']);

    $row = $xp->query('//tr[@data-gl-id="'.$held->id.'"]')->item(0);
    expect(ordText($row))->toContain('On hold')->toContain('Waiting for the owner’s tax details')->toContain('€442.80');
});

it('⚠️ shows invites waiting to be accepted, each with Resend and Cancel for a person who may manage users', function () {
    Mail::fake();
    $s = seedScenario(1);
    $admin = owpAdmin($s);   // first: it provisions the organisation's roles, which a staff (accountant) invite needs
    app(OwnerInviteService::class)->invite('Leon Hart', 'leon@owner.test', [], 18.0, 'owner');
    app(OwnerInviteService::class)->invite('Morgan Books', 'morgan@staff.test', [], null, 'accountant');
    $leon = OwnerInvite::query()->where('email', 'leon@owner.test')->firstOrFail();

    $xp = ordXPath(ordPage($this, $admin));
    $waiting = $xp->query('//*[@id="vyo-invites"]')->item(0);
    expect($waiting)->not->toBeNull();

    $rows = $xp->query('.//*['.ordCls('vyo-invite').']', $waiting);
    expect($rows->length)->toBe(2);
    $leonRow = $xp->query('.//*['.ordCls('vyo-invite').'][.//*[contains(., "leon@owner.test")]]', $waiting)->item(0);
    expect(ordText($leonRow))->toContain('Owner')->toContain('expires')
        ->and($xp->query('.//form[@action="'.route('app.owners.invites.cancel', $leon->id).'"]', $leonRow)->length)->toBe(1)
        ->and($xp->query('.//button[normalize-space(.)="Resend"]', $leonRow)->length)->toBe(1);

    // the invited owner is in the list too, marked as invited
    expect(ordText($xp->query('//tr[@data-gl-id="'.$leon->owner_id.'"]')->item(0)))->toContain('Invited');
});

it('⚠️ offers no invite control to a role that may read owners but not manage users', function () {
    Mail::fake();
    $s = seedScenario(1);
    app(OwnerInviteService::class)->invite('Leon Hart', 'leon@owner.test', [], null, 'owner');

    $html = ordPage($this, operatorUser($s, [], 'Hotel Manager'));
    $xp = ordXPath($html);

    expect($xp->query('//*[@role="dialog" and @aria-labelledby="vy-invite-title"]')->length)->toBe(0)
        ->and($xp->query('//button[normalize-space(.)="Add owner"]')->length)->toBe(0)
        ->and($xp->query('//button[normalize-space(.)="Resend"]')->length)->toBe(0)
        ->and($html)->toContain('leon@owner.test')    // the invite is still visible — reading is allowed
        // the payout run is `payouts.manage` — a link to it would be a 403 for this role ("never invite a 403")
        ->and($html)->not->toContain('Open the payout run')
        ->and(ordPage($this, owpAdmin($s)))->toContain('Open the payout run');
});

it('⚠️ brings every form on the page back to the list as it was', function () {
    Mail::fake();
    $s = seedScenario(1);
    app(OwnerInviteService::class)->invite('Leon Hart', 'leon@owner.test', [], null, 'owner');
    owpOwner($s, 'Quiet Owner');

    $xp = ordXPath(ordPage($this, owpAdmin($s)));
    $forms = $xp->query('//*['.ordCls('vyo-root').']//form[@method="POST"]');
    expect($forms->length)->toBeGreaterThanOrEqual(4);   // invite, resend, cancel, pause
    foreach ($forms as $form) {
        $label = $form->getAttribute('action') ?: $form->getAttribute('x-bind:action');
        expect($xp->query('.//input[@name="return" and @value="list"]', $form)->length)->toBe(1, "{$label} does not come back to the list")
            ->and($xp->query('.//input[@name="return_query"]', $form)->length)->toBe(1);
    }
});

it('⚠️⚠️ gives every class the Owners view uses a rule in the stylesheet — an unstyled label is how this dialog broke', function () {
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/owners.blade.php')));
    // the page's own <style> counts too — the contrast-pinned chip and label rules live there
    preg_match_all('/<style>(.*?)<\/style>/s', $source, $styles);
    $css .= implode("\n", $styles[1]);

    preg_match_all('/\sclass="([^"]*)"/', $source, $m);
    $missing = [];
    foreach ($m[1] as $attr) {
        $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
        foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            // a prefix finished at render time (`vy-paystatus--{{ key }}`) is not a class of its own
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

it('⚠️ exports the view on screen — the Export link takes the address of a filter or sort changed in place', function () {
    $s = seedScenario(1);
    owpOwner($s, 'Quiet Owner');

    $xp = ordXPath(ordPage($this, owpAdmin($s), '?sort=units'));
    $export = $xp->query('//a[contains(@href, "/owners/export")]')->item(0);

    // without script the link the server wrote is this view's own; with it, the view's address at the moment of the click
    // (🖥 before: sorting in place left the link on the address the page was loaded with)
    expect($export)->not->toBeNull()
        ->and($export->getAttribute('href'))->toBe(route('app.owners.export', ['sort' => 'units']))
        ->and($export->getAttribute('data-export-base'))->toBe(route('app.owners.export'))
        ->and($export->getAttribute('x-on:click'))->toContain('location.search');
});
