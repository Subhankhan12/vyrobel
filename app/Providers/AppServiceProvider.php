<?php

namespace App\Providers;

use App\Auth\GuestUserProvider;
use App\Contracts\Auth\SocialIdentityProvider;
use App\Events\DomainEventRecorded;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Listeners\AdjustOwnerLedgerOnRefund;
use App\Listeners\AuditOAuthTokenGranted;
use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Listeners\GenerateDepartureCleanTask;
use App\Listeners\ReattributeOwnerOnUnitMove;
use App\Listeners\RecomputeAvailabilityOnInventoryChange;
use App\Listeners\RecordAuthActivity;
use App\Listeners\SendGuestTransactionalEmails;
use App\Models\AddOn;
use App\Models\AgreementAmendment;
use App\Models\Amenity;
use App\Models\BookingQuote;
use App\Models\Building;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\EmailTemplate;
use App\Models\Fee;
use App\Models\Floor;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Group;
use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Models\HousekeepingTask;
use App\Models\InventoryBlock;
use App\Models\Invoice;
use App\Models\IpAllowRule;
use App\Models\LeaveRequest;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\ManagementAgreement;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Note;
use App\Models\OpenShift;
use App\Models\Organization;
use App\Models\OverbookingConflict;
use App\Models\Owner;
use App\Models\OwnerAttentionState;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Models\Payout;
use App\Models\PromoCode;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RateProposal;
use App\Models\RateSeason;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\SchedulePublication;
use App\Models\Shift;
use App\Models\Tag;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\ManualPaymentGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSafetyGuard;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\Rails\ManualPayoutRail;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\Auth\GoogleIdentityProvider;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\ChannexClient;
use App\Services\Channex\FakeChannexClient;
use App\Support\Auth\PasswordPolicy;
use App\Support\Llm\AnthropicLlmClient;
use App\Support\Llm\FakeLlmClient;
use App\Support\Llm\LlmClientInterface;
use App\Support\Llm\LlmCostLedger;
use App\Support\Llm\LlmSafetyGuard;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Notifications\BookingNotificationIntent;
use App\Support\OAuth\OAuthScopes;
use App\Support\Owner\OwnerContext;
use App\Support\Property\PropertyContext;
use App\Support\Safety\BaselineTenantWriteGuard;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ⚠️ F-2 — THE DEVICE AUTHORIZATION GRANT IS NOT PART OF THIS PRODUCT, and leaving it on served a
        //    500 to strangers. Passport enables it BY DEFAULT ($deviceCodeGrantEnabled = true) and registers
        //    `GET /oauth/device` with `web` and no auth. This project never configured it: no device view is
        //    published and no `DeviceUserCodeViewResponse` is bound, so an UNAUTHENTICATED visitor hit
        //    `BindingResolutionException: Target [Laravel\Passport\Contracts\DeviceUserCodeViewResponse] is
        //    not instantiable` — a debug page full of framework internals locally, and an unexplained 500 in
        //    production, on an endpoint nobody could use either way.
        //
        //    ⚠️ THE FIX IS TO NOT OFFER THE FEATURE, rather than to 404 it or render a placeholder. The OAuth
        //    server here is the authorization-code + PKCE flow for third-party apps registered by a platform
        //    admin (docs/OAUTH-DESIGN.md); the device grant is for input-constrained clients like TVs and
        //    CLIs and was never designed, scoped or tested. A route that exists only because a dependency
        //    defaults it on is not a feature. ⚠️ It must be set in register(): Passport loads its routes in
        //    boot(), and every provider registers before any provider boots.
        Passport::$deviceCodeGrantEnabled = false;

        // ⚠️⚠️ SOCIAL SIGN-IN RESOLVES TO THE REAL PROVIDER, ALWAYS. There is no config flag that can swap in
        //    a fake, because a fake provider reachable in production is an authentication bypass — whoever
        //    reaches it signs in as anyone. Compare FakeChannexClient, bound by a config default of TRUE:
        //    right for a channel manager, catastrophic here. The only fake is bound by a test, in-process,
        //    where no route exists. The feature's off-switch is the ABSENCE OF CREDENTIALS, nothing else.
        $this->app->bind(SocialIdentityProvider::class, GoogleIdentityProvider::class);
        // The tenant context is request/job-scoped shared state. It MUST be a singleton:
        // the BelongsToOrganization trait, the OrganizationScope, and the event recorders
        // all resolve app(TenantContext::class) and must see the same instance.
        $this->app->singleton(TenantContext::class);

        // Owner-portal scope (Step 6) is request-scoped shared state too — the OwnerScope global
        // scope and SetOwnerScopeForPortal middleware must see the same instance. Inactive by default.
        $this->app->singleton(OwnerContext::class);

        // Property working-context (Phase 6a · multi-property) is request-scoped shared state: the
        // SetCurrentPropertyForWebRequest middleware, the switcher, and every /app screen resolving
        // "the current property" must see the same instance. Inactive by default (CLI/queue/tests).
        $this->app->singleton(PropertyContext::class);

        // GATE 1 — the New Reservation "Send confirmation email" toggle is request-scoped: the controller
        // stashes the operator's choice and SendGuestTransactionalEmails reads it during the synchronous
        // reservation.confirmed event. Must be ONE instance per request. Defaults to "send" everywhere else.
        $this->app->singleton(BookingNotificationIntent::class);

        // Payment gateway selection is config-driven (services.payments.gateway; per-tenant
        // override is a Phase 3 hook). `manual` = offline (Phase 1); `stripe` = Step 6. FolioService
        // resolves this each use, so a per-context switch just re-reads the config.
        $this->app->bind(PaymentGateway::class, fn ($app): PaymentGateway => config('services.payments.gateway') === 'stripe'
            ? $app->make(StripeGateway::class)
            : $app->make(ManualPaymentGateway::class));

        // Owner payout rail: manual (offline) by default; `stripe_connect` is the LIVE Connect rail (Phase 8 ·
        // SG3 — real transfers to owners' connected accounts, idempotency-keyed + row-locked). Test keys/fake
        // in the build; live keys are Subhan's go-live. The container auto-injects the Stripe client seam.
        $this->app->bind(PayoutRail::class, fn ($app): PayoutRail => config('services.payouts.rail') === 'stripe_connect'
            ? $app->make(StripeConnectPayoutRail::class)
            : $app->make(ManualPayoutRail::class));

        // Config-gated Stripe SDK seam (services.stripe.fake, default true): FakeStripeClient
        // everywhere until real keys are deliberately configured. Singleton so a fake stays
        // inspectable across a request/test.
        // 🔒 Phase 8 money #1 guard: a LIVE key outside `production` is REFUSED — the fake client is bound
        // instead (StripeSafetyGuard), so a stray STRIPE_FAKE=false + a live key in a dev .env can never charge.
        $this->app->singleton(StripeClientInterface::class, function ($app): StripeClientInterface {
            $secret = (string) config('services.stripe.secret');
            $forceFake = StripeSafetyGuard::forceFakeOutsideProduction(
                $app->environment(),
                (bool) config('services.stripe.fake', true),
                $secret,
            );

            if ($forceFake) {
                if (! config('services.stripe.fake', true)) {
                    Log::warning('StripeSafetyGuard refused a live Stripe key outside production — using the fake client.', [
                        'environment' => $app->environment(),
                    ]);
                }

                return new FakeStripeClient(config('services.stripe.webhook_secret'), config('services.stripe.connect_webhook_secret'));
            }

            return new StripeClient($secret, config('services.stripe.webhook_secret'), config('services.stripe.connect_webhook_secret'));
        });

        // Config-gated (services.channex.fake, default true): FakeChannexClient everywhere
        // until real staging credentials are deliberately configured. Singleton so a fake
        // bound in a test stays inspectable after a queued AriPushJob runs against it.
        $this->app->singleton(ChannelManagerClient::class, function ($app) {
            if (config('services.channex.fake', true)) {
                return new FakeChannexClient;
            }

            return new ChannexClient(
                (string) config('services.channex.base_url'),
                (string) config('services.channex.api_key'),
            );
        });

        // Config-gated LLM seam (services.llm.fake, default true): FakeLlmClient everywhere
        // (deterministic, no network, no cost) until a real key is deliberately configured in production.
        // 🔒 Ask Vyrobel spend guard: a real Anthropic call is REFUSED outside production (or with no key) —
        // the fake is bound instead (LlmSafetyGuard), so a stray LLM_FAKE=false in a dev .env can never spend.
        $this->app->singleton(LlmClientInterface::class, function ($app): LlmClientInterface {
            $apiKey = (string) config('services.llm.api_key');
            $forceFake = LlmSafetyGuard::forceFakeOutsideProduction(
                $app->environment(),
                (bool) config('services.llm.fake', true),
                $apiKey,
            );

            if ($forceFake) {
                if (! config('services.llm.fake', true)) {
                    Log::warning('LlmSafetyGuard refused a real LLM key outside production — using the fake client.', [
                        'environment' => $app->environment(),
                    ]);
                }

                return new FakeLlmClient;
            }

            return new AnthropicLlmClient($apiKey, (string) config('services.llm.model'));
        });

        // The Ask-Vyrobel cost ledger (per-owner/day token cap) — resolved from config so the cap is one place.
        $this->app->bind(LlmCostLedger::class, static fn (): LlmCostLedger => LlmCostLedger::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ⚠️⚠️ FIRST, BEFORE ANYTHING ELSE CAN WRITE. Disarmed unless `security.baseline_tenant_guard` names
        //    an organisation — so the seeder is unaffected and only a probe harness turns it on. It exists
        //    because "do not touch tenant one" was in every brief for weeks and a probe wrote there anyway:
        //    a rule that is written down and still fires is a note, not a rule.
        BaselineTenantWriteGuard::armFromConfig();

        // HTTPS SCHEME FORCING (audit 2026-08-07 · F6-10). Every generated URL — form actions, redirects,
        // password-reset and email-verification signed links, the assets in a transactional email — is built
        // from the request scheme. Behind a TLS-terminating proxy that scheme reads `http`, so links would be
        // emitted as http:// even on an https:// site: mixed content in the browser, and http links inside
        // guest emails.
        //
        // Production-only, so local http dev is untouched. FORCE_HTTPS can enable it elsewhere (a staging box
        // behind a proxy) or disable it in production if TLS genuinely terminates at the app.
        //
        // ⚠️ Read through config(), NOT env(). `config:cache` — which the deploy runbook runs — makes Laravel
        // skip loading .env, so an env() call here returns NULL in production and this would silently never
        // fire. See config/security.php.
        /*
         * ⚠️⚠️ HOST-HEADER POISONING — THE EMAILED TOKEN (audit 2026-08-29 · H-1). THIS IS THE FIX.
         *
         * `route()`/`url()` build an ABSOLUTE url from the REQUEST's host unless a root url is forced.
         * Password-reset mail is rendered synchronously INSIDE the request, so a poisoned `Host` put the
         * attacker's domain into the victim's email — and the reset TOKEN travelled with it.
         *
         * ⚠️ REPRODUCED BOTH WAYS against the running app before this line existed:
         *     Host: attacker.example              → http://attacker.example/reset-password/<valid token>
         *     X-Forwarded-Host: evil.example      → http://evil.example/reset-password/<valid token>
         *       (the second with TRUSTED_PROXIES=*, which config/security.php recommends behind a load
         *        balancer — i.e. the NORMAL production posture)
         *
         * ⚠️ FORCING THE ROOT URL IS THE FIX, NOT `trustHosts()`. Trusted hosts only bound WHICH hosts may
         * be supplied; the request would still CHOOSE among them. Forcing the root makes every generated
         * url independent of the request entirely.
         *
         * ⚠️ SAFE HERE ONLY BECAUSE THIS APP IS SERVED ON EXACTLY ONE HOST — verified: zero `Route::domain`,
         * no subdomain routing, and `organizations.custom_domain` has no reader (it is inert). If a custom
         * domain is ever wired, THIS LINE MUST BE REVISITED FIRST.
         *
         * ⚠️ GUARDED: forcing a root url from an unset/!absolute APP_URL would be worse than the bug — every
         * link in the product would break. With no usable host configured this does nothing and behaviour is
         * exactly as before. `ops/deploy.sh` pre-flight now refuses a deploy in that state.
         *
         * ⚠️ BEFORE forceScheme, so the https forcing below still governs the scheme.
         */
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($configuredHost) && $configuredHost !== '') {
            URL::forceRootUrl((string) config('app.url'));
        }

        if ((bool) config('security.force_https')) {
            URL::forceScheme('https');
        }

        /*
         * OWNER SCOPE ON THE LIVEWIRE UPDATE PATH — DEFENCE IN DEPTH, NOT THE FIX (audit 2026-08-29 · E-1).
         *
         * ⚠️ THE FIX IS IN THE SCOPE. {@see \App\Models\Scopes\OwnerScope} now THROWS when an owner-linked
         * actor reads with no owner context, so a surface that forgets this middleware fails loudly instead of
         * returning every owner's rows. That guarantee does NOT depend on this registration.
         *
         * ⚠️ THIS LINE IS WHAT MAKES THE OWNER PORTAL WORK AGAIN. `/livewire/update` does not re-run
         * route-group middleware, so without it every owner re-render would (correctly, but uselessly) 500.
         * Livewire re-applies a persistent middleware ONLY for components whose ORIGINAL route carried it —
         * it resolves that route from the snapshot's own path/method memo — so this is confined to `/owner/*`
         * and no operator component is affected.
         *
         * ⚠️ ORDER MATTERS AND IS DELIBERATE: the scope was closed first, this was added second. Adding only
         * this would have closed ONE surface and left the shape intact for the next path that forgets.
         */
        Livewire::addPersistentMiddleware([
            SetOwnerScopeForPortal::class,
        ]);

        /*
         * PASSWORD COMPOSITION — THE ONE DEFINITION (DO13-4, 2026-08-15).
         *
         * ⚠️ WHAT IT REPLACED. Every set-password path validated `Password::min(8)` and nothing else, so
         * `aaaaaaaa` was an acceptable password on NINE surfaces — including public self-signup and the whole
         * guest realm — while the Guest Signup screen promised "8+ characters with a number and symbol".
         *
         * ⚠️ WHY IT LIVES HERE RATHER THAN AT THE CALL SITES. `Password::defaults()` is the framework's single
         * chokepoint: nine controllers now ask for `Password::defaults()`, so a future policy change is ONE
         * edit and no path can silently drift weaker than its siblings (§N.8f — the reason this defect
         * existed at all was nine independent copies of one rule).
         *
         * ⚠️ THE COMPOSITION MATCHES THE STRONGEST PROMISE THE PRODUCT MAKES, and that needed deciding rather
         * than reading off, because the prototypes do NOT agree:
         *     Vyrobel Set Password  — "At least 8 characters"                    (length only)
         *     Owner Security        — "at least 8 characters with a number."     (+ number)
         *     Guest Signup          — "8+ characters with a number and symbol"   (+ number + symbol)
         * A per-realm password policy would be a security smell and three more copies to drift, so the
         * strongest promise becomes the floor for everyone — every screen's stated rule is then satisfied,
         * and the weaker screens' COPY was corrected to say what is now enforced (a rule the UI does not
         * state produces mystery rejections; §N.8d in reverse).
         *
         * ⚠️ The composition itself — and the SENTENCE that describes it to users — live together in
         * {@see PasswordPolicy}, so a policy change cannot tighten the rule while eight screens keep
         * describing the old one. That pairing is the actual fix; this line is only the registration.
         *
         * ⚠️ `uncompromised()` (the Have-I-Been-Pwned k-anonymity breach check) is NOT enabled here — it is a
         * founder call, not a code call: it adds an outbound API dependency on every password set and rejects
         * passwords users consider fine. Flagged in DECISIONS, not bundled into a composition fix.
         *
         * ⚠️ NOT applied to `CreatePlatformAdminCommand`, which requires min(12) + mixedCase + numbers +
         * symbols. Pointing it here would WEAKEN it. A stronger bar for the platform super-admin is a real
         * difference, not drift — confirm-before-collapsing, the same call the payout-status maps needed.
         */
        Password::defaults(fn (): Password => PasswordPolicy::rule());

        // TRUSTED PROXIES (audit 2026-08-07 · F6-10). With none configured Laravel trusts NO proxy — safe (a
        // spoofed X-Forwarded-For is ignored) but behind a real load balancer three controls break QUIETLY:
        // EnforceIpAllowlist compares the PROXY's address so the operator IP allowlist blocks everyone; the
        // per-IP login rate limiter collapses into one bucket for the internet; and isSecure() reads false,
        // suppressing HSTS and defeating the secure session cookie.
        //
        // ⚠️ SET VIA THE STATIC, FROM boot(), AND BOTH HALVES OF THAT MATTER — each learned by getting it
        // wrong and watching the header disappear:
        //   • NOT `$middleware->trustProxies(...)` in bootstrap/app.php reading config(): that closure runs
        //     before the container has a `config` binding and fatals outright.
        //   • NOT a custom TrustProxies subclass either: the framework registers its OWN TrustProxies in the
        //     global stack, which runs after a prepended subclass and RESETS the trusted set to empty.
        //     `::at()` writes the static the framework's own middleware reads, so it survives.
        //   • NOT env() anywhere outside config/: `config:cache` (in the deploy runbook) stops .env loading,
        //     so it would be NULL in production — silently dead exactly where it is needed.
        $proxies = trim((string) config('security.trusted_proxies', ''));

        if ($proxies !== '') {
            TrustProxies::at($proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))));
            TrustProxies::withHeaders(
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }

        // Phase 8 · production email — THE #1 GUARD: a real-sending mail transport (Postmark/SES/…) is refused
        // outside `production`, downgraded to `log`, so no dev/CI run can ever email a real person — even with
        // MAIL_MAILER=postmark in a stray .env. Enforced in code, not by convention. See MailSafetyGuard.
        $configuredMailer = (string) config('mail.default');
        $safeMailer = MailSafetyGuard::safeMailerFor($this->app->environment(), $configuredMailer);
        if ($safeMailer !== $configuredMailer) {
            config(['mail.default' => $safeMailer]);
            Log::warning('MailSafetyGuard downgraded a real mail transport outside production.', [
                'environment' => $this->app->environment(),
                'configured' => $configuredMailer,
                'using' => $safeMailer,
            ]);
        }

        // Phase 4a — register the GUEST realm's custom user provider (blind-index lookup, tenant-scope-free).
        // Named 'guest-eloquent' in config/auth.php providers.guests. Never touches the `web`/`users` provider.
        Auth::provider('guest-eloquent', function ($app, array $config): GuestUserProvider {
            return new GuestUserProvider($app['hash'], (string) $config['model']);
        });

        // Wave D · OAuth2 (Laravel Passport) — the third-party scoped API. ADDITIVE: only the `api` guard uses it.
        //   • the LEAST-PRIVILEGE scope catalog (the ONE source, {@see OAuthScopes}) — drives the consent screen.
        //   • bounded token lifetimes (short access token; a refresh token; capped personal-access).
        // Clients are platform-admin-registered (no open self-serve); PKCE is per public client. Password grant
        // is NOT enabled (all access is owner-consented via authorization-code). Client-secret hashing is set in
        // config/passport.php (`hash_client_secrets`) rather than a runtime call in Passport 13.
        Passport::tokensCan(OAuthScopes::catalog());
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        // The BRANDED consent screen — the owner sees EXACTLY the requested scopes before authorizing.
        Passport::authorizationView('oauth.consent');
        // Audit every token issuance (#20) — the token exchange is session-less, so the listener resolves the
        // actor + org itself (there is no auth()/TenantContext at /oauth/token).
        Event::listen(AccessTokenCreated::class, AuditOAuthTokenGranted::class);

        // Stable morph aliases so polymorphic *_type columns (mediable_type, notable_type,
        // subject_type, owner_type, model_has_roles.model_type, …) store short, refactor-proof
        // names instead of FQCNs. ENFORCED: any polymorphic use of an unmapped model throws,
        // so every morphable model added in later steps must be registered here.
        Relation::enforceMorphMap([
            'organization' => Organization::class,
            'user' => User::class,
            'property' => Property::class,
            'building' => Building::class,
            'floor' => Floor::class,
            'room_type' => RoomType::class,
            'unit' => Unit::class,
            'inventory_block' => InventoryBlock::class,
            'amenity' => Amenity::class,
            'tag' => Tag::class,
            // Registered when the guest profile became the first thing to WRITE a note (Wave 2 · Sub-gate 5).
            // Latent until now only because Note is always the morph CHILD (`notable_type` stores the mapped
            // parent), so nothing ever asked Note for its own alias. Both event recorders call
            // `$subject?->getMorphClass()`, and under enforceMorphMap an unregistered model THROWS — so the
            // first `record('note.created', $note)` would have blown up. Proven, not assumed.
            'note' => Note::class,
            'media' => Media::class,
            'guest' => Guest::class,
            'reservation' => Reservation::class,
            'reservation_unit' => ReservationUnit::class,
            'group' => Group::class,
            'folio' => Folio::class,
            'folio_item' => FolioItem::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'payment_authorization' => PaymentAuthorization::class,
            'tax' => Tax::class,
            'fee' => Fee::class,
            'ip_allow_rule' => IpAllowRule::class,
            'schedule_publication' => SchedulePublication::class,
            'open_shift' => OpenShift::class,
            'add_on' => AddOn::class,
            'housekeeping_task' => HousekeepingTask::class,
            'channel' => Channel::class,
            'channel_mapping' => ChannelMapping::class,
            'rate_plan' => RatePlan::class,
            // F-22 — a season is now an AUDIT SUBJECT (rate_season.created/updated/deleted call
            // getMorphClass()), and enforceMorphMap THROWS on an unregistered model. ⚠️ This absence is
            // part of WHY that trail was empty: the recorder could not reference a season at all.
            'rate_season' => RateSeason::class,
            'overbooking_conflict' => OverbookingConflict::class,
            'webhook_event' => WebhookEvent::class,
            'booking_quote' => BookingQuote::class,
            'owner' => Owner::class,
            // My Units · GATE 3 — the Add-Unit wizard makes UnitRequest a morph PARENT (photos in `media`,
            // amenities in `amenitiables`) and an audit subject (`owner.unit_requested` calls getMorphClass()),
            // and enforceMorphMap THROWS on an unregistered model — so it needs an alias.
            'unit_request' => UnitRequest::class,
            'management_agreement' => ManagementAgreement::class,
            // Owner Agreement exact-match (#82) — the amendment is an audit subject
            // (owner.agreement.amendment_acknowledged calls getMorphClass()); enforceMorphMap THROWS on an
            // unregistered model, so it needs an alias.
            'agreement_amendment' => AgreementAmendment::class,
            // Operator Email Templates (#86) — the per-org email customization is an audit subject
            // (email_template.updated calls getMorphClass()); enforceMorphMap THROWS on an unregistered model.
            'email_template' => EmailTemplate::class,
            'owner_ledger' => OwnerLedgerEntry::class,
            'owner_expense' => OwnerExpense::class,
            'owner_attention_state' => OwnerAttentionState::class,
            'rate_proposal' => RateProposal::class,
            'owner_statement' => OwnerStatement::class,
            'owner_statement_line' => OwnerStatementLine::class,
            'payout' => Payout::class,
            'maintenance_ticket' => MaintenanceTicket::class,
            'maintenance_setting' => MaintenanceSetting::class,
            'message_thread' => MessageThread::class,
            'message' => Message::class,
            // Phase 4c — the guest wallet ledger + promo codes (used as polymorphic `source` on ledger entries
            // and folio items; enforceMorphMap THROWS on an unregistered model, so both must be mapped).
            'guest_credit_entry' => GuestCreditEntry::class,
            'promo_code' => PromoCode::class,
            // Phase 5 — reviews are a domain-event/audit subject (review.submitted), so the model needs an alias.
            'review' => Review::class,
            // Phase 7d — roster shifts + time-off are audit subjects (schedule.shift.assigned / leave.*).
            'shift' => Shift::class,
            'leave_request' => LeaveRequest::class,
        ]);

        // Domain-event consumers (synchronous, no queue). Each filters by event_type and is a
        // strict one-directional consumer: it never emits inventory events or touches sold.
        // EnqueueAriPushOnInventoryChange is registered LAST so it reads availability AFTER
        // RecomputeAvailabilityOnInventoryChange has already applied any recompute for the
        // same event (both react to inventory_block.created, etc.).
        Event::listen(DomainEventRecorded::class, RecomputeAvailabilityOnInventoryChange::class);
        Event::listen(DomainEventRecorded::class, GenerateDepartureCleanTask::class);
        Event::listen(DomainEventRecorded::class, EnqueueAriPushOnInventoryChange::class);
        // ⚠️⚠️ MONEY LISTENERS RUN BEFORE BEST-EFFORT ONES, AND THE ORDER IS LOAD-BEARING.
        //
        // These listeners are SYNCHRONOUS and run in registration order, so a throw in one stops every
        // listener after it. The two below post MONEY; the two after them send a notification and an email.
        // Registered the other way round — which is how this was — a failed notification meant a guest was
        // refunded and the OWNER'S LEDGER WAS NEVER DEBITED, and `settleRefund`'s status guard made the
        // retry a no-op, so it could never be recovered. Measured end to end before this moved.
        //
        // ⚠️ MONEY FIRST, TRAIL SECOND, TELLING PEOPLE THIRD. Nothing best-effort may sit between a
        //    committed settlement and the ledger row that settlement implies.
        //
        // #100 — a guest refund posts an owner-statement ADJUSTMENT (the owner's net-ratio share of the refund),
        // landing on the next statement close. Same bus; idempotent; a no-op for an operator-only reservation.
        Event::listen(DomainEventRecorded::class, AdjustOwnerLedgerOnRefund::class);
        // ⚠️ MONEY: the owner attribution follows the unit when a booking is moved. Calls the CERTIFIED
        //    `reattributeReservation()` (whole-transaction reversal + fresh attribution) — which existed
        //    with no caller, which is exactly why the money never followed.
        Event::listen(DomainEventRecorded::class, ReattributeOwnerOnUnitMove::class);
        // Phase 3 — turn real domain events into per-user in-app notifications (database channel). A strict
        // one-directional consumer: it reads the event, resolves recipients, and dispatches; it records no domain
        // events itself (no loop). Its failures are caught and logged, so they cannot abort the chain.
        Event::listen(DomainEventRecorded::class, DispatchNotificationsForDomainEvent::class);
        // Phase 3 · Sub-gate 4 — the GUEST transactional emails on their real events (booking confirmed /
        // cancelled / refunded). Same bus, same one-directional discipline; a mail failure never breaks the event.
        Event::listen(DomainEventRecorded::class, SendGuestTransactionalEmails::class);

        // Super Admin · Build 2 · Gate 1 — capture auth SECURITY SIGNALS (login / failed / lockout) for the
        // platform activity log. ADDED onto the framework auth events (the tenant login flow is NOT modified);
        // the write can never break auth (swallowed on failure). Privacy: 90-day retention, security purpose,
        // /admin-only access, no guest PII persisted (see RecordAuthActivity + the login_activity migration).
        Event::listen(Login::class, [RecordAuthActivity::class, 'login']);
        Event::listen(Failed::class, [RecordAuthActivity::class, 'failed']);
        Event::listen(Lockout::class, [RecordAuthActivity::class, 'lockout']);

        // Rate limiter for the public booking engine (Step 7) — per-IP, protects search/quote/book
        // from scraping/abuse. Limit is config-driven so it can be tightened per environment.
        RateLimiter::for('booking', fn (Request $request): Limit => Limit::perMinute(
            (int) config('services.booking.rate_limit', 60)
        )->by($request->ip() ?? 'unknown'));

        // ⚠️ THE TRY-BOOKING SANDBOX — the PARAMETERISED path only (front-website Gate 4b).
        //
        // ⚠️ WHY IT NEEDS ONE AT ALL. Restoring the date picker made /try-booking accept `?from=&to=&m=`,
        // and a parameterised request re-prices a two-month calendar night by night through the engine —
        // roughly sixty reads. That is a small amplification factor on an anonymous, uncached URL, so the
        // interactive path is capped per IP. ⚠️ It is NOT a pricing oracle: there is no property parameter
        // (TryBookingSandbox::DEMO_SLUG is a constant), so no request can point it at another customer's
        // rates — the throttle bounds the WORK, not the exposure.
        //
        // ⚠️ AND THE PLAIN PAGE IS DELIBERATELY UNLIMITED. /try-booking with no query string is an ordinary
        // marketing page that crawlers and share-preview fetchers hit; throttling it would 429 them for no
        // safety gain. Limit::none() says that in the one place the decision lives.
        RateLimiter::for('try-booking', fn (Request $request): Limit => $request->hasAny(['from', 'to', 'm'])
            ? Limit::perMinute((int) config('services.marketing.try_booking_rate_limit', 40))->by($request->ip() ?? 'unknown')
            : Limit::none());

        // ⚠️ THE PUBLIC CONTACT FORM (front-website Gate 7) — per IP, on an unauthenticated WRITE that
        // sends mail. The two properties that make it worth a limiter of its own: it is the only public
        // endpoint that persists a row a stranger composed, and it is the only one that can cause an email
        // to be sent. Left ungoverned it is a free mail relay and a free disk-filler.
        //
        // ⚠️ SET FOR THE HONEST MISTAKE, NOT THE ATTACK. Five a minute is far above anything a person
        // writing a message does — including mistyping their address and resubmitting twice — and far below
        // what makes the endpoint useful to anyone else. The abusive case is caught by the honeypot before
        // it reaches validation; this bounds the merely noisy one.
        RateLimiter::for('marketing-contact', fn (Request $request): Limit => Limit::perMinute(
            (int) config('services.marketing.contact_rate_limit', 5)
        )->by($request->ip() ?? 'unknown'));

        // PER-IP AUTH THROTTLE (audit 2026-08-07 · F-2a-04).
        //
        // ⚠️ THE FINDING WAS HALF RIGHT, and the difference decides the fix. The audit reported "no rate limit
        // on ANY of the four login endpoints". Verified against the code: a PER-CREDENTIAL limit already exists
        // and works — LoginRequest / GuestLoginRequest / AdminLoginRequest and both 2FA challenge controllers
        // each throttle at 5 attempts on `email|ip`, and answer with a friendly "try again in N seconds"
        // validation error rather than a bare 429.
        //
        // What was genuinely missing is the PER-IP net. Every attempt in a CREDENTIAL-STUFFING run uses a
        // DIFFERENT email, so it fills a different per-credential bucket and none of them ever trips — which is
        // exactly why the audit's 30 attempts from one IP were all accepted. This limiter adds ONLY that
        // missing dimension; the per-credential limit deliberately stays in the FormRequests, where it keeps
        // its better UX and its existing test.
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(
            (int) config('services.auth.login_ip_rate_limit', 20)
        )->by($request->ip() ?? 'unknown'));

        // Phase 7c · SG2 — owner-team UI gating. ONE source of truth with the route gate ({@see
        // EnsureOwnerCapability}): both read the acting role from OwnerContext, so a control is shown only
        // when the route would allow it (never inviting a 403). @ownercan hides a capability-scoped control;
        // @primaryowner hides team management (the primary owner's alone).
        Blade::if('ownercan', fn (string $capability): bool => app(OwnerContext::class)->can($capability));
        Blade::if('primaryowner', fn (): bool => app(OwnerContext::class)->isPrimaryOwner());
        Blade::if('ownersubuser', fn (): bool => app(OwnerContext::class)->isSubUser());
    }
}
