{{-- Sign-in problems: sign-ins refused on THIS person's address after too many wrong passwords, from the real sign-in
     log (App\Support\Security\SignInProblems). Shown only when there is one; never a sample. No place name: Vyrobel has no
     location service, so the IP address and the browser are what is known.
     $secure: the Alpine statement that opens the page's change-password dialog. --}}
@props(['problems', 'seenRoute', 'secure'])
@if ($problems['count'] > 0 && $problems['latest'] !== null)
    @php $last = $problems['latest']; $more = $problems['count'] - 1; @endphp
    <section class="vy-sx-alert vy-sx-in" aria-labelledby="vy-sx-alert-t">
        <span class="vy-sx-ico is-lg"><x-security.icon name="shield-alert" :size="18" /></span>
        <div>
            <h2 class="vy-sx-alert-title" id="vy-sx-alert-t">Sign-in blocked on your account</h2>
            <p class="vy-sx-alert-text">
                Too many wrong passwords from {{ $last['device'] }} (IP <span class="vy-sx-mono">{{ $last['ip'] ?? 'unknown' }}</span>)
                <time datetime="{{ $last['at']->toIso8601String() }}" title="{{ $last['at']->format('j M Y, H:i T') }}">{{ $last['at']->diffForHumans() }}</time>, so signing in was paused. @if ($more > 0) {{ $more }} more {{ $more === 1 ? 'attempt was' : 'attempts were' }} refused in the last {{ $problems['window_days'] }} days. @endif
                If this wasn’t you, secure your account now.
            </p>
            <div class="vy-sx-alert-acts">
                <button type="button" class="vy-sx-btn vy-sx-btn--dark" @click="{{ $secure }}">Secure my account</button>
                <form method="POST" action="{{ route($seenRoute) }}">
                    @csrf
                    <button type="submit" class="vy-sx-btn vy-sx-btn--soft">This was me</button>
                </form>
            </div>
        </div>
    </section>
@endif
