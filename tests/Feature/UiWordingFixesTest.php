<?php

declare(strict_types=1);

use App\Exceptions\Booking\ReservationAlreadyTerminalException;
use App\Exceptions\Booking\ReservationInProgressException;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationStage;
use App\Support\Reservations\ReservationStatusLabel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;

uses(DatabaseTransactions::class);

// 2026-11-11 (his "do all") — the small wording and layout items of the open list, each pinned where a test can see it.

it('⚠️ D-REFUSAL-STATUS-CODE — a refusal names the status as a word, never the stored code', function () {
    expect(ReservationStatusLabel::for('checked_in'))->toBe('checked in')
        ->and(ReservationStatusLabel::for('no_show'))->toBe('no-show')
        ->and(ReservationStatusLabel::for('checked_out'))->toBe('checked out')
        ->and(ReservationStatusLabel::for('some_new_state'))->toBe('some new state');   // an unknown code is still words

    $terminal = (new ReservationAlreadyTerminalException('checked_out', 'no_show'))->getMessage();
    expect($terminal)->toBe('This reservation is already checked out and cannot be marked no-show.')
        ->and($terminal)->not->toContain('_');

    $inProgress = ReservationInProgressException::messageFor('no_show');
    expect($inProgress)->toContain('cannot be marked no-show')->not->toContain('no_show');
});

it('⚠️ D-REFUSAL-STATUS-CODE — the word follows the reader\'s language (the same map the status badges read)', function () {
    App::setLocale('de');
    expect(ReservationStatusLabel::for('checked_in'))->not->toBe('checked in')->not->toContain('_');
    App::setLocale('en');
});

it('⚠️ D-I18N-ONE-UNITS — "1 night" and "1 unit", never "1 nights", in every language that spelled the plural only', function () {
    $key = fn (string $k): string => 'php/http/controllers/owner/owner_units_export_controller.'.$k;

    expect(trans_choice($key('total_nights'), 1, ['count' => 1]))->toBe('1 night')
        ->and(trans_choice($key('total_nights'), 12, ['count' => 12]))->toBe('12 nights')
        ->and(trans_choice($key('total_units'), 1, ['count' => 1]))->toBe('1 unit')
        ->and(trans_choice($key('total_units'), 0, ['count' => 0]))->toBe('0 units');

    // German, Spanish and Portuguese carried a plural-only string ("1 Nächte", "1 noches", "1 noites")
    foreach (['de' => ['1 Nacht', '1 Einheit'], 'es' => ['1 noche', '1 unidad'], 'pt' => ['1 noite', '1 unidade']] as $lang => [$night, $unit]) {
        App::setLocale($lang);
        expect(trans_choice($key('total_nights'), 1, ['count' => 1]))->toBe($night)
            ->and(trans_choice($key('total_units'), 1, ['count' => 1]))->toBe($unit);
    }
    App::setLocale('en');
});

it('⚠️ D-MT-COPY — the maintenance messages say "work order", and assigning to No one says Unassigned', function () {
    $key = fn (string $k): string => 'php/http/controllers/operator/maintenance_action_controller.'.$k;

    foreach (['ticket_opened', 'cannot_reassign_closed', 'assignment_updated', 'cannot_start', 'work_started', 'ticket_resolved', 'cannot_cancel_closed', 'ticket_cancelled', 'parts_closed'] as $k) {
        expect(__($key($k), ['title' => 'T', 'unit' => 'U1']))->not->toContain('icket')->toContain('ork order');
    }
    expect(trans_choice($key('bulk_unassigned'), 2, ['count' => 2]))->toBe('Unassigned 2 work orders.')
        ->and(trans_choice($key('bulk_unassigned'), 1, ['count' => 1]))->toBe('Unassigned 1 work order.')
        ->and(trans_choice($key('bulk_unassigned_skipped'), 2, ['count' => 2, 'skipped' => 1]))->toBe('Unassigned 2 work orders · 1 skipped (already closed)');
});

it('⚠️ D-NR2-ONE-NIGHT — the New reservation page uses the Calendar\'s one-night wording (already translated), not "from 10 Oct to 10 Oct"', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/operator/new-reservation.blade.php'));

    expect($view)->toContain('rack-new-reservation.price_goes_one')
        ->toContain('rack-new-reservation.for_this_booking')
        ->toContain('rack-new-reservation.season_saved_all')
        ->and($view)->toContain('$nights === 1');
    // the rack's keys exist in English with a singular, which is what makes it right
    expect(trans_choice('livewire/operator/partials/rack-new-reservation.for_this_booking', 1, ['prices' => 1, 'count' => 1]))->toContain('the 1 night of the stay that has no price');
});

