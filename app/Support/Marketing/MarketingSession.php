<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use App\Http\Controllers\CookieChoiceController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * ⚠️⚠️ VY-29 (audit of 2026-11-24, seen live) — A MARKETING PAGE STARTS NO SESSION.
 *
 * Every anonymous visit to a marketing page started a session (a `sessions` row on production, which runs the database driver) and set
 * two cookies (the session and XSRF-TOKEN) — for pages that read nothing from a session. They now skip the three web middleware that
 * make a session: no row, no Set-Cookie. ⚠️ Contact is the exception (its form needs CSRF and its "thank you" is a flash), and the
 * cookie-consent POST, which carries no session, checks the request's own Origin instead ({@see CookieChoiceController}).
 *
 * ⚠️ What this does NOT do: make the pages publicly cacheable at the CDN. That stays a deployment decision — HtmlIsNeverPubliclyCacheableTest
 *    explains why (no HTML/asset skew, the 180-second prune floor) — and is the founder's to take; a session-free page is its precondition.
 */
final class MarketingSession
{
    /** @var list<class-string> the web middleware a marketing page goes without (Laravel 13 names its CSRF middleware PreventRequestForgery) */
    public const SESSIONLESS = [StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class];
}
