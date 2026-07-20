<?php

namespace App\Providers;

use App\Events\DomainEventRecorded;
use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Listeners\GenerateDepartureCleanTask;
use App\Listeners\RecomputeAvailabilityOnInventoryChange;
use App\Listeners\SendGuestTransactionalEmails;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\BookingQuote;
use App\Models\Building;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\Fee;
use App\Models\Floor;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Group;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\InventoryBlock;
use App\Models\Invoice;
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
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Models\Payout;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Tag;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\ManualPaymentGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\Rails\ManualPayoutRail;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\ChannexClient;
use App\Services\Channex\FakeChannexClient;
use App\Support\Owner\OwnerContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

        // Payment gateway selection is config-driven (services.payments.gateway; per-tenant
        // override is a Phase 3 hook). `manual` = offline (Phase 1); `stripe` = Step 6. FolioService
        // resolves this each use, so a per-context switch just re-reads the config.
        $this->app->bind(PaymentGateway::class, fn ($app): PaymentGateway => config('services.payments.gateway') === 'stripe'
            ? $app->make(StripeGateway::class)
            : $app->make(ManualPaymentGateway::class));

        // Owner payout rail (Step 5): manual (offline) by default; Stripe Connect is a config-gated,
        // deferred skeleton that throws if selected — never the active rail in tests.
        $this->app->bind(PayoutRail::class, fn ($app): PayoutRail => config('services.payouts.rail') === 'stripe_connect'
            ? $app->make(StripeConnectPayoutRail::class)
            : $app->make(ManualPayoutRail::class));

        // Config-gated Stripe SDK seam (services.stripe.fake, default true): FakeStripeClient
        // everywhere until real keys are deliberately configured. Singleton so a fake stays
        // inspectable across a request/test.
        $this->app->singleton(StripeClientInterface::class, function (): StripeClientInterface {
            if (config('services.stripe.fake', true)) {
                return new FakeStripeClient(config('services.stripe.webhook_secret'));
            }

            return new StripeClient(
                (string) config('services.stripe.secret'),
                config('services.stripe.webhook_secret'),
            );
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
            'management_agreement' => ManagementAgreement::class,
            'owner_ledger' => OwnerLedgerEntry::class,
            'owner_expense' => OwnerExpense::class,
            'owner_statement' => OwnerStatement::class,
            'owner_statement_line' => OwnerStatementLine::class,
            'payout' => Payout::class,
            'maintenance_ticket' => MaintenanceTicket::class,
            'maintenance_setting' => MaintenanceSetting::class,
            'message_thread' => MessageThread::class,
            'message' => Message::class,
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

        // Rate limiter for the public booking engine (Step 7) — per-IP, protects search/quote/book
        // from scraping/abuse. Limit is config-driven so it can be tightened per environment.
        RateLimiter::for('booking', fn (Request $request): Limit => Limit::perMinute(
            (int) config('services.booking.rate_limit', 60)
        )->by($request->ip() ?? 'unknown'));
    }
}
