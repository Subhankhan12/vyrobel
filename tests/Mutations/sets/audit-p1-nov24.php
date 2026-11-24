<?php

declare(strict_types=1);

// Mutation set audit-p1-nov24 — the seven P1 findings of the audit of 2026-11-24 (VY-02 … VY-08), fixed the same day.
// Every guard those fixes added is removed here, one at a time, and the test aimed at it must go RED.
// Run with `php tests/Mutations/run.php --set=audit-p1-nov24`.

$vy02 = 'tests/Feature/Vy02RefundOwnerShareTest.php';
$vy03 = 'tests/Feature/Vy03CapacityGuardTest.php';
$vy04 = 'tests/Feature/Vy04CheckoutOnceTest.php';
$vy05 = 'tests/Feature/Vy05ReservationPropertyScopeTest.php';
$vy06 = 'tests/Feature/Vy06DoorCodeReleaseTest.php';
$vy07 = 'tests/Feature/Vy07CalendarHorizonTest.php';
$vy08 = 'tests/Feature/StorefrontSeoTest.php';

$set = [
    // ── VY-02 · a refund takes the owner's share of the ROOM, capped at what they were credited ──────────────────────────────
    [
        'id' => 'VY02-M1',
        'name' => 'the owner share is taken from the WHOLE refund again (tax included): the denominator is the owners\' room revenue',
        'file' => 'app/Services/OwnerAttributionService.php',
        'old' => '$chargedMinor = max($this->folioChargesMinor($reservation), $totalGross);',
        'new' => '$chargedMinor = $totalGross;',
        // ⚠️ aimed at the PART refund: on a full refund the cap (VY02-M2) alone still lands the owner at zero, so that test cannot see this layer
        'tests' => [['file' => $vy02, 'filter' => 'part refund of a taxed stay']],
    ],
    [
        'id' => 'VY02-M2',
        'name' => 'the cap is gone: the owner can be debited more than they were credited',
        'file' => 'app/Services/OwnerAttributionService.php',
        'old' => '$ownerDebit = min($share, max(0, $credited - $refundedByOwner[$ownerId]));',
        'new' => '$ownerDebit = $share;',
        'tests' => [['file' => $vy02, 'filter' => 'CAPPED']],
    ],
    [
        'id' => 'VY02-M3',
        'name' => 'an earlier refund shrinks the credit the next one is computed from (the second part refund is under-charged)',
        'file' => 'app/Services/OwnerAttributionService.php',
        'old' => "if ((string) \$row->entry_type === 'adjustment' && (string) \$row->note === 'refund') {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy02, 'filter' => 'two part refunds']],
    ],
    [
        'id' => 'VY02-M4',
        'name' => 'one owner is charged for the whole refund when only one of two rooms is theirs (folio charges ignored)',
        'file' => 'app/Services/OwnerAttributionService.php',
        'old' => "return (int) FolioItem::query()->where('folio_id', \$folio->getKey())->sum('amount_minor');",
        'new' => 'return 0;',
        'tests' => [['file' => $vy02, 'filter' => 'one of two']],
    ],

    // ── VY-03 · taking a unit away cannot oversell a room type in silence ────────────────────────────────────────────────────
    [
        'id' => 'VY03-M1',
        'name' => 'a block is no longer refused when the bookings need the unit',
        'file' => 'app/Services/AssignmentService.php',
        'old' => "if (\$impact !== null && \$impact['nights'] > 0) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy03, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY03-M2',
        'name' => 'out of order is no longer refused (the operator\'s go-ahead is not asked for)',
        'file' => 'app/Services/MaintenanceService.php',
        'old' => "if (\$impact['nights'] > 0 && ! \$acknowledgeOversell) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy03, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY03-M3',
        'name' => 'the work order form acknowledges for the operator: the first press saves',
        'file' => 'app/Http/Controllers/Operator/MaintenanceActionController.php',
        'old' => "'acknowledge_oversell' => (bool) (\$validated['acknowledge_oversell'] ?? false),",
        'new' => "'acknowledge_oversell' => true,",
        'tests' => [['file' => $vy03, 'filter' => 'shows HOW MANY']],
    ],
    [
        'id' => 'VY03-M4',
        'name' => 'the stays on a PAUSED unit are counted as needing another room (a pause reads as an overbooking)',
        'file' => 'app/Support/Inventory/RoomCapacity.php',
        'old' => '$bookings = max(0, (int) $row->sold - ($seatedOnPaused[$night] ?? 0));',
        'new' => '$bookings = (int) $row->sold;',
        'tests' => [['file' => $vy03, 'filter' => 'PAUSED unit']],
    ],
    [
        'id' => 'VY03-M5',
        'name' => 'out of order is checked against nights already past',
        'file' => 'app/Services/MaintenanceService.php',
        'old' => "\$today = CarbonImmutable::now((string) (\$unit->property?->timezone ?: config('app.timezone')))->toDateString();",
        'new' => "\$today = '2000-01-01';",
        'tests' => [['file' => $vy03, 'filter' => 'from TODAY']],
    ],
    [
        'id' => 'VY03-M6',
        'name' => 'an acknowledged overbooking leaves no trace in the audit',
        'file' => 'app/Services/MaintenanceService.php',
        'old' => "['condition_status' => \$toStatus] + (\$overbooked !== null ? ['overbooked' => \$overbooked] : []));",
        'new' => "['condition_status' => \$toStatus]);",
        'tests' => [['file' => $vy03, 'filter' => 'audit records the gap']],
    ],
    [
        'id' => 'VY03-M7',
        'name' => 'the calendar goes back to a quiet figure on an overbooked night',
        'file' => 'app/Livewire/ReservationRack.php',
        'old' => "'overbooked' => RoomCapacity::overbooked((string) \$roomType->id, \$start, \$end),",
        'new' => "'overbooked' => [],",
        'tests' => [['file' => $vy03, 'filter' => 'no more silent clamp']],
    ],
    [
        'id' => 'VY03-M8',
        'name' => 'a block reads the nights without locking them',
        'file' => 'app/Services/AssignmentService.php',
        'old' => "\$attributes['start_date'], \$attributes['end_date'], lock: true)",
        'new' => "\$attributes['start_date'], \$attributes['end_date'], lock: false)",
        'tests' => [['file' => $vy03, 'filter' => 'the lock']],
    ],
    [
        'id' => 'VY03-M9',
        'name' => 'out of order reads the nights without locking them',
        'file' => 'app/Services/MaintenanceService.php',
        'old' => '(string) $unit->getKey(), $today, null, lock: true);',
        'new' => '(string) $unit->getKey(), $today, null, lock: false);',
        'tests' => [['file' => $vy03, 'filter' => 'the lock']],
    ],
    [
        'id' => 'VY03-M10',
        'name' => 'a block leaves `blocked` to the event after the commit (a booking can read the old figure in between)',
        'file' => 'app/Services/AssignmentService.php',
        'old' => "app(AvailabilityService::class)->recompute(\$roomTypeId, \$attributes['start_date'], \$attributes['end_date']);",
        'new' => '',
        'tests' => [['file' => $vy03, 'filter' => 'no gap']],
    ],
    [
        'id' => 'VY03-M11',
        'name' => 'out of order leaves `out_of_order` to the event after the commit',
        'file' => 'app/Services/MaintenanceService.php',
        'old' => '$this->availability->recompute((string) $unit->room_type_id, $start, $end);',
        'new' => '',
        'tests' => [['file' => $vy03, 'filter' => 'no gap']],
    ],
    [
        'id' => 'VY03-M12',
        'name' => 'a room-type block is not counted as a room fewer',
        'file' => 'app/Support/Inventory/RoomCapacity.php',
        'old' => '$rooms--;                                            // a room-type block',
        'new' => '',
        'tests' => [['file' => $vy03, 'filter' => 'type-level block']],
    ],

    // ── VY-04 · one checkout page makes one booking ──────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY04-M1',
        'name' => 'a form with no token (or a malformed one) books anyway',
        'file' => 'app/Http/Controllers/BookingEngineController.php',
        'old' => "if (! is_string(\$checkoutToken) || preg_match('/^[A-Za-z0-9]{'.self::CHECKOUT_TOKEN_LENGTH.'}\$/', \$checkoutToken) !== 1) {",
        'new' => 'if (! is_string($checkoutToken) && $checkoutToken !== null) {',
        'tests' => [['file' => $vy04, 'filter' => 'NO token']],
    ],
    [
        'id' => 'VY04-M2',
        'name' => 'the quote does not store the page\'s token: the same page posted twice books twice',
        'file' => 'app/Services/BookingEngineService.php',
        'old' => "'checkout_token' => \$checkoutToken,",
        'new' => "'checkout_token' => null,",
        'tests' => [['file' => $vy04, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY04-M3',
        'name' => 'a repeat is looked up across EVERY property of the organisation (a replayed token is sent to another property\'s booking)',
        'file' => 'app/Http/Controllers/BookingEngineController.php',
        'old' => "                ->where('property_id', \$property->getKey())\n                ->where('checkout_token', \$token)",
        'new' => "                ->where('checkout_token', \$token)",
        'tests' => [['file' => $vy04, 'filter' => 'ANOTHER property']],
    ],
    [
        'id' => 'VY04-M4',
        'name' => 'a booking that could not be made burns the page (the token is not released)',
        'file' => 'app/Http/Controllers/BookingEngineController.php',
        'old' => "\$quote->forceFill(['checkout_token' => null])->save();",
        'new' => '',
        'tests' => [['file' => $vy04, 'filter' => 'does NOT use the page up']],
    ],
    [
        'id' => 'VY04-M5',
        'name' => 'the checkout page prints the same token every time',
        'file' => 'app/Http/Controllers/BookingEngineController.php',
        'old' => "'checkoutToken' => Str::random(self::CHECKOUT_TOKEN_LENGTH),",
        'new' => "'checkoutToken' => str_repeat('a', self::CHECKOUT_TOKEN_LENGTH),",
        'tests' => [['file' => $vy04, 'filter' => 'fresh token']],
    ],

    // ── VY-05 · the property scope on reservations ───────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY05-M1',
        'name' => 'PropertyScope finds a reservation on ANY property of the organisation',
        'file' => 'app/Support/Property/PropertyScope.php',
        'old' => "return Reservation::query()->whereIn('property_id', \$this->ids());",
        'new' => 'return Reservation::query();',
        'tests' => [['file' => $vy05, 'filter' => 'the scope itself']],
    ],
    [
        'id' => 'VY05-M2',
        'name' => 'a guest change request is found whatever its booking\'s property',
        'file' => 'app/Support/Property/PropertyScope.php',
        'old' => "->whereHas('reservation', fn (Builder \$r) => \$r->whereIn('property_id', \$ids))",
        'new' => "->whereHas('reservation')",
        'tests' => [['file' => $vy05, 'filter' => 'guest change request']],
    ],
    [
        'id' => 'VY05-M3',
        'name' => 'the reservation page no longer checks the property at mount',
        'file' => 'app/Livewire/ReservationDetail.php',
        'old' => "        app(PropertyScope::class)->reservation(\$reservation);\n\n        \$this->reservationId = \$reservation;",
        'new' => '        $this->reservationId = $reservation;',
        'tests' => [['file' => $vy05, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY05-M4',
        'name' => 'the refund page no longer checks the property at mount',
        'file' => 'app/Livewire/Operator/ReservationRefund.php',
        'old' => "        app(PropertyScope::class)->reservation(\$reservation);\n\n        \$this->reservationId = \$reservation;",
        'new' => '        $this->reservationId = $reservation;',
        'tests' => [['file' => $vy05, 'filter' => 'EVERY reservation']],
    ],
    [
        'id' => 'VY05-M5',
        'name' => 'the reservation page\'s id can be changed by a wire update',
        'file' => 'app/Livewire/ReservationDetail.php',
        'old' => "    #[Locked]\n    public string \$reservationId;",
        'new' => '    public string $reservationId;',
        'tests' => [['file' => $vy05, 'filter' => 'LOCKED']],
    ],
    [
        'id' => 'VY05-M6',
        'name' => 'the refund page\'s id can be changed by a wire update',
        'file' => 'app/Livewire/Operator/ReservationRefund.php',
        'old' => "    #[Locked]\n    public string \$reservationId;",
        'new' => '    public string $reservationId;',
        'tests' => [['file' => $vy05, 'filter' => 'LOCKED']],
    ],

    // ── VY-06 · the door code is released on conditions ──────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY06-M1',
        'name' => 'the balance no longer withholds the code',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => "\$withheld = \$owingMinor > 0 ? 'balance' : null;",
        'new' => '$withheld = null;',
        'tests' => [['file' => $vy06, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY06-M2',
        'name' => 'a property with NO policy row does not ask for the balance (the unsafe default)',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => 'if ($withheld === null && ($policy === null || (bool) $policy->arrival_requires_paid)) {',
        'new' => 'if ($withheld === null && ($policy !== null && (bool) $policy->arrival_requires_paid)) {',
        'tests' => [['file' => $vy06, 'filter' => 'NEW property']],
    ],
    [
        'id' => 'VY06-M3',
        'name' => 'the online check-in condition is never applied',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => "\$withheld = 'checkin';",
        'new' => '$withheld = null;',
        'tests' => [['file' => $vy06, 'filter' => 'ALSO ask for online check-in']],
    ],
    [
        'id' => 'VY06-M4',
        'name' => 'the lead time is the old constant for every property',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => 'return $days === null ? self::LEAD_DAYS : max(0, min(self::MAX_LEAD_DAYS, (int) $days));',
        'new' => 'return self::LEAD_DAYS;',
        'tests' => [['file' => $vy06, 'filter' => 'lead time is the PROPERTY']],
    ],
    [
        'id' => 'VY06-M5',
        'name' => 'a stay billed to a company account is treated as the guest\'s to pay',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => 'if ($folio === null || $folio->billed_to_account_id !== null) {',
        'new' => 'if ($folio === null) {',
        'tests' => [['file' => $vy06, 'filter' => 'company account']],
    ],
    [
        'id' => 'VY06-M6',
        'name' => 'a cancelled stay is treated like any other',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => "\$cancelled => 'cancelled',",
        'new' => "false => 'cancelled',",
        'tests' => [['file' => $vy06, 'filter' => 'cancelled stay']],
    ],
    [
        'id' => 'VY06-M7',
        'name' => 'the pre-arrival email is dated by the old constant, not the property\'s window',
        'file' => 'app/Mail/PreArrivalMail.php',
        'old' => '$asOf = $this->asOf ?? $checkIn->copy()->subDays(ArrivalPack::leadDays($r->property));',
        'new' => '$asOf = $this->asOf ?? $checkIn->copy()->subDays(ArrivalPack::LEAD_DAYS);',
        'tests' => [['file' => $vy06, 'filter' => 'EMAIL carries no code']],
    ],
    [
        'id' => 'VY06-M8',
        'name' => 'the email still says "your door code is inside" when it is not',
        'file' => 'resources/views/emails/pre-arrival.blade.php',
        'old' => "    {{-- VY-06 — \"your door code is inside\" is said only when it is. --}}\n    @if (\$doorCode)",
        'new' => '    @if (true)',
        'tests' => [['file' => $vy06, 'filter' => 'EMAIL carries no code']],
    ],
    [
        'id' => 'VY06-M9',
        'name' => 'the pre-arrival email goes out on the old fixed day for every property',
        'file' => 'app/Console/Commands/SendPreArrivalEmails.php',
        'old' => '$target = $today->copy()->addDays($forced ?? ArrivalPack::leadDays($property))->toDateString();',
        'new' => '$target = $today->copy()->addDays($forced ?? ArrivalPack::LEAD_DAYS)->toDateString();',
        'tests' => [['file' => $vy06, 'filter' => 'goes out the day EACH']],
    ],
    [
        'id' => 'VY06-M10',
        'name' => 'a form that does not carry the switches resets them (balance-first silently off)',
        'file' => 'app/Http/Controllers/Operator/SettingsActionController.php',
        'old' => '                unset($data[$switch]);',
        'new' => '                $data[$switch] = false;',
        'tests' => [['file' => $vy06, 'filter' => 'settings']],
    ],
    [
        'id' => 'VY06-M11',
        'name' => 'the lead time accepts any number',
        'file' => 'app/Http/Controllers/Operator/SettingsActionController.php',
        'old' => "'arrival_lead_days' => ['sometimes', 'nullable', 'integer', 'between:0,'.ArrivalPack::MAX_LEAD_DAYS],",
        'new' => "'arrival_lead_days' => ['sometimes', 'nullable', 'integer'],",
        'tests' => [['file' => $vy06, 'filter' => 'settings']],
    ],
    [
        'id' => 'VY06-M12',
        'name' => 'the guest pages show the date instead of what is missing',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => "'balance' => __('php/support/guest/arrival_pack.pay_first', ['amount' => MoneyFormatter::display(\$owingMinor, (string) \$reservation->currency)]),",
        'new' => "'balance' => null,",
        'tests' => [['file' => $vy06, 'filter' => 'as reproduced']],
    ],

    // ── VY-07 · the bookable calendar is kept a year ahead ───────────────────────────────────────────────────────────────────
    [
        'id' => 'VY07-M1',
        'name' => 'the job is no longer on the schedule',
        'file' => 'routes/console.php',
        'old' => "Schedule::command('availability:extend')->dailyAt('01:30');",
        'new' => '',
        'tests' => [['file' => $vy07, 'filter' => 'on the schedule']],
    ],
    [
        'id' => 'VY07-M2',
        'name' => 'the command reports success over a calendar that is still short',
        'file' => 'app/Console/Commands/ExtendAvailabilityCalendar.php',
        'old' => 'return self::FAILURE;',
        'new' => 'return self::SUCCESS;',
        'tests' => [['file' => $vy07, 'filter' => 'command FAILS']],
    ],
    [
        'id' => 'VY07-M3',
        'name' => 'an archived property\'s room types are published too',
        'file' => 'app/Support/Inventory/CalendarHorizon.php',
        'old' => "->whereHas('property')      // an ARCHIVED property (soft-deleted) sells nothing; its room types are left alone",
        'new' => '',
        'tests' => [['file' => $vy07, 'filter' => 'archived property']],
    ],
    [
        'id' => 'VY07-M4',
        'name' => 'the job republishes from today instead of adding after the last night',
        'file' => 'app/Support/Inventory/CalendarHorizon.php',
        'old' => '$from = $lastNight !== null && $lastNight->gte($today) ? $lastNight->addDay() : $today;',
        'new' => '$from = $today;',
        'tests' => [['file' => $vy07, 'filter' => 'never touches a night already there']],
    ],
    [
        'id' => 'VY07-M5',
        'name' => 'the job stops one night short of the year',
        'file' => 'app/Support/Inventory/CalendarHorizon.php',
        'old' => '$end = $today->addDays(self::DAYS);              // exclusive: the last night published is $end − 1 day',
        'new' => '$end = $today->addDays(self::DAYS - 30);',
        'tests' => [['file' => $vy07, 'filter' => 'never touches a night already there']],
    ],
    [
        'id' => 'VY07-M6',
        'name' => 'the alarm never calls a calendar short',
        'file' => 'app/Support/Inventory/CalendarHorizon.php',
        'old' => 'if ($shortBy > self::TOLERANCE_DAYS) {',
        'new' => 'if ($shortBy > 100000) {',
        'tests' => [['file' => $vy07, 'filter' => 'ALARM']],
    ],
    [
        'id' => 'VY07-M7',
        'name' => 'the health card stays green over a short calendar',
        'file' => 'app/Support/Platform/Health/HealthBoard.php',
        'old' => "\$worst['short_by'] > CalendarHorizon::RED_AFTER_DAYS ? HealthCard::RED : HealthCard::AMBER,",
        'new' => 'HealthCard::GREEN,',
        'tests' => [['file' => $vy07, 'filter' => 'ALARM']],
    ],
    [
        'id' => 'VY07-M8',
        'name' => 'the alarm counts an archived organisation\'s room types as short',
        'file' => 'app/Support/Inventory/CalendarHorizon.php',
        'old' => "            ->whereNull('o.deleted_at')\n",
        'new' => '',
        'tests' => [['file' => $vy07, 'filter' => 'archived property']],
    ],

    // ── VY-08 · an internal test tenant is never advertised ──────────────────────────────────────────────────────────────────
    [
        'id' => 'VY08-M1',
        'name' => 'an internal organisation\'s storefront pages say index, follow',
        'file' => 'app/Support/Storefront/StorefrontSeo.php',
        'old' => 'return ! Organization::query()->whereKey($organizationId)->internal()->exists();',
        'new' => 'return true;',
        'tests' => [['file' => $vy08, 'filter' => 'says noindex']],
    ],
    [
        'id' => 'VY08-M2',
        'name' => 'an internal organisation\'s property and landing page are in the sitemap',
        'file' => 'app/Http/Controllers/StorefrontSitemapController.php',
        'old' => "->orWhereNotNull('deleted_at')->orWhere('is_internal', true))",
        'new' => "->orWhereNotNull('deleted_at'))",
        'tests' => [['file' => $vy08, 'filter' => 'left out of the storefront sitemap']],
    ],
];

// ── VY-05 · EVERY controller lookup, one by one ──────────────────────────────────────────────────────────────────────────────
// Each of the 18 reservation lookups in the three controllers (and the folio PDF) is put back on the tenant scope alone; the sweep
// test posts every one of those routes for another property's booking and must stop getting its 404. The anchors are READ from the
// files (the method's own signature down to its lookup), so a lookup added tomorrow gets its mutation without anyone listing it.
$controllers = [
    'app/Http/Controllers/FrontDeskActionController.php',
    'app/Http/Controllers/Operator/ReservationLifecycleController.php',
    'app/Http/Controllers/Operator/FolioPdfController.php',
    'app/Http/Controllers/ReservationFolioActionController.php',
];

foreach ($controllers as $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 3).'/'.$file);
    $short = basename($file, '.php');

    if (preg_match_all('/    public function (\w+)\([^\n]*\n(?:(?!    public function )[^\n]*\n)*?[^\n]*app\(PropertyScope::class\)->reservation\(\$reservation(, \[\'reservationUnits\'\])?\);/', $source, $matches, PREG_SET_ORDER) < 1) {
        continue;
    }

    foreach ($matches as $m) {
        $with = ($m[2] ?? '') !== '' ? "->with('reservationUnits')" : '';
        $old = $m[0];
        $new = (string) preg_replace(
            '/app\(PropertyScope::class\)->reservation\(\$reservation(, \[\'reservationUnits\'\])?\);$/',
            '\\App\\Models\\Reservation::query()'.$with.'->findOrFail($reservation);',
            $old,
        );

        $set[] = [
            'id' => 'VY05-'.$short.'-'.$m[1],
            'name' => $short.'::'.$m[1].'() finds its reservation through the tenant scope only',
            'file' => $file,
            'old' => $old,
            'new' => $new,
            'tests' => [['file' => $vy05, 'filter' => $short === 'FolioPdfController' ? 'as reproduced' : 'EVERY reservation']],
        ];
    }
}

return $set;
