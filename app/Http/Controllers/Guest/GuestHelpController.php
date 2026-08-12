<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Property;
use App\Support\Guest\PropertySupport;
use App\Support\Help\HelpKnowledgeBase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Guest Help centre (2026-08-12) — a searchable FAQ over the ONE curated {@see HelpKnowledgeBase}, the same
 * engine the owner Help page and the grounded assistant read. The view renders the engine's entries; it holds
 * no help content of its own, so the FAQ cannot drift from the source the way two copies of a claim always do.
 *
 * ⚠️ WHY THIS EXISTS AT ALL. A help CENTRE was refused on 2026-08-07 on the grounds that "its content would
 * have to be invented". That premise was true then and is false now: `HelpKnowledgeBase` is curated, real,
 * screen-linked content, and the guest set was written by reading the code behind each answer. The refusal is
 * annotated as REVERSED in DECISIONS.md by founder ruling.
 *
 * ⚠️ THE SUPPORT BLOCK (2026-08-12) — WHAT CHANGED, AND WHAT DID NOT. This page shipped as a PURE FAQ because
 * the product had no support data to show and a help page is the worst place to promise support that will not
 * answer (the `DO-EMAIL-9` class). That ruling has not been relaxed; the backend it was waiting for now exists.
 * Support details are per-property columns an operator populates deliberately, and this page renders ONLY what
 * is populated — see {@see PropertySupport}. A property with nothing set contributes nothing: the guest sees
 * exactly the page they saw before, whose one contact is the real guest→property relay.
 *
 * ⚠️ WHICH PROPERTY. A support number is a promise made by a specific front desk, so a block is drawn PER
 * PROPERTY and carries its name — never merged, never "the" support number. The properties are the ones behind
 * this guest's OWN bookings, resolved through {@see ResolvesGuest} the same way every other guest surface
 * resolves anything: a guest cannot see the support details of a property they have never booked, and cancelled
 * stays are excluded because the desk that would answer is no longer looking after anything of theirs.
 *
 * AUTH: behind `auth:guest`, deliberately. The page renders in `<x-guest.shell>` and every answer routes to a
 * guest-only screen, so a public version would be a different page with different links, not this one served
 * logged-out. The FAQ itself holds no guest-specific data; the support block does read the guest's own bookings,
 * and that read goes through the certified isolation relation rather than any request-supplied id.
 *
 * READ-ONLY: search and category are GET query parameters (§N.4k — safe by construction; no write exists on
 * this page), which also keeps the guest realm's no-Alpine convention.
 */
class GuestHelpController extends Controller
{
    use ResolvesGuest;

    public function show(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $all = HelpKnowledgeBase::guestArticles();

        /** @var list<string> $categories */
        $categories = array_values(array_unique(array_column($all, 'category')));

        // Filter server-side so the page works without JavaScript, like the rest of the guest realm.
        $matches = array_values(array_filter($all, static function (array $a) use ($query, $category): bool {
            if ($category !== '' && $a['category'] !== $category) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            // The same haystack the owner page searches: question + answer + the curated synonyms.
            $haystack = mb_strtolower($a['question'].' '.$a['answer'].' '.$a['keywords']);

            return str_contains($haystack, mb_strtolower($query));
        }));

        // Group for rendering; a filtered-to-nothing result yields an empty group set and the honest empty state.
        $grouped = [];
        foreach ($matches as $article) {
            $grouped[$article['category']][] = $article;
        }

        return view('guest.help', [
            'articles' => $matches,
            'grouped' => $grouped,
            'categories' => $categories,
            'query' => $query,
            'activeCategory' => $category,
            'totalCount' => count($all),
            'support' => $this->supportBlocks(),
        ]);
    }

    /**
     * The support details of the properties behind this guest's own bookings — populated fields only.
     *
     * ⚠️ HONEST ABSENCE, TWICE OVER. A property with no support columns set is dropped by {@see PropertySupport}
     * and never reaches the view, and a guest with no bookings gets an empty list. Both cases render the page
     * exactly as it was before this backend existed: the relay, and nothing claimed about it.
     *
     * Ordering is the most recent stay first, so a guest mid-trip reads the desk currently looking after them at
     * the top. Cancelled and no-show stays are excluded — that desk is no longer theirs to call.
     *
     * @return list<array{property: string, phone: string|null, reply_norm: string|null, hours: string|null}>
     */
    private function supportBlocks(): array
    {
        // Through the guest's OWN relation (lead_guest_id), never a request id — the realm's isolation rule.
        $propertyIds = $this->guest()->reservations()
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderByDesc('check_in')
            ->pluck('property_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->unique()
            ->values();

        if ($propertyIds->isEmpty()) {
            return [];
        }

        /** @var array<string, Property> $properties */
        $properties = Property::query()
            ->whereIn('id', $propertyIds->all())
            ->get()
            ->keyBy(static fn (Property $p): string => (string) $p->getKey())
            ->all();

        $blocks = [];
        foreach ($propertyIds as $id) {
            $property = $properties[$id] ?? null;
            if ($property === null) {
                continue;
            }

            $block = PropertySupport::for($property);
            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }
}
