<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\Platform\OrganizationDeletion;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ⚠️ ADMIN 06 — a Stripe Checkout page the app opened (a subscription, or a guest's card payment), from creation
 * until it completes or expires. It exists so a permanent deletion can SEE an open payment page (KNOWN-LIMITS L19):
 * a page paid after its organisation was deleted leaves Stripe charging a customer nobody here can see.
 *
 * @property string $stripe_session_id
 * @property string $mode
 * @property bool|null $livemode
 * @property Carbon|null $expires_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expired_at
 */
class CheckoutSession extends Model
{
    use BelongsToOrganization, HasUlids;

    /** 📖 Stripe: a Checkout Session expires 30 minutes to 24 hours after creation — 24 hours by default. */
    public const LONGEST_LIFETIME_HOURS = 24;

    protected $table = 'stripe_checkout_sessions';

    protected $fillable = ['organization_id', 'stripe_session_id', 'mode', 'livemode', 'expires_at', 'completed_at', 'expired_at'];

    protected function casts(): array
    {
        return [
            'livemode' => 'boolean',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /**
     * Still open: neither completed nor expired, and inside its lifetime. A session whose expiry Stripe did not
     * report is treated as open for the LONGEST lifetime Stripe allows — never shorter.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        self::whereOpen($query);

        return $query;
    }

    /**
     * ⚠️ THE ONE DEFINITION of "open", for the model scope AND a raw table read — a permanent deletion reads the table
     * with no tenant context ({@see OrganizationDeletion::refusal()}), so it cannot use the scope.
     */
    public static function whereOpen(QueryBuilder $query): QueryBuilder
    {
        return $query->whereNull('completed_at')->whereNull('expired_at')
            ->where(fn (QueryBuilder $q) => $q->where('expires_at', '>', now())
                ->orWhere(fn (QueryBuilder $n) => $n->whereNull('expires_at')->where('created_at', '>', now()->subHours(self::LONGEST_LIFETIME_HOURS))));
    }

    /**
     * ⚠️ REVIEW 2 (D-R2-2) — a LIVE page with no outcome reported: neither completed nor expired, whatever its expiry says.
     * Past its expiry it can no longer be paid, but it may already have been — the report still in Stripe's retries — so
     * a permanent deletion waits for Stripe's word on a live page. A test-mode page moves no real money; its expiry is enough.
     */
    public static function whereUnreported(QueryBuilder $query): QueryBuilder
    {
        return $query->where('livemode', true)->whereNull('completed_at')->whereNull('expired_at');
    }
}
