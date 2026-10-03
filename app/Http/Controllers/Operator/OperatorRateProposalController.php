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
        ], [
            // ⚠️ 2026-10-03 — the owner's page reopens its dialog with these beside the fields; before, a refused proposal
            //    closed the form and the page said nothing.
            'rate.required' => 'Enter the nightly rate you propose.',
            'weekend_uplift.numeric' => 'The weekend uplift is a percentage between 0 and 200.',
            'weekend_uplift.min' => 'The weekend uplift is a percentage between 0 and 200.',
            'weekend_uplift.max' => 'The weekend uplift is a percentage between 0 and 200.',
            'min_stay.integer' => 'The minimum stay is a whole number of nights, 1 to 365.',
            'min_stay.min' => 'The minimum stay is a whole number of nights, 1 to 365.',
            'min_stay.max' => 'The minimum stay is a whole number of nights, 1 to 365.',
            'from_date.required' => 'Choose the first night.',
            'to_date.required' => 'Choose the last night.',
            'to_date.after_or_equal' => 'The last night has to be on or after the first.',
            'note.max' => 'A note to the owner can be up to 500 characters.',
        ]);

        // The owner must belong to the operator's tenant (Owner is BelongsToOrganization → org-scoped).
        $owner = Owner::query()->findOrFail($data['owner_id']);

        $priceMinor = MoneyInput::toMinor((string) $data['rate'], self::MAX_PRICE_MINOR);
        if ($priceMinor === null) {
            return back()->withInput()->withErrors(['rate' => 'Enter a valid nightly rate, e.g. 175 or 175.50.']);
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
            return redirect()->to($back)->withInput()->with('rate_error', $e->getMessage());
        }

        return redirect()->to($back)->with('rate_notice', 'Rate proposal sent to the owner for approval.');
    }

    public function withdraw(Request $request, string $proposal): RedirectResponse
    {
        // Tenant-scoped by BelongsToOrganization; a cross-org id 404s. A pending OR countered one may be withdrawn.
        $model = RateProposal::query()->findOrFail($proposal);
        $back = route('app.owners.show', $model->owner_id);

        try {
            $this->proposals->withdraw($model, $request->user());
        } catch (\RuntimeException) {
            return redirect()->to($back)->with('rate_notice', 'That proposal was already decided.');
        }

        return redirect()->to($back)->with('rate_notice', 'Proposal withdrawn.');
    }

    /**
     * OPERATOR ACCEPTS the owner's counter (#81) → the owner's countered rate is applied via the certified path
     * (the rate really changes) → `approved`. Tenant-scoped; gated `rates.manage` at the route. Only a
     * `countered` proposal can be accepted; anything else is a graceful no-op flash.
     */
    public function acceptCounter(Request $request, string $proposal): RedirectResponse
    {
        $model = RateProposal::query()->findOrFail($proposal);
        $back = route('app.owners.show', $model->owner_id);

        try {
            $this->proposals->acceptCounter($model, $request->user());
        } catch (\RuntimeException) {
            return redirect()->to($back)->with('rate_notice', 'That counter is no longer open.');
        }

        return redirect()->to($back)->with('rate_notice', 'Counter accepted — the owner’s rate is now live.');
    }
}
