<?php

namespace App\Providers;

use App\Auth\GuestUserProvider;
use App\Events\DomainEventRecorded;
use App\Listeners\AuditOAuthTokenGranted;
use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Listeners\GenerateDepartureCleanTask;
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
use App\Models\LeaveRequest;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\ManagementAgreement;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Note;
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
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Review;
use App\Models\RoomType;
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
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\ChannexClient;
use App\Services\Channex\FakeChannexClient;
use App\Support\Llm\AnthropicLlmClient;
use App\Support\Llm\FakeLlmClient;
use App\Support\Llm\LlmClientInterface;
use App\Support\Llm\LlmCostLedger;
use App\Support\Llm\LlmSafetyGuard;
use App\Support\Mail\MailSafetyGuard;
use App\Support\OAuth\OAuthScopes;
use App\Support\Owner\OwnerContext;
use App\Support\Property\PropertyContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
            'add_on' => AddOn::class,
            'housekeeping_task' => HousekeepingTask::class,
            'channel' => Channel::class,
            'channel_mapping' => ChannelMapping::class,
            'rate_plan' => RatePlan::class,
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
        // Phase 3 — turn real domain events into per-user in-app notifications (database channel). A strict
        // one-directional consumer: it reads the event, resolves recipients, and dispatches; it records no domain
        // events itself (no loop).
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

        // Phase 7c · SG2 — owner-team UI gating. ONE source of truth with the route gate ({@see
        // EnsureOwnerCapability}): both read the acting role from OwnerContext, so a control is shown only
        // when the route would allow it (never inviting a 403). @ownercan hides a capability-scoped control;
        // @primaryowner hides team management (the primary owner's alone).
        Blade::if('ownercan', fn (string $capability): bool => app(OwnerContext::class)->can($capability));
        Blade::if('primaryowner', fn (): bool => app(OwnerContext::class)->isPrimaryOwner());
        Blade::if('ownersubuser', fn (): bool => app(OwnerContext::class)->isSubUser());
    }
}