it('⚠️ D-NR2-NEXT-NOPRICE — Next says there is no price, in the words of the person\'s own permission', function () {
    expect(__('livewire/operator/new-reservation.miss_unit_no_price'))->toBe('No unit has a price for these dates yet — set one above.')
        ->and(__('livewire/operator/new-reservation.miss_unit_no_price_ask'))->toContain('ask someone who manages rates');
});

it('⚠️ D-NR2-SCOPE-WORDING — the season disclosure no longer says "That count" with no count above it (his ruling)', function () {
    $text = __('livewire/operator/partials/season-apply-disclosure.count_scope');

    expect($text)->toStartWith('Owner-agreed nights are counted only when agreed through a proposal')
        ->and($text)->not->toContain('That count')
        // F-21: the scope stays stated, in full
        ->and($text)->toContain('only while the price still matches what was agreed')
        ->and($text)->toContain('A rate an owner set themselves, or one that has since moved, is not counted here');
});

it('⚠️ D-GB-SYMBOL — the Group booking wizard prints the symbol the rest of the app does (£, not "GBP ")', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/operator/group-booking.blade.php'));

    expect($view)->toContain('MoneyFormatter::symbol(')->toContain('return this.symbol +')
        ->and($view)->not->toContain("'$' : this.currency + ' '");   // the two-currency map is gone
    expect(MoneyFormatter::symbol('GBP'))->toBe('£');
});

it('⚠️ D-CHECKOUT-TYPE — the checkout compares the property\'s TYPE CODE (the label never matched)', function () {
    $view = (string) file_get_contents(resource_path('views/booking/checkout.blade.php'));

    expect($view)->toContain("(\$property['type_code'] ?? '') === 'hotel'")->not->toContain("\$property['type'] === 'hotel'");
    expect((string) file_get_contents(app_path('Http/Controllers/BookingEngineController.php')))->toContain("'type_code' => (string) \$property->type");
});

it('⚠️ D-GS2-STACK-A11Y — the Guests table keeps its table semantics where CSS stacks the rows', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/operator/guests-list.blade.php'));
    $table = substr($view, (int) strpos($view, '<table class="vy-gl-table'), (int) strpos($view, '</table>') - (int) strpos($view, '<table class="vy-gl-table'));

    expect($table)->toContain('role="table"')->toContain('<thead role="rowgroup">')->toContain('<tbody role="rowgroup">');
    // every row, header and cell carries its role — counted tag by tag
    // (a cell's tag may hold a `>` inside a Blade expression, so the roles are counted, not matched tag-by-tag)
    expect(preg_match_all('/<tr\b/', $table))->toBe(substr_count($table, 'role="row"'))
        ->and(preg_match_all('/<th\b/', $table))->toBe(substr_count($table, 'role="columnheader"'))
        ->and(preg_match_all('/<td\b/', $table))->toBe(substr_count($table, 'role="cell"'))
        ->and(preg_match_all('/<td\b/', $table))->toBeGreaterThan(5);
});

it('⚠️ D-STAY-WORD — one spelling: the Guests Stay column and the Inbox read the badge\'s own word', function () {
    expect(ReservationStage::meta(ReservationStage::IN_HOUSE)['label'])->toBe('In-house');

    foreach (['app/Support/Reservations/StayLabel.php', 'app/Livewire/Operator/GuestsList.php'] as $file) {
        expect((string) file_get_contents(base_path($file)))->toContain('ReservationStage::meta(ReservationStage::IN_HOUSE)')
            ->not->toContain("stay_label.in_house'")->not->toContain("guests_list.in_house'");
    }
});

it('⚠️ D-NOTIFPREFS-320 and D-GUEST-TILE-NARROW — the layouts that overflowed now size by their own box', function () {
    $prefs = (string) file_get_contents(resource_path('views/livewire/operator/notification-preferences.blade.php'));
    expect($prefs)->toContain('.vy-np-cols { grid-template-columns: minmax(0, 1fr); }')   // never a bare 1fr (a min-content floor)
        ->toContain('@media (max-width: 420px)')->toContain('minmax(0, 1fr) 44px 44px 44px');

    $tiles = (string) file_get_contents(resource_path('views/guest/reservation.blade.php'));
    expect($tiles)->toContain('container-type: inline-size')->toContain('@container (max-width: 560px)')
        ->not->toContain('min(19px,3.7vw)');
});

it('⚠️ D-SHELL-PROPNAME — the sidebar\'s property box gives its text the room, and the full name is on hover', function () {
    $layout = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    expect($layout)->toContain('.vy-side .vy-propsw select.vy-propsw-select:not([multiple]):not([size]):not([data-native]) { padding-right: 22px !important');

    expect((string) file_get_contents(resource_path('views/components/operator/property-switcher.blade.php')))->toContain('title="{{ $currentName');
});
