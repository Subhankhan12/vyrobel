<?php

use App\Models\Folio;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerUser;
use App\Models\PayoutMethod;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\FolioService;
use App\Services\InboxService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Owner\OwnerCollection;
use App\Support\Owner\OwnerTeamRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

// Shared owner-accounting test helpers (Phase 3 · Module 1). In a Support file (required from
// Pest.php) so any owner test can reuse them without depending on cross-file function-load order —
// same discipline as channel_helpers.php.

if (! function_exists('ownUnit')) {
    /** Assign a unit to a fresh owner and give that owner a governing agreement covering the stay. */
    function ownUnit(Unit $unit, array $agreement = []): Owner
    {
        $owner = Owner::factory()->create(['payout_currency' => 'EUR']);
        $unit->update(['owner_id' => $owner->id]);

        ManagementAgreement::factory()->create(array_merge([
            'owner_id' => $owner->id,
            'unit_id' => null,
            'commission_pct' => 15.00,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ], $agreement));

        return $owner;
    }
}

if (! function_exists('ownerActor')) {
    /**
     * A portal user who IS the given owner (`owners.user_id`), so an owner-scoped read sees THAT owner's data.
     *
     * ⚠️ WHY THIS EXISTS (audit E-1 follow-up, 2026-08-29). Several fixtures attributed their data to an owner
     * built by {@see ownUnit()} — which creates an Owner with NO user — and then acted as `ownerRoleUser()`,
     * which mints a SECOND owner and reassigns the unit to it. The acting user therefore owned the UNIT but not
     * the LEDGER DATA, and the assertions only passed because OwnerScope fell open on the
     * `Livewire::actingAs()->test()` path (which, like /livewire/update, runs no route middleware).
     * Now that the scope holds on every path, the fixture has to say what it always meant: act as the owner who
     * actually owns the data. The assertions are untouched — only the fixture's world is corrected.
     */
    function ownerActor(Owner $owner, array $scenario): User
    {
        $user = User::factory()->create(['name' => 'Olive Owner', 'status' => 'active']);

        Membership::create([
            'organization_id' => $scenario['org']->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $owner->forceFill(['user_id' => $user->id])->save();

        return $user;
    }
}

if (! function_exists('postRoom')) {
    /** Open a folio and post the lump room charge (how BookingEngineService posts room revenue). */
    function postRoom(Reservation $reservation, int $minor): void
    {
        $folio = app(FolioService::class)->openFolio($reservation);
        app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of($minor, 'EUR'));
    }
}

if (! function_exists('collectFolio')) {
    /**
     * ⚠️ SETTLE the reservation's folio in full — i.e. the guest actually PAID.
     *
     * ⚠️ NEEDED BECAUSE OF THE COLLECTION GATE (②+accrual, 2026-08-23). `owner_ledger` is an accrual and
     * still records what is OWED the moment the folio charge is posted — but a STATEMENT now claims only
     * rows backed by money actually received ({@see OwnerCollection}), because a
     * finalized statement is what the payout engine pays from.
     *
     * ⚠️ So `postRoom()` alone leaves a realistic PAY-ON-ARRIVAL booking: charged, unpaid, and correctly
     * NOT payable. A test that means to exercise the projection maths must say the guest paid — which is
     * what this does. It is deliberately NOT folded into `postRoom()`: doing so would hide the gate from
     * every fixture in the suite and leave the most dangerous path untested.
     */
    function collectFolio(Reservation $reservation): void
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
        $due = FolioTotals::for($folio)['balance_minor'];

        if ($due <= 0) {
            return;
        }

        $payment = app(FolioService::class)->recordPayment($folio, Money::of($due, (string) $folio->currency));
        app(FolioService::class)->settlePayment($payment, 'succeeded');
    }
}

if (! function_exists('ownerNet')) {
    /** Sum signed minor across every ledger line for an owner (credits +, debits −, reversals included). */
    function ownerNet(string $ownerId): int
    {
        return OwnerLedgerEntry::where('owner_id', $ownerId)->get()
            ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
    }
}

