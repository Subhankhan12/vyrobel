<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\RateProposal;
use App\Services\RateProposalService;
use App\Support\Money\MoneyInput;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * OPERATOR rate-proposal writes (Wave C · GATE 1). The operator PROPOSES a rate change to an owner (the owner
 * then approves/declines it in their portal) and may WITHDRAW a pending one. Tenant-scoped (the owner + room type
 * belong to the operator's org); gated `rates.manage` at the route (invariant #30 — NOT Front Desk). The proposed
 * € is parsed to integer minor units HERE ({@see MoneyInput}, string arithmetic) before any write (#17). The
 * exclusive-ownership boundary is enforced in {@see RateProposalService::propose} — a shared room type is refused.
 */
class OperatorRateProposalController extends Controller
{
    /** A rate cannot exceed €1,000,000.00 — a sanity bound on a hand-typed field. */
    private const MAX_PRICE_MINOR = 100_000_000;

    public function __construct(private readonly RateProposalService $proposals) {}

    public function store(Request $request, RateProposalService $proposals): RedirectResponse
    {
        $data = $request->validate([
            'owner_id' => ['required', 'string'],
            'room_type_id' => ['required', 'string'],
            'rate' => ['required', 'string', 'max:20'],
            'weekend_uplift' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'min_stay' => ['nullable', 'integer', 'min:1', 'max:365'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // The owner must belong to the operator's tenant (Owner is BelongsToOrganization → org-scoped).
        $owner = Owner::query()->findOrFail($data['owner_id']);

        $priceMinor = MoneyInput::toMinor((string) $data['rate'], self::MAX_PRICE_MINOR);
        if ($priceMinor === null) {
            return back()->with('rate_error', 'Enter a valid nightly rate, e.g. 175 or 175.50.');
        }

        $back = route('app.owners.show', $owner->getKey());

        try {
            $proposals->propose(
                $request->user(),
                (string) $owner->getKey(),
                (string) $data['room_type_id'],
                $priceMinor,
                isset($data['weekend_uplift']) ? round((float) $data['weekend_uplift'], 2) : null,
                isset($data['min_stay']) ? (int) $data['min_stay'] : null,
                CarbonImmutable::parse($data['from_date'])->toDateString(),
                CarbonImmutable::parse($data['to_date'])->toDateString(),
                $data['note'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->to($back)->with('rate_error', $e->getMessage());
        }

        return redirect()->to($back)->with('rate_notice', 'Rate proposal sent to the owner for approval.');
    }

    public function withdraw(Request $request, string $proposal): RedirectResponse
    {
        // Tenant-scoped by BelongsToOrganization; a cross-org id 404s. Only a pending one may be withdrawn.
        $model = RateProposal::query()->findOrFail($proposal);
        $back = route('app.owners.show', $model->owner_id);

        try {
            $this->proposals->withdraw($model, $request->user());
        } catch (\RuntimeException) {
            return redirect()->to($back)->with('rate_notice', 'That proposal was already decided.');
        }

        return redirect()->to($back)->with('rate_notice', 'Proposal withdrawn.');
    }
}
