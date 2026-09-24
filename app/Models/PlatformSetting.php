<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Platform\PlatformSwitches;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 1 — one platform runtime setting (a kill switch), keyed by name. Written only by
 * {@see PlatformSwitches}. ⚠️ A MISSING ROW MEANS "ON" — see the migration.
 *
 * @property string $key
 * @property mixed $value
 * @property string|null $updated_by
 * @property Carbon|null $updated_at
 */
class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
