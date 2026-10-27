<?php

declare(strict_types=1);

use App\Mail\OwnerInviteMail;
use App\Models\LoginActivity;
use App\Models\Organization;
use App\Models\OwnerInvite;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use App\Services\OwnerInviteService;
use App\Support\Platform\DeviceLabel;
use App\Support\Platform\OrganizationSignIns;
use App\Support\Platform\PlatformFunnel;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// ⚠️⚠️ ADMIN 04 (2026-09-26) — the founder asked for three things in the console's Organisations: create one,
// update one, and see when each one's people last signed in, "the exact details". His rulings: the founder of a
// console-created organisation gets a set-password link by email (clicking it verifies the address); name, country,
// language and billing are editable, currency and the web address are not; sign-ins show time, user, IP and device.

uses(DatabaseTransactions::class);

const A04_CHROME_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';
const A04_SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

function a04Org(string $name): Organization
{
    return Organization::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)), 'status' => 'active', 'default_currency' => 'EUR']);
}

function a04Member(Organization $org, string $name): User
{
    $user = User::factory()->create(['name' => $name, 'email' => 'a04-'.Str::lower(Str::random(10)).'@vyrobel.com', 'status' => 'active']);
    DB::table('organization_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $org->id, 'user_id' => $user->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

    return $user;
}

function a04SignIn(string $event, array $attributes): void
{
    LoginActivity::create(array_merge(['event' => $event, 'guard' => 'web', 'occurred_at' => now()], $attributes));
}

// ══ 1 · SIGN-IN ACTIVITY — who, when, from which IP, on which device; this organisation's people only ══════════

it('⚠️⚠️ shows this organisation\'s sign-ins — exact time, who, IP and device — and failed attempts against its people', function () {
    $org = a04Org('A04 Harbour Stays');
    $ana = a04Member($org, 'Ana Harbour');
    $other = a04Member(a04Org('A04 Elsewhere Hotels'), 'Otto Elsewhere');

    a04SignIn('login', ['subject_type' => 'user', 'subject_id' => $ana->id, 'identifier' => $ana->email, 'ip' => '203.0.113.7', 'user_agent' => A04_CHROME_MAC, 'occurred_at' => now()->subHour()]);
    // The tenant login records a LOCKOUT after repeated wrong passwords (it fires no per-attempt failure); a lockout
    // only has the address that was typed — matched without regard to case.
    a04SignIn('lockout', ['identifier' => strtoupper($ana->email), 'ip' => '198.51.100.9', 'user_agent' => A04_SAFARI_IPHONE, 'occurred_at' => now()->subHours(2)]);
    // None of these may appear here: another organisation's person (a sign-in AND a lockout), a guest, attempts at the
    // PLATFORM CONSOLE's login typed with Ana's address, and a sign-in older than the 90-day window.
    a04SignIn('login', ['subject_type' => 'user', 'subject_id' => $other->id, 'identifier' => $other->email, 'ip' => '192.0.2.44', 'user_agent' => A04_CHROME_MAC]);
    a04SignIn('lockout', ['identifier' => $other->email, 'ip' => '192.0.2.45', 'user_agent' => A04_CHROME_MAC]);
    a04SignIn('login', ['guard' => 'guest', 'subject_type' => 'guest', 'subject_id' => (string) Str::ulid(), 'ip' => '192.0.2.99']);
    a04SignIn('failed', ['guard' => 'admin', 'identifier' => $ana->email, 'ip' => '192.0.2.61', 'user_agent' => A04_CHROME_MAC]);
    a04SignIn('lockout', ['guard' => 'admin', 'identifier' => $ana->email, 'ip' => '192.0.2.62', 'user_agent' => A04_CHROME_MAC]);
    a04SignIn('login', ['subject_type' => 'user', 'subject_id' => $ana->id, 'identifier' => $ana->email, 'ip' => '192.0.2.70', 'user_agent' => A04_CHROME_MAC, 'occurred_at' => now()->subDays(LoginActivity::RETENTION_DAYS + 5)]);

    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))->assertOk()->getContent();

    expect($html)->toContain('Sign-in activity')
        ->toContain('Ana Harbour')->toContain('203.0.113.7')->toContain('Chrome on macOS')
        ->toContain('Locked out')->toContain('198.51.100.9')->toContain('Safari on iPhone')
        ->toContain(now()->subHour()->utc()->format('j M Y, H:i').' UTC')   // the exact moment, in UTC, not "an hour ago"
        ->not->toContain('Otto Elsewhere')->not->toContain('192.0.2.44')->not->toContain('192.0.2.45')
        ->not->toContain('192.0.2.99')->not->toContain('192.0.2.61')->not->toContain('192.0.2.62')->not->toContain('192.0.2.70');

    $read = app(OrganizationSignIns::class)->for((string) $org->id);
    expect($read['last']['name'])->toBe('Ana Harbour')
        ->and($read['last']['ip'])->toBe('203.0.113.7')
        ->and(array_column($read['rows'], 'event'))->toBe(['login', 'lockout'])
        ->and($read['rows'][1]['name'])->toBe('Ana Harbour', 'a lockout typed in capitals is still HER lockout');
})->group('security');

