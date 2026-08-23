<?php

use App\Models\Owner;
use App\Models\Unit;
use App\Notifications\Owner\ArrivalSecretRevealedNotification;
use App\Services\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// ══ CLUSTER E FOLLOW-UP — A REVEAL NOTIFIES THE UNIT'S OWNER ═════════════════════════════════════════
//
// ⚠️⚠️ WHY. A reveal is SILENT where an overwrite is DETECTABLE: change a door code and it stops working
// and somebody complains; READ one and the only trace is an audit row nobody routinely opens. The owner is
// told so the silent act is visible to the person whose building it unlocks.
//
// ⚠️⚠️ THE ONE ABSOLUTE CONSTRAINT: the notification says THAT it happened, never WHAT was revealed. A
// notification is the worst possible destination for a credential — it lands in the `notifications` table
// AND, on the mail channel, in an inbox, unencrypted and indefinitely, outside every guard cluster E built.

const REV_CODE = 'REVEAL-9931';

/** A unit owned by a real owner-with-user, carrying both secrets. */
function revealScenario(): array
{
    $s = seedScenario(1);
    $unit = $s['units'][0];

    // ⚠️ The SHARED owner fixture — it creates the Owner, links the user and assigns this unit, so this
    //    test cannot drift from every other test about what an "owner" is.
    $ownerUser = ownerRoleUser($s, 0);
    $owner = $unit->fresh()->owner;
    $unit->forceFill(['door_code' => REV_CODE, 'wifi_password' => 'wifi-'.REV_CODE])->save();

    $admin = operatorUser(['org' => $s['org']], ['name' => 'Ada Admin'], 'Group Admin');

    return ['s' => $s, 'unit' => $unit->fresh(), 'owner' => $owner, 'ownerUser' => $ownerUser, 'admin' => $admin];
}

it('notifies the unit’s owner that a reveal happened', function () {
    ['unit' => $unit, 'admin' => $admin, 'ownerUser' => $ownerUser] = revealScenario();
    Notification::fake();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info/reveal")->assertRedirect();

    // ⚠️ Asserted against the FAKE, never a log line: MAIL_MAILER is `log` outside production, so "an email
    // was sent" proves nothing on its own.
    Notification::assertSentTo($ownerUser, ArrivalSecretRevealedNotification::class);
});

// ══ ⚠️⚠️ THE CREDENTIAL APPEARS NOWHERE ══════════════════════════════════════════════════════════════
it('⚠️ puts the credential in NO part of the notification — payload, feed row, or mail body', function () {
    ['unit' => $unit, 'admin' => $admin, 'ownerUser' => $ownerUser] = revealScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info/reveal")->assertRedirect();

    // ⚠️ NON-VACUITY: the reveal must actually have happened and the notification actually been stored,
    // or every "absent" assertion below passes for the wrong reason.
    $rows = DB::table('notifications')->get();
    Assert::assertGreaterThan(0, $rows->count(), 'no notification was stored — the assertions below would be vacuous');
    Assert::assertSame(REV_CODE, (string) $unit->fresh()->door_code, 'the fixture never stored a code to leak');

    // ⚠️ THE WHOLE TABLE, not the field I expected it to be absent from — the audit test's shape.
    foreach ($rows as $row) {
        Assert::assertStringNotContainsString(REV_CODE, json_encode($row),
            '⚠️ the credential reached the notifications table');
    }

    // The rendered feed row and the mail body, built the way the framework builds them.
    $n = new ArrivalSecretRevealedNotification('Unit 1', 'Demo Property', 'Ada Admin', ['door_code', 'wifi_password']);
    Assert::assertStringNotContainsString(REV_CODE, json_encode($n->data($ownerUser)));
    Assert::assertStringNotContainsString(REV_CODE, json_encode($n->toArray($ownerUser)));

    $mail = $n->toMail($ownerUser);
    Assert::assertStringNotContainsString(REV_CODE, json_encode([$mail->subject, $mail->viewData]),
        '⚠️ the credential reached the mail body');

    // ⚠️ THE SERIALISED FORM. FeedNotification is not queued today, but a constructor that cannot carry a
    // secret stays safe if that ever changes — so assert the object itself holds none.
    Assert::assertStringNotContainsString(REV_CODE, serialize($n),
        '⚠️ the credential is inside the notification object and would ride into any queue payload');
});

