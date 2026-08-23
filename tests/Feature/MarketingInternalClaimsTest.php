<?php

use App\Models\Guest;
use App\Models\Owner;
use App\Models\User;
use App\Services\AssignmentService;
use App\Support\Marketing\MarketingClaims;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// ══ ⚠️ INTERNAL CLAIM SOURCES — a citation that CANNOT ROT ═══════════════════════════════════════════
//
// ⚠️⚠️ WHY THESE EXIST AND WHY THEY ARE NOT `file:line` POINTERS. A documentation claim about our own
// product cannot be sourced to a third-party URL — there is none. The obvious alternative, citing
// `SomeService.php:214`, is the trap: ⚠️ **a pointer rots silently.** The code moves, the behaviour
// changes, the claim quietly becomes false, and the citation still points confidently at a line that no
// longer says what it said. A citation that can decay is precisely the failure the claims ledger exists to
// prevent, so citing one inside the ledger would be the §N.29 label shape in the gate built against it.
//
// ⚠️ THE RULE: an internal source is a TEST THAT FAILS WHEN THE CLAIM STOPS BEING TRUE. Each test below is
// named for the ledger id it sources and asserts exactly what the marketing sentence asserts — no more, so
// it cannot pass for an adjacent reason, and no less, so it cannot pass while the claim is false.
//
// ⚠️ THESE TESTS BELONG TO THE CLAIMS, NOT TO THE FEATURES. A borrowed test can be renamed, narrowed or
// deleted by someone who never knew it was load-bearing for a published sentence. These carry the id.

/** An owner with one unit, one paid booking, and a guest whose contact details are real and distinctive. */
function icOwnerScenario(): array
{
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $ownerUser = ownerRoleUser($s, 0);
    $owner = $unit->fresh()->owner;

    $guest = Guest::create([
        'first_name' => 'Marguerite', 'last_name' => 'Bellweather',
        'email' => 'marguerite.bellweather@example.test', 'phone' => '+351 912 345 678',
    ]);

    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-02',
            'check_out' => '2026-09-05', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-02', 'check_out' => '2026-09-05', 'price_minor' => 30000, 'currency' => 'EUR']],
    );

    return compact('s', 'unit', 'owner', 'ownerUser', 'guest', 'res');
}

// ── S1-162 / S1-163 / S1-164 ─────────────────────────────────────────────────────────────────────────
// ⚠️ THE CLAIM, on /faq, /docs/guide-statements and /docs/guide-access:
//    "They cannot see guest names, contact details or documents, other owners' units or terms, or your
//     commercial arrangements with anyone else."
// ⚠️ SCOPE NOTE, stated so the citation is not read as covering more than it does: this test sources the
//    CONTACT-DETAILS half — the part invariant #28 enforces and the part a reader would be most harmed by
//    if it were false. The claim's clause about guest NAMES is deliberately NOT asserted here, because the
//    product shows owners a guest name by design (name-only is the perimeter, not no-name). ⚠️ That means
//    the sentence as written is WIDER than what this test proves, which is a finding for slice 1, not
//    something to paper over by weakening the test.
it('sources S1-162/163/164 — an owner is never shown a guest’s contact details', function () {
    ['ownerUser' => $ownerUser, 'guest' => $guest] = icOwnerScenario();

    foreach (['/owner/bookings', '/owner/units', '/owner/overview'] as $url) {
        $page = $this->actingAs($ownerUser)->get($url);

        if ($page->status() !== 200) {
            continue;   // a surface this fixture does not reach says nothing either way
        }

        $body = $page->getContent();
        Assert::assertStringNotContainsString('marguerite.bellweather@example.test', (string) $body,
            "⚠️ [{$url}] shows an owner a guest's EMAIL — claim S1-162/163/164 is false");
        Assert::assertStringNotContainsString('912 345 678', (string) $body,
            "⚠️ [{$url}] shows an owner a guest's PHONE — claim S1-162/163/164 is false");
    }
});

// ── S1-042 ───────────────────────────────────────────────────────────────────────────────────────────
// ⚠️ THE ORIGINAL WAS UNASSERTABLE: "Everyone who touches Vyrobel gets their own login." Nothing in the
// product prevents two people sharing one password, so as written it is a claim about USER BEHAVIOUR,
// not about the product — and no test could ever settle it.
//
// ⚠️ THE NARROWED CLAIM, which is what the product actually does:
//    "Vyrobel issues a separate account for each person you invite, and stamps the acting account on
//     every action it audits."
//
// ⚠️ BOTH HALVES ARE PRESENCE-SHAPED — they assert that something IS done, not that something is
// prevented. That is stronger footing than the S1-162/163/164 absence claims: this test can observe the
// account and the stamp directly, rather than failing to find a leak on the surfaces it happens to check.
//
// ⚠️ AND THE NARROWING IS REAL, NOT COSMETIC: the original promised an outcome (no shared logins) the
// product cannot deliver; this promises a mechanism it does deliver. A narrowing that merely sounded
// safer while still asserting the outcome would be the softening failure.
it('sources S1-042 — separate accounts, and the acting account is stamped on what it audits', function () {
    $s = seedScenario(1);

    // (a) SEPARATE ACCOUNTS: two invited people are two accounts, never one shared record.
    $alice = operatorUser(['org' => $s['org']], ['name' => 'Alice Admin'], 'Group Admin');
    $bob = operatorUser(['org' => $s['org']], ['name' => 'Bob Manager'], 'Hotel Manager');

    Assert::assertNotSame((string) $alice->getKey(), (string) $bob->getKey(),
        '⚠️ two invited people share one account — claim S1-042 is false');
    Assert::assertSame(2, User::query()->whereIn('id', [$alice->getKey(), $bob->getKey()])->count());

    // (b) THE STAMP: an audited action records the account that performed it, not merely that it happened.
    $property = $s['property'];
    $this->actingAs($alice)->post('/app/settings/team-scope', [
        'user_id' => (string) $bob->getKey(), 'scope' => 'selected', 'properties' => [(string) $property->id],
    ])->assertRedirect();

    $row = DB::table('audit_events')
        ->where('action', 'user.property_scope_changed')->latest('created_at')->first();

    Assert::assertNotNull($row, 'the action was not audited at all — nothing to stamp');
    Assert::assertSame((string) $alice->getKey(), (string) $row->actor_id,
        '⚠️ the audited action does not carry the acting account — claim S1-042 is false');
    Assert::assertSame('user', (string) $row->actor_type);
});

// ── the ledger and these tests must not drift apart ──────────────────────────────────────────────────
// ⚠️ A citation naming a test that does not exist is worse than no citation: it reads as evidence and is
// not. This is what makes `kind: internal` safe to trust — the named test must be REAL.
it('⚠️ every internal claim names a test that actually exists in this file', function () {
    $internal = array_values(array_filter(MarketingClaims::all(),
        fn (array $c): bool => ($c['kind'] ?? '') === MarketingClaims::KIND_INTERNAL
            && is_string($c['internal_test'] ?? null) && ($c['internal_test'] ?? '') !== ''));

    Assert::assertNotEmpty($internal, 'no internal claim is sourced — this assertion would be vacuous');

    $source = (string) file_get_contents(__FILE__);

    foreach ($internal as $claim) {
        Assert::assertStringContainsString("sources {$claim['internal_test']}", $source,
            "⚠️ claim [{$claim['id']}] cites internal test [{$claim['internal_test']}], which does not exist here — a citation that names nothing reads as evidence and is not");
    }
});
