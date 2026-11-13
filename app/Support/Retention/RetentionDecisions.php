<?php

declare(strict_types=1);

namespace App\Support\Retention;

use App\Support\Privacy\RetentionCoverage;

/**
 * DEC-4 (his ruling, 2026-11-13: "C") — THE REGISTRY OF "KEPT ON PURPOSE".
 *
 * The retention card has three buckets, and the middle one needs somewhere to read its facts from: a store that no timer
 * prunes counts as "kept on purpose" ONLY when an entry here gives, in writing, WHY it is kept, FOR HOW LONG, and WHEN that
 * was decided. All three are required — a half-filled entry counts for nothing ({@see isComplete()}) — so the count can
 * only rise when someone has actually decided.
 *
 * ⚠️ THIS IS EMPTY ON PURPOSE. The periods (how long invoices and payment records must be kept, how long an audit trail is
 * evidence) are the lawyer's to state (`OPEN.md` F6); a session that filled one in would be inventing a legal period and the
 * card would be reporting a decision nobody made. `audit_events` and `domain_events` are the obvious first entries — they are
 * already kept deliberately ({@see RetentionPolicy::NEVER_PRUNED}, with the reason written there) but have no stated period.
 *
 * To record a decision: add `'<table>' => ['reason' => '…', 'period' => '…', 'decided_on' => 'YYYY-MM-DD']` below and commit
 * it with the source of the answer in the message. {@see RetentionCoverage} reads it.
 */
final class RetentionDecisions
{
    /**
     * table => ['reason' => why it is kept, 'period' => for how long, 'decided_on' => the date it was decided].
     *
     * @var array<string, array{reason: string, period: string, decided_on: string}>
     */
    public const DECISIONS = [];

    /** @return array<string, array{reason: string, period: string, decided_on: string}> */
    public static function all(): array
    {
        return self::DECISIONS;
    }

    /** A decision counts only with a reason, a period AND a date — never a half-filled one. */
    public static function isComplete(?array $decision): bool
    {
        if ($decision === null) {
            return false;
        }

        foreach (['reason', 'period', 'decided_on'] as $field) {
            if (trim((string) ($decision[$field] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}
