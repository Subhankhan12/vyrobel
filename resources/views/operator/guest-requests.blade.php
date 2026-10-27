{{-- Operator: guest change requests (Phase 4b · SG3) — the confirm/decline side. Status only; the real change is
     applied with the operator's own tools. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator :title="__('operator/guest-requests.title')">
<x-operator.shell active="guest-requests" :title="__('operator/guest-requests.title')" :subtitle="__('operator/guest-requests.subtitle')">

    @if (session('status'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('status') }}</div>
        </div>
    @endif

    <p class="vy-line-sub" style="font-size:12.5px;margin-bottom:16px;color:var(--vy-muted)">
        {{ __('operator/guest-requests.intro') }}
    </p>

    <section class="vy-card vy-fade">
        @forelse ($requests as $req)
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:13px 4px;border-bottom:1px solid var(--vy-hairline);flex-wrap:wrap">
                <div>
                    <div style="font-size:14px;font-weight:700;color:var(--vy-ink)">{{ $req['type'] }} · {{ $req['guest'] }}</div>
                    <div style="font-size:12.5px;color:var(--vy-muted);margin-top:1px">{{ $req['detail'] }}</div>
                    <div style="font-size:11px;color:var(--vy-faint);margin-top:2px">{{ __('operator/guest-requests.booking_requested', ['reference' => $req['reference'], 'requested_at' => $req['at']]) }}</div>
                </div>
                <div style="display:flex;gap:7px;flex:none">
                    <form method="POST" action="{{ route('app.guest-requests.confirm', $req['id']) }}" style="margin:0">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 14px;font-size:12.5px">{{ __('common.confirm') }}</button>
                    </form>
                    <form method="POST" action="{{ route('app.guest-requests.decline', $req['id']) }}" style="margin:0">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:7px 14px;font-size:12.5px">{{ __('operator/guest-requests.decline') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="vy-empty">{{ __('operator/guest-requests.empty_state') }}</div>
        @endforelse
    </section>

</x-operator.shell>
</x-layouts.operator>
