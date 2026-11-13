<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * DEC-6 (his ruling, 2026-11-13: "C is fine right now") — STAFF AND OWNERS CAN REQUEST DELETION OF THEIR OWN ACCOUNT.
 *
 * "Request", not "delete": the button files a request on the Privacy desk's register — due in one month, answered by a
 * person — and erases NOTHING. (Guests already have their own 14-day self-delete; an instant self-delete for staff and
 * owners waits for the lawyer's answer on how long financial records must be kept — it would risk the money history.)
 */
function deletionRequestsOf(User $user)
{
    return PrivacyRequest::query()->where('subject_user_id', $user->getKey())->where('kind', 'delete');
}

// ── Staff ────────────────────────────────────────────────────────────────────────────────────────────────────
it('files a deletion request for the signed-in staff member, due in one month', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);

    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1'])
        ->assertRedirect(route('app.security'));

    $row = deletionRequestsOf($user)->sole();
    expect($row->subject_kind)->toBe('account_holder')
        ->and($row->channel)->toBe('in_app')
        ->and($row->status)->toBe('received')
        ->and($row->related_organization_id)->toBe((string) $s['org']->id)
        ->and($row->due_at->equalTo($row->received_at->copy()->addMonthNoOverflow()))->toBeTrue()
        ->and(AuditEvent::query()->where('action', 'user.deletion_requested')->where('subject_id', $user->getKey())->count())->toBe(1);
});

it('erases nothing — the account, the membership and the sign-in all still work', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1', 'name' => 'Uma Unchanged']);

    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1']);

    $fresh = $user->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->name)->toBe('Uma Unchanged')
        ->and($fresh->status)->toBe('active')
        ->and($fresh->deleted_at ?? null)->toBeNull();
    $this->actingAs($fresh)->get('/app/security')->assertOk();
});

it('refuses a wrong password — no request is filed', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);

    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'not-the-password'])
        ->assertSessionHasErrors('current_password');

    expect(deletionRequestsOf($user)->count())->toBe(0);
});

it('does not file a second request while one is still open', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);

    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1']);
    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1']);

    expect(deletionRequestsOf($user)->count())->toBe(1);
});

it('lets the person ask again once the earlier request is closed', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);

    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1']);
    deletionRequestsOf($user)->update(['status' => 'refused', 'closed_at' => now()]);
    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1']);

    expect(deletionRequestsOf($user)->count())->toBe(2)
        ->and(deletionRequestsOf($user)->whereIn('status', ['received', 'in_progress'])->count())->toBe(1);
});

it('is always about the signed-in person — a posted user id is ignored', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);
    $other = operatorUser($s);

    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1', 'user_id' => $other->getKey(), 'subject_user_id' => $other->getKey()]);

    expect(deletionRequestsOf($user)->count())->toBe(1)->and(deletionRequestsOf($other)->count())->toBe(0);
});

it('needs a signed-in person', function () {
    $this->post('/app/security/deletion-request', ['current_password' => 'x'])->assertRedirect();
    expect(PrivacyRequest::query()->where('channel', 'in_app')->count())->toBe(0);
});

it('lets a person who signs in with Google or X (no password of their own) file it without one', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    $user->forceFill(['password' => null])->save();

    $this->actingAs($user)->post('/app/security/deletion-request', [])->assertRedirect(route('app.security'));

    expect(deletionRequestsOf($user)->count())->toBe(1);
});

// ── Owners ───────────────────────────────────────────────────────────────────────────────────────────────────
it('files the same request for an owner, from the owner portal', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);
    $owner->forceFill(['password' => 'owner-secret-1'])->save();

    $this->actingAs($owner)->post('/owner/security/deletion-request', ['current_password' => 'owner-secret-1'])
        ->assertRedirect(route('owner.security'));

    $row = deletionRequestsOf($owner)->sole();
    expect($row->channel)->toBe('in_app')->and($row->status)->toBe('received');
    $this->actingAs($owner->fresh())->get('/owner/security')->assertOk();   // nothing was erased
});

it('keeps the owner routes and the staff routes apart — a staff member cannot use the owner one', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1']);

    $this->actingAs($user)->post('/owner/security/deletion-request', ['current_password' => 'current-secret-1'])->assertRedirect();

    expect(deletionRequestsOf($user)->count())->toBe(0);
});

// ── The screens ──────────────────────────────────────────────────────────────────────────────────────────────
it('shows a Request deletion card on both Security pages, and the state of the request once it is filed', function () {
    $s = seedScenario(1);
    $staff = operatorUser($s, ['password' => 'current-secret-1']);
    $owner = ownerRoleUser($s);
    $owner->forceFill(['password' => 'owner-secret-1'])->save();

    foreach ([[$staff, '/app/security', '/app/security/deletion-request', 'current-secret-1'], [$owner, '/owner/security', '/owner/security/deletion-request', 'owner-secret-1']] as [$user, $page, $post, $pw]) {
        $res = $this->actingAs($user)->get($page)->assertOk();
        $res->assertSee('Request deletion')->assertSee($post, false);
        assertNoRawBlade($res);

        $this->actingAs($user)->post($post, ['current_password' => $pw]);

        $after = $this->actingAs($user->fresh())->get($page)->assertOk();
        $after->assertSee('We received your request')->assertDontSee($post, false);
        assertNoRawBlade($after);
    }
});

it('appears on the Privacy desk as an in-app request with its due date', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['password' => 'current-secret-1', 'name' => 'Desk Visible']);
    $this->actingAs($user)->post('/app/security/deletion-request', ['current_password' => 'current-secret-1']);

    $this->flushSession();
    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.privacy.index'))->assertOk()->getContent();

    expect($html)->toContain('Desk Visible')->toContain('In the app');
});
