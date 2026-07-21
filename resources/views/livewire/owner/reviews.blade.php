{{-- Owner Reviews (Phase 5 · SG4) — the published reviews for the owner's OWN units, read-only (the operator owns
     the response). Owner-scoped via OwnerPortalService::units(); guest byline is first-name + initial (#28).
     Shell from <x-owner.shell>. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="reviews" title="Reviews" :viewer="$viewer">
    <p class="vy-lead" style="color:var(--vy-muted);font-size:13.5px;margin:0 0 18px">What your guests said about their stays. Your property manager responds to reviews on your behalf.</p>

    @forelse ($reviews as $review)
        <section class="vy-card" style="margin-bottom:12px;padding:16px 18px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                <div>
                    <span style="font-size:17px;color:var(--vy-accent-3,#E8703A);letter-spacing:2px">{{ $review['stars'] }}</span>
                    <span style="font-size:13px;color:var(--vy-ink);font-weight:600;margin-left:8px">{{ $review['byline'] }}</span>
                    @if ($review['room'] !== '')<span style="font-size:12px;color:var(--vy-muted)"> · {{ $review['room'] }}</span>@endif
                </div>
                <span style="font-size:11.5px;color:var(--vy-faint)">{{ $review['at'] }}</span>
            </div>

            @if (count($review['tags']) > 0)
                <div style="margin-top:9px">@foreach ($review['tags'] as $tag)<span style="display:inline-block;padding:3px 9px;border-radius:14px;background:var(--vy-wash,#F6EBE2);font-size:11px;color:var(--vy-muted);margin:0 5px 5px 0">{{ $tag }}</span>@endforeach</div>
            @endif
            @if ($review['body'] !== '')
                <div style="font-size:13.5px;color:var(--vy-ink);margin-top:9px;line-height:1.5">{{ $review['body'] }}</div>
            @endif
            @if ($review['response'] !== '')
                <div style="margin-top:12px;padding:10px 13px;border-radius:10px;background:var(--vy-wash,#F6EBE2);font-size:13px;color:var(--vy-muted)"><strong style="color:var(--vy-ink)">Manager’s response:</strong> {{ $review['response'] }}</div>
            @endif
        </section>
    @empty
        <section class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px">No reviews yet for your units.</section>
    @endforelse
</x-owner.shell>
