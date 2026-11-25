<?php

declare(strict_types=1);

// Mutation set audit-nov25-third — the third batch of the audit of 2026-11-24 (fixed 2026-11-25): VY-23 … VY-35.
// Every guard those fixes added is removed here, one at a time, and the test aimed at it must go RED.
// Run with `php tests/Mutations/run.php --set=audit-nov25-third`.
// ⚠️ Not mutated, and why: VY-23's two belts in release() — the pending row re-read under the lock, and "a booking with a settled
//    payment is never released" — each stops the same case the other does (a payment that settled meanwhile is no longer pending AND
//    is settled), so removing one alone survives truthfully; VY-26's "never close a period twice" (stamped lines already leave nothing owed); VY-28's delegated JS
//    listeners (no JavaScript runs in this suite — proven in a real browser instead, 2026-11-25). Each was read, not assumed.

$vy23 = 'tests/Feature/Vy23UnpaidBookingsReleaseTest.php';
$vy24 = 'tests/Feature/Vy24HousekeeperReportsAProblemTest.php';
$vy26 = 'tests/Feature/Vy26StatementsAndNoShowsAutomatedTest.php';
$vy27 = 'tests/Feature/Vy27SecondPersonInspectionTest.php';
$vy28 = 'tests/Feature/Vy28ScriptNonceTest.php';
$vy29 = 'tests/Feature/Vy29MarketingSeoAndSessionlessTest.php';
$vy32a = 'tests/Feature/Vy32ArrivalPackRoomsAndClockTest.php';
$vy32s = 'tests/Feature/Vy32StatementNumberingAndFxTest.php';
$vy33 = 'tests/Feature/Vy33QueueDashboardOnlyWithRedisTest.php';
$vy34 = 'tests/Feature/Vy34OperationsPhotosArePrivateTest.php';
$vy35 = 'tests/Feature/Vy35LapsedWorkspaceClosesStorefrontTest.php';

$release = 'app/Support/Booking/UnpaidBookingRelease.php';
$storefront = 'app/Http/Controllers/BookingEngineController.php';

