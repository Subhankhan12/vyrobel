{{-- Guest Review (Phase 5 · SG1) — rate a real completed stay: tap-to-rate stars, tag chips, a written review, and
     "send privately to the manager". RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="'Review · '.$reference">
    <style>
        .vy-stars { display:inline-flex; flex-direction:row-reverse; gap:4px; }
        .vy-stars input { position:absolute; opacity:0; width:0; height:0; }
        .vy-stars label { font-size:30px; line-height:1; color:rgba(120,70,40,.20); cursor:pointer; transition:color .12s; }
        .vy-stars input:checked ~ label, .vy-stars label:hover, .vy-stars label:hover ~ label { color:var(--vy-accent-3); }
        .vy-chip-opt { display:inline-flex; align-items:center; gap:6px; padding:7px 13px; border-radius:20px; border:1px solid var(--vy-glass-border); font-size:12.5px; color:var(--vy-muted); cursor:pointer; margin:0 6px 6px 0; }
        .vy-chip-opt input { position:absolute; opacity:0; }
        .vy-chip-opt:has(input:checked) { background:rgba(194,65,12,.08); border-color:var(--vy-accent); color:var(--vy-accent); }
    </style>

    <a class="vy-link" href="{{ route('guest.trips') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Trips</a>
    <h1 class="vy-h1" style="margin-top:10px">How was your stay?</h1>
    <p class="vy-lead">{{ $property }} · {{ $stay }}</p>

    @if (session('error'))
        <div class="vy-notice" style="margin-bottom:14px;background:rgba(180,68,58,.10);border-color:rgba(180,68,58,.3);border-left-color:rgb(150,50,42)">{{ session('error') }}</div>
    @endif

    @if ($existing)
        {{-- Already reviewed — read-only --}}
        <section class="vy-card">
            <div class="vy-eyebrow" style="margin-bottom:8px">Your review</div>
            <div style="font-size:26px;color:var(--vy-accent-3);letter-spacing:2px">{{ str_repeat('★', $existing['rating']).str_repeat('☆', 5 - $existing['rating']) }}</div>
            @if (count($existing['tags']) > 0)
                <div style="margin-top:10px">@foreach ($existing['tags'] as $t)<span style="display:inline-block;padding:4px 10px;border-radius:16px;background:var(--vy-wash);font-size:11.5px;color:var(--vy-muted);margin:0 5px 5px 0">{{ $t }}</span>@endforeach</div>
            @endif
            @if ($existing['body'] !== '')
                <div style="font-size:13.5px;color:var(--vy-ink);margin-top:10px;line-height:1.5">{{ $existing['body'] }}</div>
            @endif
            <div style="font-size:11.5px;color:var(--vy-faint);margin-top:12px">
                @if ($existing['private']) Sent privately to the manager. @elseif ($existing['status'] === 'published') Published. @elseif ($existing['status'] === 'rejected') Not published. @else Thanks — your review is with the property for review. @endif
            </div>
        </section>
    @else
        {{-- The review form --}}
        <form method="POST" action="{{ route('guest.review.submit', $reservation) }}">
            @csrf
            <section class="vy-card" style="margin-bottom:14px">
                <div class="vy-eyebrow" style="margin-bottom:10px">Your rating</div>
                <div class="vy-stars">
                    @foreach ([5, 4, 3, 2, 1] as $n)
                        <input type="radio" name="rating" id="star{{ $n }}" value="{{ $n }}" @if ($n === 5) required @endif>
                        <label for="star{{ $n }}" title="{{ $n }} star{{ $n > 1 ? 's' : '' }}">★</label>
                    @endforeach
                </div>
            </section>

            {{-- Optional per-category sub-ratings (Cleanliness/Communication/Location/Value) → the owner's
                 "By category" averages. Optional — a blank category simply doesn't count (never fabricated). --}}
            <section class="vy-card" style="margin-bottom:14px">
                <div class="vy-eyebrow" style="margin-bottom:4px">Rate by category</div>
                <div style="font-size:12px;color:var(--vy-muted);margin-bottom:12px">Optional — leave any blank.</div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach (\App\Models\Review::CATEGORIES as $key => $label)
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
                <div class="vy-eyebrow" style="margin-bottom:10px">What stood out?</div>
                <div>
                    @foreach ($positiveTags as $tag)
                        <label class="vy-chip-opt"><input type="checkbox" name="tags[]" value="{{ $tag }}"> {{ $tag }}</label>
                    @endforeach
                </div>
                <div style="margin-top:8px">
                    @foreach ($negativeTags as $tag)
                        <label class="vy-chip-opt"><input type="checkbox" name="tags[]" value="{{ $tag }}"> {{ $tag }}</label>
                    @endforeach
                </div>
            </section>

            <section class="vy-card" style="margin-bottom:14px">
                <div class="vy-eyebrow" style="margin-bottom:10px">Tell us more</div>
                <textarea name="body" rows="4" maxlength="5000" placeholder="What did you love, or what could be better?" style="width:100%;padding:11px 13px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff;resize:vertical"></textarea>
                <label style="display:flex;align-items:center;gap:9px;font-size:13px;color:var(--vy-muted);margin-top:12px">
                    <input type="checkbox" name="private" value="1"> Send privately to the manager (not published)
                </label>
            </section>

            <button type="submit" class="vy-btn">Submit review</button>
        </form>
    @endif
</x-guest.shell>
