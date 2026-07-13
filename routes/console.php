<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tier 1 sweep · Step 3 — daily cleanup of genuinely expired, never-accepted invites + their
// never-activated artifacts (conservative + idempotent; see OwnerInviteService::pruneExpired()).
Schedule::command('invites:prune-expired')->daily();
