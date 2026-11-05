<?php

declare(strict_types=1);

use App\Exceptions\Owner\StatementSealedException;
use App\Http\Controllers\Operator\InboxActionController;
use App\Http\Controllers\Operator\PayoutRunActionController;
use App\Models\OwnerStatement;
use App\Models\Plan;
use App\Services\OwnerStatementService;
use App\Support\Billing\UniquePlanTierCycle;
use App\Support\Pdf\GuestReversalDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// I18N-01 · the leftovers pass (2026-11-05) — English stays the words it was; every word a person reads now goes through a key.

uses(DatabaseTransactions::class);

afterEach(fn () => app()->setLocale('en'));

/** A test language whose words are known: the given keys carry a marker, every other key falls back to English. */
function ilPretend(array $lines): void
{
    Lang::addLines($lines, 'xx');
    app()->setLocale('xx');
}

function ilSource(string $path): string
{
    return (string) file_get_contents(base_path($path));
}

it('A · a booking limit of one night says "night", a larger one says "nights"', function () {
    $key = 'livewire/operator/new-reservation.too_long';
    expect(trans_choice($key, 1, ['count' => 1]))->toBe('A booking can be at most 1 night — pick a shorter stay.')
        ->and(trans_choice($key, 30, ['count' => 30]))->toBe('A booking can be at most 30 nights — pick a shorter stay.');
});

it('B · the full stop that ends an email sentence is a key, not a hard-coded Latin dot (English unchanged)', function () {
    expect(__('common.sentence_end'))->toBe('.');
    foreach (['cancellation', 'booking-confirmation'] as $view) {
        $source = ilSource("resources/views/emails/{$view}.blade.php");
        expect($source)->toContain("__('common.sentence_end')");   // read from the key (cancellation holds it in a variable: lang:check forbids two translations glued side by side)
    }
    expect(ilSource('resources/views/emails/cancellation.blade.php'))->not->toContain('{{ $sourceLine }}.</p>')
        ->and(ilSource('resources/views/emails/booking-confirmation.blade.php'))->not->toContain('@endif.</p>');
    ilPretend(['common.sentence_end' => '。']);
    expect(__('common.sentence_end'))->toBe('。');
});

it('C · the payout run says its mode in the reader\'s words, not the raw code', function () {
    $call = fn (string $mode) => (new ReflectionMethod(PayoutRunActionController::class, 'modeWord'))->invoke(new PayoutRunActionController, $mode);
    expect($call('test'))->toBe('test')->and($call('live'))->toBe('live');
    ilPretend(['php/http/controllers/operator/payout_run_action_controller.mode_test' => 'XX-TEST']);
    expect($call('test'))->toBe('XX-TEST')->and($call('live'))->toBe('live');
});

it('D + E · a room type\'s name label and a unit\'s Paused tag have their own keys (English unchanged)', function () {
    $view = ilSource('resources/views/livewire/units-list.blade.php');
    expect(substr_count($view, "__('livewire/units-list.room_type_name_label')"))->toBe(2)
        ->and($view)->not->toContain("__('common.name')")
        ->and($view)->toContain('tone-paused">{{ __(\'livewire/units-list.unit_paused_tag\') }}')
        ->and(substr_count($view, "__('livewire/units-list.paused')"))->toBe(1);   // the KPI keeps the old key
    expect(__('livewire/units-list.room_type_name_label'))->toBe('Name')
        ->and(__('livewire/units-list.unit_paused_tag'))->toBe('Paused')
        ->and(__('livewire/units-list.paused'))->toBe('Paused');
});

it('F · a sealed statement is refused with a typed exception, and the controller shows a translated reason, never the exception\'s message', function () {
    $sealed = new OwnerStatement;
    $sealed->locked = true;
    expect(fn () => app(OwnerStatementService::class)->finalize($sealed))->toThrow(StatementSealedException::class);
    expect(StatementSealedException::cannotRegenerate())->toBeInstanceOf(RuntimeException::class);

    $controller = ilSource('app/Http/Controllers/Operator/OwnerManageController.php');
    expect($controller)->not->toContain("['reason' => \$e->getMessage()]")
        ->and($controller)->toContain('StatementSealedException');
    expect(__('php/http/controllers/operator/owner_manage_controller.period_sealed'))->toBe('Cannot regenerate a finalized/locked statement.');
    ilPretend(['php/http/controllers/operator/owner_manage_controller.period_sealed' => 'XX-SEALED']);
    expect(__('php/http/controllers/operator/owner_manage_controller.period_sealed'))->toBe('XX-SEALED');
});

it('G · the plan-clash message names the billing interval through a key', function () {
    $code = 'tiercycle_'.Str::lower(Str::random(8));
    Plan::create(['code' => $code.'_monthly', 'name' => 'Probe', 'pricing_type' => Plan::PRICING_FLAT, 'price_minor' => 100, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false]);
    $message = function () use ($code): string {
        $v = Validator::make(['code' => $code, 'interval' => 'monthly'], ['code' => [(new UniquePlanTierCycle)]]);

        return $v->errors()->first('code');
    };
    expect($message())->toContain('for the monthly interval');
    ilPretend(['php/support/billing/unique_plan_tier_cycle.interval_monthly' => 'XX-MONTH']);
    expect($message())->toContain('for the XX-MONTH interval')->not->toContain('monthly interval');
});