$set = [
    // ── VY-23 · unpaid card bookings give their room back; quotes expire on a schedule; the deposit is real ─────────────────────
    [
        'id' => 'VY23-M1',
        'name' => 'a closed, unpaid card page never counts as over — the booking keeps its room for ever again',
        'file' => $release,
        'old' => 'return $page->expired_at !== null || ($page->expires_at !== null && $page->expires_at->lessThan($graceAgo));',
        'new' => 'return false;',
        'tests' => [['file' => $vy23, 'filter' => 'as read']],
    ],
    [
        'id' => 'VY23-M3',
        'name' => 'a COMPLETED page (a payment still in flight) is released like an abandoned one',
        'file' => $release,
        'old' => 'if (! $page instanceof CheckoutSession || $page->completed_at !== null) {',
        'new' => 'if (! $page instanceof CheckoutSession) {',
        'tests' => [['file' => $vy23, 'filter' => 'was COMPLETED']],
    ],
    [
        'id' => 'VY23-M4',
        'name' => 'Stripe\'s "the page expired" closes the record and nothing else again',
        'file' => 'app/Http/Controllers/Concerns/HandlesStripeLifecycleEvents.php',
        'old' => "if (\$event->type === 'checkout.session.expired') {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy23, 'filter' => 'page expired" releases the booking at once']],
    ],
    [
        'id' => 'VY23-M5',
        'name' => 'the storefront\'s Stripe page is opened with no expiry (Stripe\'s 24 hours)',
        'file' => $storefront,
        'old' => 'expiresAt: UnpaidBookingRelease::pageExpiresAt(),',
        'new' => 'expiresAt: null,',
        'tests' => [['file' => $vy23, 'filter' => 'as read']],
    ],
    [
        'id' => 'VY23-M6',
        'name' => 'a deposit property with no way to take a card books anyway (the web checkout)',
        'file' => $storefront,
        'old' => 'if ($depositPercent !== null && ! $cards) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $vy23, 'filter' => 'no way to take a card']],
    ],
    [
        'id' => 'VY23-M7',
        'name' => 'the deposit is ignored: the whole balance is charged',
        'file' => $storefront,
        'old' => '$chargeMinor = $depositPercent !== null ? StorefrontPayment::depositMinor($balanceMinor, $depositPercent) : $balanceMinor;',
        'new' => '$chargeMinor = $balanceMinor;',
        'tests' => [['file' => $vy23, 'filter' => 'charges its share by card']],
    ],
    [
        'id' => 'VY23-M8',
        'name' => 'the JSON endpoint books a deposit property with no card gateway',
        'file' => $storefront,
        'old' => 'if (StorefrontPayment::depositPercent($property) !== null && ! $this->engine->takesCardPayments()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $vy23, 'filter' => 'no way to take a card']],
    ],
    [
        'id' => 'VY23-M9',
        'name' => 'quote expiry is taken off the schedule again',
        'file' => 'routes/console.php',
        'old' => "Schedule::command('booking-quotes:expire')->everyFifteenMinutes()->withoutOverlapping();",
        'new' => '// (removed)',
        'tests' => [['file' => $vy23, 'filter' => 'quotes past their expiry']],
    ],

    // ── VY-24 · a housekeeper reports a problem ──────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY24-M1',
        'name' => 'the report route loses its gate: a role that may only look can open work orders',
        'file' => 'routes/web.php',
        'old' => "Route::post('/housekeeping/{task}/report', [HousekeepingActionController::class, 'reportProblem'])->middleware('can:housekeeping.manage')->name('housekeeping.report');",
        'new' => "Route::post('/housekeeping/{task}/report', [HousekeepingActionController::class, 'reportProblem'])->name('housekeeping.report');",
        'tests' => [['file' => $vy24, 'filter' => 'the gate is the housekeeper']],
    ],
    [
        'id' => 'VY24-M2',
        'name' => 'a report takes the room out of order — managing, not reporting',
        'file' => 'app/Http/Controllers/HousekeepingActionController.php',
        'old' => "'takes_out_of_service' => false,",
        'new' => "'takes_out_of_service' => true, 'acknowledge_oversell' => true,",
        'tests' => [['file' => $vy24, 'filter' => 'as read']],
    ],

    // ── VY-26 · automatic no-shows and monthly statements ────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY26-M1',
        'name' => 'the no-show line falls at midnight, not at the property\'s check-out time',
        'file' => 'app/Console/Commands/MarkNoShows.php',
        'old' => "\$latestArrival = \$now->format('H:i') >= \$cutoff ? \$now->subDay() : \$now->subDays(2);",
        'new' => '$latestArrival = $now->subDay();',
        'tests' => [['file' => $vy26, 'filter' => 'not a minute before']],
    ],
    [
        'id' => 'VY26-M2',
        'name' => 'a stay that is already over is relabelled a no-show',
        'file' => 'app/Console/Commands/MarkNoShows.php',
        'old' => "->whereDate('check_out', '>', \$now->toDateString())",
        'new' => '',
        'tests' => [['file' => $vy26, 'filter' => 'NEVER: a guest who checked in']],
    ],
    [
        'id' => 'VY26-M3',
        'name' => 'no-shows are marked at properties that never switched the rule on',
        'file' => 'app/Console/Commands/MarkNoShows.php',
        'old' => "->where('auto_no_show', true)->pluck('property_id')",
        'new' => "->pluck('property_id')",
        'tests' => [['file' => $vy26, 'filter' => 'NEVER: a guest who checked in']],
    ],
    [
        'id' => 'VY26-M4',
        'name' => 'statements close on any day, not the day the organisation chose',
        'file' => 'app/Console/Commands/CloseMonthlyStatements.php',
        'old' => "fn (\$q) => \$q->where('statement_close_day', \$today->day))",
        'new' => 'fn ($q) => $q)',
        'tests' => [['file' => $vy26, 'filter' => 'on the day the organisation picked']],
    ],

    // ── VY-27 · inspection by a second person ────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY27-M1',
        'name' => 'the person who cleaned the room inspects it again, rule or no rule',
        'file' => 'app/Services/HousekeepingService.php',
        'old' => 'if ($required && (string) $task->cleaned_by === $actorId) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $vy27, 'filter' => 'as read']],
    ],
    [
        'id' => 'VY27-M2',
        'name' => 'who cleaned the room is not recorded',
        'file' => 'app/Services/HousekeepingService.php',
        'old' => "\$updates['cleaned_by'] = \$actorId;",
        'new' => "\$updates['cleaned_by'] = null;",
        'tests' => [['file' => $vy27, 'filter' => 'records WHO cleaned it']],
    ],

    // ── VY-28 · script nonces ────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY28-M1',
        'name' => 'the policy allows inline scripts again',
        'file' => 'app/Http/Middleware/SecurityHeaders.php',
        'old' => "\"'self' 'nonce-\".Vite::cspNonce().\"' 'unsafe-eval'\"",
        'new' => "\"'self' 'unsafe-inline' 'unsafe-eval'\"",
        'tests' => [['file' => $vy28, 'filter' => 'on a real page of every realm']],
    ],
    [
        'id' => 'VY28-M2',
        'name' => '@nonce prints an empty attribute: every inline script would be blocked',
        'file' => 'app/Providers/AppServiceProvider.php',
        'old' => "Blade::directive('nonce', fn (): string => '<?php echo \\'nonce=\"\\'.e(\\Illuminate\\Support\\Facades\\Vite::cspNonce() ?? \\'\\').\\'\"\\'; ?>');",
        'new' => "Blade::directive('nonce', fn (): string => '');",
        // ⚠️ aimed at the directive's own test: the page renders read COMPILED views, which a directive change does not recompile
        'tests' => [['file' => $vy28, 'filter' => 'directive itself prints']],
    ],

    // ── VY-29 · marketing: no session, the sitemap, the consent's own CSRF defence ───────────────────────────────────────────────
    [
        'id' => 'VY29-M1',
        'name' => 'the marketing pages start a session again',
        'file' => 'routes/web.php',
        'old' => "Route::controller(MarketingController::class)->name('marketing.page.')->withoutMiddleware(MarketingSession::SESSIONLESS)->group(function () {",
        'new' => "Route::controller(MarketingController::class)->name('marketing.page.')->group(function () {",
        'tests' => [['file' => $vy29, 'filter' => 'a marketing page starts no session']],
    ],
    [
        'id' => 'VY29-M2',
        'name' => 'the session-free consent post accepts a post from any site',
        'file' => 'app/Http/Controllers/CookieChoiceController.php',
        'old' => 'abort_unless(self::sameSite($request), 403);',
        'new' => '',
        'tests' => [['file' => $vy29, 'filter' => 'accepted only from this site']],
    ],
    [
        'id' => 'VY29-M3',
        'name' => 'the sitemap lists pages that say noindex',
        'file' => 'app/Support/Marketing/MarketingSitemap.php',
        'old' => "public const NOINDEX = ['case-studies', 'story-cyclades', 'story-lisbon', 'story-rotterdam'];",
        'new' => 'public const NOINDEX = [];',
        'tests' => [['file' => $vy29, 'filter' => 'lists every indexable marketing page']],
    ],

    // ── VY-32 · the five small defects ───────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY32-M1',
        'name' => 'the exchange rounds down: the exact half is lost again',
        'file' => 'app/Services/OwnerStatementService.php',
        'old' => 'toScale(0, RoundingMode::HalfUp)',
        'new' => 'toScale(0, RoundingMode::Down)',
        'tests' => [['file' => $vy32s, 'filter' => 'as read']],
    ],
    [
        'id' => 'VY32-M2',
        'name' => 'a stale copy of a sealed statement is sealed a second time',
        'file' => 'app/Services/OwnerStatementService.php',
        'old' => "if (OwnerStatement::query()->whereKey(\$statement->getKey())->value('locked')) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $vy32s, 'filter' => 'numbering']],
    ],
    [
        'id' => 'VY32-M3',
        'name' => 'a second room\'s door code shows before the window opens',
        'file' => 'app/Support/Guest/ArrivalPack.php',
        'old' => "'door_code' => \$released ? self::clean(\$u->door_code) : null,",
        'new' => "'door_code' => self::clean(\$u->door_code),",
        'tests' => [['file' => $vy32a, 'filter' => 'withheld means withheld for EVERY room']],
    ],
    [
        'id' => 'VY32-M4',
        'name' => 'the Arrival page asks the server\'s day again, not the property\'s',
        'file' => 'app/Http/Controllers/Guest/GuestArrivalController.php',
        'old' => "'pack' => ArrivalPack::for(\$model, ArrivalPack::today(\$model->property)),",
        'new' => "'pack' => ArrivalPack::for(\$model, \\Carbon\\CarbonImmutable::today()),",
        'tests' => [['file' => $vy32a, 'filter' => 'AHEAD of UTC']],
    ],

    // ── VY-33 · the queue dashboard only where its queue is ──────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY33-M1',
        'name' => 'Horizon is registered whatever the queue is',
        'file' => 'app/Support/Ops/QueueDashboard.php',
        'old' => "return config('queue.default') === 'redis';",
        'new' => 'return true;',
        'tests' => [['file' => $vy33, 'filter' => 'not one Horizon route is registered']],
    ],

    // ── VY-34 · operations photos are private ────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY34-M1',
        'name' => 'new photos go to the public disk again',
        'file' => 'app/Support/Media/OperationsPhotos.php',
        'old' => "public const DISK = 'local';",
        'new' => "public const DISK = 'public';",
        'tests' => [['file' => $vy34, 'filter' => 'as read']],
    ],
    [
        'id' => 'VY34-M2',
        'name' => 'the photo route skips the person\'s properties',
        'file' => 'app/Support/Media/OperationsPhotos.php',
        'old' => "'hk_proof' => \$scope->task((string) \$media->mediable_id),",
        'new' => "'hk_proof' => null,",
        'tests' => [['file' => $vy34, 'filter' => 'staff limited to another property']],
    ],
    [
        'id' => 'VY34-M3',
        'name' => 'the photo response may be stored by a shared cache',
        'file' => 'app/Support/Media/OperationsPhotos.php',
        'old' => '$response->setPrivate();',
        'new' => '',
        'tests' => [['file' => $vy34, 'filter' => 'never stored by the browser or the edge']],
    ],
    [
        'id' => 'VY34-M4',
        'name' => '/media serves housekeeping photos by address again',
        'file' => 'app/Http/Controllers/PublicMediaController.php',
        'old' => "public const SERVED_DIRECTORIES = ['room-photos', 'demo-photos', 'brand-logos', 'avatars', 'unit-request-photos'];",
        'new' => "public const SERVED_DIRECTORIES = ['room-photos', 'demo-photos', 'brand-logos', 'avatars', 'housekeeping', 'maintenance', 'unit-request-photos'];",
        'tests' => [['file' => $vy34, 'filter' => 'signed out, the photo is not served']],
    ],

    // ── VY-35 · a lapsed workspace's storefront is closed ────────────────────────────────────────────────────────────────────────
    [
        'id' => 'VY35-M1',
        'name' => 'a lapsed workspace keeps its storefront open',
        'file' => 'app/Support/Storefront/StorefrontAvailability.php',
        'old' => 'return $organization->billingStatus()->isLapsed();',
        'new' => 'return false;',
        'tests' => [['file' => $vy35, 'filter' => 'as read']],
    ],
];

return $set;
