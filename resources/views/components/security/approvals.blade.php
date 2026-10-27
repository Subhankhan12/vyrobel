{{-- Sign-ins waiting for THIS person's approval (login approval on), approved or denied from a device where they are already
     signed in (2026-10-24, the founder's design). Real rows only: LoginApprovalService::waiting() — each with the browser and
     IP the waiting sign-in came from, and how long ago. $route: the realm's decide route. --}}
@props(['waiting', 'route'])
@foreach ($waiting as $w)
    <div class="vy-sx-wait" role="group" aria-labelledby="vy-sx-wait-{{ $w['id'] }}">
        <div class="vy-sx-wait-top">
            <span class="vy-sx-wait-dot" aria-hidden="true"></span>
            <span class="vy-sx-wait-label" id="vy-sx-wait-{{ $w['id'] }}">{{ __('components/security/approvals.approval_needed') }}</span>
            <span class="vy-sx-wait-when">{{ $w['when'] }}</span>
        </div>
        <div class="vy-sx-row-title">{{ __('components/security/approvals.new_sign_in', ['device' => $w['device']]) }}</div>
        <div class="vy-sx-row-meta">{{ $w['ip'] !== null ? __('components/security/approvals.ip', ['ip' => $w['ip']]) : __('components/security/approvals.ip_unknown') }}</div>
        <div class="vy-sx-wait-acts">
            <form method="POST" action="{{ route($route) }}">
                @csrf
                <input type="hidden" name="approval" value="{{ $w['id'] }}">
                <input type="hidden" name="decision" value="deny">
                <button type="submit" class="vy-sx-btn vy-sx-btn--soft">{{ __('components/security/approvals.deny') }}</button>
            </form>
            <form method="POST" action="{{ route($route) }}">
                @csrf
                <input type="hidden" name="approval" value="{{ $w['id'] }}">
                <input type="hidden" name="decision" value="approve">
                <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ __('components/security/approvals.approve') }}</button>
            </form>
        </div>
    </div>
@endforeach
