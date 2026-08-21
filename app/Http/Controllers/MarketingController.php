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
}
