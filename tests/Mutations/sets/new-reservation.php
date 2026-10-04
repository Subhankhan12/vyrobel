<?php

declare(strict_types=1);

// Mutation set new-reservation — the New reservation pass (2026-10-04, the founder: "it is not possible to do reservation"):
// a missing price set from inside the booking through the Rates write's own rules, the property asked for under "All
// properties" and named when it has no rooms, a refused booking that keeps everything entered (never a new guest's email or
// phone, #28) and reopens at the step to fix, the override that no longer 500s on "Other" without a note, a refused booking
// that leaves no new guest behind, and money shown as the folio charges it (a unit's own price, the folio's taxes and fees,
// the stay's currency). Run with `php tests/Mutations/run.php --set=new-reservation`.
// ⚠️ The wizard's client-side checks (each step's "what's missing", the comma guard on amounts, the stepper that cannot skip
// an unfinished step, the draft kept per property) are JavaScript: browser-verified on the walk, not proven here.

$wizard = 'tests/Feature/NewReservationWizardTest.php';
$xshow = 'tests/Feature/XShowInlineDisplayContractTest.php';

$create = 'app/Http/Controllers/Operator/ReservationCreateController.php';
$rates = 'app/Http/Controllers/Operator/RateActionController.php';
$page = 'app/Livewire/Operator/NewReservation.php';
$view = 'resources/views/livewire/operator/new-reservation.blade.php';

