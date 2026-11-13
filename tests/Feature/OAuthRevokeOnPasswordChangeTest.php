<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

uses(RefreshDatabase::class);

/**
 * DEC-3 (his ruling, 2026-11-13: "pick A") — A PASSWORD CHANGE OR RESET CUTS OFF EVERY CONNECTED APP.
 *
 * Before: `changePasswordTo()` ended every other browser session but never touched the OAuth tables, so a thief who
 * reset the password kept an owner's connected apps. A token is another kind of session; it now ends with the rest.
 * The rule lives in the ONE shared path (`User::changePasswordTo`), so the in-app change and the emailed reset
 * cannot drift apart.
 */
function connectedAppToken(User $user, string $clientName = 'Acme Insights'): Token
{
    // Passport issues every personal token under the ONE personal-access client, so each "app" here gets a client of
    // its own: two tokens of one client are one app, which is exactly what the audit count must say.
    $client = app(ClientRepository::class)->createPersonalAccessGrantClient($clientName);

    /** @var Token $token */
    $token = $user->createToken($clientName, ['read:bookings'])->token;
    $token->forceFill(['client_id' => $client->getKey()])->save();

    RefreshToken::query()->create([
        'id' => bin2hex(random_bytes(20)),
        'access_token_id' => $token->id,
        'revoked' => false,
        'expires_at' => now()->addDays(30),
    ]);

    return $token;
}

it('revokes every connected app of the person when their password is changed', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    $a = connectedAppToken($user, 'App A');
    $b = connectedAppToken($user, 'App B');

    $user->changePasswordTo('a-brand-new-password-1');

    expect(Token::query()->whereKey([$a->id, $b->id])->where('revoked', false)->count())->toBe(0)
        ->and(RefreshToken::query()->whereIn('access_token_id', [$a->id, $b->id])->where('revoked', false)->count())->toBe(0);
});

it('never touches another person’s connected apps', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    $other = operatorUser(seedScenario(1));
    connectedAppToken($user, 'Mine');
    $theirs = connectedAppToken($other, 'Theirs');

    $user->changePasswordTo('a-brand-new-password-2');

    expect(Token::query()->whereKey($theirs->id)->value('revoked'))->toBeFalsy()
        ->and(RefreshToken::query()->where('access_token_id', $theirs->id)->value('revoked'))->toBeFalsy();
});

it('cuts the apps off through the in-app change-password route too', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);
    $token = connectedAppToken($user);

    $this->actingAs($user)->post('/app/security/password', [
        'current_password' => 'current-secret-1',
        'password' => 'new-strong-pass-9',
        'password_confirmation' => 'new-strong-pass-9',
    ])->assertRedirect(route('app.security'));

    expect((bool) Token::query()->whereKey($token->id)->value('revoked'))->toBeTrue();
});

it('cuts the apps off through the emailed reset route', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['email' => 'reset-apps@example.test', 'password' => 'old-password-1']);
    $token = connectedAppToken($user);
    $plain = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $plain,
        'email' => 'reset-apps@example.test',
        'password' => 'A-brand-new-pass1!',
        'password_confirmation' => 'A-brand-new-pass1!',
    ])->assertRedirect();

    expect((bool) Token::query()->whereKey($token->id)->value('revoked'))->toBeTrue();
});

it('records HOW MANY apps were cut off in the audit row — a number, never a name or a token', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    connectedAppToken($user, 'App A');
    connectedAppToken($user, 'App B');

    $user->changePasswordTo('a-brand-new-password-3');

    $row = AuditEvent::query()->where('action', 'user.password_changed')->latest('created_at')->firstOrFail();

    expect($row->after['oauth_apps_disconnected'] ?? null)->toBe(2)
        ->and(json_encode($row->after))->not->toContain('App A');
});

it('a person with no connected apps changes their password exactly as before', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);

    $user->changePasswordTo('a-brand-new-password-4');

    $row = AuditEvent::query()->where('action', 'user.password_changed')->latest('created_at')->firstOrFail();
    expect($row->after['oauth_apps_disconnected'] ?? null)->toBe(0);
});
