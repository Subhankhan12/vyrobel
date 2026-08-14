{{-- Guest Help centre (2026-08-12) — a searchable FAQ rendered ENTIRELY from the ONE curated
     App\Support\Help\HelpKnowledgeBase (the same engine the owner Help page and the grounded assistant read).

     ⚠️ NO HELP CONTENT LIVES IN THIS FILE. Every question, answer and link comes from the engine. A hardcoded
     answer here would be a second copy of a claim about the product, and the repo's whole mock-value family is
     what happens when a claim has two homes.

     ⚠️ THE SUPPORT BLOCK IS CONDITIONAL, AND ABSENT BY DEFAULT. This page shipped as a pure FAQ because the
     product had no support data; the backend now exists (three nullable per-property columns), so the page
     renders what an operator has ACTUALLY set and nothing else. No phone unless one is stored, no hours unless
     they are stored, no reply line unless a norm is chosen — and no placeholder, blank row or "not provided" for
     any of them. A promise nobody can keep is the DO-EMAIL-9 class, and that ruling has not been relaxed.
     The ONE unconditional contact remains the real relay (Messages) — a link, not a new form.

     Conventions: <x-guest.shell> (its shell fixes are IN it), --vy-* tokens only (never literals — N.14.9),
     and the guest realm's no-Alpine GET convention: search and category are GET params, the accordion is a
     native <details>. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="help" title="Help">
    <style>
        /* ⚠️ N.14.2 — this page reuses the shell's classes and tokens, and every one was checked to exist
           before use. `.vy-sr-only` did NOT: it is defined here rather than assumed, because an undefined
           visually-hidden class does not hide anything, it just renders the label as stray text. */
        .vy-sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
            clip:rect(0 0 0 0); white-space:nowrap; border:0; }
        .vy-hlp-search { display:flex; align-items:center; gap:10px; padding:6px 6px 6px 16px; border-radius:30px;
            background:rgba(255,252,248,.72); border:1px solid var(--vy-glass-border); box-shadow:var(--vy-shadow); }
        .vy-hlp-search input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit;
            font-size:14.5px; color:var(--vy-ink); padding:11px 0; }
        .vy-hlp-search input::placeholder { color:var(--vy-muted); }
        /* :focus-within, so the ring is visible on the composed control rather than only the bare input. */
        .vy-hlp-search:focus-within { outline:2px solid var(--vy-accent-text); outline-offset:2px; }
        .vy-hlp-cats { display:flex; flex-wrap:wrap; gap:8px; margin:14px 0 4px; }
        .vy-hlp-cat { display:inline-flex; align-items:center; padding:8px 13px; border-radius:30px; font-size:12.5px;
            font-weight:500; text-decoration:none; color:var(--vy-muted); background:rgba(255,255,255,.55);
            border:1px solid var(--vy-hairline); }
        .vy-hlp-cat:hover { color:var(--vy-ink); }
        .vy-hlp-cat.is-on { color:var(--vy-accent-text); background:rgba(194,65,12,.10); border-color:rgba(194,65,12,.28); font-weight:600; }
        .vy-hlp-cat:focus-visible { outline:2px solid var(--vy-accent-text); outline-offset:2px; }
        .vy-hlp-grp { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
            color:var(--vy-muted); margin:18px 0 8px; }
        .vy-hlp-faq { border-bottom:1px solid var(--vy-hairline); }
        .vy-hlp-faq:last-child { border-bottom:none; }
        .vy-hlp-faq summary { display:flex; align-items:center; justify-content:space-between; gap:14px;
            padding:15px 2px; cursor:pointer; font-size:14.5px; font-weight:500; color:var(--vy-ink);
            list-style:none; }
        /* appearance:none, never display:none — a hidden marker must not cost the row its focusability. */
        .vy-hlp-faq summary::-webkit-details-marker { display:none; }
        .vy-hlp-faq summary::after { content:'+'; flex:none; font-size:17px; line-height:1; color:var(--vy-accent-text); }
        .vy-hlp-faq[open] summary::after { content:'–'; }
        .vy-hlp-faq summary:focus-visible { outline:2px solid var(--vy-accent-text); outline-offset:2px; border-radius:8px; }
        .vy-hlp-ans { padding:0 2px 16px; font-size:13.5px; line-height:1.6; color:var(--vy-muted); }
        .vy-hlp-ans a { color:var(--vy-accent-text); font-weight:500; }
        /* Support cards. NEW classes, defined here rather than assumed — the shell defines .vy-card,
           .vy-btn and .vy-eyebrow (all checked) but nothing named vy-sup-*. */
        .vy-sup { margin-top:12px; }
        .vy-sup-row { display:flex; align-items:flex-start; gap:11px; padding:9px 0; }
        .vy-sup-row + .vy-sup-row { border-top:1px solid var(--vy-hairline); }
        .vy-sup-row svg { flex:none; margin-top:2px; }
        .vy-sup-lbl { font-size:11px; font-weight:600; letter-spacing:.05em; text-transform:uppercase;
            color:var(--vy-muted); margin-bottom:2px; }
        .vy-sup-val { font-size:13.5px; color:var(--vy-ink); }
        .vy-sup-phone { font-size:14.5px; font-weight:600; color:var(--vy-accent-text); text-decoration:none; }
        .vy-sup-phone:hover { text-decoration:underline; }
        .vy-sup-phone:focus-visible { outline:2px solid var(--vy-accent-text); outline-offset:3px; border-radius:6px; }
    </style>

    {{-- Hero --}}
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:22px 26px;margin-bottom:18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:52px;height:52px;flex-shrink:0;border-radius:16px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 1 1 3.3 2.4c-.6.2-.9.8-.9 1.4v.4"/><path d="M12 17h.01"/></svg>
        </div>
        <div style="min-width:0">
            <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin:0">Help</h1>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">Answers about your stay — arrival, payments, changes and your account.</div>
        </div>
    </div>

    {{-- Search · a GET form: no write exists on this page, so Enter is a safe read (§N.4k). --}}
    <form method="GET" action="{{ route('guest.help') }}" class="vy-hlp-search" style="margin-bottom:6px" role="search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-text)" stroke-width="2" stroke-linecap="round" aria-hidden="true" style="flex:none"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <label for="vy-help-q" class="vy-sr-only">Search help</label>
        <input id="vy-help-q" type="search" name="q" value="{{ $query }}" placeholder="Search help — door code, payments, cancelling…" maxlength="120">
        @if ($activeCategory !== '')<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
        <button type="submit" class="vy-btn" style="flex:none">Search</button>
    </form>

    {{-- Category filter · GET links, so no JS is required to narrow the list. --}}
    <nav class="vy-hlp-cats" aria-label="Help categories">
        <a class="vy-hlp-cat {{ $activeCategory === '' ? 'is-on' : '' }}"
           href="{{ route('guest.help', array_filter(['q' => $query])) }}"
           @if ($activeCategory === '') aria-current="true" @endif>All</a>
        @foreach ($categories as $cat)
            <a class="vy-hlp-cat {{ $activeCategory === $cat ? 'is-on' : '' }}"
               href="{{ route('guest.help', array_filter(['q' => $query, 'category' => $cat])) }}"
               @if ($activeCategory === $cat) aria-current="true" @endif>{{ $cat }}</a>
        @endforeach
    </nav>

    {{-- Results --}}
    <section class="vy-card" style="margin-top:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        @forelse ($grouped as $groupName => $groupArticles)
            <div class="vy-hlp-grp">{{ $groupName }}</div>
            @foreach ($groupArticles as $article)
                <details class="vy-hlp-faq">
                    <summary>{{ $article['question'] }}</summary>
                    <div class="vy-hlp-ans">
                        {{ $article['answer'] }}
                        <div style="margin-top:10px"><a href="{{ route($article['route']) }}">{{ $article['label'] }} →</a></div>
                    </div>
                </details>
            @endforeach
        @empty
            {{-- Honest empty state: say what was searched, and offer the one contact that really exists. --}}
            <div style="text-align:center;padding:26px 8px">
                <div style="font-family:var(--vy-display);font-weight:600;font-size:16px;color:var(--vy-ink)">
                    @if ($query !== '')No answers for “{{ $query }}”@else Nothing here yet @endif
                </div>
                <p style="margin:8px 0 0;font-size:13.5px;color:var(--vy-muted)">Try another word, or message the property — they can answer anything this page can't.</p>
            </div>
        @endforelse
    </section>

    {{-- The contact that ALWAYS exists. A link to the REAL relay — not a new form, and not a promise about
         how fast anyone replies. Everything below it is conditional; this is not. --}}
    <section class="vy-card" style="margin-top:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;justify-content:space-between;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
        <div style="min-width:0">
            <div style="font-size:14px;font-weight:600;color:var(--vy-ink)">Still need help?</div>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:2px">Message the property directly — it goes to the people looking after your stay.</div>
        </div>
        <a class="vy-btn" href="{{ route('guest.messages') }}" style="flex:none">Message us</a>
    </section>

    {{-- ── SUPPORT DETAILS — one card per property, and ONLY what that property has actually set.

         ⚠️ THIS BLOCK IS ABSENT BY DEFAULT. `$support` is empty unless an operator populated a column, so a
         property that offers no phone contributes no card, and a card whose property set only hours shows only
         hours. There is no "not provided", no greyed-out row and no placeholder anywhere in this file — an
         affordance a guest can see is an affordance somebody answers.

         ⚠️ Each card NAMES its property. A guest with two bookings must not be shown one number as though it
         reached both desks.

         ⚠️ The reply line is rendered from the shared catalogue and always reads "usually …". The template
         cannot state a guarantee because it does not hold the words — they come from PropertySupport. --}}
    @foreach ($support as $s)
        <section class="vy-card vy-sup" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .18s both">
            <div class="vy-eyebrow" style="margin-bottom:10px">{{ $s['property'] }}</div>

            @if ($s['phone'] !== null)
                <div class="vy-sup-row">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-text)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.9.6 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z"/></svg>
                    <div style="min-width:0">
                        <div class="vy-sup-lbl">Phone</div>
                        {{-- The link's accessible name says WHO is being called; the visible text is the number
                             itself, so a sighted guest can read it without dialling. --}}
                        <a class="vy-sup-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}"
                           aria-label="Call {{ $s['property'] }} on {{ $s['phone'] }}">{{ $s['phone'] }}</a>
                    </div>
                </div>
            @endif

            @if ($s['hours'] !== null)
                <div class="vy-sup-row">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-text)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <div style="min-width:0">
                        <div class="vy-sup-lbl">Hours</div>
                        <div class="vy-sup-val">{{ $s['hours'] }}</div>
                    </div>
                </div>
            @endif

            @if ($s['reply_norm'] !== null)
                <div class="vy-sup-row">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-text)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-3.8-.9L3 20.5l1.5-4.4A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4Z"/></svg>
                    <div style="min-width:0">
                        <div class="vy-sup-lbl">Messages</div>
                        <div class="vy-sup-val">We {{ $s['reply_norm'] }}.</div>
                    </div>
                </div>
            @endif
        </section>
    @endforeach
</x-guest.shell>
