<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Services\OwnerPayoutService;
use App\Services\PayoutRunService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An owner payout (Phase 3 · Module 1 Step 5) — the disbursement of a finalized statement's net
 * payable. Append-only lifecycle: a failed/reversed payout is superseded by a NEW row
 * (`supersedes_id`), never edited away. `amount` is Money in the owner's payout currency.
 *
 * `status` spans the full lifecycle enum (`pending`/`processing`/`paid`/`failed`/`reversed`/`held` —
 * the latter two added by raw ALTERs the static analyser can't read from the schema), so it is typed
 * `string` here rather than the create-migration's narrower union.
 *
 * @property string $status
 * @property string|null $payout_run_id
 * @property Carbon|null $held_at
 * @property bool|null $livemode ADMIN 06 — Stripe's own, from the transfer; null = the manual rail
 */
class Payout extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    /**
     * The statuses in which a payout still CLAIMS its statement's money — queued, moving, or already sent.
     * ⚠️ THE ONE DEFINITION (§N.8f): a statement may have at most one payout in this set, and both writers
     * enforce it against THIS list — {@see OwnerPayoutService::createFromStatement()} so an
     * engine re-run never makes a second payout, and {@see OwnerPayoutService::retry()} so a
     * retry never does either. A second live row for one statement is a DOUBLE PAYMENT, because
     * {@see PayoutRunService::processOwner()} disburses every pending payout an owner has.
     * `failed` and `reversed` are deliberately absent — they claim nothing, which is what makes them
     * retryable.
     *
     * @var list<string>
     */
    public const LIVE_STATUSES = ['pending', 'processing', 'held', 'paid'];

    protected $fillable = [
        'organization_id',
        'owner_id',
        'owner_statement_id',
        'amount_minor',
        'currency',
        'rail',
        'status',
        'external_ref',
        'connect_account_id',
        'livemode',
        'supersedes_id',
        'payout_run_id',
        'initiated_by',
        'failure_reason',
        'notes',
        'paid_at',
        'failed_at',
        'held_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // ⚠️ ADMIN 06 — Stripe's own `livemode`, stored when the record was written (null: not a Stripe record)
            'livemode' => 'boolean',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'held_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<OwnerStatement, $this>
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(OwnerStatement::class, 'owner_statement_id');
    }

    /**
     * The prior payout this one replaces (a retry of a failed payout, or a reversal), if any.
     *
     * @return BelongsTo<Payout, $this>
     */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(Payout::class, 'supersedes_id');
    }

    /**
     * The automated engine run that created this payout (null for a manual/operator-triggered payout).
     *
     * @return BelongsTo<PayoutRun, $this>
     */
    public function payoutRun(): BelongsTo
    {
        return $this->belongsTo(PayoutRun::class, 'payout_run_id');
    }
}
