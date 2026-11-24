<?php

declare(strict_types=1);

// Mutation set audit-nov24-second — the second batch of the audit of 2026-11-24: VY-01 (P0), VY-09, VY-12 … VY-18.
// Every guard those fixes added is removed here, one at a time, and the test aimed at it must go RED.
// Run with `php tests/Mutations/run.php --set=audit-nov24-second`.

$vy01 = 'tests/Feature/Vy01PublicBookingNeverPaysManuallyTest.php';
$vy09 = 'tests/Feature/Vy09PublicClaimsTest.php';
$vy12 = 'tests/Feature/Vy12ScheduledBackupTest.php';
$vy13 = 'tests/Feature/Vy13CheckInNeedsAReadyRoomTest.php';
$vy14 = 'tests/Feature/Vy14ReservationReadGateTest.php';
$vy15 = 'tests/Feature/Vy15PublicCheckoutInputRulesTest.php';
$vy16 = 'tests/Feature/Vy16QuotedPriceOnBookingLineTest.php';
$vy17 = 'tests/Feature/Vy17OneGuestPerEmailTest.php';
$vy18 = 'tests/Feature/Vy18GuestIdViewerAndRetentionTest.php';

$engine = 'app/Services/BookingEngineService.php';
$storefront = 'app/Http/Controllers/BookingEngineController.php';

