<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A room type a guest has saved to their wishlist (Phase 4c · Sub-gate 4). Guest-owned + tenant-scoped; a guest
 * only ever reads/removes their OWN items (resolved through the guest guard).
 */
class GuestWishlistItem extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = ['organization_id', 'guest_id', 'room_type_id'];

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
