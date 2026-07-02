<?php

namespace App\Providers;

use App\Events\DomainEventRecorded;
use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Listeners\GenerateDepartureCleanTask;
use App\Listeners\RecomputeAvailabilityOnInventoryChange;
use App\Models\AddOn;
use App\Models\Amenity;
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
use App\Models\Media;
use App\Models\Organization;
use App\Models\OverbookingConflict;
use App\Models\Payment;
use App\Models\PaymentAuthorization;
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
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\ChannexClient;
use App\Services\Channex\FakeChannexClient;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
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

        // Payment gateway selection is config-driven (services.payments.gateway; per-tenant
        // override is a Phase 3 hook). `manual` = offline (Phase 1); `stripe` = Step 6. FolioService
        // resolves this each use, so a per-context switch just re-reads the config.
        $this->app->bind(PaymentGateway::class, fn ($app): PaymentGateway => config('services.payments.gateway') === 'stripe'
            ? $app->make(StripeGateway::class)
            : $app->make(ManualPaymentGateway::class));

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
        ]);

        // Domain-event consumers (synchronous, no queue). Each filters by event_type and is a
        // strict one-directional consumer: it never emits inventory events or touches sold.
        // EnqueueAriPushOnInventoryChange is registered LAST so it reads availability AFTER
        // RecomputeAvailabilityOnInventoryChange has already applied any recompute for the
        // same event (both react to inventory_block.created, etc.).
        Event::listen(DomainEventRecorded::class, RecomputeAvailabilityOnInventoryChange::class);
        Event::listen(DomainEventRecorded::class, GenerateDepartureCleanTask::class);
        Event::listen(DomainEventRecorded::class, EnqueueAriPushOnInventoryChange::class);
    }
}
