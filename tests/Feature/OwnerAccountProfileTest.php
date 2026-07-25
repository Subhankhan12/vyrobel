<?php

declare(strict_types=1);

use App\Mail\OwnerEmailChangeMail;
use App\Models\Owner;
use App\Models\User;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/**
 * Owner account area · Gate 1 — the identity split on "Profile & account".
 *
 * MY PROFILE is per-USER (auth-resolved) so accountant/view-only SUB-USERS work (the old 404 fix); the
 * OWNER-PARTY / business card is PRIMARY-OWNER-ONLY (#42); a login-email change RE-VERIFIES before it switches.
 */

/** A primary owner + one sub-user per role, all for the same owner party. */
function accountCast(): array
{
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    return [
        'owner' => $owner,
        'accountant' => ownerSubUser($s, $ownerId, OwnerTeamRole::ACCOUNTANT),
        'view' => ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY),
        'ownerId' => $ownerId,
        'scenario' => $s,
    ];
}

// ── 1. THE SUB-USER FIX: My Profile loads + saves for a sub-user (no 404) ────────────────────────────
it('renders Profile & account for a sub-user with a working My-profile (no 404)', function () {
    $c = accountCast();

    $res = $this->actingAs($c['accountant'])->get('/owner/settings');

    $res->assertOk();
    assertNoRawBlade($res);
    $res->assertSee('My profile');
    $res->assertSee((string) $c['accountant']->email);
    // The owner-party form is NOT shown to a sub-user — only the honest note.
    $res->assertDontSee('Save business details');
    $res->assertSee('managed by the account owner');
});

it('lets a sub-user save their OWN name + language — the gap fix (previously a 404)', function () {
    $c = accountCast();

    $this->actingAs($c['accountant'])
        ->post('/owner/account', ['name' => 'Ada Accountant', 'locale' => 'pt'])
        ->assertRedirect();

    $fresh = $c['accountant']->fresh();
    expect($fresh->name)->toBe('Ada Accountant');
    expect($fresh->locale)->toBe('pt');
});

// ── 2. #28 SELF-ONLY: a profile save touches ONLY the acting login, never another user ───────────────
it('is self-only — a sub-user editing their profile never touches the primary owner', function () {
    $c = accountCast();
    $ownerNameBefore = $c['owner']->name;

    $this->actingAs($c['accountant'])->post('/owner/account', ['name' => 'X', 'locale' => 'en'])->assertRedirect();

    expect($c['owner']->fresh()->name)->toBe($ownerNameBefore);
});

it('rejects an unsupported language', function () {
    $c = accountCast();

    $this->actingAs($c['accountant'])
        ->post('/owner/account', ['name' => 'A', 'locale' => 'zz'])
        ->assertSessionHasErrors('locale');
});

// ── 3. #42 OWNER-PARTY / business details is PRIMARY-OWNER-ONLY ───────────────────────────────────────
it('refuses the owner-party (business) update to every sub-user, allows the primary owner', function () {
    $c = accountCast();

    $this->actingAs($c['accountant'])->post('/owner/settings', ['name' => 'Hijack'])->assertForbidden();
    $this->actingAs($c['view'])->post('/owner/settings', ['name' => 'Hijack'])->assertForbidden();

    // Primary owner passes the gate (a redirect, never 403).
    $ok = $this->actingAs($c['owner'])->post('/owner/settings', ['name' => 'Seaside Group']);
    expect($ok->status())->not->toBe(403);
    expect(Owner::query()->where('user_id', $c['owner']->id)->value('name'))->toBe('Seaside Group');
});

// ── 4. EMAIL CHANGE RE-VERIFIES before it switches ───────────────────────────────────────────────────
it('parks a new email + mails a verify link WITHOUT switching the login email', function () {
    Mail::fake();
    $c = accountCast();
    $before = (string) $c['owner']->email;

    $this->actingAs($c['owner'])
        ->post('/owner/account/email', ['email' => 'brand-new@example.test'])
        ->assertRedirect();

    $fresh = $c['owner']->fresh();
    expect($fresh->email)->toBe($before);                     // unchanged
    expect($fresh->pending_email)->toBe('brand-new@example.test');
    Mail::assertSent(OwnerEmailChangeMail::class);
});

it('switches the email only when the signed verify link is opened', function () {
    $c = accountCast();
    $user = $c['owner'];
    $new = 'verified-new@example.test';
    $user->forceFill(['pending_email' => $new])->save();

    $url = URL::temporarySignedRoute('owner.account.email.verify', now()->addHour(), [
        'user' => $user->getKey(),
        'hash' => sha1($new),
    ]);

    $this->get($url)->assertRedirect(route('login'));

    $fresh = $user->fresh();
    expect($fresh->email)->toBe($new);
    expect($fresh->pending_email)->toBeNull();
});

it('rejects an email-verify link whose hash does not match the pending address', function () {
    $c = accountCast();
    $user = $c['owner'];
    $before = (string) $user->email;
    $user->forceFill(['pending_email' => 'pending@example.test'])->save();

    // A validly-signed link, but for a DIFFERENT address than the one parked.
    $url = URL::temporarySignedRoute('owner.account.email.verify', now()->addHour(), [
        'user' => $user->getKey(),
        'hash' => sha1('someone-else@example.test'),
    ]);

    $this->get($url);

    expect($user->fresh()->email)->toBe($before);             // not switched
});

it('refuses a tampered (unsigned) verify link', function () {
    $c = accountCast();
    $user = $c['owner'];
    $user->forceFill(['pending_email' => 'p@example.test'])->save();

    $this->get('/owner/account/email/verify/'.$user->getKey().'/'.sha1('p@example.test'))
        ->assertStatus(403); // missing/invalid signature
});

// ── 5. AVATAR upload — real file, self-only, stored on the public disk ────────────────────────────────
it('uploads + removes an avatar for the acting user', function () {
    Storage::fake('public');
    $c = accountCast();

    $this->actingAs($c['accountant'])
        ->post('/owner/account/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)])
        ->assertRedirect();

    $path = $c['accountant']->fresh()->avatar_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $this->actingAs($c['accountant'])->delete('/owner/account/avatar')->assertRedirect();
    expect($c['accountant']->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('rejects a non-image avatar upload', function () {
    Storage::fake('public');
    $c = accountCast();

    $this->actingAs($c['accountant'])
        ->post('/owner/account/avatar', ['avatar' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])
        ->assertSessionHasErrors('avatar');
});

// ── 6. Primary owner sees the editable business card ─────────────────────────────────────────────────
it('shows the editable owner-party form to the primary owner', function () {
    $c = accountCast();

    $res = $this->actingAs($c['owner'])->get('/owner/settings');
    $res->assertOk();
    $res->assertSee('Save business details');
});
