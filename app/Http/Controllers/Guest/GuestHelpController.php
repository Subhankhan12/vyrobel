<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
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
 * ⚠️ PURE FAQ, BY RULING. There is no support block here — no phone, no hours, no reply time, no on-call line.
 * None of that exists in the product, and a help page is the worst place to promise support that will not
 * answer (the class of false claim `DO-EMAIL-9` records). The ONE contact offered is the real guest→property
 * relay the guest already has.
 *
 * AUTH: behind `auth:guest`, deliberately. The page renders in `<x-guest.shell>` and every answer routes to a
 * guest-only screen, so a public version would be a different page with different links, not this one served
 * logged-out. Nothing here is guest-specific data — no reservation, no PII — so there is nothing to scope: the
 * guard is about the shell and the destinations, not about protecting the content.
 *
 * READ-ONLY: search and category are GET query parameters (§N.4k — safe by construction; no write exists on
 * this page), which also keeps the guest realm's no-Alpine convention.
 */
class GuestHelpController extends Controller
{
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
        ]);
    }
}
