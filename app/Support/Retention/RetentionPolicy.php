<?php

declare(strict_types=1);

namespace App\Support\Retention;

use App\Console\Commands\PruneRetainedRecords;

/**
 * ⚠️ THE ONE RETENTION DECLARATION (audit 2026-08-29 · M-6). Nine tables grew forever while the scheduler ran
 * eight commands and two heartbeats, not one of which was a prune.
 *
 * ⚠️ ONE DECLARATION, NOT SEVEN COMMANDS (§N.8f). Every entry below is the SAME operation — "delete rows older
 * than N days from a log-like store" — so seven copies of it would be seven places to drift. The three EXISTING
 * retention commands stay as they are: each is a distinct DOMAIN operation (guest erasure, ID-document purge,
 * login-activity prune) with its own service and its own rules, not a row-age sweep.
 * ⚠️ {@see PruneRetainedRecords} isolates each store, so one failing store cannot stop
 * the rest — the independence a per-command split would have bought, without the drift a split would cost.
 */
final class RetentionPolicy
{
    /**
     * ⚠️⚠️ NEVER PRUNED — AND THIS IS A DECISION, NOT AN OVERSIGHT. READ THIS BEFORE "TIDYING UP".
     *
     * `audit_events` and `domain_events` are EVIDENCE. They are how a disputed owner figure is reconstructed:
     * who changed whose money, when, and from what to what. An owner dispute surfaces LATE — often after the
     * statement is paid and the period is sealed — so a retention window sized for "logs" would delete exactly
     * the rows a dispute needs. Deleting them does not free a problem; it removes the reconstruction path for a
     * ledger figure.
     *
     * ⚠️ THEIR SIZE PROBLEM IS AN INDEX, NOT DELETION. Both now carry a `created_at` index
     * (2026_09_28_000000_add_retention_indexes_to_log_tables) so paging them by recency stays a range scan
     * instead of a filesort as they grow. That is the whole fix, and it is the only one available here.
     *
     * ⚠️ GUARDED BY A TEST, NOT BY THIS COMMENT — see RetentionPolicyTest: adding either table to
     * {@see WINDOWS} fails the build. A refusal with no reason gets re-opened; a refusal with no test gets
     * re-opened silently.
     *
     * ⚠️ OPEN, REPORTED, NOT DECIDED HERE (audit M-6): `audit_events` also stores `ip` and `user_agent` on
     * every row. Those are personal data, and this project already prunes the SAME data class at 90 days in
     * `login_activity` ("security-monitoring purpose, limited retention"). Keeping the audit ROW forever is
     * sound; keeping those two COLUMNS forever is a different question and belongs to the founder/a lawyer.
     * Nulling them past a window would preserve the evidence and drop the personal data — NOT done here.
     *
     * @var list<string>
     */
    public const NEVER_PRUNED = [
        'audit_events',
        'domain_events',
    ];

    /**
     * table => [days, the timestamp column keyed on, what it holds]
     *
     * ⚠️ THE WINDOWS ARE FIXED IN CODE AND CANNOT VARY BY JURISDICTION — an already-recorded product limit
     * (`docs/DATA-RESIDENCY-INVENTORY.md`), restated here because this is where someone would look for a knob.
     *
     * @var array<string, array{days: int, column: string, holds: string}>
     */
    public const WINDOWS = [
        // ⚠️⚠️ A PRIVACY WINDOW, NOT A DEBUGGING ONE. `failed_jobs` is NOT an error log: a failed mailable is
        // serialised whole, and OwnerReportMail carries `public readonly string $pdfBytes` — an owner's entire
        // statement PDF — plus the recipient's address. Seven days is how long a debugger plausibly needs; the
        // payload is why it is not thirty. (Removing the PDF from the payload is audit M-7, a separate pass.)
        'failed_jobs' => ['days' => 7, 'column' => 'failed_at', 'holds' => 'serialised job payloads — may include an owner statement PDF and a recipient email address'],

        // In-app notification feed. Holds a subject line and a link, addressed to one notifiable.
        'notifications' => ['days' => 90, 'column' => 'created_at', 'holds' => 'in-app notification rows (recipient id, title, link)'],

        // ⚠️ 30 days vastly exceeds the 120-MINUTE configured session lifetime (config/session.php), so this
        // can never prune a live session and log someone out. Verified, not assumed.
        'sessions' => ['days' => 30, 'column' => 'last_activity', 'holds' => 'session payloads, user id, IP address and user agent'],
        'mail_send_log' => ['days' => 365, 'column' => 'created_at', 'holds' => 'which email TYPE was accepted or suppressed, and when — no address, subject or body'],

        // ⚠️⚠️ THIS TABLE DEDUPLICATES REPLAYS — UNIQUE(source, external_event_id). Pruning a key too early
        // lets a replayed event be processed a SECOND time, and on the Connect plane that is a money event.
        // Stripe documents retries for up to 3 days; 90 gives a 30x margin. Channex is not live
        // (FakeChannexClient is bound). ⚠️ If a provider with a longer retry window is ever added, RAISE THIS.
        'webhook_events' => ['days' => 90, 'column' => 'created_at', 'holds' => 'raw provider webhook payloads (may contain customer identifiers)'],

        // Export download audit — who downloaded which export, when.
        // ⚠️ A NIGHTLY REPORT IS NOT EVIDENCE. The audit and domain-event tables are never pruned because
        //    they record what happened; a reconciliation run records what was TRUE at 04:00, which stops
        //    being interesting long before it stops taking space. Declared here rather than given its own
        //    pruning command, so there is one place windows live (§N.8f).
        'reconciliation_runs' => ['days' => 90, 'column' => 'created_at', 'holds' => 'reconciliation findings (subscription, owner and payment ids — no payloads)'],
        'export_downloads' => ['days' => 90, 'column' => 'created_at', 'holds' => 'export download records (actor id, export type)'],

        // AI assistant usage accounting.
        'llm_usage' => ['days' => 90, 'column' => 'created_at', 'holds' => 'AI request accounting (token counts, model, no message bodies)'],

        // ⚠️ `login_activity` is DELIBERATELY ABSENT. It is already pruned at 90 days by the existing
        // `platform:prune-activity` command (LoginActivity::RETENTION_DAYS), on an indexed `occurred_at`.
        // Adding it here would be a SECOND definition of one window — the drift this class exists to prevent.
    ];

    /** Windows keyed by table, with the never-pruned tables structurally excluded. */
    public static function windows(): array
    {
        return array_diff_key(self::WINDOWS, array_flip(self::NEVER_PRUNED));
    }
}
