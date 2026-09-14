{{-- Operator Reviews (Phase 5 · SG3) — see reviews, respond, moderate (publish/reject). Guest NAME only (#28);
     controls gated on reviews.manage so the UI never invites a 403. RAW-BLADE RULE: every `@` follows a space,
     newline or tag. --}}
<x-layouts.operator title="Reviews">
<x-operator.shell active="reviews" title="Reviews">
    @if (session('status'))
        <div class="vy-note" style="margin:0 0 14px;padding:12px 14px;border-radius:12px;background:rgba(70,120,60,.10);color:rgb(60,105,52);font-size:13px">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="vy-note" style="margin:0 0 14px;padding:12px 14px;border-radius:12px;background:rgba(180,68,58,.10);color:rgb(150,50,42);font-size:13px">{{ session('error') }}</div>
    @endif

    <p style="color:var(--vy-muted,#6b6b6b);font-size:13px;margin:0 0 16px">
        Respond to your guests and choose what’s published. Auto-responses and review-gating are coming soon —
        for now, every response and publish decision is yours.
    </p>

    {{-- #104 GATE 2 — REAL reputation summary (overall rating + 5-star distribution + reply rate), all real
         aggregates over the org's reviews via the shared ReviewRating. Honest empty state when there are none. --}}
    @if ($summary['has_reviews'])
        <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade" style="margin-bottom:16px">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Overall rating</div>
                <div class="vy-dk-val">{{ number_format((float) $summary['average'], 1) }}<span class="vy-dk-unit"> ★</span></div>
                <div class="vy-dk-sub">{{ $summary['count'] }} published {{ \Illuminate\Support\Str::plural('review', $summary['count']) }}</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Reply rate</div>
                <div class="vy-dk-val">{{ $summary['reply_rate'] }}<span class="vy-dk-unit">%</span></div>
                <div class="vy-dk-sub">{{ $summary['responded'] }} of {{ $summary['count'] }} answered
                    @if ($summary['pending'] > 0)· {{ $summary['pending'] }} awaiting review @endif
                </div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Rating breakdown</div>
                <div style="display:flex;flex-direction:column;gap:3px;margin-top:8px">
                    @foreach ([5, 4, 3, 2, 1] as $star)
                        @php $n = $summary['distribution'][$star]; $pct = $summary['count'] > 0 ? (int) round($n * 100 / $summary['count']) : 0; @endphp
                        <div style="display:flex;align-items:center;gap:8px;font-size:11px">
                            <span style="width:28px;color:var(--vy-muted)">{{ $star }}★</span>
                            <span style="flex:1;height:6px;border-radius:4px;background:rgba(120,70,40,.10);overflow:hidden"><span style="display:block;height:100%;width:{{ $pct }}%;background:var(--vy-grad-peach)"></span></span>
                            <span style="width:26px;text-align:right;color:var(--vy-muted)">{{ $n }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- #104 GATE 2 — REAL status filter tabs (All / Published / Pending / Rejected), driven by the real column. --}}
    <div class="vy-fade" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
        @foreach (['all' => 'All', 'published' => 'Published', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $key => $label)
            <a class="vy-pill @if ($filter === $key) is-on @endif" href="?filter={{ $key }}">{{ $label }}</a>
        @endforeach
    </div>

    @forelse ($reviews as $review)
        <section class="vy-card" style="margin-bottom:12px;padding:16px 18px;border-radius:14px;background:var(--vy-card,#fff);border:1px solid var(--vy-hairline)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                {{-- ⚠️ Tokenised + AA-corrected. This card was written in raw hex and never picked up the
                     design tokens, so it still carried the PRE-AA greys: the room type used #8A8079 (the old
                     --vy-muted, measured 3.86) and the date #A69A92 (the old --vy-faint, 2.74). The stars
                     used the brand fill #E8703A as 18px TEXT and measured 3.08 — and 18px regular is NOT
                     "large text" under WCAG, so 4.5 is the real bar.
                     ⚠️ AND THE RATING WAS CARRIED BY STARS ALONE. The numeric value now sits beside them
                     so the score is never conveyed by shape or colour alone (1.4.1); the stars themselves are aria-hidden decoration.
                     `rating` is already in the controller payload — it is NOT a second derivation of the
                     shared ReviewRating (#76), which stays the only source of the AGGREGATE. --}}
                <div>
                    <span style="font-size:18px;color:var(--vy-accent-text);letter-spacing:2px" aria-hidden="true">{{ $review['stars'] }}</span>
                    <span style="font-size:12.5px;color:var(--vy-muted);font-weight:600;margin-left:4px">{{ $review['rating'] }}/5</span>
                    <span style="font-size:13px;color:var(--vy-ink);font-weight:600;margin-left:8px">{{ $review['guest'] }}</span>
                    @if ($review['room'] !== '')<span style="font-size:12px;color:var(--vy-muted)"> · {{ $review['room'] }}</span>@endif
                    <span style="font-size:11px;color:var(--vy-faint)"> · {{ $review['at'] }}</span>
                </div>
                <div style="display:flex;gap:6px;align-items:center">
                    @if ($review['private'])
                        <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#8A6410;background:#FBEFD8;border-radius:8px;padding:2px 8px">Private</span>
                    @elseif ($review['status'] === 'published')
                        <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:rgb(60,105,52);background:rgba(70,120,60,.12);border-radius:8px;padding:2px 8px">Published</span>
                    @elseif ($review['status'] === 'rejected')
                        <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:rgb(150,50,42);background:rgba(180,68,58,.10);border-radius:8px;padding:2px 8px">Rejected</span>
                    @else
                        <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#B26A08;background:#FBEFD8;border-radius:8px;padding:2px 8px">Pending</span>
                    @endif
                </div>
            </div>

            @if (count($review['tags']) > 0)
                <div style="margin-top:10px">@foreach ($review['tags'] as $tag)<span style="display:inline-block;padding:3px 9px;border-radius:14px;background:var(--vy-accent-wash,#F6EBE2);font-size:11px;color:var(--vy-muted);margin:0 5px 5px 0">{{ $tag }}</span>@endforeach</div>
            @endif
            @if ($review['body'] !== '')
                <div style="font-size:13.5px;color:var(--vy-ink);margin-top:10px;line-height:1.5">{{ $review['body'] }}</div>
            @endif

            @if ($review['response'] !== '')
                <div style="margin-top:12px;padding:10px 13px;border-radius:10px;background:var(--vy-accent-wash,#F6EBE2);font-size:13px;color:var(--vy-ink)"><strong style="color:var(--vy-ink)">Your response:</strong> {{ $review['response'] }}</div>
            @endif

            @can('reviews.manage')
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--vy-hairline);display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start">
                    <form method="POST" action="{{ route('app.reviews.respond', $review['id']) }}" style="flex:1;min-width:220px;display:flex;gap:8px">
                        @csrf
                        <label class="vy-sr-only" for="vy-lbl-reviews-response">Write a response…</label>
                        <input id="vy-lbl-reviews-response" type="text" name="response" maxlength="2000" placeholder="Write a response…" value="{{ $review['response'] }}" style="flex:1;padding:9px 12px;border-radius:10px;border:1px solid var(--vy-hairline);font-family:inherit;font-size:13px">
                        <button type="submit" style="padding:9px 15px;border-radius:20px;border:none;background:var(--vy-accent);color:#fff;font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer">Respond</button>
                    </form>
                    @if (! $review['private'] && $review['status'] !== 'published')
                        <form method="POST" action="{{ route('app.reviews.publish', $review['id']) }}">@csrf<button type="submit" style="padding:9px 15px;border-radius:20px;border:1px solid rgba(70,120,60,.35);background:#fff;color:rgb(60,105,52);font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer">Publish</button></form>
                    @endif
                    @if ($review['status'] !== 'rejected')
                        <form method="POST" action="{{ route('app.reviews.reject', $review['id']) }}">@csrf<button type="submit" style="padding:9px 15px;border-radius:20px;border:1px solid rgba(180,68,58,.3);background:#fff;color:rgb(150,50,42);font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer">Reject</button></form>
                    @endif
                </div>
            @endcan
        </section>
    @empty
        <section class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px;background:var(--vy-card,#fff);border-radius:14px;border:1px solid var(--vy-hairline)">No reviews yet.</section>
    @endforelse
</x-operator.shell>
</x-layouts.operator>