if (! function_exists('assertNoRawBlade')) {
    /**
     * Assert no unrendered Blade directive leaked into the response body.
     *
     * A directive whose `@` follows a word character is NOT compiled (Blade guards with `\B`, so an
     * email address renders literally) — e.g. `expenses@if ($x) …@endif` ships the raw source to the
     * user. That renders as ordinary text, so an assertSee/assertDontSee suite passes straight over
     * it; only scanning the body catches it. Found in the browser on the owner Expenses screen
     * (Wave 1 · Sub-gate 1) — this locks the whole class of bug for every owner screen.
     *
     * ⚠️ THIS GUARD WAS VACUOUS UNTIL 2026-08-10 AND COULD NEVER FAIL. It used
     * `expect($body)->not->toContain($directive, "…message…")`, but Pest's `toContain()` is VARIADIC
     * OVER NEEDLES — the "message" was read as a SECOND NEEDLE, so the assertion meant "the body does
     * not contain BOTH the directive AND that sentence", which is true whenever the sentence is absent.
     * Mutation-proven: a literal `expenses@endif` was rendered into the owner dashboard and this
     * helper stayed GREEN. It now uses PHPUnit's assertion, which takes a real message argument, and
     * the same mutation turns it RED. (The `@` here is deliberately not adjacent to a word character.)
     */
    function assertNoRawBlade(TestResponse $response): void
    {
        $body = $response->getContent();

        foreach (['@if', '@endif', '@else', '@elseif', '@foreach', '@endforeach', '@forelse', '@empty', '@unless', '@isset', '@php', '@endphp'] as $directive) {
            Assert::assertStringNotContainsString(
                $directive,
                $body,
                "Unrendered Blade directive {$directive} leaked into the rendered page",
            );
        }
    }
}

if (! function_exists('assertDontSeeInContent')) {
    /**
     * `assertDontSee` over the page's CONTENT only — inline <style> and <script> blocks are stripped first.
     *
     * ⚠️ Why this exists. A fabrication guard states a claim about what the owner is SHOWN
     * ("no invented earnings goal", #39). A raw `assertDontSee('goal')` also matches a CSS class name
     * like `.vy-ov-goal` in the layout's inline stylesheet — so adding a correctly-refused element's
     * STYLES turns the guard red while the page renders nothing at all. That is the same over-broad-guard
     * trap the operator pass hit twice (the CSS-comment false positives); the fix is to scope the guard to
     * the CLAIM, never to weaken it. The needle must still be absent from every rendered byte of content.
     *
     * ⚠️ It uses PHPUnit's assertion, NOT `expect(...)->not->toContain($needle, $message)` — Pest's
     * `toContain()` is VARIADIC over needles, so a "message" passed as a second argument becomes a
     * SECOND NEEDLE. `->not->toContain('goal', 'some message')` then means "does not contain BOTH",
     * which is true whenever the message string is absent — i.e. always. That assertion can never
     * fail. Proven by mutation here, and it is why this helper asserts the way it does.
     */
    function assertDontSeeInContent(TestResponse $response, string $needle): void
    {
        $content = (string) preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $response->getContent());

        Assert::assertStringNotContainsString($needle, $content, "\"{$needle}\" was rendered to the page content");
    }
}

if (! function_exists('lineFor')) {
    /** The single live (non-reversed) ledger line of a given entry_type for an owner. */
    function lineFor(string $ownerId, string $entryType): ?OwnerLedgerEntry
    {
        return OwnerLedgerEntry::where('owner_id', $ownerId)
            ->where('entry_type', $entryType)->whereNull('reverses_id')->first();
    }
}

