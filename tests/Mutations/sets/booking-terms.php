<?php

declare(strict_types=1);

// Mutation set booking-terms — the booking terms and the proof of acceptance (his ruling 2026-11-14, option B; OPEN D-BOOK-NO-TERMS), one mutation per
// guard, each aimed at the test that watches it. Run with `php tests/Mutations/run.php --set=booking-terms`.

$t = 'tests/Feature/BookingTermsTest.php';
$ctl = 'app/Http/Controllers/BookingEngineController.php';
$engine = 'app/Services/BookingEngineService.php';
$terms = 'app/Support/Booking/BookingTerms.php';
$settingsCtl = 'app/Http/Controllers/Operator/SettingsActionController.php';

return [
    // ── the web checkout refuses without the terms ──────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M1',
        'name' => 'the checkout stops requiring the box',
        'file' => $ctl,
        'old' => "'terms_accepted' => ['accepted'],",
        'new' => "'terms_accepted' => ['sometimes'],",
        'tests' => [['file' => $t, 'filter' => 'refuses a booking without the box']],
    ],
    [
        'id' => 'BT-M2',
        'name' => 'a booking goes through even when the terms changed after the guest loaded the page',
        'file' => $ctl,
        'old' => "if (! hash_equals(\$terms['version'], (string) (\$validated['terms_version'] ?? ''))) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $t, 'filter' => 'stores no lie']],
    ],
    [
        'id' => 'BT-M3',
        'name' => 'a page that carried no terms version is trusted',
        'file' => $ctl,
        'old' => "(string) (\$validated['terms_version'] ?? '')",
        'new' => "(string) (\$validated['terms_version'] ?? \$terms['version'])",
        'tests' => [['file' => $t, 'filter' => 'stale tab']],
    ],
    // ── the proof is written with the reservation ───────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M4',
        'name' => 'the acceptance is not written on the reservation',
        'file' => $engine,
        'old' => "...(\$terms !== null ? ['terms_accepted_at' => now(), 'terms_version' => \$terms['version'], 'terms_snapshot' => \$terms] : []),",
        'new' => '...[],',
        'tests' => [['file' => $t, 'filter' => 'stores the acceptance']],
    ],
    [
        'id' => 'BT-M5',
        'name' => 'the terms exactly as shown are not kept',
        'file' => $engine,
        'old' => "'terms_version' => \$terms['version'], 'terms_snapshot' => \$terms]",
        'new' => "'terms_version' => \$terms['version'], 'terms_snapshot' => null]",
        'tests' => [['file' => $t, 'filter' => 'stores the acceptance']],
    ],
    [
        'id' => 'BT-M6',
        'name' => 'the audit row stops saying WHICH terms were accepted',
        'file' => 'app/Services/AssignmentService.php',
        'old' => "...(\$reservation->terms_version !== null ? ['terms_version' => \$reservation->terms_version] : []),",
        'new' => '...[],',
        'tests' => [['file' => $t, 'filter' => 'holds NO guest data']],
    ],
    // ── the machine API is not a way round it ──────────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M7',
        'name' => 'the JSON booking endpoint stops requiring terms_accepted',
        'file' => $ctl,
        'old' => "'terms_accepted' => ['required', 'accepted'],",
        'new' => "'terms_accepted' => ['sometimes'],",
        'tests' => [['file' => $t, 'filter' => 'refuses a JSON booking']],
    ],
    [
        'id' => 'BT-M8',
        'name' => 'the JSON booking endpoint accepts a wrong terms_version',
        'file' => $ctl,
        'old' => "if (isset(\$validated['terms_version']) && ! hash_equals(\$terms['version'], (string) \$validated['terms_version'])) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $t, 'filter' => 'refuses a JSON booking']],
    ],
    // ── the terms are built from what the property really set, and the version means something ─────────────────
    [
        'id' => 'BT-M9',
        'name' => 'a rule the property never set is shown anyway',
        'file' => $terms,
        'old' => '        if ($pet !== null) {',
        'new' => '        if (true) {',
        'tests' => [['file' => $t, 'filter' => 'says nothing about a rule']],
    ],
    [
        'id' => 'BT-M10',
        'name' => 'the version ignores the hotel\'s own words',
        'file' => $terms,
        'old' => "            'deposit' => \$depositRequired,\n            'extra' => \$extra,",
        'new' => "            'deposit' => \$depositRequired,",
        'tests' => [['file' => $t, 'filter' => 'keeps the version stable']],
    ],
    // ── the hotel's own words are plain text ───────────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M11',
        'name' => 'the hotel\'s own text is printed as HTML',
        'file' => 'resources/views/components/booking/terms.blade.php',
        'old' => "white-space:pre-line;overflow-wrap:anywhere;\">{{ \$terms['extra'] }}</p>",
        'new' => "white-space:pre-line;overflow-wrap:anywhere;\">{!! \$terms['extra'] !!}</p>",
        'tests' => [['file' => $t, 'filter' => 'no stored XSS']],
    ],
    // ── the checkout page ──────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M12',
        'name' => 'the box is no longer marked required',
        'file' => 'resources/views/booking/checkout.blade.php',
        'old' => '<input type="checkbox" name="terms_accepted" value="1" required style=',
        'new' => '<input type="checkbox" name="terms_accepted" value="1" style=',
        'tests' => [['file' => $t, 'filter' => 'unticked required box']],
    ],
    [
        'id' => 'BT-M13',
        'name' => 'the box is ticked for the guest',
        'file' => 'resources/views/booking/checkout.blade.php',
        'old' => '<input type="checkbox" name="terms_accepted" value="1" required style=',
        'new' => '<input type="checkbox" name="terms_accepted" value="1" checked required style=',
        'tests' => [['file' => $t, 'filter' => 'unticked required box']],
    ],
    [
        'id' => 'BT-M14',
        'name' => 'the form stops carrying the version the page showed',
        'file' => 'resources/views/booking/checkout.blade.php',
        'old' => '<input type="hidden" name="terms_version" value="{{ $terms[\'version\'] }}">',
        'new' => '',
        'tests' => [['file' => $t, 'filter' => 'unticked required box']],
    ],
    // ── the confirmation email ─────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M15',
        'name' => 'the email claims an acceptance that was never made',
        'file' => 'resources/views/emails/booking-confirmation.blade.php',
        'old' => '    @if (! empty($termsAcceptedOn))',
        'new' => '    @if (true)',
        'tests' => [['file' => $t, 'filter' => 'repeats what the guest accepted']],
    ],
    // ── Settings ───────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'BT-M16',
        'name' => 'the hotel\'s text is no longer length-limited',
        'file' => $settingsCtl,
        'old' => "'max:'.BookingTerms::MAX_EXTRA]",
        'new' => "'max:99999']",
        'tests' => [['file' => $t, 'filter' => 'rejects a hotel text longer']],
    ],
    [
        'id' => 'BT-M17',
        'name' => 'a save that does not send the field wipes the hotel\'s words',
        'file' => $settingsCtl,
        'old' => "if (\$request->has('booking_terms_extra')) {",
        'new' => 'if (true) {',
        'tests' => [['file' => $t, 'filter' => 'partial save keeps them']],
    ],
    [
        'id' => 'BT-M18',
        'name' => 'a property with no public address breaks Settings (a 500)',
        'file' => 'app/Livewire/Operator/OperatorSettings.php',
        'old' => "\$property !== null && filled(\$property->slug) ? route('booking.terms', \$property->slug) : null,",
        'new' => "\$property !== null ? route('booking.terms', \$property->slug) : null,",
        'tests' => [['file' => $t, 'filter' => 'never 500']],
    ],
    [
        'id' => 'BT-M19',
        'name' => 'the controller requires a smoking field the form does not have (every real save refused, silently)',
        'file' => $settingsCtl,
        'old' => "'smoking' => ['sometimes', 'in:allowed,on_request,not_allowed'],",
        'new' => "'smoking' => ['required', 'in:allowed,on_request,not_allowed'],",
        'tests' => [['file' => $t, 'filter' => 'REAL Settings form']],
    ],
    [
        'id' => 'BT-M20',
        'name' => 'a refused Policies save shows no reason',
        'file' => 'resources/views/livewire/operator/operator-settings.blade.php',
        'old' => '@if ($polErrors->isNotEmpty())',
        'new' => '@if (false)',
        'tests' => [['file' => $t, 'filter' => 'shows the reason when a Policies save is refused']],
    ],
    [
        'id' => 'BT-M21',
        'name' => 'the accepted-terms card renders for a booking with no acceptance on record',
        'file' => 'resources/views/components/booking/accepted-terms.blade.php',
        'old' => '@if ($at !== null && $snapshot !== null)',
        'new' => '@if (true)',
        'tests' => [['file' => $t, 'filter' => 'renders nothing by itself']],
    ],
    [
        'id' => 'BT-M22',
        'name' => 'the guest portal stops showing what the guest accepted',
        'file' => 'resources/views/guest/reservation.blade.php',
        'old' => '@if ($termsRecord->terms_accepted_at !== null)',
        'new' => '@if (false)',
        'tests' => [['file' => $t, 'filter' => 'nothing on a booking with none on record']],
    ],
    [
        'id' => 'BT-M23',
        'name' => 'the operator reservation stops showing what the guest accepted',
        'file' => 'resources/views/livewire/reservation-detail.blade.php',
        'old' => '@if ($termsRecord->terms_accepted_at !== null)',
        'new' => '@if (false)',
        'tests' => [['file' => $t, 'filter' => 'shows the operator, on the reservation']],
    ],
];
