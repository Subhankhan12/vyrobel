{{-- Sign-in problems: sign-ins refused on THIS person's address after too many wrong passwords, from the real sign-in
     log (App\Support\Security\SignInProblems). Shown only when there is one; never a sample. No place name: Vyrobel has no
     location service, so the IP address and the browser are what is known.
     $secure: the Alpine statement that opens the page's change-password dialog. --}}
@props(['problems', 'seenRoute', 'secure'])
@if ($problems['count'] > 0 && $problems['latest'] !== null)
    @php
        $last = $problems['latest'];
        $more = $problems['count'] - 1;
        // I18N-01 — the <time> sits inside the sentence, so it travels into the translation as a placeholder.
        $vyWhenHtml = '<time datetime="'.e($last['at']->toIso8601String()).'" title="'.e($last['at']->translatedFormat('j M Y, H:i T')).'">'.e($last['at']->diffForHumans()).'</time>';
    @endphp
    <section class="vy-sx-alert vy-sx-in" aria-labelledby="vy-sx-alert-t">
        <span class="vy-sx-ico is-lg"><x-security.icon name="shield-alert" :size="18" /></span>
        <div>
            <h2 class="vy-sx-alert-title" id="vy-sx-alert-t">{{ __('components/security/problems.heading') }}</h2>
            <p class="vy-sx-alert-text">
                {!! __('components/security/problems.blocked', ['device' => e($last['device']), 'ip' => e($last['ip'] ?? __('components/security/problems.ip_unknown')), 'when' => $vyWhenHtml]) !!}
                @if ($more > 0) {{ trans_choice('components/security/problems.more_refused', $more, ['count' => $more, 'days' => $problems['window_days']]) }} @endif
                {{ __('components/security/problems.secure_now') }}
            </p>
            <div class="vy-sx-alert-acts">
                <button type="button" class="vy-sx-btn vy-sx-btn--dark" @click="{{ $secure }}">{{ __('components/security/problems.secure_button') }}</button>
                <form method="POST" action="{{ route($seenRoute) }}">
                    @csrf
                    <button type="submit" class="vy-sx-btn vy-sx-btn--soft">{{ __('components/security/problems.this_was_me') }}</button>
                </form>
            </div>
        </div>
    </section>
@endif
