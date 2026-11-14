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
 * ⚠️⚠️ THE MONEY RECORDS ARE DECIDED — BY THE FOUNDER, ON 2026-11-14, WITHOUT A LAWYER (his words: "I don't want to wait for
 * lawyer for anything. So go for the best options."). This file was EMPTY on purpose until then, because the period is a legal
 * one (`OPEN.md` F6). The decision is the longest period the accounting rules of the countries we serve commonly require — about 6
 * to 10 years in the EU — so that nothing is ever deleted too early. ⚠️ It is NOT legal advice and the entries say so in their
 * own reason; counsel can change the number (ONE constant, {@see FINANCIAL_RECORDS_YEARS}) when one is engaged.
 *
 * What is NOT decided here, and stays "no rule yet": every store that is not a money record. In particular `audit_events` and
 * `domain_events` are NOT entered — they hold staff IP addresses and user agents, and a long period for those is a different
 * question from the money records' one.
 *
 * To record another decision: add `'<table>' => ['reason' => '…', 'period' => '…', 'decided_on' => 'YYYY-MM-DD']` below, say
 * WHO decided and on what basis in the reason, and commit it with the source of the answer in the message. {@see RetentionCoverage}
 * reads it.
 */
final class RetentionDecisions
{
    /**
     * How long money records are kept, in YEARS — ONE number. It is read by every entry below AND stated, in words, on the "Delete my
     * account" card; a test holds the card's English to this constant, so the two cannot drift (changing it means re-translating the card).
     */
    public const FINANCIAL_RECORDS_YEARS = 10;

    /** The one decision every money table shares — the same facts, so the same words. */
    private const MONEY = [
        'reason' => 'Money record (invoice, payment, ledger, statement or payout): accounting and tax rules in the countries we serve require such records to be kept for several years — typically 6 to 10 in the EU. DECIDED BY THE FOUNDER on 2026-11-14 WITHOUT legal advice: the longest of those periods, so that nothing is deleted too early. Counsel may change the number when one is engaged.',
        'period' => self::FINANCIAL_RECORDS_YEARS.' years',
        'decided_on' => '2026-11-14',
    ];

    /**
     * table => ['reason' => why it is kept, 'period' => for how long, 'decided_on' => the date it was decided].
     *
     * @var array<string, array{reason: string, period: string, decided_on: string}>
     */
    public const DECISIONS = [
        'folios' => self::MONEY,
        'folio_items' => self::MONEY,
        'payments' => self::MONEY,
        'payment_authorizations' => self::MONEY,
        'payment_disputes' => self::MONEY,
        'guest_credit_ledger' => self::MONEY,
        'owner_ledger' => self::MONEY,
        'owner_statements' => self::MONEY,
        'owner_statement_lines' => self::MONEY,
        'owner_expenses' => self::MONEY,
        'payouts' => self::MONEY,
        'payout_runs' => self::MONEY,
        'invoices' => self::MONEY,
        'subscriptions' => self::MONEY,
    ];

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