it('⚠️⚠️ a burst of lockouts — which anyone who knows an address can cause — never hides the last real sign-in', function () {
    $org = a04Org('A04 Flooded Inn');
    $ana = a04Member($org, 'Ana Flooded');
    a04SignIn('login', ['subject_type' => 'user', 'subject_id' => $ana->id, 'identifier' => $ana->email, 'ip' => '203.0.113.50', 'user_agent' => A04_CHROME_MAC, 'occurred_at' => now()->subHours(3)]);
    foreach (range(1, OrganizationSignIns::LIMIT + 1) as $i) {
        a04SignIn('lockout', ['identifier' => $ana->email, 'ip' => '198.51.100.200', 'user_agent' => 'curl/8.4.0', 'occurred_at' => now()->subMinutes($i)]);
    }

    $read = app(OrganizationSignIns::class)->for((string) $org->id);
    expect($read['last']['ip'])->toBe('203.0.113.50')->and($read['last']['name'])->toBe('Ana Flooded');
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))->assertOk()
        ->assertSee('Last sign-in')->assertSee('203.0.113.50');
})->group('security');

it('an organisation whose people have not signed in says so, and never borrows another organisation\'s sign-ins', function () {
    $quiet = a04Org('A04 Quiet Lodge');
    a04Member($quiet, 'Quinn Quiet');
    $busy = a04Member(a04Org('A04 Busy Inn'), 'Bea Busy');
    a04SignIn('login', ['subject_type' => 'user', 'subject_id' => $busy->id, 'identifier' => $busy->email, 'ip' => '192.0.2.10', 'user_agent' => A04_CHROME_MAC]);

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $quiet->id))->assertOk()
        ->assertSee('No sign-ins in the last '.LoginActivity::RETENTION_DAYS.' days.')
        ->assertDontSee('Bea Busy')->assertDontSee('192.0.2.10');
})->group('security');

it('names a device in words — and shows an unknown agent as itself rather than guessing', function () {
    expect(DeviceLabel::from(A04_CHROME_MAC))->toBe('Chrome on macOS')
        ->and(DeviceLabel::from(A04_SAFARI_IPHONE))->toBe('Safari on iPhone')
        ->and(DeviceLabel::from('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0'))->toBe('Edge on Windows')
        ->and(DeviceLabel::from('Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0'))->toBe('Firefox on Linux')
        ->and(DeviceLabel::from('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36'))->toBe('Chrome on Android')
        ->and(DeviceLabel::from('vyrobel-probe-signup/1.0'))->toBe('vyrobel-probe-signup/1.0')
        ->and(DeviceLabel::from(null))->toBe('Unknown device');
});

// ══ 2 · UPDATE — name, country, language and billing; never currency or the web address ══════════════════════════

function a04Details(array $overrides = []): array
{
    return array_merge([
        'name' => 'A04 Renamed Stays', 'country' => 'PT', 'default_locale' => 'pt',
        'billing_email' => 'billing@a04-renamed.example.net', 'billing_company' => 'A04 Renamed Lda', 'billing_vat_id' => '',
        'billing_city' => 'Porto', 'reason' => 'The customer asked us to correct their company details.',
    ], $overrides);
}

