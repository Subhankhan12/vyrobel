{{-- THE COOKIE NOTICE (front-website Gate 6, ruling D11).

     ⚠️ ONE DEFINITION, TWO PLACEMENTS. `mode="banner"` is the first-visit notice; `mode="panel"` is the
     always-available copy in the footer. Both render the SAME disclosure and the SAME form, because two
     descriptions of one set of cookies would drift and then disagree (§N.8f).

     ⚠️ IT IS NOT A MODAL, ON PURPOSE. A dialog that overlays the page and traps focus is both an
     accessibility hazard and a coercion: EU guidance is that a notice must not obstruct access to the
     content in a way that pressures acceptance. This sits at the bottom, takes focus from nobody, and the
     page underneath stays fully readable and operable with the notice ignored.

     ⚠️ NO JAVASCRIPT. Two submit buttons in a plain form. Nothing can be set before the visitor acts,
     because the browser has to post before the server writes anything — and there is no client state for a
     persistent worker to leak between visitors.

     ⚠️ ACCEPT AND DECLINE ARE THE SAME CONTROL. Same element, same size, same weight, same colour, same
     one click, same destination. A "reject" that is a shade quieter than "accept" is a dark pattern, and
     the styling below is deliberately identical rather than merely similar. --}}
@props(['mode' => 'banner'])
@php
    use App\Support\Privacy\CookieConsent;
    $choice = CookieConsent::choice(request());
    // ⚠️ THE PATH ONLY — NEVER THE QUERY STRING. The first version echoed the whole request URI into a
    // hidden field, which meant any query a visitor arrived with was reflected into the HTML of every
    // page. Blade escapes it, so it was not an injection — but it is needless reflected surface, and it
    // was caught by ANOTHER gate's guard: Gate 4b's pricing-oracle test found "rival-cliffside" in the
    // page after requesting "?property=rival-cliffside", because this field had put it there. Returning
    // the visitor to the same PATH is all this needs to do.
    $return = request()->getPathInfo();
    $isBanner = $mode === 'banner';
    $btn = 'display:inline-flex;align-items:center;justify-content:center;min-width:132px;padding:10px 20px;'
        .'border-radius:var(--vy-r-pill);border:1px solid var(--vy-ink);background:var(--vy-ink);color:#fff;'
        .'font-family:var(--vy-sans);font-size:13.5px;font-weight:600;line-height:1.2;cursor:pointer;';
@endphp

{{-- ⚠️ The anchor id lives on the PANEL, not the banner: the banner disappears the moment the visitor
     answers, and the controller redirects to "#vy-cookie-notice" — a fragment pointing at an element that
     has just been removed would scroll nowhere. The panel is on every page for ever, so it is the stable
     target. Two elements sharing one id would also be invalid HTML and would confuse a screen reader. --}}
