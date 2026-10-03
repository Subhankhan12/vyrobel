<?php

declare(strict_types=1);

use App\Models\Payout;
use App\Models\User;
use App\Services\OwnerPayoutService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE PAYOUT RUN PAGE (2026-10-03, the founder: "all the sort of work you have done in the previous pages … in this module
// … make sure everything works very well"). Every write on this page records money owed or paid; each now asks first, says
// what it records and that it moves no money, and the run button is not offered while the payout engine is off. Each test
// failed first against the page as it stood.

function prpPage(User $actor): string
{
    return (string) test()->actingAs($actor)->get(route('app.payouts.run'))->assertOk()->getContent();
}

function prpXPath(string $html): DOMXPath
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

/** @return array<string, mixed> the confirm step a form asks before it posts */
function prpConfirm(DOMXPath $xp, string $action, ?string $field = null, ?string $value = null): array
{
    $forms = iterator_to_array($xp->query('//form[@action="'.$action.'"]'));
    if ($field !== null) {
        $forms = array_values(array_filter($forms, fn (DOMElement $f) => $xp->query('.//input[@name="'.$field.'" and @value="'.$value.'"]', $f)->length === 1));
    } else {
        $forms = array_values(array_filter($forms, fn (DOMElement $f) => $xp->query('.//input[@name="statement_id" or @name="payout_id"]', $f)->length === 0));
    }
    expect($forms)->toHaveCount(1);
    expect($forms[0]->getAttribute('x-on:submit'))->toBe('ask($event)');

    return json_decode($forms[0]->getAttribute('data-confirm'), true, 512, JSON_THROW_ON_ERROR);
}

it('⚠️ offers no Run payouts now while the payout engine is off — it says why instead (D-PAYRUN-BUTTON)', function () {
    config(['services.payouts.engine_enabled' => false]);
    $s = seedScenario(1);

    $html = prpPage(owpAdmin($s));

    // 🔬 before: the button was offered, and pressing it could only be refused ("The payout engine is disabled …")
    expect($html)->not->toContain('action="'.route('app.payouts.run-now').'"')
        ->and($html)->toContain('The automatic payout engine is switched off');
});

it('offers Run payouts now, behind a confirm, when the engine is on', function () {
    config(['services.payouts.engine_enabled' => true]);
    $s = seedScenario(1);

    $confirm = prpConfirm(prpXPath(prpPage(owpAdmin($s))), route('app.payouts.run-now'));

    expect($confirm['title'])->toBe('Run payouts now?')
        ->and($confirm['button'])->toBe('Run payouts');
});

it('⚠️⚠️ asks before creating a payout — and says it records what is owed and moves no money', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, 'Inês Carvalho');
    $statement = owpStatement($owner, '2026-07', 48200);

    $xp = prpXPath(prpPage(owpAdmin($s)));

    // 🔬 before: one click posted, with nothing between the button and the record
    $one = prpConfirm($xp, route('app.payouts.create'), 'statement_id', (string) $statement->id);
    expect($one['title'])->toBe('Create a payout for Inês Carvalho?')
        ->and($one['button'])->toBe('Create payout')
        ->and(implode(' ', $one['facts']))->toContain('moves no money');

    $all = prpConfirm($xp, route('app.payouts.create'));
    expect($all['title'])->toBe('Create 1 payout?')
        ->and($all['button'])->toBe('Create 1 payout');
});

it('⚠️⚠️ asks before marking a payout paid — and says Vyrobel records it, it does not send it', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, 'Inês Carvalho');
    $statement = owpStatement($owner, '2026-07', 48200);
    test()->actingAs(owpAdmin($s));
    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);
    expect($payout)->toBeInstanceOf(Payout::class);

    $xp = prpXPath(prpPage(owpAdmin($s)));

    $one = prpConfirm($xp, route('app.payouts.pay'), 'payout_id', (string) $payout->id);
    expect($one['title'])->toBe('Mark '.MoneyFormatter::display(48200, 'EUR').' to Inês Carvalho as paid?')
        ->and($one['button'])->toBe('Mark paid')
        ->and(implode(' ', $one['facts']))->toContain('does not send money');

    $all = prpConfirm($xp, route('app.payouts.pay'));
    expect($all['title'])->toBe('Mark 1 payout paid?');
});

it('opens the confirm as the shared centred dialog', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, 'Inês Carvalho');
    owpStatement($owner, '2026-07', 48200);

    $xp = prpXPath(prpPage(owpAdmin($s)));
    $dialog = $xp->query('//*[@role="dialog" and @aria-labelledby="vyo-pr-confirm-t"]')->item(0);

    expect($dialog)->toBeInstanceOf(DOMElement::class)
        ->and(preg_split('/\s+/', $dialog->parentNode->getAttribute('class')))->toContain('vy-modal-backdrop')
        ->and(preg_split('/\s+/', $dialog->getAttribute('class')))->toContain('vyo-dlg')
        ->and($dialog->getAttribute('x-trap.noscroll'))->toBe('confirmOpen');
});

it('⚠️ uses no inline style — every display is a class, and every class has a rule', function () {
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/payout-run.blade.php')));
    expect(substr_count($source, 'style="'))->toBe(0);

    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    preg_match_all('/\sclass="([^"]*)"/', $source, $m);
    $missing = [];
    foreach ($m[1] as $attr) {
        $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
        foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (! str_ends_with($token, '-') && preg_match('/^vyo?-[a-z0-9-]+$/', $token)
                && preg_match('/\.'.preg_quote($token, '/').'(?![a-zA-Z0-9_-])/', $css) !== 1) {
                $missing[] = $token;
            }
        }
    }
    expect(array_values(array_unique($missing)))->toBe([]);
});
