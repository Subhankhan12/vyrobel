{{-- Guest Trips (Phase 4b · SG1) — the authed guest's OWN stays (upcoming + past). Isolation is in the
     controller (the guest's own reservations only). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" title="Your trips">
    <h1 class="vy-h1">Your trips</h1>
    <p class="vy-lead">Your stays with us — upcoming and past.</p>

    <div class="vy-eyebrow" style="margin-bottom:10px">Upcoming</div>
    @forelse ($upcoming as $trip)
        @include('guest.partials.trip-card', ['trip' => $trip])
    @empty
        <div class="vy-card vy-empty" style="margin-bottom:26px">No upcoming stays. When you book, they’ll appear here.</div>
    @endforelse

    @if (! empty($past))
        <div class="vy-eyebrow" style="margin:26px 0 10px">Past &amp; cancelled</div>
        @foreach ($past as $trip)
            @include('guest.partials.trip-card', ['trip' => $trip])
        @endforeach
    @endif
</x-guest.shell>
