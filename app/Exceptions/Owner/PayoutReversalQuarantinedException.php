<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use App\Services\OwnerPayoutService;
use RuntimeException;

/**
 * R-REVERSE (founder, 2026-09-19, after FIX 37's stop) — {@see OwnerPayoutService::reverse()} IS QUARANTINED.
 *
 * ⚠️⚠️ REACHING THIS EXCEPTION MEANS SOMEBODY WIRED A PATH TO A METHOD THAT IS DELIBERATELY UNREACHABLE. The
 * method is not broken-and-unnoticed; it is known-broken and deliberately closed, and the two defects below
 * must be fixed AT THE CAUSE before it is opened.
 *
 * ⚠️⚠️ WHY A THROW IS CORRECT HERE, AND WHY THE NO-THROW RULE DOES NOT APPLY. This codebase forbids throwing
 * inside an EXTERNAL SYNC — FIX 14 ruled that, because a throw on a retrying webhook becomes
 * `webhook_events` FAILED → retry → dead letter, an outage nobody can diagnose from the interface. That rule
 * is about a path a REMOTE SYSTEM drives. This is a service method with 🔬 ZERO callers and ZERO routes, so
 * reaching it is a PROGRAMMER ERROR, and a programmer error must be loud. ⚠️ **Do not "helpfully" soften this
 * to a log, a null return or a no-op** — a silent refusal here is how the quarantine gets removed by accident.
 */
class PayoutReversalQuarantinedException extends RuntimeException
{
    public static function make(string $payoutId): self
    {
        return new self(
            'Payout reversal is QUARANTINED and must not run (payout '.$payoutId.'). '
            ."\n"
            .'⚠️ F-53 — reverse() is an UNGUARDED APPEND: it never changes the source payout\'s status, so its '
            .'own `status !== \'paid\'` check cannot refuse a second press, and a reversed row keys NULL on the '
            .'`payouts_one_live_per_statement` unique index, where NULLs never collide. Calling this twice '
            .'would append TWO reversals for ONE payout — an operator double-click would reverse an owner\'s '
            .'payout twice.'
            ."\n"
            .'⚠️ F-54 — it would also STRAND the statement: the statement returns to `finalized` while the '
            .'source payout stays `paid` and keeps the unique live-statement key, so createFromStatement() '
            .'hands back that same paid row and the statement can NEVER be paid again. The owner would be owed '
            .'money the system believes it has already sent.'
            ."\n"
            .'⚠️ THE PRECONDITION FOR LIFTING THIS: the source payout must reach a TERMINAL status on reversal. '
            .'That single change both releases the unique live key (so a new payout can be created) and makes '
            .'the existing status check able to refuse a second press. Until then this stays closed — '
            .'see docs/AUDIT-SUMMARY.md §4 (R-REVERSE), F-53 and F-54.'
        );
    }
}
