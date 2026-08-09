<?php

declare(strict_types=1);

use App\Support\Security\ActiveSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * SECURITY REGRESSION — the auth/isolation pass, item 3.
 *
 * A password change rotated the hash and the remember-token — which kills "remember me" COOKIES — but left
 * live SERVER-SIDE sessions untouched. So an attacker holding a session was completely unaffected by the
 * victim resetting their password, which is the entire reason someone resets a compromised password.
 *
 * The termination now lives in `User::changePasswordTo()` — the ONE path BOTH the reset flow and the in-app
 * change flow already call — so neither can forget it or drift from the other.
 */
function seatSession(string $userId, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => base64_encode('x'),
        'last_activity' => time(),
    ]);
}

it('terminates every other session when a password is reset', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    seatSession((string) $user->getKey(), 'attacker-session');
    seatSession((string) $user->getKey(), 'victim-session');

    expect(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(2);

    // The reset flow passes no session to keep — the user is signed in fresh immediately afterwards.
    $user->changePasswordTo('a-brand-new-password');

    // The attacker's live session is GONE, not merely cookie-rotated.
    expect(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(0);
});

it('keeps the browser doing an in-app change signed in, killing only the others', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    seatSession((string) $user->getKey(), 'mine');
    seatSession((string) $user->getKey(), 'attacker-session');

    // The in-app change-password flow spares the current session.
    $user->changePasswordTo('another-new-password', 'mine');

    $remaining = DB::table('sessions')->where('user_id', $user->getKey())->pluck('id')->all();

    expect($remaining)->toBe(['mine']);   // mine survives, the attacker's does not
});

it('never touches another user’s sessions', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    $other = operatorUser(seedScenario(1));

    seatSession((string) $user->getKey(), 'mine');
    seatSession((string) $other->getKey(), 'someone-else');

    $user->changePasswordTo('yet-another-password');

    // A reset is scoped to the resetting user — it is not a global sign-out.
    expect(DB::table('sessions')->where('user_id', $other->getKey())->count())->toBe(1)
        ->and(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(0);
});

it('reports how many sessions it terminated', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    seatSession((string) $user->getKey(), 'one');
    seatSession((string) $user->getKey(), 'two');
    seatSession((string) $user->getKey(), 'three');

    expect(ActiveSessions::terminateFor((string) $user->getKey(), 'one'))->toBe(2)
        ->and(DB::table('sessions')->where('user_id', $user->getKey())->pluck('id')->all())->toBe(['one']);
});