if (! function_exists('inboxOwner')) {
    /**
     * An owner with a real portal login, a unit and an agreement — as production builds one.
     *
     * In Support/ rather than a test file because BOTH the isolation suite and the screen suite need it, and
     * a `function` declared in one Pest file only exists when THAT file loads — running a sibling in
     * isolation would fatal on an undefined function. (Exactly the trap `ownerRoleUser()` hit in Wave 2.)
     *
     * @param  array<string, mixed>  $scenario
     * @return array{user: User, owner: Owner}
     */
    function inboxOwner(array $scenario, string $name, int $unitIndex): array
    {
        $user = User::factory()->create(['name' => $name, 'status' => 'active']);
        Membership::create([
            'organization_id' => $scenario['org']->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $owner = Owner::factory()->create([
            'user_id' => $user->id,
            'name' => $name,
            'payout_currency' => 'EUR',
        ]);
        $scenario['units'][$unitIndex]->update(['owner_id' => $owner->id]);

        ManagementAgreement::factory()->create([
            'owner_id' => $owner->id,
            'commission_pct' => 15.00,
            'effective_from' => '2026-01-01',
        ]);

        return ['user' => $user, 'owner' => $owner];
    }
}

if (! function_exists('ownerThread')) {
    /** An owner↔operator thread, opened through the REAL service (never a raw insert). */
    function ownerThread(Owner $owner, string $subject = 'A question'): MessageThread
    {
        return app(InboxService::class)->startThread([
            'channel' => 'manual',      // internal: recording IS the delivery
            'subject' => $subject,
            'owner_id' => $owner->id,   // guest_id XOR owner_id — the discriminator
        ]);
    }
}

if (! function_exists('ownerSubUser')) {
    /**
     * An OWNER-TEAM sub-user (Phase 7c): a User with an active Membership (so the tenant resolves) + the
     * owner-realm `External Owner` role (so #26 routes them to /owner, not a dead-end 403) + an `owner_users`
     * row linking them to $ownerId at $role. Their portal scope + acting role resolve from that pivot.
     *
     * Shared in Support/ (not a test file) so BOTH the isolation and capability suites use it without
     * cross-file function-load order — the exact discipline the header describes.
     *
     * @param  array<string, mixed>  $scenario  a seedScenario() result
     */
    function ownerSubUser(array $scenario, string $ownerId, string $role = 'view_only', string $status = 'active'): User
    {
        $user = User::factory()->create(['status' => 'active']);
        Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        grantBackOfficeRole($user, $scenario['org'], 'External Owner');
        OwnerUser::create([
            'organization_id' => $scenario['org']->id,
            'owner_id' => $ownerId,
            'user_id' => $user->id,
            'role' => $role,
            'status' => $status,
        ]);

        return $user;
    }
}

if (! function_exists('ownerTeamCast')) {
    /**
     * ⚠️⚠️ THE OWNER-TEAM CAST — one primary owner and one sub-user per capability role.
     *
     * ⚠️ IT LIVES HERE BECAUSE IT IS SHARED, AND IT WAS NOT. Until FIX 41 this was a plain global function
     * declared inside `OwnerTeamCapabilityTest.php` while `OwnerCapabilityMutationBoundaryTest.php` also
     * called it. Pest loads test files independently, so running the SECOND file alone died with
     * `Call to undefined function ownerTeamCast()` — which reads exactly like a regression, in a project
     * that runs subsets constantly during mutation work. 🔬 The failure was real and was mistaken for one
     * during FIX 40's control run.
     *
     * ⚠️ THE FILE'S OWN RULE, APPLIED: this Support file is required from `tests/Pest.php`, so helpers here
     * "can be reused without depending on cross-file function-load order" — the discipline the docblock at
     * the top of this file already states, and the one that shape broke.
     *
     * ⚠️ NOTHING ABOUT WHAT EITHER TEST ASSERTS CHANGED — this is a MOVE, byte-for-byte in its body.
     */
    function ownerTeamCast(): array
    {
        $s = seedScenario(1);
        $owner = ownerRoleUser($s, 0);
        $ownerId = (string) $s['units'][0]->fresh()->owner_id;
        $s['units'][0]->update(['code' => 'TEAMUNIT']);

        return [
            'owner' => $owner,                                              // primary owner → co_owner + team
            'co' => ownerSubUser($s, $ownerId, OwnerTeamRole::CO_OWNER),
            'accountant' => ownerSubUser($s, $ownerId, OwnerTeamRole::ACCOUNTANT),
            'view' => ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY),
            'scenario' => $s,
        ];
    }
}

if (! function_exists('unitDetailOwner')) {
    /**
     * A linked owner-portal user who owns the unit at $index. Reuses the shared ownUnit() helper
     * (tests/Support/owner_helpers.php) for the owner + governing agreement + unit assignment, and only
     * adds the portal user link on top. ⚠️ MOVED HERE IN FIX 41. Its old docblock said it was "named for this file so it cannot
     * collide with another test file's helper" — but `PerUnitRateDisplayTest.php` already called it, so
     * running THAT file alone would have fatalled the moment its `->skip()` was lifted. Latent sibling of
     * the `ownerTeamCast()` defect; both now live where this file's own docblock says shared helpers go.
     *
     * @param  array<string, mixed>  $unitAttributes  real `units` columns to set before assigning the owner
     * @return array{user: User, owner: Owner, unit: Unit}
     */
    function unitDetailOwner(array $s, int $index, array $unitAttributes = []): array
    {
        /** @var Unit $unit */
        $unit = $s['units'][$index];

        if ($unitAttributes !== []) {
            $unit->update($unitAttributes);
        }

        $owner = ownUnit($unit);   // shared helper: owner + 15% agreement + unit->owner_id

        $user = User::factory()->create(['name' => 'Amara Okafor']);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);   // link the owner record to a portal user

        return ['user' => $user, 'owner' => $owner, 'unit' => $unit];
    }
}

