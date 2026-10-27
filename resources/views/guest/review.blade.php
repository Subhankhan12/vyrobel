{{-- Guest Review (Phase 5 · SG1) — rate a real completed stay: tap-to-rate stars, tag chips, a written review, and
     "send privately to the manager". RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="__('guest/review.page_title', ['reference' => $reference])" :accent="$brandAccent">
    <style>
        .vy-stars { display:inline-flex; flex-direction:row-reverse; gap:4px; }
        .vy-stars input { position:absolute; opacity:0; width:0; height:0; }
        /* The radio is visually hidden (correctly still in the tab order — never display:none), but an
           invisible input shows an INVISIBLE focus ring, so the star it controls carries one instead.
           Without this a keyboard guest arrow-keys through the rating with no idea where they are. */
        .vy-stars input:focus-visible + label { outline:2px solid var(--vy-accent); outline-offset:3px; border-radius:4px; }
        .vy-stars-fs { border:0; padding:0; margin:0; min-inline-size:0; }
        .vy-stars label { font-size:30px; line-height:1; color:rgba(120,70,40,.20); cursor:pointer; transition:color .12s; }
        .vy-stars input:checked ~ label, .vy-stars label:hover, .vy-stars label:hover ~ label { color:var(--vy-accent-3); }
        .vy-chip-opt { display:inline-flex; align-items:center; gap:6px; padding:7px 13px; border-radius:20px; border:1px solid var(--vy-glass-border); font-size:12.5px; color:var(--vy-muted); cursor:pointer; margin:0 6px 6px 0; }
        .vy-chip-opt input { position:absolute; opacity:0; }
        .vy-chip-opt:has(input:checked) { background:rgba(194,65,12,.08); border-color:var(--vy-accent-text); color:var(--vy-accent-text); }
    </style>

    <a class="vy-link" href="{{ route('guest.trips') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('guest/review.back_to_trips') }}</a>

    {{-- Hero (per-property brand accent) --}}
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:22px 26px;margin:12px 0 18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:52px;height:52px;flex-shrink:0;border-radius:16px;background:linear-gradient(140deg,var(--vy-accent-3),var(--vy-accent));display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff" stroke="none"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.9 6.1 20.5l1.2-6.5L2.5 9.4l6.6-.9z"/></svg>
        </div>
        <div style="min-width:0">
            {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
            <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin:0">{{ __('guest/review.heading') }}</h1>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">{{ $property }} · {{ $stay }}</div>
        </div>
    </div>

    @if (session('error'))
        <div class="vy-notice" style="margin-bottom:14px;background:rgba(180,68,58,.10);border-color:rgba(180,68,58,.3);border-left-color:rgb(150,50,42)">{{ session('error') }}</div>
    @endif

    @if ($existing)
        {{-- Already reviewed — read-only --}}
        <section class="vy-card">
            <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('guest/review.your_review') }}</div>
            {{-- DO10-4 — the guest realm's ONE real star-DISPLAY site (G1 established that Trips' glyphs are
                 decorative, not ratings). The shared component carries what a hand-written str_repeat cannot:
                 ONE accessible name for the row ("5 out of 5 stars") instead of five announced glyphs, a
                 floor that can never round a rating up to flatter it, and filled-vs-empty distinguished by
                 GLYPH (★/☆) rather than colour alone (WCAG 1.4.1). --}}
            {{-- ⚠️ The colour override that used to live here is GONE, and deliberately: it existed because the
                 component shipped #E8A63A, which measured 2.05 here and missed the 3:1 a star must meet as the
                 VISUAL carrier of the rating (WCAG 1.4.11) — so this page passed its own value while the
                 shared default stayed broken. The DO10-4 capstone fixed the DEFAULT instead, measured against
                 the worst real plate in the product, so every rating row now inherits one gold and this
                 override became redundant. Don't reintroduce a per-page star colour — fix the component. --}}
            <x-star-rating :value="$existing['rating']" size="26px" gap="3px" />
            @if (count($existing['tags']) > 0)
                <div style="margin-top:10px">@foreach ($existing['tags'] as $t)<span style="display:inline-block;padding:4px 10px;border-radius:16px;background:var(--vy-wash);font-size:11.5px;color:var(--vy-muted);margin:0 5px 5px 0">{{ $tagLabels[$t] ?? $t }}</span>@endforeach</div>
            @endif
            @if ($existing['body'] !== '')
                <div style="font-size:13.5px;color:var(--vy-ink);margin-top:10px;line-height:1.5">{{ $existing['body'] }}</div>
            @endif
            <div style="font-size:11.5px;color:var(--vy-faint);margin-top:12px">
                @if ($existing['private']) {{ __('guest/review.sent_privately') }} @elseif ($existing['status'] === 'published') {{ __('guest/review.published') }} @elseif ($existing['status'] === 'rejected') {{ __('guest/review.not_published') }} @else {{ __('guest/review.with_property') }} @endif
            </div>
        </section>
    @else
        {{-- The review form --}}
        {{-- ⚠️ §N.4k — a review is PUBLIC content, and Enter published it early. Measured in the browser:
             with a star focused, Enter submits the form, so a guest who arrow-keys to a rating and presses
             Enter to "confirm my choice" publishes a rating with no review written. The textarea below is
             safe by construction (a textarea never implicitly submits — G7), but a radio group is not.
             Highest stakes of the pass so far: G5's cancellation was a request an operator reviews, G7's
             message was private to one property; this is visible to other travellers. Guarded with the
             repo's own destructive-submit idiom (admin/customers/show), plus the shared double-submit
             guard. --}}
        <form method="POST" action="{{ route('guest.review.submit', $reservation) }}" data-guard-submit
              onsubmit="return confirm(@js(__('guest/review.publish_confirm')));">
            @csrf
            <section class="vy-card" style="margin-bottom:14px">
                {{-- A REAL radio group, so the rating is keyboard-operable by construction (arrow keys move
                     between options) — not a mouse-only star widget. Two things it was missing: the group had
                     no accessible name (the eyebrow was a plain div), and each option announced as "black
                     star", because a <label>'s only content is the glyph. aria-label puts the value on the
                     control itself; the glyph stays decorative. --}}
                <fieldset class="vy-stars-fs">
                    <legend class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/review.your_rating') }}</legend>
                    <div class="vy-stars">
                        @foreach ([5, 4, 3, 2, 1] as $n)
                            <input type="radio" name="rating" id="star{{ $n }}" aria-label="{{ trans_choice('guest/review.stars', $n, ['count' => $n]) }}" value="{{ $n }}" {{ (($prefillRating ?? null) === $n) ? 'checked' : '' }} @if ($n === 5) required @endif>
                            <label for="star{{ $n }}" title="{{ trans_choice('guest/review.stars', $n, ['count' => $n]) }}" aria-hidden="true">★</label>
                        @endforeach
                    </div>
                </fieldset>
            </section>

            {{-- Optional per-category sub-ratings (Cleanliness/Communication/Location/Value) → the owner's
                 "By category" averages. Optional — a blank category simply doesn't count (never fabricated). --}}
            <section class="vy-card" style="margin-bottom:14px">
                <div class="vy-eyebrow" style="margin-bottom:4px">{{ __('guest/review.rate_by_category') }}</div>
                <div style="font-size:12px;color:var(--vy-muted);margin-bottom:12px">{{ __('guest/review.category_optional') }}</div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach (\App\Models\Review::categoryLabels() as $key => $label)
                        <label style="display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:13.5px;color:var(--vy-ink)">
                            <span>{{ $label }}</span>
                            <select name="{{ $key }}" style="padding:7px 10px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
                                <option value="">—</option>
                                @foreach ([5, 4, 3, 2, 1] as $n)
                                    <option value="{{ $n }}">{{ $n }} ★</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="vy-card" style="margin-bottom:14px">
                <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/review.what_stood_out') }}</div>
                <div>
                    @foreach ($positiveTags as $tag)
                        <label class="vy-chip-opt"><input type="checkbox" name="tags[]" value="{{ $tag }}"> {{ $tagLabels[$tag] ?? $tag }}</label>
                    @endforeach
                </div>
                <div style="margin-top:8px">
                    @foreach ($negativeTags as $tag)
                        <label class="vy-chip-opt"><input type="checkbox" name="tags[]" value="{{ $tag }}"> {{ $tagLabels[$tag] ?? $tag }}</label>
                    @endforeach
                </div>
            </section>

            <section class="vy-card" style="margin-bottom:14px">
                <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/review.tell_us_more') }}</div>
                <label class="vy-sr-only" for="vy-lbl-review-body">{{ __('guest/review.body_placeholder') }}</label>
                <textarea id="vy-lbl-review-body" name="body" rows="4" maxlength="5000" placeholder="{{ __('guest/review.body_placeholder') }}" style="width:100%;padding:11px 13px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff;resize:vertical"></textarea>
                <label style="display:flex;align-items:center;gap:9px;font-size:13px;color:var(--vy-muted);margin-top:12px">
                    <input type="checkbox" name="private" value="1"> {{ __('guest/review.send_privately') }}
                </label>
            </section>

            <button type="submit" class="vy-btn">{{ __('guest/review.submit') }}</button>
        </form>
    @endif
</x-guest.shell>
