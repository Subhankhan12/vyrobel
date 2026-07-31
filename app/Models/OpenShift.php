<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * #104b GATE 3 — an UNASSIGNED shift slot (an open shift). A Shift row can't be open (user_id non-nullable +
 * unique(property,user,date)), so an open slot is its own row; filling it creates a real Shift via
 * ShiftRosterService and deletes this row. Tenant-scoped ({@see BelongsToOrganization}).
 *
 * @property Carbon $date
 * @property string $shift_type
 * @property string|null $department
 */
class OpenShift extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = ['organization_id', 'property_id', 'date', 'shift_type', 'department', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
