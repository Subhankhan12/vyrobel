{{-- Email Previews (#109) — the tabbed gallery, matched to "Email Previews.html". Each tab renders the REAL
     Mailable via the app.email-previews.show route inside an <iframe> (email-CSS isolation; can't drift from the
     send). The Send-test goes through the certified own-email path (EmailPreviewController::testSend → the
     operator's OWN inbox only, MailSafetyGuard). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator title="Email previews">
<x-operator.shell active="email-previews" title="Email previews" subtitle="Switch between the automated guest emails">

    @if (session('email_preview_notice'))
        <div class="vy-flash is-ok vy-fade" role="status">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
            {{ session('email_preview_notice') }}
        </div>
    @endif

    <div x-data="{ tab: 'booking-confirmation', statuses: {{ \Illuminate\Support\Js::from(collect($catalog)->map(fn ($e) => $e['status'])) }} }">

        {{-- Send-test — the recipient is LOCKED to the operator's own inbox (the certified rule; never an arbitrary
             address). Gated @can(properties.manage) so a viewer who can't send never sees a 403-ing button (#30). --}}
        <div class="vy-card vy-fade" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
            <div style="font-size:12.5px;color:var(--vy-muted);">Previews render this org’s latest booking. @can('properties.manage')A test sends the real email to <strong style="color:var(--vy-ink);">your own inbox</strong> — never a guest.@else Nothing is sent from here.@endcan</div>
            @can('properties.manage')
                <form method="POST" :action="'{{ url('app/email-previews') }}/' + tab + '/test'" style="display:flex;align-items:center;gap:8px;margin:0;">
                    @csrf
                    <input type="email" value="{{ $operatorEmail }}" readonly aria-label="Test recipient (you)" class="vy-input" style="min-width:200px;background:var(--vy-wash);color:var(--vy-muted);">
                    <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12.5px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                        Send test
                    </button>
                </form>
            @endcan
        </div>

        {{-- Tab switcher — the 9 automated guest emails (reuses the preview catalog). --}}
        <div class="vy-fade" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
            @foreach ($catalog as $key => $email)
                <button type="button" @click="tab = '{{ $key }}'"
                        :style="tab === '{{ $key }}' ? 'background:var(--vy-accent);color:#fff;border-color:var(--vy-accent)' : 'background:var(--vy-card,#fff);color:var(--vy-muted)'"
                        class="vy-btn vy-btn--ghost" style="padding:6px 14px;font-size:12px;">{{ $email['title'] }}</button>
            @endforeach
        </div>

        {{-- Honest per-email status caption (the wireframe drops these; kept as an honest signal). --}}
        <div class="vy-fade" style="font-size:11.5px;color:var(--vy-faint);margin-bottom:12px;" x-text="statuses[tab]"></div>

        {{-- Inline preview — the REAL Mailable, isolated in an iframe so its email CSS can't leak into /app. --}}
        <div class="vy-card vy-fade" style="padding:0;overflow:hidden;">
            <iframe :src="'{{ url('app/email-previews') }}/' + tab" title="Email preview" style="width:100%;height:900px;border:0;display:block;background:#F6EBE2;"></iframe>
        </div>
    </div>

</x-operator.shell>
</x-layouts.operator>