it('⚠️⚠️ updates name, country, language and billing — read back, audited by field NAME, never by value', function () {
    $org = a04Org('A04 Original Stays');

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.update-details', $org->id), a04Details())
        ->assertRedirect(route('admin.customers.show', $org->id))->assertSessionHasNoErrors();

    $org->refresh();
    expect($org->name)->toBe('A04 Renamed Stays')->and($org->country)->toBe('PT')->and($org->default_locale)->toBe('pt')
        ->and($org->billing_email)->toBe('billing@a04-renamed.example.net')->and($org->billing_company)->toBe('A04 Renamed Lda')
        ->and($org->billing_city)->toBe('Porto')
        ->and(session('admin_notice'))->toStartWith('Saved — read back from the database');

    $row = PlatformAdminAction::query()->where('action', 'platform.customer.update_details')->where('target_id', $org->id)->sole();
    expect($row->reason)->toBe('The customer asked us to correct their company details.')
        ->and($row->meta['changed'])->toContain('name', 'country', 'default_locale', 'billing_email', 'billing_company', 'billing_city')
        ->and(json_encode($row->meta))->not->toContain('a04-renamed')->not->toContain('Porto');
})->group('security');

it('⚠️⚠️ never changes currency or the web address — or status, or internal — whatever is posted', function () {
    $org = a04Org('A04 Fixed Money');
    $slug = $org->slug;

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.update-details', $org->id),
        a04Details(['default_currency' => 'USD', 'slug' => 'taken-over', 'status' => 'suspended', 'is_internal' => '1']))
        ->assertRedirect(route('admin.customers.show', $org->id))->assertSessionHasNoErrors();   // the save SUCCEEDED — and still left these alone

    $org->refresh();
    expect($org->default_currency)->toBe('EUR')->and($org->slug)->toBe($slug)->and($org->status)->toBe('active')->and($org->isInternal())->toBeFalse();
})->group('security');

it('⚠️⚠️ refuses the protected baseline outright, and needs a protected organisation\'s exact name typed', function () {
    // ⚠️ ONE admin for every request: the console tracks its own sessions, so a second admin in the same test
    //    session is signed out (a 302 to the login) before any gate runs — which would hide the refusal under test.
    $admin = platformAdmin();
    $baseline = a04Org('A04 Baseline');
    config(['security.baseline_tenant_guard' => (string) $baseline->id]);
    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $baseline->id), a04Details(['name' => 'Hijacked']));
    expect($baseline->fresh()->name)->toBe('A04 Baseline')
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.refused')->where('target_id', $baseline->id)->exists())->toBeTrue();

    $locked = a04Org('A04 Internal Locked');
    $locked->forceFill(['lifecycle_lock' => 'confirm'])->save();
    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $locked->id), a04Details(['name' => 'A04 Changed Untyped']))
        ->assertSessionHasErrors('confirm_name');
    expect($locked->fresh()->name)->toBe('A04 Internal Locked');

    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $locked->id),
        a04Details(['name' => 'A04 Internal Renamed', 'confirm_name' => 'A04 Internal Locked']))->assertSessionHasNoErrors();
    expect($locked->fresh()->name)->toBe('A04 Internal Renamed');
})->group('security');

it('⚠️ a changed VAT number reaches Stripe from here too, as it does from the operator\'s own screens', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'active',
        'current_period_end' => now()->addMonth(), 'stripe_customer_id' => 'cus_a04_vat',
    ]);

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.update-details', $s['org']->id),
        a04Details(['name' => (string) $s['org']->name, 'billing_vat_id' => 'PT123456789']))->assertSessionHasNoErrors();

    expect(json_encode(app(StripeClientInterface::class)->customerTaxIds['cus_a04_vat'] ?? []))->toContain('PT123456789');
})->group('security');

