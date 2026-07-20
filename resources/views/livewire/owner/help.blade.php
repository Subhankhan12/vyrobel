{{-- Owner Help & Support (Phase 2 · Sub-gate 6). STATIC help: FAQ answers linking to the real owner screens +
     the ONE real contact route (Message your manager → the owner↔operator inbox). No faked AI / ticket queue /
     status board (those are unbuilt subsystems). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="help" :title="'Help & support'" :subtitle="'Answers and how to reach us'" :viewer="$viewer">

    {{-- Real contact — the in-app owner↔operator inbox (Wave 3) --}}
    <section class="vy-card vy-card--accent vy-fade" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
        <div>
            <div style="font-family:var(--vy-display);font-weight:700;font-size:15px">Message your manager</div>
            <div class="vy-line-sub" style="font-size:12.5px;margin-top:4px">The quickest way to reach us — send a message and your management team replies in your inbox.</div>
        </div>
        <a href="{{ route('owner.messages') }}" class="vy-btn vy-btn--accent" style="padding:10px 18px">Open messages</a>
    </section>

    <div class="vy-cols" style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;align-items:start">

        {{-- LEFT — FAQ, each answer links to the real screen --}}
        <section class="vy-card vy-fade">
            <h2 class="vy-card-title" style="margin-bottom:10px">Owner FAQs</h2>

            @php
                $faqs = [
                    ['When and how do I get paid?', 'Payouts are created when your monthly statement is finalized and sent to your default bank account. Add or change that account on your Payout method page.', 'owner.payout-method', 'Payout method'],
                    ['How do I read my statement?', 'Each statement breaks your gross revenue down to the net paid to you — room revenue, commission, fees and any adjustments. Open any statement from your archive.', 'owner.archive', 'Statements'],
                    ['What does the management team charge?', 'Your commission and any fees are set in your management agreement. You can review the effective terms anytime.', 'owner.agreement', 'Agreement'],
                    ['What are my tax and VAT obligations?', 'Add your tax ID (payouts are held until it’s on file) and download an annual summary for your accountant in the Tax Center. Nothing here is tax advice.', 'owner.tax', 'Tax center'],
                    ['Can I block dates for my own stay?', 'Yes — hold dates on any of your units from the Block calendar. Blocked nights won’t accept guest bookings.', 'owner.calendar', 'Block calendar'],
                    ['How do I add another unit?', 'Send a request with the details and your management team will set it up — a new unit affects availability and billing, so it’s created on their side.', 'owner.add-unit', 'Add a unit'],
                    ['A guest was refunded — how does that affect me?', 'A refund is attributed back through your ledger, so it shows on the statement for the period it happened. Your running position reflects it on your Balance.', 'owner.balance', 'Balance'],
                ];
            @endphp

            @foreach ($faqs as $faq)
                <details style="border-bottom:1px solid var(--vy-hairline);padding:10px 0">
                    <summary style="cursor:pointer;font-size:13.5px;font-weight:600;color:var(--vy-ink);list-style:none">{{ $faq[0] }}</summary>
                    <div class="vy-line-sub" style="font-size:12.5px;margin-top:8px;line-height:1.5">
                        {{ $faq[1] }}
                        <div style="margin-top:8px"><a href="{{ route($faq[2]) }}" style="color:var(--vy-accent);font-weight:600;font-size:12.5px">Go to {{ $faq[3] }} →</a></div>
                    </div>
                </details>
            @endforeach
        </section>

        {{-- RIGHT — quick links + honest note --}}
        <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <div class="vy-eyebrow">Jump to</div>
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                    <a href="{{ route('owner.payouts') }}" class="vy-line-sub" style="color:var(--vy-accent);font-size:13px">Payouts</a>
                    <a href="{{ route('owner.archive') }}" class="vy-line-sub" style="color:var(--vy-accent);font-size:13px">Statements</a>
                    <a href="{{ route('owner.tax') }}" class="vy-line-sub" style="color:var(--vy-accent);font-size:13px">Tax center</a>
                    <a href="{{ route('owner.payout-method') }}" class="vy-line-sub" style="color:var(--vy-accent);font-size:13px">Payout method</a>
                    <a href="{{ route('owner.agreement') }}" class="vy-line-sub" style="color:var(--vy-accent);font-size:13px">Agreement</a>
                </div>
            </section>

            <section class="vy-card vy-fade" style="animation-delay:.06s">
                <div class="vy-eyebrow">Prefer to talk?</div>
                <p class="vy-line-sub" style="font-size:12px;margin-top:6px">Your management team’s phone and email are on your welcome pack and your latest statement. For anything account-specific, messaging keeps it all in one place with your history.</p>
            </section>
        </div>
    </div>
</x-owner.shell>