return [
    // ── A missing price, set here ────────────────────────────────────────────────────────────────────
    [
        'id' => 'NR-M1',
        'name' => 'a price set from the wizard goes to the Rates page again, and the booking is lost',
        'file' => $rates,
        'old' => "        return \$request->input('return') === self::RETURN_WIZARD;",
        'new' => '        return false;',
        'tests' => [['file' => $wizard, 'filter' => 'brings a refused price back to the wizard']],
    ],
    [
        'id' => 'NR-M2',
        'name' => 'the wizard is told the Rates summary, not that the price is set and a unit can be picked',
        'file' => $rates,
        'old' => '        if ($this->returnsToWizard($request) && $setRate && count($roomTypes) === 1) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $wizard, 'filter' => 'lets a person who manages rates set the price']],
    ],
    [
        'id' => 'NR-M3',
        'name' => 'no way to set the missing price from the wizard',
        'file' => $view,
        'old' => "                                            @if (\$rt['has_plan'] && \$canSetPrice)",   // @if since the second pass
        'new' => '                                            @if (false)',
        'tests' => [['file' => $wizard, 'filter' => 'lets a person who manages rates set the price']],
    ],
    [
        'id' => 'NR-M4',
        'name' => 'the price form is offered to someone who cannot set prices',
        'file' => $page,
        'old' => "        \$canSetPrice = (bool) auth()->user()?->can('rates.manage');",   // computed once since the second pass
        'new' => '        $canSetPrice = true;',
        'tests' => [['file' => $wizard, 'filter' => 'tells a person who cannot set prices who can']],
    ],
    // ── Which property ───────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'NR-M5',
        'name' => 'under "All properties" the booking goes silently into the first property again',
        'file' => $page,
        'old' => '        $chooseProperty = $done === null && $this->viewingAllProperties && count($properties) > 1;',
        'new' => '        $chooseProperty = false;',
        'tests' => [['file' => $wizard, 'filter' => 'asks which property when']],
    ],
    [
        'id' => 'NR-M6',
        'name' => 'a property with no rooms shows the wizard and "nothing is free" again',
        'file' => $view,
        'old' => "    @elseif (\$rooms['bookable'] === 0)",
        'new' => '    @elseif (false)',
        'tests' => [['file' => $wizard, 'filter' => 'says a property has no rooms yet']],
    ],
    [
        'id' => 'NR-M7',
        'name' => 'no way to see or switch the property from the wizard',
        'file' => $view,
        'old' => '                @if ($hasProperty && count($properties) > 1)',
        'new' => '                @if (false)',
        'tests' => [['file' => $wizard, 'filter' => 'shows which property you are booking into']],
    ],
    [
        'id' => 'NR-M8',
        'name' => 'the chooser no longer says which property has no rooms',
        'file' => $page,
        'old' => "'rooms' => (int) (\$open[(string) \$p->getKey()] ?? 0),",
        'new' => "'rooms' => 0,",
        'tests' => [['file' => $wizard, 'filter' => 'asks which property when']],
    ],
    // ── A refusal keeps the booking ──────────────────────────────────────────────────────────────────
    [
        'id' => 'NR-M9',
        'name' => 'a refused booking comes back empty again',
        'file' => $create,
        'old' => '            ->withInput($request->except(self::NEVER_ECHOED));',
        'new' => '            ->withInput([]);',
        'tests' => [['file' => $wizard, 'filter' => 'keeps everything entered when a booking is refused']],
    ],
    [
        'id' => 'NR-M10',
        'name' => 'a new guest\'s email and phone are flashed into the session with the rest (#28)',
        'file' => $create,
        'old' => "    private const NEVER_ECHOED = ['_token', 'guest_email', 'guest_phone', 'restriction_override_reason', 'restriction_override_note', 'restriction_override_seen'];",
        'new' => "    private const NEVER_ECHOED = ['_token', 'restriction_override_reason', 'restriction_override_note', 'restriction_override_seen'];",
        'tests' => [['file' => $wizard, 'filter' => 'never echoes a new guest']],
    ],
    [
        'id' => 'NR-M11',
        'name' => 'a refused payment reopens at step 1, not at Confirm (Payment, before the second pass)',
        'file' => $create,
        'old' => "                return \$this->back(\$request, 'Enter a valid payment amount, e.g. 150 or 150.00.', NewReservation::STEP_CONFIRM);",
        'new' => "                return \$this->back(\$request, 'Enter a valid payment amount, e.g. 150 or 150.00.');",
        'tests' => [['file' => $wizard, 'filter' => 'keeps everything entered when a booking is refused']],
    ],
    [
        'id' => 'NR-M12',
        'name' => 'the step to reopen is not sent back',
        'file' => $create,
        'old' => "            ->with('new_reservation_step', \$step)",
        'new' => "            ->with('new_reservation_step', 1)",
        'tests' => [['file' => $wizard, 'filter' => 'says a restricted booking was refused in plain words']],
    ],
    [
        'id' => 'NR-M13',
        'name' => 'a still-free unit is dropped from the restored booking',
        'file' => $page,
        'old' => "        if (! is_array(\$rt) || ! \$rt['priced'] || ! collect(\$rt['units'])->contains('id', \$unitId)) {",
        'new' => '        if (true) {',
        'tests' => [['file' => $wizard, 'filter' => 'reopens with the override and the whole booking']],
    ],
    [
        'id' => 'NR-M14',
        'name' => 'the restored state never reaches the page',
        'file' => $page,
        'old' => '        $restored = $property !== null ? $this->restored($roomTypes, $extras) : null;',
        'new' => '        $restored = null;',
        'tests' => [['file' => $wizard, 'filter' => 'keeps everything entered when a booking is refused']],
    ],
    // ── The override and a refused booking's guest ───────────────────────────────────────────────────
    [
        'id' => 'NR-M15',
        'name' => '"Other" without a note — or an unknown reason — reaches the service and 500s again',
        'file' => $create,
        'old' => "        if (\$overrideReason !== '' && \$request->user()?->can('reservations.restriction_override') === true) {",
        'new' => '        if (false) {',
        'tests' => [
            ['file' => $wizard, 'filter' => 'never sends a booking to an error page'],
            ['file' => $wizard, 'filter' => 'refuses an unknown override reason'],
        ],
    ],
    [
        'id' => 'NR-M16',
        'name' => 'the banner shows the engine\'s sentence on a first refusal again',
        'file' => $create,
        'old' => "            \$notice = \$overrideReason !== ''",
        'new' => '            $notice = true',
        'tests' => [['file' => $wizard, 'filter' => 'says a restricted booking was refused in plain words']],
    ],
    [
        'id' => 'NR-M17',
        'name' => 'a restriction is listed as its code again',
        'file' => $page,
        'old' => "                'min_stay' => 'Arriving '.\$date.', a stay must be at least '.\$value.' '.\$nightsWord,",
        'new' => "                'min_stay' => 'min_stay on '.\$date,",
        'tests' => [['file' => $wizard, 'filter' => 'never sends a booking to an error page']],
    ],
    [
        'id' => 'NR-M18',
        'name' => 'a refused booking leaves the new guest\'s record behind again',
        'file' => $create,
        'old' => '            if (! isset($reservation) && $guest->wasRecentlyCreated',
        'new' => '            if (false',
        'tests' => [['file' => $wizard, 'filter' => 'leaves no guest behind when a booking is refused']],
    ],
    // ── Money as the folio charges it ────────────────────────────────────────────────────────────────
    [
        'id' => 'NR-M19',
        'name' => 'a unit\'s own price is hidden behind the room type\'s',
        'file' => $page,
        'old' => "            \$units[\$i]['own_price'] = \$own !== null ? MoneyFormatter::display(\$own, \$currency) : null;",
        'new' => "            \$units[\$i]['own_price'] = null;",
        'tests' => [['file' => $wizard, 'filter' => 'shows a unit’s own price']],
    ],
    [
        'id' => 'NR-M20',
        'name' => 'the estimate adds an included VAT again',
        'file' => $page,
        'old' => '$taxes->filter(fn (Tax $t): bool => StayEstimator::taxApplies($t, $currency))',
        'new' => '$taxes->filter(fn (Tax $t): bool => true)',
        'tests' => [['file' => $wizard, 'filter' => 'estimates with the same taxes and fees']],
    ],
    [
        'id' => 'NR-M21',
        'name' => 'the estimate leaves out the cleaning fee again',
        'file' => $page,
        'old' => "                'fees' => \$fees,",
        'new' => "                'fees' => [],",
        'tests' => [['file' => $wizard, 'filter' => 'estimates with the same taxes and fees']],
    ],
    [
        'id' => 'NR-M22',
        'name' => 'a GBP property is priced in euros',
        'file' => $page,
        'old' => "                    ? MoneyFormatter::display(\$nightly[0], \$currency).' a night'",
        'new' => "                    ? MoneyFormatter::display(\$nightly[0], 'EUR').' a night'",
        'tests' => [['file' => $wizard, 'filter' => 'shows prices in the property’s own currency']],
    ],
    // ── Keyboard, design, guests ─────────────────────────────────────────────────────────────────────
    [
        'id' => 'NR-M23',
        'name' => 'the unit radio is hidden from the keyboard again',
        'file' => $view,
        'old' => '<input type="radio" class="vy-sr-only" name="unit_pick"',
        'new' => '<input type="radio" hidden name="unit_pick"',
        'tests' => [['file' => $wizard, 'filter' => 'lets the keyboard pick a unit']],
    ],
    [
        'id' => 'NR-M24',
        'name' => 'an x-show element carries an inline display again',
        'file' => $view,
        'old' => '            <div class="vy-nr-draft" x-show="draftRestored" x-cloak role="status">',
        'new' => '            <div class="vy-nr-draft" x-show="draftRestored" x-cloak role="status" style="display: flex;">',
        'tests' => [
            ['file' => $wizard, 'filter' => 'uses no inline style'],
            ['file' => $xshow, 'filter' => 'leaves no element on the Guests'],
        ],
    ],
    [
        'id' => 'NR-M25',
        'name' => 'same-name guests are identical rows again',
        'file' => $page,
        'old' => "                    'history' => GuestHistory::for(\$guest->reservations)['stat'] ?? 'No stays yet',",
        'new' => "                    'history' => '',",
        'tests' => [['file' => $wizard, 'filter' => 'tells same-name guests apart']],
    ],
    // ── The independent review's findings ────────────────────────────────────────────────────────────
    [
        'id' => 'NR-M26',
        'name' => 'a guest is removed even when a saved booking points at it (a failure after the commit)',
        'file' => $create,
        'old' => "                && ! Reservation::withTrashed()->where('lead_guest_id', \$guest->getKey())->exists()) {",
        'new' => '                ) {',
        'tests' => [['file' => $wizard, 'filter' => 'never strips a saved booking of its new guest']],
    ],
    [
        'id' => 'NR-M27',
        'name' => 'a property whose rooms are all closed is said to have none',
        'file' => $page,
        'old' => "'rooms_all' => (int) (\$all[(string) \$p->getKey()] ?? 0)])",
        'new' => "'rooms_all' => 0])",
        'tests' => [['file' => $wizard, 'filter' => 'says when a property’s rooms are all closed']],
    ],
    [
        'id' => 'NR-M28',
        'name' => 'a stay over the limit shows "nothing is free" again',
        'file' => $page,
        'old' => '        $tooLong = $nights > UnitAvailabilityService::MAX_NIGHTS;',
        'new' => '        $tooLong = false;',
        'tests' => [['file' => $wizard, 'filter' => 'says a stay is too long']],
    ],
    [
        'id' => 'NR-M29',
        'name' => 'the booking write no longer names the night limit',
        'file' => $create,
        'old' => '        if (CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut)) > UnitAvailabilityService::MAX_NIGHTS) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $wizard, 'filter' => 'says a stay is too long']],
    ],
    [
        'id' => 'NR-M30',
        'name' => 'the done card shows a booking from a property the person does not work in',
        'file' => $page,
        'old' => '            ->when($context->active(), fn (Builder $q) => $q->whereIn(\'property_id\', $context->accessibleIds()))',
        'new' => '            ->when(false, fn (Builder $q) => $q->whereIn(\'property_id\', $context->accessibleIds()))',
        'tests' => [['file' => $wizard, 'filter' => 'shows the done card only for a booking in a property']],
    ],
    [
        'id' => 'NR-M31',
        'name' => 'a hand-made array becomes a 500 again ("Array to string conversion")',
        'file' => $create,
        'old' => '        return is_scalar($value) ? trim((string) $value) : $default;',
        'new' => '        return trim((string) $value);',
        'tests' => [['file' => $wizard, 'filter' => 'turns hand-made array inputs into a refusal']],
    ],
    [
        'id' => 'NR-M32',
        'name' => 'a hand-made breach list reaches the service unchecked',
        'file' => $create,
        'old' => "            if (is_array(\$b) && in_array(\$b['restriction'] ?? null, ['min_stay', 'max_stay', 'closed_to_arrival', 'closed_to_departure', 'stop_sell'], true)",
        'new' => '            if (true',
        'tests' => [['file' => $wizard, 'filter' => 'turns hand-made array inputs into a refusal']],
    ],
    [
        'id' => 'NR-M33',
        'name' => 'taxes are chosen by the property\'s currency, not the stay\'s',
        'file' => $page,
        'old' => "        \$currencies = array_values(array_unique(array_merge([\$currency], array_column(\$roomTypes, 'currency'))));",
        'new' => '        $currencies = [$currency];',
        'tests' => [['file' => $wizard, 'filter' => 'estimates with the taxes of the stay’s own currency']],
    ],
    [
        'id' => 'NR-M34',
        'name' => '"pick a unit" after a price set on a plan the wizard does not book',
        'file' => $rates,
        'old' => '            if ($primary !== null && (string) $plan->getKey() === (string) $primary->getKey()) {',
        'new' => '            if (true) {',
        'tests' => [['file' => $wizard, 'filter' => 'only says “pick a unit” when the price went on the plan']],
    ],
];