it('only a tenant manager may update, and a language outside the app\'s own list is refused', function () {
    $org = a04Org('A04 Gated');
    foreach (['billing', 'analyst'] as $role) {
        $this->flushSession();   // a fresh session per admin — see the lock test above
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->post(route('admin.customers.update-details', $org->id), a04Details())->assertForbidden();
    }
    $this->flushSession();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $org->id), a04Details(['default_locale' => 'xx']))
        ->assertSessionHasErrors('default_locale');
    // ⚠️ the country column holds an ISO code (char 2): a name is normalised to its code, an unknown value is refused
    //    as a field error — it never reaches the column (where it would not fit, and the page would 500).
    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $org->id), a04Details(['country' => 'Atlantis']))
        ->assertSessionHasErrors('country');
    expect($org->fresh()->name)->toBe('A04 Gated');
    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $org->id), a04Details(['country' => 'Portugal']))
        ->assertSessionHasNoErrors();
    expect($org->fresh()->country)->toBe('PT');
})->group('security');

it('a language and billing values set on the operator\'s own screens survive an unrelated console save', function () {
    $org = a04Org('A04 Legacy Values');
    $longName = str_repeat('Longer Company Name ', 10);   // 200 characters — the operator's Billing screen allows 255
    $org->forceFill(['default_locale' => 'pt-PT', 'billing_company' => trim($longName)])->save();
    $admin = platformAdmin();   // one admin per test — a second one in the same session is signed out first

    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $org->id), [
        'name' => 'A04 Legacy Values', 'default_locale' => 'pt-PT', 'billing_company' => trim($longName), 'reason' => 'Unrelated save, nothing to change.',
    ])->assertSessionHasNoErrors();
    expect(session('admin_notice'))->toStartWith('Nothing changed')->and($org->fresh()->default_locale)->toBe('pt-PT');

    // a NEW value still meets the stricter limit, so whatever the console writes passes both operator screens
    $this->actingAs($admin, 'admin')->post(route('admin.customers.update-details', $org->id), [
        'name' => 'A04 Legacy Values', 'default_locale' => 'pt-PT', 'billing_company' => trim($longName).' Changed', 'reason' => 'Trying a longer name.',
    ])->assertSessionHasErrors('billing_company');
});

it('⚠️ a Stripe outage never turns a saved edit into an error — the save stands, the ledger stays true, the admin is told', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    Subscription::create([
        'organization_id' => $s['org']->getKey(), 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'active',
        'current_period_end' => now()->addMonth(), 'stripe_customer_id' => 'cus_a04_down',
    ]);
    app()->instance(StripeClientInterface::class, new class extends FakeStripeClient
    {
        public function syncCustomerTaxId(string $customerId, array $taxId): bool
        {
            throw new RuntimeException('Stripe is unreachable (test)');
        }
    });

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.update-details', $s['org']->id),
        a04Details(['name' => (string) $s['org']->name, 'billing_vat_id' => 'PT123456789']))
        ->assertRedirect(route('admin.customers.show', $s['org']->id))->assertSessionHasNoErrors();

    expect(Organization::query()->findOrFail($s['org']->id)->billing_vat_id)->toBe('PT123456789')
        ->and(session('admin_notice'))->toContain('could not be sent to Stripe')
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.update_details.failed')->where('target_id', $s['org']->id)->exists())->toBeFalse();
})->group('security');

it('saving the values it already has changes nothing and writes no ledger row', function () {
    $org = a04Org('A04 Same Values');
    $org->forceFill(['default_locale' => 'en'])->save();

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.update-details', $org->id),
        ['name' => 'A04 Same Values', 'default_locale' => 'en', 'reason' => 'Checking that nothing changes.'])->assertSessionHasNoErrors();

    expect(session('admin_notice'))->toStartWith('Nothing changed')
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.update_details')->where('target_id', $org->id)->exists())->toBeFalse();
});

// ══ 3 · CREATE — through the one door; the founder sets their own password from a single-use link ═════════════

function a04NewOrg(array $overrides = []): array
{
    return array_merge([
        'org_name' => 'A04 Created Stays '.Str::random(5), 'currency' => 'EUR', 'country' => 'PT',
        'property_name' => 'A04 Seaside House', 'property_type' => 'hotel', 'location' => 'Lisbon',
        'founder_name' => 'Fiona Founder', 'founder_email' => 'a04-founder-'.Str::lower(Str::random(8)).'@vyrobel.com',
        'reason' => 'A customer signed a contract offline; creating their workspace.',
    ], $overrides);
}

