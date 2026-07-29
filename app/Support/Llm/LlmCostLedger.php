<?php

declare(strict_types=1);

namespace App\Support\Llm;

use App\Models\LlmUsage;
use Illuminate\Support\Facades\DB;

/**
 * The Ask-Vyrobel cost ledger (Wave D · support AI · Phase 2) — caps and records per-owner/day LLM spend over
 * the {@see LlmUsage} table. {@see capReached()} is checked BEFORE a call (→ escalate when over); {@see record()}
 * is called AFTER a successful call. Atomic increment (no read-modify-write race). Holds no owner PII (#28).
 */
final class LlmCostLedger
{
    public function __construct(private readonly int $dailyTokenCap) {}

    public static function fromConfig(): self
    {
        return new self((int) config('services.llm.daily_token_cap', 20000));
    }

    /** Whether this owner has reached today's token cap (→ the caller escalates instead of calling the LLM). */
    public function capReached(?string $ownerId): bool
    {
        if ($this->dailyTokenCap <= 0) {
            return false;
        }

        $used = LlmUsage::query()
            ->where('owner_id', $ownerId)
            ->whereDate('usage_date', today())
            ->value('token_count');

        return (int) $used >= $this->dailyTokenCap;
    }

    /** Record one call's real token spend against this owner's day counter (atomic upsert + increment). */
    public function record(?string $ownerId, int $tokens): void
    {
        $row = LlmUsage::query()->firstOrCreate(
            ['owner_id' => $ownerId, 'usage_date' => today()],
            ['request_count' => 0, 'token_count' => 0],
        );

        $row->newQuery()->whereKey($row->getKey())->update([
            'request_count' => DB::raw('request_count + 1'),
            'token_count' => DB::raw('token_count + '.max(0, $tokens)),
            'updated_at' => now(),
        ]);
    }
}
