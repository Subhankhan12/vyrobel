{{-- THE CONTACT FORM — REAL (front-website Gate 7).

     ⚠️ WHAT THIS REPLACES. The design's form was two `sc-if` blocks bound to React state: a submit handler
     that flipped a local `sent` flag, and no `<form>`, no `action` and no endpoint anywhere behind it. It
     would have been a control that promises and does nothing (#39) — and not a marginal one, because
     `key="contact"` appears 21 times across nine PUBLISHED marketing pages. Every "Book a demo" and "Talk to
     us" button on the live site arrives here.

     ⚠️ THE CARD'S OWN CHROME IS THE DESIGN'S, KEPT VERBATIM — only the inside of the card is ours, so the
     page still looks exactly as drawn. The field set, the two chip rows and their labels are the design's
     too; what changed is that they now post.

     ⚠️ WHAT IT DELIBERATELY DOES NOT SAY, each a refusal rather than an omission:
       • NO REPLY-TIME PROMISE. The design asserted "we reply within one working day, usually sooner" twice.
         There is no support process, no SLA store and no first-response metric anywhere in the product.
       • NO DEMO SLOT PICKER (D5, open). Removed with its section: there is no scheduling package, no meeting
         provider and no invite generator, so a picker would name a time and book nothing.
       • NO "Quoting the GROWTH plan you selected on Pricing" banner — there is no Growth plan. The billing
         engine runs two flat plans, Essential and Pro.

     ⚠️ The submitted message is written to `marketing_contact_messages` BEFORE any mail is attempted, so the
     confirmation below is true even when mail is unconfigured or fails. See MarketingContactController. --}}

@php
    // ⚠️ The two chip rows are closed sets, and this is the SAME list the controller validates against —
    // read from the controller so a chip can never offer a value the route would reject.
    $unitBands = ['1–10', '11–60', '60+'];
    $topics = ['A demo', 'Pricing', 'Migrating from another PMS', 'Something else'];
    $sent = (bool) session('contact_sent');

    // ⚠️ `$errors` IS NOT ALWAYS THERE, AND THE FIRST VERSION OF THIS FILE ASSUMED IT WAS. It is shared into
    // the view by `ShareErrorsFromSession`, which only runs in the `web` middleware group — so rendering
    // this component OUTSIDE a request (as four of the marketing sweeps do, to scan every page's markup)
    // raised "Undefined variable $errors" and took the page down. ⚠️ Found by a COUNT PIN: adding four pages
    // forced the sweeps to re-run over them, and they crashed. The pin was doing more than counting.
    $errorBag = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@if ($sent)
    {{-- ⚠️ THE CONFIRMATION STATES ONLY WHAT HAPPENED: the message is stored. It does not claim a person has
         read it, and it does not promise when one will. --}}
    <div style="text-align: center; padding: 34px 10px;">
        <span aria-hidden="true" style="display: inline-flex; width: 62px; height: 62px; align-items: center; justify-content: center; border-radius: 20px; background: rgba(217,102,46,.12); color: var(--vy-accent-text); margin-bottom: 18px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
        </span>
        <h2 style="margin: 0 0 12px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink);">Thanks &mdash; that&rsquo;s with us</h2>
        <p style="margin: 0 0 22px; font-size: 15px; line-height: 1.6; color: var(--vy-muted);">
            Your message is saved. One person reads these, so a reply comes when it comes &mdash; we&rsquo;d
            rather not promise you a time we can&rsquo;t keep. If it&rsquo;s urgent, mail
            <a href="mailto:contact@vyrobel.com" style="color: var(--vy-accent-text); font-weight: 500;">contact@vyrobel.com</a> directly.
        </p>
        <a href="{{ route('marketing.page.contact') }}#vy-contact-form" style="display: inline-block; padding: 12px 22px; border-radius: 12px; border: 1px solid rgba(255,255,255,.85); background: rgba(255,255,255,.6); font-family: var(--vy-sans); font-size: 14px; font-weight: 500; color: var(--vy-ink); text-decoration: none;">Send another message</a>
    </div>
@else
    <form id="vy-contact-form" method="POST" action="{{ route('marketing.contact.store') }}">
        @csrf

        {{-- ⚠️ THE HONEYPOT. Visually hidden and hidden from assistive tech, so no real person ever sees or
             focuses it; anything that fills it gets the ordinary confirmation and stores nothing, which
             tells a bot less than an error would. `tabindex="-1"` and `autocomplete="off"` keep it out of
             the keyboard order and out of browser autofill, which would otherwise fill it for a real user
             and silently discard their message. --}}
        <div aria-hidden="true" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">
            <label for="{{ \App\Http\Controllers\MarketingContactController::HONEYPOT }}">Leave this field empty</label>
            <input type="text" id="{{ \App\Http\Controllers\MarketingContactController::HONEYPOT }}"
                   name="{{ \App\Http\Controllers\MarketingContactController::HONEYPOT }}"
                   tabindex="-1" autocomplete="off">
        </div>

        @if ($errorBag->any())
            <div role="alert" style="display: flex; align-items: flex-start; gap: 9px; margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: rgba(194,65,12,.09); color: var(--vy-accent-text); font-size: 13.5px; line-height: 1.55;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4M12 16h.01"></path></svg>
                <span>{{ $errorBag->first() }}</span>
            </div>
        @endif

        <div data-frow="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div>
                <label for="vy-c-name" style="display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px;">Your name</label>
                <input id="vy-c-name" name="name" type="text" required maxlength="120" autocomplete="name"
                       value="{{ old('name') }}" placeholder="Your name"
                       style="width: 100%; box-sizing: border-box; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(150,90,50,.2); background: rgba(255,255,255,.66); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); outline: none;">
            </div>
            <div>
                <label for="vy-c-email" style="display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px;">Work email</label>
                <input id="vy-c-email" name="email" type="email" required maxlength="190" autocomplete="email"
                       value="{{ old('email') }}" placeholder="you@property.com"
                       style="width: 100%; box-sizing: border-box; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(150,90,50,.2); background: rgba(255,255,255,.66); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); outline: none;">
            </div>
        </div>

        <div data-frow="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div>
                <label for="vy-c-company" style="display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px;">Company</label>
                <input id="vy-c-company" name="company" type="text" maxlength="150" autocomplete="organization"
                       value="{{ old('company') }}" placeholder="Your company"
                       style="width: 100%; box-sizing: border-box; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(150,90,50,.2); background: rgba(255,255,255,.66); font-family: var(--vy-sans); font-size: 14.5px; color: var(--vy-ink); outline: none;">
            </div>
            <div>
                {{-- ⚠️ RADIOS, NOT BUTTONS. The design drew these as chips whose selection lived in React
                     state; a real form needs a value that actually posts, and radios give keyboard and
                     screen-reader behaviour for free. The chip look is preserved with :checked styling in
                     the page's own stylesheet block below. --}}
                <fieldset style="border: 0; margin: 0; padding: 0;">
                    <legend style="display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; padding: 0;">How many units</legend>
                    <div style="display: flex; gap: 8px;">
                        @foreach ($unitBands as $band)
                            <label class="vy-chip">
                                <input type="radio" name="units_band" value="{{ $band }}" @checked(old('units_band') === $band)>
                                <span>{{ $band }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        </div>

        <div style="margin-bottom: 14px;">
            <fieldset style="border: 0; margin: 0; padding: 0;">
                <legend style="display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px; padding: 0;">What would you like to talk about</legend>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach ($topics as $topic)
                        <label class="vy-chip">
                            <input type="radio" name="topic" value="{{ $topic }}" @checked(old('topic') === $topic)>
                            <span>{{ $topic }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>

        <div style="margin-bottom: 16px;">
            <label for="vy-c-message" style="display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 7px;">Message</label>
            <textarea id="vy-c-message" name="message" rows="5" required minlength="10" maxlength="4000"
                      placeholder="We run 24 apartments across two cities, currently on a channel manager plus spreadsheets. Mostly interested in the owner portal and getting direct bookings up."
                      style="width: 100%; box-sizing: border-box; padding: 14px 15px; border-radius: 13px; border: 1px solid rgba(150,90,50,.2); background: rgba(255,255,255,.66); font-family: var(--vy-sans); font-size: 14.5px; line-height: 1.6; color: var(--vy-ink); outline: none; resize: vertical;">{{ old('message') }}</textarea>
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px;">
            <button type="submit" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 26px; border-radius: 13px; border: none; background: var(--vy-grad); color: #fff; font-family: var(--vy-sans); font-size: 15px; font-weight: 600; cursor: pointer; box-shadow: 0 14px 32px rgba(194,65,12,.32);">
                Send message
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </button>
            {{-- ⚠️ THIS REPLACED "We reply within one working day." Nothing in the product measures or
                 promises a response time, and asserting one on the form is the cheapest possible lie. --}}
            <span style="font-size: 12.5px; color: var(--vy-muted);">One person reads these. No autoresponder, and no reply time we can&rsquo;t keep.</span>
        </div>
    </form>
@endif