<section @if ($mode !== 'banner') id="vy-cookie-notice" @endif role="region" aria-labelledby="vy-cookie-h-{{ $mode }}"
         @if ($isBanner) style="position:fixed;left:0;right:0;bottom:0;z-index:600;padding:0 16px 16px;" @endif>
    <div style="max-width:{{ $isBanner ? '980px' : '100%' }};margin:0 auto;padding:20px 24px;border-radius:20px;
                background:{{ $isBanner ? 'rgba(255,252,248,.94)' : 'transparent' }};
                @if ($isBanner) backdrop-filter:blur(20px) saturate(1.15);-webkit-backdrop-filter:blur(20px) saturate(1.15);
                border:1px solid var(--vy-glass-border);box-shadow:var(--vy-shadow-sm); @else padding:0; @endif">

        <h2 id="vy-cookie-h-{{ $mode }}" style="margin:0 0 8px;font-family:var(--vy-display);font-weight:600;
            font-size:{{ $isBanner ? '16px' : '15px' }};letter-spacing:-.01em;color:var(--vy-ink);">Cookies on this site</h2>

        {{-- ⚠️ THE HONEST HEADLINE. Saying "there is nothing here to refuse" is the opposite of what a
             consent banner usually does, and it is the only true thing to say: the product has no
             analytics, no marketing and no third-party cookie of any kind. Offering a toggle for them
             would be a control that promises and does nothing (#39) — and worse than none, because a
             visitor who switched it off would believe they had opted out. --}}
        {{-- ⚠️ EVERY NUMBER HERE IS DERIVED FROM THE CATALOGUE, never typed. The first version printed a
             hard "3 cookies" — neither the catalogue size nor what a visitor actually receives — and it
             shipped on every page before a screenshot caught it. --}}
        <p style="margin:0 0 14px;font-size:13.5px;line-height:1.62;color:var(--vy-ink);max-width:760px;">
            An ordinary visit sets <strong>{{ count(CookieConsent::alwaysSet()) }} cookies</strong>
            &mdash; {{ implode(' and ', array_keys(CookieConsent::alwaysSet())) }}.
            {{ count(CookieConsent::onRequestOnly()) }} more appear only if you ask for them.
            <strong>All {{ count(CookieConsent::disclosed()) }} are strictly necessary</strong>, and there
            are <strong>no analytics, no marketing and no third-party cookies at all</strong> &mdash;
            nothing here tracks you across other sites.
            <strong>So there is nothing on this notice to switch off today.</strong>
            @if (CookieConsent::nonEssentialCategories() === [])
                We record your answer anyway, because it binds us if that ever changes.
            @endif
        </p>

        <details style="margin:0 0 14px;" @if (! $isBanner) open @endif>
            <summary style="cursor:pointer;font-size:12.5px;font-weight:600;color:var(--vy-accent-text);">
                What each one is for
            </summary>
            <div style="overflow-x:auto;margin-top:10px;">
                <table style="border-collapse:collapse;font-size:12.5px;color:var(--vy-ink);min-width:560px;">
                    <caption class="sr-only">Every cookie this site sets</caption>
                    <thead>
                        <tr>
                            @foreach (['Cookie', 'What it does', 'How long', 'When it is set'] as $head)
                                <th scope="col" style="text-align:left;padding:6px 14px 6px 0;font-weight:600;color:var(--vy-muted);border-bottom:1px solid var(--vy-hairline);">{{ $head }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (CookieConsent::disclosed() as $name => $cookie)
                            <tr>
                                <td style="padding:8px 14px 8px 0;vertical-align:top;border-bottom:1px solid var(--vy-hairline);"><code style="font-size:12px;">{{ $name }}</code></td>
                                <td style="padding:8px 14px 8px 0;vertical-align:top;border-bottom:1px solid var(--vy-hairline);line-height:1.55;max-width:380px;">{{ $cookie['purpose'] }}</td>
                                <td style="padding:8px 14px 8px 0;vertical-align:top;border-bottom:1px solid var(--vy-hairline);white-space:nowrap;">{{ $cookie['lifetime'] }}</td>
                                <td style="padding:8px 0;vertical-align:top;border-bottom:1px solid var(--vy-hairline);line-height:1.55;max-width:280px;">{{ $cookie['set_when'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

        <form method="POST" action="{{ route('cookies.choice') }}"
              style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
            @csrf
            <input type="hidden" name="return" value="{{ $return }}">
            {{-- ⚠️ IDENTICAL, not merely similar: same $btn string on both, so neither can be nudged ahead
                 of the other by a later edit without the edit being obvious. --}}
            <button type="submit" name="choice" value="{{ CookieConsent::ACCEPTED }}" style="{{ $btn }}">That's fine</button>
            <button type="submit" name="choice" value="{{ CookieConsent::DECLINED }}" style="{{ $btn }}">Decline non-essential</button>
            <x-marketing.nav-link key="privacy" style="font-size:12.5px;color:var(--vy-accent-text);font-weight:600;">Privacy notice</x-marketing.nav-link>
        </form>

        @if ($choice !== null)
            <p data-cookie-choice="{{ $choice['choice'] }}" style="margin:12px 0 0;font-size:12px;line-height:1.55;color:var(--vy-muted);">
                {{-- ⚠️ DEMONSTRABLE, and shown to the person it concerns: what was answered, when, and
                     against which version of the disclosure above. A stored choice nobody can see is a
                     claim about a record rather than a record. --}}
                Your answer: <strong>{{ $choice['choice'] === CookieConsent::ACCEPTED ? "that's fine" : 'non-essential declined' }}</strong>,
                recorded {{ \Carbon\CarbonImmutable::parse($choice['at'])->format('j M Y') }}
                against version {{ $choice['version'] }} of this notice. You can change it above at any time.
            </p>
        @endif
    </div>
</section>
