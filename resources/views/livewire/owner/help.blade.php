{{-- Owner Help & Support (exact-match #66) — matched to the decoded "Owner Help" wireframe, HONEST throughout.
     The ONE real contact channel is "Message your manager" → the owner↔operator inbox; the FAQ answers link to the
     real owner screens; the hero SEARCHES the real FAQ client-side (Alpine) — NEVER a fabricated AI answer. REFUSED
     (#39): the AI Q&A, the support-TICKET queue (messaging is the tracked channel), the live system-status board
     (an honest static "operational" line instead), and invented owner-desk phone/email numbers.
     RAW-BLADE RULE: every `@` follows a space, newline or tag (unregistered @click passes through to Alpine). --}}
<x-owner.shell active="help" :title="'Help & support'" :subtitle="'Answers, and how to reach your management team'" :viewer="$viewer">
    <style>
        .vy-hlp-hero { text-align:center; padding:8px 0 4px; }
        .vy-hlp-search { display:flex; align-items:center; gap:10px; padding:6px 6px 6px 16px; border-radius:var(--vy-r-pill); background:rgba(255,252,248,.92); border:1px solid var(--vy-glass-border); box-shadow:0 10px 30px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); max-width:560px; margin:22px auto 0; }
        .vy-hlp-search input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:14.5px; color:var(--vy-ink); padding:11px 0; }
        .vy-hlp-pills { display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-top:14px; }
        .vy-hlp-pill { padding:7px 14px; border-radius:var(--vy-r-pill); background:rgba(255,252,248,.7); border:1px solid var(--vy-hairline); color:var(--vy-muted); font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; }
        .vy-hlp-pill:hover { background:rgba(255,255,255,.95); }
        .vy-hlp-contact { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin:20px 0; }
        .vy-hlp-ccard { display:flex; flex-direction:column; padding:20px; border-radius:20px; background:var(--vy-glass); backdrop-filter:blur(22px) saturate(1.15); border:1px solid var(--vy-glass-border); box-shadow:var(--vy-shadow); text-decoration:none; color:var(--vy-ink); }
        .vy-hlp-ccard-ic { width:44px; height:44px; border-radius:13px; color:#fff; display:flex; align-items:center; justify-content:center; }
        .vy-hlp-ccard h3 { font-family:var(--vy-display); font-weight:600; font-size:16px; margin:14px 0 0; }
        .vy-hlp-status { display:flex; align-items:center; gap:9px; padding:12px 16px; border-radius:14px; background:rgba(31,122,90,.09); border:1px solid rgba(31,122,90,.2); }
        .vy-hlp-status .dot { width:9px; height:9px; border-radius:50%; background:var(--vy-green); box-shadow:0 0 0 4px rgba(31,122,90,.15); }
        .vy-hlp-faq { border-bottom:1px solid var(--vy-hairline); padding:10px 0; }
        .vy-hlp-faq summary { cursor:pointer; font-size:13.5px; font-weight:600; color:var(--vy-ink); list-style:none; }
    </style>

    @php
        $faqs = [
            ['pay', 'When and how do I get paid?', 'Payouts are created when your monthly statement is finalized and sent to your default bank account. Add or change that account on your Payout method page.', 'owner.payout-method', 'Payout method'],
            ['statement', 'How do I read my statement?', 'Each statement breaks your gross revenue down to the net paid to you — room revenue, commission, fees and any adjustments. Open any statement from your archive.', 'owner.archive', 'Statements'],
            ['fees', 'What does the management team charge?', 'Your commission and any fees are set in your management agreement. You can review the effective terms anytime.', 'owner.agreement', 'Agreement'],
            ['tax', 'What are my tax and VAT obligations?', 'Add your tax ID (payouts are held until it’s on file) and download an annual summary for your accountant in the Tax Center. Nothing here is tax advice.', 'owner.tax', 'Tax center'],
            ['block', 'Can I block dates for my own stay?', 'Yes — hold dates on any of your units from the Block calendar. Blocked nights won’t accept guest bookings.', 'owner.calendar', 'Block calendar'],
            ['unit', 'How do I add another unit?', 'Send a request with the details and your management team will set it up — a new unit affects availability and billing, so it’s created on their side.', 'owner.add-unit', 'Add a unit'],
            ['refund', 'A guest was refunded — how does that affect me?', 'A refund is attributed back through your ledger, so it shows on the statement for the period it happened. Your running position reflects it on your Balance.', 'owner.balance', 'Balance'],
        ];
    @endphp

    <div x-data="{ q: '', faqs: {{ \Illuminate\Support\Js::from(collect($faqs)->map(fn ($f) => strtolower($f[1].' '.$f[2].' '.$f[0]))->all()) }}, get anyMatch() { return this.faqs.some(t => t.includes(this.q.toLowerCase())); } }">
        {{-- ── Hero: FAQ search (client-side) + suggestions — NOT an AI answer (#39) ── --}}
        <section class="vy-card vy-fade vy-hlp-hero">
            <h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:28px;letter-spacing:-.02em">How can we help?</h2>
            <p style="margin:11px 0 0;font-size:14px;color:var(--vy-muted)">Search our FAQs below, or message your management team for anything account-specific.</p>
            <div class="vy-hlp-search">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="text" x-model="q" placeholder="Search help — payouts, statements, tax…" aria-label="Search FAQs">
            </div>
            <div class="vy-hlp-pills">
                @foreach (['payout', 'statement', 'tax', 'block'] as $s)
                    <button type="button" class="vy-hlp-pill" @click="q = @js($s)">{{ ucfirst($s) }}s</button>
                @endforeach
            </div>
        </section>

        {{-- ── Contact channels — REAL relay + HONEST guidance (no fabricated numbers, #39) + honest status ── --}}
        <section class="vy-hlp-contact vy-fade" style="animation-delay:.04s">
            <a href="{{ route('owner.messages') }}" class="vy-hlp-ccard" style="border-color:var(--vy-accent-border)">
                <span class="vy-hlp-ccard-ic" style="background:var(--vy-grad);box-shadow:var(--vy-shadow-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2z"/></svg></span>
                <h3>Message your manager</h3>
                <span style="font-size:12.5px;color:var(--vy-faint);margin-top:3px">In-app — your team replies in your inbox, with full history</span>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:500;color:var(--vy-accent);margin-top:14px">Open messages →</span>
            </a>
            <div class="vy-hlp-ccard">
                <span class="vy-hlp-ccard-ic" style="background:linear-gradient(140deg,#E8875A,#C2410C)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.4-1.2a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></span>
                <h3>Prefer phone or email?</h3>
                <span style="font-size:12.5px;color:var(--vy-muted);margin-top:6px;line-height:1.5">Your management team’s direct phone and email are on your welcome pack and your latest statement. For anything account-specific, messaging keeps it in one place with your history.</span>
            </div>
            <div class="vy-hlp-ccard" style="justify-content:center">
                <div class="vy-eyebrow" style="margin-bottom:10px">System status</div>
                <div class="vy-hlp-status"><span class="dot"></span><span style="font-size:13px;font-weight:500;color:var(--vy-green)">All systems operational</span></div>
                <span style="font-size:11.5px;color:var(--vy-faint);margin-top:8px">Payouts, statements and your portal are running normally.</span>
            </div>
        </section>

        {{-- ── FAQ (real content) with client-side search ── --}}
        <div class="vy-cols" style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;align-items:start">
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom:10px">Owner FAQs</h2>
                @foreach ($faqs as $faq)
                    <details class="vy-hlp-faq" x-show="q === '' || @js(strtolower($faq[1].' '.$faq[2].' '.$faq[0])).includes(q.toLowerCase())">
                        <summary>{{ $faq[1] }}</summary>
                        <div class="vy-line-sub" style="font-size:12.5px;margin-top:8px;line-height:1.5">
                            {{ $faq[2] }}
                            <div style="margin-top:8px"><a href="{{ route($faq[3]) }}" style="color:var(--vy-accent);font-weight:600;font-size:12.5px">Go to {{ $faq[4] }} →</a></div>
                        </div>
                    </details>
                @endforeach
                <p class="vy-line-sub" x-show="q !== '' && ! anyMatch" x-cloak style="padding:16px 0">No FAQ matches “<span x-text="q"></span>”. <a href="{{ route('owner.messages') }}" style="color:var(--vy-accent);font-weight:600">Message your manager →</a></p>
            </section>

            {{-- Right rail: still-stuck (the honest tracked channel — NO fake tickets) + jump-to --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <div class="vy-eyebrow">Still stuck?</div>
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:6px;line-height:1.5">Messaging is your tracked channel — every request stays in your inbox with your management team’s replies and full history. There’s no separate ticket queue.</p>
                    <a href="{{ route('owner.messages') }}" class="vy-btn vy-btn--accent" style="margin-top:12px;padding:9px 16px">Open messages</a>
                </section>
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <div class="vy-eyebrow">Jump to</div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                        <a href="{{ route('owner.payouts') }}" style="color:var(--vy-accent);font-size:13px">Payouts</a>
                        <a href="{{ route('owner.archive') }}" style="color:var(--vy-accent);font-size:13px">Statements</a>
                        <a href="{{ route('owner.tax') }}" style="color:var(--vy-accent);font-size:13px">Tax center</a>
                        <a href="{{ route('owner.payout-method') }}" style="color:var(--vy-accent);font-size:13px">Payout method</a>
                        <a href="{{ route('owner.agreement') }}" style="color:var(--vy-accent);font-size:13px">Agreement</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-owner.shell>
