<?php

use App\Models\TaxRate;
use App\Services\TaxRateService;
use App\Support\Countries;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * THE RESIDENCY DROPDOWN COULD ONLY MAKE THE DATA WRONG (audit 2026-08-07 · §5 parity).
 *
 * The owner Tax Center's residency <select> emitted country NAMES as its option values:
 *
 *     <option value="Portugal">Portugal</option>
 *
 * but `owners.tax_country` is an ISO-3166-1 alpha-2 CODE by contract. The `tax:set-rate` command's own
 * signature says so ("matches owners.tax_country, e.g. PT"), the seeded example rates are keyed `PT`,
 * and the demo owner's stored value is `PT`.
 *
 * ⚠️ SO THIS WAS A MONEY DEFECT, NOT A COSMETIC ONE. Two services look a configured rate up by that
 * exact column — WithholdingService (a REAL deduction from a REAL payout) and OwnerVatService. Choosing
 * a residency in the UI wrote "Portugal", which matched no configured rate, so the withholding silently
 * computed ZERO. Silent in the worst direction: the founder configures PT, the owner picks "Portugal",
 * the rate never applies, and nothing anywhere raises an error.
 *
 * Pinned below: the lookup really does miss on a name, the shared list really does normalise it, and
 * the option VALUES are codes so the UI cannot reintroduce the mismatch.
 */

it('proves the defect — a display name matches NO configured rate', function () {
    TaxRate::query()->create([
        'jurisdiction' => 'PT',
        'rate_type' => TaxRate::TYPE_WITHHOLDING,
        'percent' => 25.0,
        'effective_from' => now()->subYear()->toDateString(),
        'is_example' => false,
        'note' => 'test',
    ]);

    $service = app(TaxRateService::class);

    // What the old dropdown stored — no match, and therefore a silent zero withholding.
    expect($service->rateFor('Portugal', TaxRate::TYPE_WITHHOLDING))->toBeNull();

    // What the column is contracted to hold — the rate the founder actually configured.
    expect($service->rateFor('PT', TaxRate::TYPE_WITHHOLDING))->not->toBeNull();
});

it('normalises a display name to the ISO code that the rate lookup uses', function () {
    expect(Countries::toCode('Portugal'))->toBe('PT')
        ->and(Countries::toCode('portugal'))->toBe('PT')
        ->and(Countries::toCode('pt'))->toBe('PT')
        ->and(Countries::toCode('PT'))->toBe('PT')
        ->and(Countries::toCode('United Kingdom'))->toBe('GB');

    expect(Countries::toCode(null))->toBeNull()
        ->and(Countries::toCode('   '))->toBeNull();

    // ⚠️ An unrecognised value is PASSED THROUGH, never silently dropped — normalising must not lose
    // something an operator deliberately entered.
    expect(Countries::toCode('Kosovo'))->toBe('Kosovo');
});

it('renders the residency select with ISO codes as option values, names as labels', function () {
    $options = Countries::options();

    expect($options)->toHaveKey('PT')
        ->and($options['PT'])->toBe('Portugal')
        ->and(array_keys($options))->each->toMatch('/^[A-Z]{2}$/');

    // The Blade emits `value="{{ $code }}"` — the exact line that was wrong. Guard the markup, since
    // this defect is invisible to every static check and to any test that only reads the model.
    $blade = file_get_contents(resource_path('views/livewire/owner/tax-center.blade.php'));

    expect($blade)
        ->toContain('Countries::options()')
        ->toContain('<option value="{{ $code }}"')
        ->not->toContain("\$countries = ['Portugal'");
});

it('keeps a legacy stored name working instead of blanking the owner\'s residency', function () {
    // A row written by the OLD dropdown must still resolve — both for display and for the lookup once
    // it is normalised on the next save.
    expect(Countries::name('Portugal'))->toBe('Portugal')
        ->and(Countries::name('PT'))->toBe('Portugal')
        ->and(Countries::isKnownCode('Portugal'))->toBeFalse()
        ->and(Countries::isKnownCode('PT'))->toBeTrue();
});
