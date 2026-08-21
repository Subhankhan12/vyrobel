<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Marketing\MarketingContent;
use Illuminate\Contracts\View\View;

/**
 * THE PUBLIC MARKETING SITE (front-website Gate 2).
 *
 * ⚠️ STATELESS AND READ-ONLY BY CONSTRUCTION. Every action returns a view and nothing else — no model is
 * loaded, no tenant context is resolved, no session is touched, no query runs. The marketing site is the
 * one surface with no data of its own: its only content is the guarded ledger, read inside the components
 * through {@see MarketingContent} (Gate 0's single source, §N.8f).
 *
 * ⚠️ NO MIDDLEWARE BEYOND `web`. These pages are public and cacheable; they must never resolve a tenant,
 * because doing so would give an anonymous request a tenant context — the opposite of the storefront's
 * deliberate `ResolvePublicOrganization` design.
 */
class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home');
    }

    public function product(): View
    {
        return view('marketing.product');
    }

    public function features(): View
    {
        return view('marketing.features');
    }

    public function modules(): View
    {
        return view('marketing.modules');
    }

    public function howItWorks(): View
    {
        return view('marketing.how-it-works');
    }

    public function pricing(): View
    {
        return view('marketing.pricing');
    }

    /**
     * ⚠️ THE SANDBOX — read-only. It resolves the real demo property and renders the real confirmation
     * mail; it creates nothing. See App\Support\Marketing\TryBookingSandbox for why nothing is written.
     */
    public function tryBooking(): View
    {
        return view('marketing.try-booking');
    }

    public function switching(): View
    {
        return view('marketing.switching');
    }

    public function forHotels(): View
    {
        return view('marketing.for-hotels');
    }

    public function forApartments(): View
    {
        return view('marketing.for-apartments');
    }

    public function forVillas(): View
    {
        return view('marketing.for-villas');
    }

    /**
     * ⚠️ THE ILLUSTRATIVE SCENARIOS (ruling A) — not case studies. Each page carries a prominent label in
     * its own voice saying so, and MarketingScenarioTest holds all six honesty conditions in place.
     */
    public function exampleScenarios(): View
    {
        return view('marketing.case-studies');
    }

    public function scenarioVillas(): View
    {
        return view('marketing.story-cyclades');
    }

    public function scenarioHotel(): View
    {
        return view('marketing.story-lisbon');
    }

    public function scenarioApartments(): View
    {
        return view('marketing.story-rotterdam');
    }
}