$set = [
    // ── VY-01 · a visitor never records a payment through the manual gateway ─────────────────────────────────────────────────
    [
        'id' => 'VY01-M1',
        'name' => 'the public engine hands the manual gateway a visitor\'s word again: a booking is recorded as paid',
        'file' => $engine,
        'old' => '$payment = $takePayment && $this->takesCardPayments()',
        'new' => '$payment = $takePayment',
        'tests' => [['file' => $vy01, 'filter' => 'as reproduced'], ['file' => $vy01, 'filter' => 'the guard is in the SERVICE']],
    ],
    [
        'id' => 'VY01-M2',
        'name' => 'every gateway counts as a card gateway, the manual one included',
        'file' => $engine,
        'old' => "return app(PaymentGateway::class)->name() !== 'manual';",
        'new' => 'return true;',
        'tests' => [['file' => $vy01, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY01-M3',
        'name' => 'the public endpoint demands a card token even when there is no card gateway to give it to',
        'file' => $storefront,
        'old' => "'payment_token' => [\$this->engine->takesCardPayments() ? 'required' : 'nullable', 'string'],",
        'new' => "'payment_token' => ['required', 'string'],",
        'tests' => [['file' => $vy01, 'filter' => 'no longer needs a card token']],
    ],

    // ── VY-16 · the booking line carries the quoted price ────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY16-M1',
        'name' => 'the quote stops storing its line\'s price',
        'file' => $engine,
        'old' => "'price_minor' => \$totalMinor,",
        'new' => "'price_minor' => null,",
        'tests' => [['file' => $vy16, 'filter' => 'CONTROL']],
    ],
    [
        'id' => 'VY16-M2',
        'name' => 'booking ignores the stored line price (0 on the line)',
        'file' => $engine,
        'old' => "? \$line['price_minor']\n",
        'new' => "? 0\n",
        'tests' => [['file' => $vy16, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY16-M3',
        'name' => 'an older quote (no price on its line) is re-priced at booking time again',
        'file' => $engine,
        'old' => ': (count($requested) === 1',
        'new' => ': (false',
        'tests' => [['file' => $vy16, 'filter' => 'made BEFORE the line carried a price']],
    ],

    // ── VY-15 · the public checkout refuses a stay it could never have offered ───────────────────────────────────────────────
    [
        'id' => 'VY15-M1',
        'name' => 'a stay in the past is quoted again',
        'file' => $engine,
        'old' => "if (\$this->hasPassed(\$property, \$checkIn)) {\n            throw new StayNotBookableException('check_in'",
        'new' => "if (false) {\n            throw new StayNotBookableException('check_in'",
        'tests' => [['file' => $vy15, 'filter' => 'a stay in the past is refused on the web checkout'], ['file' => $vy15, 'filter' => 'a stay in the past is refused on the JSON API']],
    ],
    [
        'id' => 'VY15-M2',
        'name' => '"the past" is judged on the server\'s day, not the property\'s',
        'file' => $engine,
        'old' => 'BoardDate::today($property->timezone)->toDateString();',
        'new' => 'BoardDate::today()->toDateString();',
        'tests' => [['file' => $vy15, 'filter' => 'own day']],
    ],
    [
        'id' => 'VY15-M3',
        'name' => 'too many children alone is no longer refused',
        'file' => $engine,
        'old' => 'if ($adults > $maxAdults || $children > $maxChildren) {',
        'new' => 'if ($adults > $maxAdults) {',
        'tests' => [['file' => $vy15, 'filter' => 'too many adults ALONE']],
    ],
    [
        'id' => 'VY15-M4',
        'name' => 'a switched-off rate plan is quoted again',
        'file' => $engine,
        'old' => 'if (! $ratePlan->is_active) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $vy15, 'filter' => 'switched off cannot be booked']],
    ],
    [
        'id' => 'VY15-M5',
        'name' => 'a quote is no longer re-checked when it becomes a booking',
        'file' => $engine,
        'old' => "        \$this->assertQuoteStillBookable(\$quote);\n",
        'new' => "\n",
        'tests' => [['file' => $vy15, 'filter' => 'cannot be turned into a booking after its arrival day'], ['file' => $vy15, 'filter' => 'switched off AFTER the quote']],
    ],
    [
        'id' => 'VY15-M6',
        'name' => 'the search offers a stay that has already passed',
        'file' => $engine,
        'old' => "if (\$this->hasPassed(\$property, \$checkIn)) {\n            return [];",
        'new' => "if (false) {\n            return [];",
        'tests' => [['file' => $vy15, 'filter' => 'search offers nothing']],
    ],
    [
        'id' => 'VY15-M7',
        'name' => 'the web checkout no longer turns the refusal into a message (the guest gets an error page)',
        'file' => $storefront,
        'old' => 'return $this->soldOutRedirect($property, $validated, $adults, $children, $e->getMessage());',
        'new' => 'throw $e;',
        'tests' => [['file' => $vy15, 'filter' => 'nine adults and seven children']],
    ],
    [
        'id' => 'VY15-M8',
        'name' => 'the room page stops telling its guest fields the room\'s own limit',
        'file' => 'app/Http/Controllers/BookingEngineController.php',
        'old' => "'max_adults' => (int) \$rt->max_adults,",
        'new' => "'max_adults' => 16,",
        'tests' => [['file' => $vy15, 'filter' => 'caps its guest fields']],
    ],

    // ── VY-17 · one guest per email in an organisation ───────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY17-M1',
        'name' => 'every public booking makes a new guest again',
        'file' => $engine,
        'old' => '$guest = GuestEmailMatch::bookingGuest($guestEmail) ?? Guest::create([',
        'new' => '$guest = Guest::create([',
        'tests' => [['file' => $vy17, 'filter' => 'joins the SAME guest']],
    ],
    [
        'id' => 'VY17-M2',
        'name' => 'a guest whose erasure is scheduled is joined by a new booking',
        'file' => 'app/Support/Guest/GuestEmailMatch.php',
        'old' => "            ->whereNull('scheduled_deletion_at')\n",
        'new' => '',
        'tests' => [['file' => $vy17, 'filter' => 'ERASED guest']],
    ],
    [
        'id' => 'VY17-M3',
        'name' => 'the portal account no longer comes first: the booking joins the older, unclaimed record',
        'file' => 'app/Support/Guest/GuestEmailMatch.php',
        'old' => "->orderByRaw('CASE WHEN password IS NOT NULL AND email_verified_at IS NOT NULL THEN 0 ELSE 1 END')\n",
        'new' => "\n",
        'tests' => [['file' => $vy17, 'filter' => 'PORTAL ACCOUNT']],
    ],
    [
        'id' => 'VY17-M4',
        'name' => 'the match reads every organisation: another hotel\'s guest is joined',
        'file' => 'app/Support/Guest/GuestEmailMatch.php',
        'old' => "return Guest::query()\n            ->whereNull('anonymized_at')",
        'new' => "return Guest::query()->withoutGlobalScopes()\n            ->whereNull('anonymized_at')",
        'tests' => [['file' => $vy17, 'filter' => 'another ORGANISATION']],
    ],

    // ── VY-13 · check-in needs a room, and a room in service ─────────────────────────────────────────────────────────────────
    [
        'id' => 'VY13-M1',
        'name' => 'the action stops checking the rooms: a booking with no room is checked in',
        'file' => 'app/Actions/Reservations/CheckInReservation.php',
        'old' => "        \$this->assertRoomsReady(\$reservation);\n",
        'new' => "\n",
        'tests' => [['file' => $vy13, 'filter' => 'a booking with NO room is not checked in'], ['file' => $vy13, 'filter' => 'the guard is in the ACTION']],
    ],
    [
        'id' => 'VY13-M2',
        'name' => 'a closed room no longer refuses the check-in',
        'file' => 'app/Actions/Reservations/CheckInReservation.php',
        'old' => '} elseif (in_array((string) $unit->condition_status, Unit::UNBOOKABLE_CONDITIONS, true)) {',
        'new' => '} elseif (false) {',
        'tests' => [['file' => $vy13, 'filter' => 'OUT OF SERVICE is not checked in'], ['file' => $vy13, 'filter' => 'OUT OF ORDER room']],
    ],
    [
        'id' => 'VY13-M3',
        'name' => 'the front desk no longer shows the page that names the problem (the refusal becomes an error page)',
        'file' => 'app/Http/Controllers/FrontDeskActionController.php',
        'old' => "return view('operator.check-in-blocked', ['reservation' => \$model, 'problems' => \$e->problems]);",
        'new' => 'throw $e;',
        'tests' => [['file' => $vy13, 'filter' => 'a booking with NO room is not checked in']],
    ],
    [
        'id' => 'VY13-M4',
        'name' => 'a booking with no active line at all is checked in',
        'file' => 'app/Actions/Reservations/CheckInReservation.php',
        'old' => 'if ($lines->isEmpty()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $vy13, 'filter' => 'only line was cancelled']],
    ],

    // ── VY-14 · the reservations list and a reservation's page need a reason to read them ────────────────────────────────────
    [
        'id' => 'VY14-M1',
        'name' => 'the reservations LIST loses its gate',
        'file' => 'routes/web.php',
        'old' => "ReservationsList::class)->middleware(EnsureCanReadReservations::class)->name('reservations');",
        'new' => "ReservationsList::class)->name('reservations');",
        'tests' => [['file' => $vy14, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY14-M2',
        'name' => 'a reservation\'s PAGE loses its gate',
        'file' => 'routes/web.php',
        'old' => "ReservationDetail::class)->middleware(EnsureCanReadReservations::class)->name('reservations.show');",
        'new' => "ReservationDetail::class)->name('reservations.show');",
        'tests' => [['file' => $vy14, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY14-M3',
        'name' => 'the rule lets every signed-in person through',
        'file' => 'app/Support/Authorization/ReservationRead.php',
        'old' => 'return $user !== null && $user->canAny(self::PERMISSIONS);',
        'new' => 'return $user !== null;',
        'tests' => [['file' => $vy14, 'filter' => 'as reproduced']],
    ],
    [
        'id' => 'VY14-M4',
        'name' => 'the rule forgets the folio permission: the Accountant is locked out of the folio\'s own page',
        'file' => 'app/Support/Authorization/ReservationRead.php',
        'old' => "public const PERMISSIONS = ['reservations.view', 'folios.view'];",
        'new' => "public const PERMISSIONS = ['reservations.view'];",
        'tests' => [['file' => $vy14, 'filter' => 'CONTROL']],
    ],
    [
        'id' => 'VY14-M5',
        'name' => 'the sidebar offers the Reservations link to everyone again',
        'file' => 'resources/views/components/operator/shell.blade.php',
        'old' => "\\App\\Support\\Authorization\\ReservationRead::allowed(auth()->user()) ? route('app.reservations') : null],",
        'new' => "route('app.reservations')],",
        'tests' => [['file' => $vy14, 'filter' => 'sidebar does not offer']],
    ],
    [
        'id' => 'VY14-M6',
        'name' => 'the Calendar links every role to a reservation page again',
        'file' => 'app/Livewire/ReservationRack.php',
        'old' => "'canOpenReservations' => ReservationRead::allowed(auth()->user()),",
        'new' => "'canOpenReservations' => true,",
        'tests' => [['file' => $vy14, 'filter' => 'Calendar does not link']],
    ],

    [
        'id' => 'VY14-M7',
        'name' => 'a "New booking" notice links a refused role to the reservations list again',
        'file' => 'app/Support/Notifications/NotificationFeed.php',
        'old' => "'url' => self::reachable(is_string(\$n->data['url'] ?? null) ? (string) \$n->data['url'] : null),",
        'new' => "'url' => is_string(\$n->data['url'] ?? null) ? (string) \$n->data['url'] : null,",
        'tests' => [['file' => $vy14, 'filter' => 'New booking']],
    ],

    // ── VY-18 · the ID viewer, its audit trail, and how long an image is kept ────────────────────────────────────────────────
    [
        'id' => 'VY18-M1',
        'name' => 'the viewer route loses its permission gate',
        'file' => 'routes/web.php',
        'old' => "[GuestIdDocumentController::class, 'show'])->middleware('can:guest_documents.manage')->name('reservations.id-documents.show');",
        'new' => "[GuestIdDocumentController::class, 'show'])->name('reservations.id-documents.show');",
        'tests' => [['file' => $vy18, 'filter' => 'a role WITHOUT the permission']],
    ],
    [
        'id' => 'VY18-M2',
        'name' => 'an opening is no longer recorded',
        'file' => 'app/Http/Controllers/Operator/GuestIdDocumentController.php',
        'old' => "        \$ids->recordViewed(\$guestDocument);\n",
        'new' => "\n",
        'tests' => [['file' => $vy18, 'filter' => 'the opening is recorded']],
    ],
    [
        'id' => 'VY18-M3',
        'name' => 'a document is served under any booking of the organisation, not only its own',
        'file' => 'app/Http/Controllers/Operator/GuestIdDocumentController.php',
        'old' => "            ->where('reservation_id', \$model->getKey())\n",
        'new' => "\n",
        'tests' => [['file' => $vy18, 'filter' => 'another ORGANISATION']],
    ],
    [
        'id' => 'VY18-M4',
        'name' => 'the image may be cached by a browser or a CDN',
        'file' => 'app/Http/Controllers/Operator/GuestIdDocumentController.php',
        'old' => "'Cache-Control' => 'no-store, private, max-age=0',",
        'new' => "'Cache-Control' => 'public, max-age=3600',",
        'tests' => [['file' => $vy18, 'filter' => 'the opening is recorded']],
    ],
    [
        'id' => 'VY18-M5',
        'name' => 'the decision route accepts any word as a decision',
        'file' => 'app/Http/Controllers/Operator/GuestIdDocumentController.php',
        'old' => "'decision' => ['required', 'string', 'in:'.implode(',', GuestIdDocumentService::DECISIONS)],",
        'new' => "'decision' => ['required', 'string'],",
        'tests' => [['file' => $vy18, 'filter' => 'CHECKED or REJECTED']],
    ],
    [
        'id' => 'VY18-M6',
        'name' => 'the decision route loses its permission gate',
        'file' => 'routes/web.php',
        'old' => "[GuestIdDocumentController::class, 'review'])->middleware('can:guest_documents.manage')->name('reservations.id-documents.review');",
        'new' => "[GuestIdDocumentController::class, 'review'])->name('reservations.id-documents.review');",
        'tests' => [['file' => $vy18, 'filter' => 'CHECKED or REJECTED']],
    ],
    [
        'id' => 'VY18-M7',
        'name' => 'a no-show keeps its ID image',
        'file' => 'app/Listeners/PurgeGuestIdDocumentsOnTermination.php',
        'old' => "private const TRIGGERS = ['reservation.cancelled', 'reservation.no_show'];",
        'new' => "private const TRIGGERS = ['reservation.cancelled'];",
        'tests' => [['file' => $vy18, 'filter' => 'NO-SHOW']],
    ],
    [
        'id' => 'VY18-M8',
        'name' => 'the purge-on-cancellation listener is not registered',
        'file' => 'app/Providers/AppServiceProvider.php',
        'old' => "        Event::listen(DomainEventRecorded::class, PurgeGuestIdDocumentsOnTermination::class);\n",
        'new' => "\n",
        'tests' => [['file' => $vy18, 'filter' => 'CANCELLING a booking']],
    ],
    [
        'id' => 'VY18-M9',
        'name' => 'the nightly job forgets cancelled and no-show bookings',
        'file' => 'app/Console/Commands/PurgeExpiredGuestDocuments.php',
        'old' => "                        ->orWhereHas('reservation', fn (\$q) => \$q->whereIn('status', self::STAY_WILL_NOT_HAPPEN))\n",
        'new' => "\n",
        'tests' => [['file' => $vy18, 'filter' => 'nightly job']],
    ],
    [
        'id' => 'VY18-M10',
        'name' => 'the nightly job never matches an image whose booking is gone',
        'file' => 'app/Console/Commands/PurgeExpiredGuestDocuments.php',
        'old' => "->orWhereDoesntHave('reservation');",
        'new' => ';',
        'tests' => [['file' => $vy18, 'filter' => 'nightly job']],
    ],
    [
        'id' => 'VY18-M11',
        'name' => 'the reservation page shows the ID card (and its links) to a role without the permission',
        'file' => 'app/Livewire/ReservationDetail.php',
        'old' => "if (auth()->user()?->can('guest_documents.manage') !== true) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy18, 'filter' => 'shows the ID card to a permitted role only']],
    ],

    // ── VY-12 · the scheduled backup ─────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY12-M1',
        'name' => 'the passphrase is not handed to the script',
        'file' => 'app/Console/Commands/RunBackup.php',
        'old' => "'BACKUP_PASSPHRASE' => \$passphrase,",
        'new' => "'BACKUP_PASSPHRASE' => '',",
        'tests' => [['file' => $vy12, 'filter' => 'read from the FILE']],
    ],
    [
        'id' => 'VY12-M2',
        'name' => 'a blank passphrase file counts as set up',
        'file' => 'app/Support/Ops/BackupStatus.php',
        'old' => "return \$value === '' ? null : \$value;",
        'new' => 'return $value;',
        'tests' => [['file' => $vy12, 'filter' => 'REFUSES']],
    ],
    [
        'id' => 'VY12-M3',
        'name' => 'the job is scheduled even on a host with no passphrase file',
        'file' => 'routes/console.php',
        'old' => '->when(fn (): bool => BackupStatus::configured())',
        'new' => '',
        'tests' => [['file' => $vy12, 'filter' => 'on the schedule']],
    ],
    [
        'id' => 'VY12-M4',
        'name' => 'an old backup still reads as healthy',
        'file' => 'app/Support/Ops/BackupStatus.php',
        'old' => "'healthy' => \$age !== null && \$age <= self::STALE_AFTER_SECONDS,",
        'new' => "'healthy' => \$age !== null,",
        'tests' => [['file' => $vy12, 'filter' => 'ALARM']],
    ],
    [
        'id' => 'VY12-M5',
        'name' => 'a failed off-server copy is reported as success',
        'file' => 'app/Console/Commands/RunBackup.php',
        'old' => "return \$this->failed('the archive was made but NOT copied off the server (disk \"'.\$disk.'\"): '.\$e->getMessage());",
        'new' => 'return self::SUCCESS;',
        'tests' => [['file' => $vy12, 'filter' => 'off-server disk is named']],
    ],
    [
        'id' => 'VY12-M6',
        'name' => 'the board shows green with no copy off the server',
        'file' => 'app/Support/Platform/Health/HealthBoard.php',
        'old' => "if (! \$report['offsite_healthy']) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy12, 'filter' => 'ALARM']],
    ],
    [
        'id' => 'VY12-M7',
        'name' => 'a failing script is reported as success',
        'file' => 'app/Console/Commands/RunBackup.php',
        'old' => 'if (! $process->isSuccessful()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $vy12, 'filter' => 'a failing backup exits non-zero']],
    ],

    // ── VY-09 · a public statement comes back ────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY09-M1',
        'name' => 'the home page says "in real time" again',
        'file' => 'resources/views/marketing/home.blade.php',
        'old' => 'housekeeping and maintenance from one place — and gives you',
        'new' => 'housekeeping and maintenance in real time — and gives you',
        'tests' => [['file' => $vy09, 'filter' => 'in real time']],
    ],
    [
        'id' => 'VY09-M2',
        'name' => 'the modules page says every module is in every plan again',
        'file' => 'resources/views/marketing/modules.blade.php',
        'old' => '>Seventeen of the eighteen are in every plan</h2>',
        'new' => '>Every module is in every plan</h2>',
        'tests' => [['file' => $vy09, 'filter' => 'Every module is in every plan']],
    ],
    [
        'id' => 'VY09-M3',
        'name' => 'the pricing page\'s FAQ data promises an import again',
        'file' => 'resources/views/marketing/pricing.blade.php',
        'old' => "'text' => 'Yes — start with one property, no card.'",
        'new' => "'text' => 'Yes — start with one property, no card. We will import your bookings during the trial.'",
        'tests' => [['file' => $vy09, 'filter' => 'import your bookings']],
    ],
];

return $set;
