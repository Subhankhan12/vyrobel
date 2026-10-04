<?php

declare(strict_types=1);

// Mutation set new-reservation-bookable — the New reservation page's SECOND pass (2026-10-04, the founder again: "it is not
// possible to do reservation … more cool, advanced, minimal and operational"). 🔬 On production his working property had no
// rooms and his other property had never had a price: the €200 "Winter" season he saved was never applied, and the page never
// said so. Run with `php tests/Mutations/run.php --set=new-reservation-bookable`.
// ⚠️ The wizard's script (the four-step walk, the calendar's nightly tags, the email box following the address, the bar on a
// phone) is JavaScript: browser-verified on the walk, not proven here.

$test = 'tests/Feature/NewReservationBookableTest.php';

$page = 'app/Livewire/Operator/NewReservation.php';
$view = 'resources/views/livewire/operator/new-reservation.blade.php';
$seasons = 'app/Http/Controllers/Operator/SeasonActionController.php';
$write = 'app/Http/Controllers/Operator/ReservationCreateController.php';

return [
    // ── His report: the way out of a property with no rooms, and the season that was never applied ────────────────
    [
        'id' => 'NB-M1',
        'name' => 'the property with no rooms is offered as a way out of itself',
        'file' => $page,
        'old' => "            'bookableElsewhere' => array_values(array_filter(\$properties, fn (array \$p): bool => \$p['rooms'] > 0)),",
        'new' => "            'bookableElsewhere' => array_values(array_filter(\$properties, fn (array \$p): bool => true)),",
        'tests' => [['file' => $test, 'filter' => 'offers the properties that can take a booking']],
    ],
    [
        'id' => 'NB-M2',
        'name' => 'a saved season is never mentioned again ("No price for these nights yet")',
        'file' => $page,
        'old' => "            'seasons' => ! \$rt['priced'] && \$planId !== '' ? \$this->savedSeasons(\$planId, \$checkIn, \$checkOut) : [],",
        'new' => "            'seasons' => [],",
        'tests' => [['file' => $test, 'filter' => 'shows a saved season that covers the stay']],
    ],
    [
        'id' => 'NB-M3',
        'name' => 'applying the season from the booking lands on the Rates page instead',
        'file' => $seasons,
        'old' => '        return $request->input(\'return\') === RateActionController::RETURN_WIZARD;',
        'new' => '        return false;',
        'tests' => [['file' => $test, 'filter' => 'applies the season from the booking and comes back']],
    ],
    [
        'id' => 'NB-M4',
        'name' => 'the way back to the booking forgets the dates',
        'file' => $seasons,
        'old' => "            'check_in' => \$this->dateString(\$request->input('return_check_in')),",
        'new' => "            'check_in' => null,",
        'tests' => [['file' => $test, 'filter' => 'applies the season from the booking and comes back']],
    ],
    [
        'id' => 'NB-M6',
        'name' => 'a paused workspace sends the booking to the Rates page',
        'file' => $seasons,
        'old' => "            ? \$this->toWizard(\$request)->with('new_reservation_notice', \$message)",
        'new' => "            ? \$this->toPage(\$request)->with('rates_error', \$message)",
        'tests' => [['file' => $test, 'filter' => 'refuses the season from the booking while the workspace is paused']],
    ],
    // ── Only true sentences ───────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'NB-M7',
        'name' => 'an unset pets rule says "Not permitted" again',
        'file' => $view,
        'old' => "<div><span>Pets</span><strong>{{ \$houseRules['pet'] ?? 'Not set' }}</strong></div>",
        'new' => "<div><span>Pets</span><strong>{{ \$houseRules['pet'] ?? 'Not permitted' }}</strong></div>",
        'tests' => [['file' => $test, 'filter' => 'never invents a house rule']],
    ],
    [
        'id' => 'NB-M8',
        'name' => 'the stored pets code is printed, not its words',
        'file' => $page,
        'old' => "            'pet' => PropertyPolicy::ruleLabel(data_get(\$policy, 'pet')),",
        'new' => "            'pet' => data_get(\$policy, 'pet'),",
        'tests' => [['file' => $test, 'filter' => 'shows the house rules a property did set in words']],
    ],
    [
        'id' => 'NB-M9',
        'name' => 'the stay’s own free-cancellation date is not used',
        'file' => $page,
        'old' => "                \$freeUntil !== null => 'Free until '.\$freeUntil->format('j M'),",
        'new' => "                false => 'Free until '.\$freeUntil->format('j M'),",
        'tests' => [['file' => $test, 'filter' => 'shows the house rules a property did set in words']],
    ],
    [
        'id' => 'NB-M10',
        'name' => 'a minimum stay reads as the old "2 night min"',
        'file' => $page,
        'old' => "                default => (int) \$minStay.' nights',",
        'new' => "                default => (int) \$minStay.' night min',",
        'tests' => [['file' => $test, 'filter' => 'shows the house rules a property did set in words']],
    ],
    [
        'id' => 'NB-M11',
        'name' => 'the arrival step promises directions in the confirmation again',
        'file' => $page,
        'old' => "            default => 'Directions, and the door code where the unit has one, reach the guest by email '.\$lead",
        'new' => "            default => 'The confirmation includes directions and parking. Directions, and the door code where the unit has one, reach the guest by email '.\$lead",
        'tests' => [['file' => $test, 'filter' => 'says how directions really reach the guest']],
    ],
    [
        'id' => 'NB-M12',
        'name' => 'a guest with no email gets a check-in code they cannot use',
        'file' => $page,
        'old' => "        \$canSignIn = \$guest !== null && trim((string) \$guest->email) !== '';",
        'new' => '        $canSignIn = $guest !== null;',
        'tests' => [['file' => $test, 'filter' => 'shows the self check-in code only to a guest who can sign in']],
    ],
    [
        'id' => 'NB-M13',
        'name' => 'the "price set" note shows above every step again',
        'file' => $view,
        'old' => "    @if (session('new_reservation_ok') && ! \$__nrWizard)",
        'new' => "    @if (session('new_reservation_ok'))",
        'tests' => [['file' => $test, 'filter' => 'keeps the “price set” note on the first step']],
    ],
    [
        'id' => 'NB-M14',
        'name' => 'the confirmation can be ticked with no address to send it to',
        'file' => $view,
        'old' => 'x-bind:checked="sendConfirmation &amp;&amp; canEmail()" x-bind:disabled="! canEmail()"',
        'new' => 'x-bind:checked="sendConfirmation"',
        'tests' => [['file' => $test, 'filter' => 'does not offer to email the confirmation to a guest with no email']],
    ],
    [
        'id' => 'NB-M15',
        'name' => 'a returning guest is said to have an email whether or not they do',
        'file' => $page,
        'old' => "                    'has_email' => trim((string) \$guest->email) !== ''];",
        'new' => "                    'has_email' => true];",
        'tests' => [['file' => $test, 'filter' => 'knows which returning guests can be emailed']],
    ],
    // ── Fewer steps, more on screen ───────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'NB-M16',
        'name' => 'a property with no extras is walked through an empty Extras step',
        'file' => $page,
        'old' => "self::STEP_EXTRAS => \$hasExtras ? 'Extras' : '',",
        'new' => "self::STEP_EXTRAS => 'Extras',",
        'tests' => [['file' => $test, 'filter' => 'walks four steps']],
    ],
    [
        'id' => 'NB-M17',
        'name' => 'a refused card payment reopens at the old step 7',
        'file' => $write,
        'old' => "            return \$this->back(\$request, 'Card payments are coming soon — take cash or bank transfer now, or record it later from the folio.', NewReservation::STEP_CONFIRM);",
        'new' => "            return \$this->back(\$request, 'Card payments are coming soon — take cash or bank transfer now, or record it later from the folio.', 7);",
        'tests' => [['file' => $test, 'filter' => 'reopens a refused booking at the step that holds what was refused']],
    ],
    [
        'id' => 'NB-M18',
        'name' => 'a refused booking reopens on a step this property does not ask',
        'file' => $page,
        'old' => '        $initialStep = isset($steps[$initialStep]) ? $initialStep : self::STEP_CONFIRM;',
        'new' => '        $initialStep = $initialStep;',
        'tests' => [['file' => $test, 'filter' => 'reopens at Confirm when a refused booking’s step is one this property does not ask']],
    ],
    [
        'id' => 'NB-M19',
        'name' => 'the calendar shows no price on a night that has one',
        'file' => $page,
        'old' => "            \$price = \$prices[\$planOf[(string) \$row->room_type_id] ?? ''][\$date] ?? null;",
        'new' => '            $price = null;',
        'tests' => [['file' => $test, 'filter' => 'shows each night’s price and the rooms left on the calendar']],
    ],
    [
        'id' => 'NB-M20',
        'name' => 'the calendar’s today is the server’s day, not the property’s',
        'file' => $page,
        'old' => "                'today' => \$today,",
        'new' => "                'today' => CarbonImmutable::today()->toDateString(),",
        'tests' => [['file' => $test, 'filter' => 'shows each night’s price and the rooms left on the calendar']],
    ],
    [
        'id' => 'NB-M21',
        'name' => 'a guest’s country can only be one of the 17 tax countries',
        'file' => $page,
        'old' => "            'countries' => Countries::everywhere(),",
        'new' => "            'countries' => Countries::options(),",
        'tests' => [['file' => $test, 'filter' => 'lists countries by name']],
    ],
    // ── The independent review (2026-10-04) — each proves a test written after its fix would have caught the old code ──
    [
        'id' => 'NB-M22',
        'name' => 'H1 — a refused returning guest loses the confirmation again',
        'file' => $page,
        'old' => "                    + ((\$restored['existingGuestId'] ?? '') !== '' ? [\$restored['existingGuestId'] => (bool) (\$restored['existingGuestHasEmail'] ?? false)] : [])),",
        'new' => '                    + []),',
        'tests' => [['file' => $test, 'filter' => 'keeps a returning guest’s confirmation when their booking comes back refused']],
    ],
    [
        'id' => 'NB-M23',
        'name' => 'H2 — Apply is offered without the Rates page’s disclosure',
        'file' => $view,
        'old' => "                                    @include('livewire.operator.partials.season-apply-disclosure')",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'shows a saved season that covers the stay']],
    ],
    [
        'id' => 'NB-M24',
        'name' => 'M1 — "pick a unit" after a season that left nights unpriced',
        'file' => $seasons,
        'old' => "                \$stay = \$missing <= 0 ? ' Pick a unit to continue.'",
        'new' => "                \$stay = true ? ' Pick a unit to continue.'",
        'tests' => [['file' => $test, 'filter' => 'says what still lacks a price after a season that covers only part of the stay']],
    ],
    [
        'id' => 'NB-M25',
        'name' => 'M2 — a season over priced nights only is offered as the fix',
        'file' => $page,
        'old' => '            if ($prices === 0) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $test, 'filter' => 'offers only a season that would price a night still without one']],
    ],
    [
        'id' => 'NB-M26',
        'name' => 'M3 — an email promised where none is sent',
        'file' => $page,
        'old' => "            ! \$mailSends => 'Email isn’t being sent from Vyrobel yet'.\$yourself,",
        'new' => "            false => 'Email isn’t being sent from Vyrobel yet'.\$yourself,",
        'tests' => [['file' => $test, 'filter' => 'says how directions really reach the guest']],
    ],
    [
        'id' => 'NB-M27',
        'name' => 'M3 — a pre-arrival email promised for a stay that starts too soon',
        'file' => $page,
        'old' => '            $checkIn !== null && $checkIn < CarbonImmutable::today()->addDays($lead)->toDateString()',
        'new' => '            false',
        'tests' => [['file' => $test, 'filter' => 'says how directions really reach the guest']],
    ],
    [
        'id' => 'NB-M28',
        'name' => 'M3 — a pre-arrival email promised while it is switched off',
        'file' => $page,
        'old' => "            \$property !== null && ! EmailTemplateResolver::enabled((string) \$property->organization_id, 'pre-arrival')",
        'new' => '            false',
        'tests' => [['file' => $test, 'filter' => 'says how directions really reach the guest']],
    ],
    [
        'id' => 'NB-M29',
        'name' => 'a free-cancellation date already gone is shown as still to come',
        'file' => $page,
        'old' => "                \$freeUntil !== null && \$freeUntil->toDateString() < CarbonImmutable::now((string) (\$property->timezone ?: config('app.timezone')))->toDateString()",
        'new' => '                false',
        'tests' => [['file' => $test, 'filter' => 'words a free-cancellation window that has already closed']],
    ],
    [
        'id' => 'NB-M30',
        'name' => 'a zero-day policy reads as a date',
        'file' => $page,
        'old' => "                \$freeDays === 0 => 'Free until check-in',",
        'new' => "                false => 'Free until check-in',",
        'tests' => [['file' => $test, 'filter' => 'words a free-cancellation window that has already closed']],
    ],
];
