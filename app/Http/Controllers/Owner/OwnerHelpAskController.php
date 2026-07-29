<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\AskVyrobelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Ask Vyrobel" — the grounded support-assistant question endpoint (Wave D · support AI · Phase 3).
 *
 * A controller POST (a plain <form>), NOT a Livewire action — every owner write in this app is a controller
 * route, so /livewire/update carries no mutation to bypass a gate (see OwnerCapabilityMutationBoundaryTest).
 * This is a READ over generic help content: it touches NO owner data (#28 trivial); the only per-owner state is
 * the cost counter. It resolves, validates, delegates to {@see AskVyrobelService}, and redirects back (PRG) with
 * the result — the honesty/grounding/guardrails all live in the service, never here.
 */
class OwnerHelpAskController extends Controller
{
    public function ask(Request $request, AskVyrobelService $assistant): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        // The asker's id keys the per-owner/day cost cap. Resolved from auth, never the request body.
        $ownerId = $request->user()?->id;

        $result = $assistant->ask($ownerId !== null ? (string) $ownerId : null, $validated['question']);

        return redirect()
            ->route('owner.help')
            // Flash PRIMITIVE data, never the object — some session drivers (database) round-trip an object
            // back as a plain array, which would fatal in the view. toArray() is the portable shape.
            ->with('ask_result', $result->toArray())
            ->with('ask_question', trim($validated['question']));
    }
}