it('H · the refund receipt names the manual rail through a key; a brand stays a brand (English unchanged)', function () {
    $label = fn (string $g) => (new ReflectionMethod(GuestReversalDocument::class, 'methodLabel'))->invoke(null, $g);
    expect($label('manual'))->toBe('Manual')->and($label('stripe'))->toBe('Stripe')->and($label('bank_transfer'))->toBe('Bank transfer');
    ilPretend(['pdfs/guest-refund-receipt.method_manual' => 'XX-MANUAL']);
    expect($label('manual'))->toBe('XX-MANUAL')->and($label('stripe'))->toBe('Stripe');
});

it('I · the front desk joins two dates through a key, English "12 Nov–14 Nov" unchanged', function () {
    $view = ilSource('resources/views/livewire/front-desk.blade.php');
    expect($view)->not->toContain('<span x-text="row.check_in_label"></span>–<span')
        ->and(substr_count($view, 'livewire/front-desk.date_range'))->toBe(2);
    expect(__('livewire/front-desk.date_range', ['from' => '12 Nov', 'to' => '14 Nov']))->toBe('12 Nov–14 Nov');
});

it('K · the owner Overview button says it messages the management team, not guests', function () {
    expect(__('livewire/owner/dashboard.message_guests'))->toBe('Message the management team')
        ->and(__('livewire/owner/dashboard.message_guests'))->not->toContain('guests');
});

it('L + O · the check-in refusals tell staff what to do with the real control names', function () {
    $out = __('php/actions/reservations/check_in_reservation.refused_checked_out');
    expect($out)->toContain('use Reverse check-out')->not->toContain('is not available')
        ->and(__('livewire/reservation-detail.reverse_check_out'))->toBe('Reverse check-out');
    expect(__('php/actions/reservations/check_in_reservation.refused_no_show'))->toContain('take a Walk-in on the front desk')
        ->and(__('livewire/front-desk.walk_in'))->toBe('Walk-in');
});

it('M · the agreement fee line says "management" once', function () {
    expect(__('livewire/owner/agreement.fee_covers_sub_no_rate'))->toBe('Included in the management fee — no extra charge.');
});

it('N · staff screens do not say "Stripe seam"', function () {
    expect(__('livewire/partials/fd-collect-form.hint'))->not->toContain('seam')->toContain('not switched on here yet')
        ->and(__('livewire/front-desk.card_terminal_hint'))->not->toContain('seam')->toContain('not switched on here yet');
});

it('P · an invalid owner-team invitation does not blame an operator', function () {
    expect(__('auth/invite-invalid.body'))->toBe('It may have expired or already been used. Please ask whoever invited you to send a new invitation.');
});

it('Q · the owner password dialog does not talk about a "remember me" nobody can tick', function () {
    expect(__('livewire/owner/owner-security.change_password_sub'))->not->toContain('remember me')
        ->and(ilSource('resources/views/auth/login.blade.php'))->not->toContain('remember');
});

it('R · the room-moves note names no class', function () {
    expect(__('livewire/reservation-detail.room_moves_note'))->not->toContain('AssignmentService');
});

it('S · the statement PDF\'s closing fees sentence starts with a capital', function () {
    expect(__('pdfs/owner-statement.fees', ['currency' => 'EUR']))->toStartWith('Payment and platform fees');
});

it('T · a pause with no resume date never prints "until ."', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');
    $s['org']->forceFill(['readonly_from' => now()->subDay()])->save();

    $this->actingAs($admin)->post(route('app.portfolio.store'), []);
    expect((string) session('portfolio_error'))->toBe('Workspace paused — adding a property is read-only for now.');

    $this->actingAs($admin)->post(route('app.portfolio.update'), []);
    expect((string) session('portfolio_error'))->toBe('Workspace paused — editing a property is read-only for now.');

    // its sibling in the Inbox says the same, never "until ."
    $message = (new ReflectionMethod(InboxActionController::class, 'pausedMessage'))->invoke(app(InboxActionController::class));
    expect($message)->toBe('Workspace paused — messaging is read-only for now.');
});

it('T · with a resume date the English is exactly as before', function () {
    expect(__('php/http/controllers/operator/property_controller.paused_adding', ['resume' => '5 Oct']))->toBe('Workspace paused — adding a property is read-only until 5 Oct.')
        ->and(__('php/http/controllers/operator/property_controller.paused_editing', ['resume' => '5 Oct']))->toBe('Workspace paused — editing a property is read-only until 5 Oct.');
});

it('U · API and OTA are written as acronyms', function () {
    expect(__('php/livewire/operator/operator_roles.section_api'))->toBe('API')
        ->and(__('php/livewire/operator/operator_search.source_ota'))->toBe('OTA');
});