/** A delivering mailer, faked: the console's own refusal treats the suite's `array` mailer as discarding. */
function a04MailOn(): void
{
    config(['mail.default' => 'smtp']);
    Mail::fake();
}

it('⚠️⚠️ creates the organisation through the one door — roles, a password-less unverified founder, property, trial', function () {
    a04MailOn();
    $input = a04NewOrg(['internal' => '1']);

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.store'), $input)->assertSessionHasNoErrors();

    $org = Organization::query()->where('name', $input['org_name'])->sole();
    $founder = User::query()->where('email', $input['founder_email'])->sole();
    expect($org->default_currency)->toBe('EUR')->and($org->country)->toBe('PT')->and($org->signup_source)->toBe('console')->and($org->isInternal())->toBeTrue()
        ->and(DB::table('users')->where('id', $founder->id)->value('password'))->toBeNull()          // no password typed by anyone
        ->and($founder->hasVerifiedEmail())->toBeFalse()                                           // the link will verify it
        ->and(DB::table('properties')->where('organization_id', $org->id)->count())->toBe(1)
        ->and(DB::table('subscriptions')->where('organization_id', $org->id)->count())->toBe(1)
        ->and(DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.organization_id', $org->id)->where('model_has_roles.model_id', $founder->id)->pluck('roles.name')->all())->toBe(['Property Owner']);

    // Audited: the intent with the reason BEFORE acting, then the result with the new id, then the link.
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.create')->where('reason', $input['reason'])->exists())->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.created')->where('target_id', $org->id)->exists())->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.founder_link')->where('target_id', $org->id)->exists())->toBeTrue();

    Mail::assertSent(OwnerInviteMail::class, fn (OwnerInviteMail $m) => $m->hasTo($input['founder_email']) && $m->invite->role === 'founder');
})->group('security');

it('⚠️⚠️ the founder\'s link sets their password, VERIFIES the address, signs them in — and is spent after one use', function () {
    a04MailOn();
    $input = a04NewOrg();
    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.store'), $input)->assertSessionHasNoErrors();

    $token = null;
    Mail::assertSent(OwnerInviteMail::class, function (OwnerInviteMail $m) use (&$token): bool {
        $token = $m->token;

        return true;
    });
    $html = (new OwnerInviteMail(OwnerInvite::query()->withoutGlobalScopes()->where('email', $input['founder_email'])->sole(), (string) $token, 'Fiona Founder'))->render();
    expect($html)->toContain('Vyrobel set up a workspace for '.$input['org_name'])->toContain(url('/invite/'.$token))
        ->not->toContain('invited you as a property owner')
        ->toContain('a workspace was set up for this address')->not->toContain('no account is created');

    $this->flushSession();
    Auth::guard('admin')->logout();
    $this->get('/invite/'.$token)->assertOk()->assertSee('Fiona Founder');
    $this->post('/invite/'.$token, ['password' => 'Str0ng-Founder-Pw!', 'password_confirmation' => 'Str0ng-Founder-Pw!'])
        ->assertRedirect(route('app.dashboard'));

    $founder = User::query()->where('email', $input['founder_email'])->sole();
    expect(Hash::check('Str0ng-Founder-Pw!', (string) $founder->password))->toBeTrue()
        ->and($founder->hasVerifiedEmail())->toBeTrue()
        ->and(Auth::guard('web')->id())->toBe((string) $founder->id)
        ->and(OwnerInvite::query()->withoutGlobalScopes()->where('email', $input['founder_email'])->value('status'))->toBe('accepted');

    Auth::guard('web')->logout();
    $this->post('/invite/'.$token, ['password' => 'An0ther-Pw-Tried!', 'password_confirmation' => 'An0ther-Pw-Tried!']);
    expect(Hash::check('Str0ng-Founder-Pw!', (string) $founder->fresh()->password))->toBeTrue('a spent link must not set a second password');
})->group('security');

