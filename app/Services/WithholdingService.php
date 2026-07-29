<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\TaxRate;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * The WITHHOLDING engine (Wave D · tax engine · Phase 3) — ⚠️ REAL money. Tax the agency withholds from an owner's
 * payout on their behalf, at a FOUNDER-CONFIGURED rate for the owner's residency ({@see Owner::$tax_country}).
 *
 * Applied at statement FINALIZE (called by {@see OwnerStatementService::finalize} before it claims lines): it posts
 * ONE real `withholding` DEBIT to `owner_ledger`, dated in the period, so the certified statement engine claims it
 * into the withholding bucket → `net_source_minor`/`net_payable_minor` drop by the withheld amount → the payout is
 * genuinely smaller, reconciling to the cent (#17) — exactly the `payment_fee` pattern. Append-only + idempotent
 * (a deterministic key per owner+period), computed on the PRE-withholding net (Σ of the period's other lines), so it
 * is never circular. NO configured rate → nothing posted (a genuine €0) — NEVER a fabricated rate (#39).
 */
class WithholdingService
{
    public function __construct(private TaxRateService $rates) {}

    /**
     * Post this owner's withholding for the period, if a rate is configured. Idempotent + non-mutating.
     */
    public function apply(Owner $owner, string $periodStart, string $periodEnd): void
    {
        $jurisdiction = (string) ($owner->tax_country ?? '');
        if ($jurisdiction === '') {
            return;
        }

        $rate = $this->rates->rateFor($jurisdiction, TaxRate::TYPE_WITHHOLDING, CarbonImmutable::parse($periodEnd));
        if ($rate === null) {
            return; // no withholding configured for this jurisdiction — withhold nothing (genuine €0)
        }

        // The pre-withholding net = the period's claimable lines EXCLUDING any withholding line (non-circular).
        $claimable = OwnerLedgerEntry::query()
            ->where('owner_id', $owner->getKey())
            ->whereNull('statement_id')
            ->whereDate('service_date', '<=', $periodEnd)
            ->where('entry_type', '!=', TaxRate::TYPE_WITHHOLDING)
            ->get();

        $preNet = (int) $claimable->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
        if ($preNet <= 0) {
            return; // nothing owed to withhold on
        }

        // preNet > 0 guarantees a non-empty claimable set, so first() is present.
        $currency = (string) $claimable->first()->currency;
        $withheld = Money::of($preNet, $currency)->percentage($rate->percentFloat())->amountMinor();
        if ($withheld <= 0) {
            return;
        }

        OwnerLedgerEntry::firstOrCreate(
            ['idempotency_key' => 'withholding:'.$owner->getKey().':'.$periodStart],
            [
                'organization_id' => $owner->organization_id,
                'owner_id' => $owner->getKey(),
                'entry_type' => TaxRate::TYPE_WITHHOLDING,
                'direction' => 'debit',                // reduces the owner's net
                'amount_minor' => $withheld,
                'currency' => $currency,
                'description' => 'Withholding tax ('.rtrim(rtrim($rate->percent, '0'), '.').'%)',
                'service_date' => $periodEnd,
                'attribution_version' => 1,
                'note' => 'tax_withholding',
                'posted_at' => now(),
            ],
        );
    }
}
