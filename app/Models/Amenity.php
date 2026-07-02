<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Global shared amenity catalog (no organization_id — a reference/lookup reused across
 * tenants). Associations to tenant entities live in the polymorphic `amenitiables` pivot.
 */
class Amenity extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'icon',
        'category',
    ];
}