// ⚠️⚠️ THE ASSERTION THAT ACTUALLY GUARDS THE CONSTRAINT — and the first version of this file did NOT
// have it. That version serialised a notification IT built itself, which can only ever prove that the
// object I constructed in the test is clean. ⚠️ A mutation that passed the real door code into the REAL
// dispatch sailed straight through: `data()` filters unknown field names out of the rendered text, so the
// table stayed clean while the OBJECT held the credential — and any future move to a queued notification
// would have serialised it into the payload. ⚠️ "Check what is actually serialised, not what you intended
// to pass": this inspects the instance the SERVICE dispatched.
it('⚠️ the notification the SERVICE dispatches holds no credential in its own state', function () {
    ['unit' => $unit, 'admin' => $admin, 'ownerUser' => $ownerUser] = revealScenario();
    Notification::fake();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info/reveal")->assertRedirect();

    $seen = 0;
    Notification::assertSentTo($ownerUser, ArrivalSecretRevealedNotification::class,
        function (ArrivalSecretRevealedNotification $n) use (&$seen, $ownerUser): bool {
            $seen++;
            // ⚠️ The whole object graph, as a queue would take it.
            Assert::assertStringNotContainsString(REV_CODE, serialize($n),
                '⚠️ the dispatched notification CARRIES the credential in its own state');
            // ⚠️ And the wifi password, which is the other encrypted column.
            Assert::assertStringNotContainsString('wifi-'.REV_CODE, serialize($n),
                '⚠️ the dispatched notification carries the wifi password');
            Assert::assertStringNotContainsString(REV_CODE, json_encode($n->data($ownerUser)));

            return true;
        });

    Assert::assertSame(1, $seen, 'the closure never ran — this test would have proven nothing');
});

it('⚠️ says WHICH fields were viewed, so the message is useful without being dangerous', function () {
    $n = new ArrivalSecretRevealedNotification('Apt 4', 'Seaside', 'Ada Admin', ['door_code', 'wifi_password']);
    $data = $n->data((object) []);

    Assert::assertStringContainsString('door code', $data['context']);
    Assert::assertStringContainsString('wifi password', $data['context']);
    Assert::assertStringContainsString('Ada Admin', $data['context']);
    Assert::assertStringContainsString('Apt 4', $data['context']);
});

// ══ ⚠️ THE NO-OWNER CASE MUST NEVER BREAK THE REVEAL ═════════════════════════════════════════════════
it('⚠️ still reveals when the unit has no owner, and notifies nobody', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $unit->forceFill(['door_code' => REV_CODE])->save();          // operator-owned: owner_id stays null
    $admin = operatorUser(['org' => $s['org']], [], 'Group Admin');

    Notification::fake();

    // ⚠️ THE OPERATOR ASKED FOR A CREDENTIAL AND MUST STILL GET IT — a missing owner is not an error.
    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info/reveal")->assertRedirect();
    $this->actingAs($admin)->get('/app/units')->assertOk()->assertSee(REV_CODE);

    Notification::assertNothingSent();
    Assert::assertSame(1, DB::table('audit_events')->where('action', 'unit.arrival_secrets_revealed')->count(),
        'the reveal was not audited when there was no owner to notify');
});

it('still reveals when the owner has no user account', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $owner = Owner::factory()->create(['user_id' => null, 'payout_currency' => 'EUR']);
    $unit->forceFill(['owner_id' => $owner->getKey(), 'door_code' => REV_CODE])->save();
    $admin = operatorUser(['org' => $s['org']], [], 'Group Admin');

    Notification::fake();
    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info/reveal")->assertRedirect();
    Notification::assertNothingSent();
});

// ══ ALWAYS-ON ════════════════════════════════════════════════════════════════════════════════════════
it('⚠️ cannot be switched off — a credential to your own building being read is not a preference', function () {
    Assert::assertTrue(NotificationPreferences::isAlwaysOn('arrival_secret_revealed'));
});