// ── The operator Owners page (2026-10-01) — fixtures shared by OwnersPageFixesTest and OwnersPageRedesignTest ──────────

if (! function_exists('owpAdmin')) {
    /** An operator who may invite (the organisation administrator holds `users.manage`); the factory's password is "password". */
    function owpAdmin(array $scenario): User
    {
        return operatorUser($scenario, [], 'Property Owner');
    }
}

if (! function_exists('owpOwner')) {
    /** An active owner with tax details and a payout account on file. */
    function owpOwner(array $scenario, string $name = 'Amara Okafor', array $extra = []): Owner
    {
        $owner = Owner::factory()->create(array_merge(['name' => $name, 'email' => Str::lower(Str::random(8)).'@owner.test',
            'payout_currency' => 'EUR', 'status' => 'active', 'tax_id' => 'IE1234567X'], $extra));
        PayoutMethod::create(['owner_id' => $owner->id, 'label' => 'Main', 'account_holder' => $name, 'iban' => 'IE29AIBK93115212344821',
            'iban_last4' => '4821', 'iban_country' => 'IE', 'currency' => 'EUR', 'is_default' => true]);

        return $owner;
    }
}

if (! function_exists('owpStatement')) {
    /** A finalized statement for a month, written straight to the table — a DISPLAY fixture; the money path is tested elsewhere. */
    function owpStatement(Owner $owner, string $month, int $net, array $extra = []): OwnerStatement
    {
        $start = CarbonImmutable::parse($month.'-01');

        return OwnerStatement::create(array_merge([
            'owner_id' => $owner->id, 'period_start' => $start->toDateString(), 'period_end' => $start->endOfMonth()->toDateString(),
            'currency' => 'EUR', 'source_currency' => 'EUR', 'status' => 'finalized', 'locked' => true,
            'gross_minor' => $net, 'net_source_minor' => $net, 'net_payable_minor' => $net, 'has_unverified' => false,
            'sequence_no' => 1, 'finalized_at' => now(),
        ], $extra));
    }
}
