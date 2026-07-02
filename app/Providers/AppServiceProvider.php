<?php

namespace App\Providers;

use App\Events\DomainEventRecorded;
use App\Listeners\GenerateDepartureCleanTask;
use App\Listeners\RecomputeAvailabilityOnInventoryChange;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\Building;
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
use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Tag;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\ManualPaymentGateway;
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

        // Phase 1 ships only the manual/offline gateway; real gateways bind here in Phase 2.
        $this->app->bind(PaymentGateway::class, ManualPaymentGateway::class);
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
        ]);

        // Domain-event consumers (synchronous, no queue). Each filters by event_type and is a
        // strict one-directional consumer: it never emits inventory events or touches sold.
        Event::listen(DomainEventRecorded::class, RecomputeAvailabilityOnInventoryChange::class);
        Event::listen(DomainEventRecorded::class, GenerateDepartureCleanTask::class);
    }
}