it('⚠️⚠️ refuses — and creates NOTHING — when the link could not reach the founder', function () {
    $count = fn (): int => Organization::query()->withTrashed()->count();

    // 1 · mail discarding
    config(['mail.default' => 'log']);
    $admin = platformAdmin();
    $before = $count();
    $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), a04NewOrg())->assertSessionHasErrors('founder_email');
    expect($count())->toBe($before);

    // 2 · an address that can never receive mail · 3 · an address an account already holds
    a04MailOn();
    $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), a04NewOrg(['founder_email' => 'probe-x@vyrobel-probe.invalid']))->assertSessionHasErrors('founder_email');
    $taken = User::factory()->create(['email' => 'a04-taken-'.Str::lower(Str::random(6)).'@vyrobel.com']);
    $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), a04NewOrg(['founder_email' => strtoupper($taken->email)]))->assertSessionHasErrors('founder_email');

    expect($count())->toBe($before);
    Mail::assertNothingSent();
})->group('security');

it('a founder email that could not be sent is recorded as FAILED, and the admin is told how to recover', function () {
    // a real SMTP attempt at a port nothing listens on — the send throws, as an unreachable provider would
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);
    $input = a04NewOrg();

    $this->actingAs(platformAdmin(), 'admin')->post(route('admin.customers.store'), $input)->assertSessionHasNoErrors();

    $org = Organization::query()->where('name', $input['org_name'])->sole();
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.founder_link.failed')->where('target_id', $org->id)->exists())->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.founder_link')->where('target_id', $org->id)->exists())->toBeFalse()
        ->and(session('admin_notice'))->toContain('could not be sent');
})->group('security');

it('the signup funnel gives console-created organisations their own column, so the columns add up to "All"', function () {
    a04MailOn();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), a04NewOrg())->assertSessionHasNoErrors();

    $this->actingAs($admin, 'admin')->get(route('admin.funnel', ['internal' => 'include']))->assertOk()->assertSee('Console');
    $f = app(PlatformFunnel::class)->compute(true);
    foreach (PlatformFunnel::STAGES as $stage => $label) {
        expect(array_sum(array_map(fn (array $source): int => $source[$stage], $f['sources'])))->toBe($f['total'][$stage], "the columns must add up to All at {$stage}");
    }
})->group('security');

it('a founder\'s pending link is not listed among the operator\'s own pending invites', function () {
    $s = seedScenario(1);
    $staff = operatorUser($s, [], 'Hotel Manager');
    $founder = User::factory()->create(['email' => 'a04-founder-link-'.Str::lower(Str::random(6)).'@vyrobel.com']);
    app(OwnerInviteService::class)->issueFounderActivation($founder, (string) $s['org']->id);
    $ownerEmail = 'a04-owner-invite-'.Str::lower(Str::random(6)).'@vyrobel.com';
    app(TenantContext::class)->forOrganization((string) $s['org']->id, fn () => OwnerInvite::create([
        'organization_id' => $s['org']->id, 'email' => $ownerEmail, 'token_hash' => hash('sha256', Str::random(40)),
        'role' => 'owner', 'status' => 'pending', 'expires_at' => now()->addDays(7),
    ]));

    $this->actingAs($staff)->get(route('app.owners'))->assertOk()->assertSee($ownerEmail)->assertDontSee($founder->email);
})->group('security');

it('the create page is reachable (not read as an organisation id), and only by a tenant manager', function () {
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->get(route('admin.customers.create'))->assertOk()->assertSee('Create an organisation')->assertSee("Founder's email");   // I18N-01: printed through {{ }}, so the apostrophe is &#039; in the HTML
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index'))->assertOk()->assertSee(route('admin.customers.create'));

    foreach (['billing', 'analyst'] as $role) {
        $this->flushSession();
        $refused = platformAdmin(['role' => $role]);
        $this->actingAs($refused, 'admin')->get(route('admin.customers.create'))->assertForbidden();
        $this->actingAs($refused, 'admin')->post(route('admin.customers.store'), a04NewOrg())->assertForbidden();
    }
})->group('security');
