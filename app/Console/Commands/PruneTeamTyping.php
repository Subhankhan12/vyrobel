<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TeamChatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sweeps the team chat's "is typing…" rows (D-TC-TYPING-SWEEP, 2026-11-11, his go). There is one row per person per conversation, updated in
 * place, so the table never grows past people × conversations — but a person who typed once and never came back left a row for ever.
 * A row is read as "typing now" only for {@see TeamChatService::TYPING_SECONDS} seconds, so anything older than {@see self::KEEP_MINUTES}
 * minutes is dead weight: this deletes it. Idempotent, hourly (routes/console.php).
 *
 * Runs with no tenant (it is the scheduler's own shape), so it reads the table directly — the same sweep for every organisation, deleting
 * only rows that no screen would show.
 */
class PruneTeamTyping extends Command
{
    /** Far longer than a typing row is ever read (a few seconds); short enough that a quiet team leaves no pile. */
    public const KEEP_MINUTES = 60;

    protected $signature = 'team-chat:prune-typing';

    protected $description = 'Delete team-chat "is typing" rows nobody could still see (older than an hour; they are read for a few seconds).';

    public function handle(): int
    {
        $deleted = DB::table('team_typing')->where('typed_at', '<', now()->subMinutes(self::KEEP_MINUTES))->delete();

        $this->info(sprintf('Deleted %d stale typing row(s).', $deleted));

        return self::SUCCESS;
    }
}
