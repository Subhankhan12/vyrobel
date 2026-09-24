{{-- ⚠️ ADMIN 01 · PHASE 5 — THE PUBLIC STATUS PAGE'S INCIDENTS, written by hand. ⚠️ EVERYTHING POSTED HERE IS PUBLIC. --}}
@php
    use App\Models\StatusIncident;
    $me = auth('admin')->user();
    $canPost = $me?->may('tenants.manage') ?? false;
@endphp
<x-admin.shell active="status" title="Status page" subtitle="Incidents on the public status page — posted by hand. Nothing internal ever reaches it.">


    <div class="ad-card" style="margin-bottom: 18px;">
        <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">
            ⚠️ <strong>Everything written here is public</strong> at <a class="ad-cell-link" href="{{ route('status') }}" target="_blank" rel="noopener">{{ route('status') }}</a>.
            The page shows only what is posted: with nothing posted it says “No incidents have been posted” — never “all systems operational”.
        </p>
    </div>

    <div class="ad-card" style="overflow-x: auto;">
        @if ($incidents->isEmpty())
            <div class="ad-empty">No incident has ever been posted.</div>
        @else
            @foreach ($incidents as $i)
                <div style="padding: 12px 0; border-bottom: 1px solid var(--ad-border);">
                    <strong>{{ $i->title }}</strong> · {{ StatusIncident::IMPACTS[$i->impact] ?? $i->impact }} · {{ StatusIncident::STATUSES[$i->status] ?? $i->status }}
                    <div style="font-size: 12px; color: var(--ad-muted);">Started <x-admin.time :at="$i->started_at" />@if ($i->resolved_at) · resolved <x-admin.time :at="$i->resolved_at" />@endif</div>
                    <p class="ad-stat-sub" style="margin: 6px 0;">{{ $i->message }}</p>
                    @if ($canPost && $i->isOpen())
                        <form method="POST" action="{{ route('admin.status.update', $i->id) }}" data-guard-submit style="display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 8px;">
                            @csrf
                            <select aria-label="Status" name="status" class="ad-input">@foreach (StatusIncident::STATUSES as $k => $label)<option value="{{ $k }}" @selected($i->status === $k)>{{ $label }}</option>@endforeach</select>
                            <input aria-label="Public message" class="ad-input" name="message" required maxlength="2000" placeholder="Public update">
                            <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Internal reason (audit log)" style="grid-column: 1 / -1;">
                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm" style="grid-column: 1 / -1; justify-self: start;">Post update</button>
                        </form>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    @if ($canPost)
        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">Post an incident</h2>
            <form method="POST" action="{{ route('admin.status.store') }}" data-guard-submit>
                @csrf
                <label class="ad-label" for="si-title">Public title</label>
                <input id="si-title" class="ad-input" name="title" required maxlength="120">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                    <div><label class="ad-label" for="si-impact">Impact</label><select id="si-impact" name="impact" class="ad-input">@foreach (StatusIncident::IMPACTS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="si-status">Status</label><select id="si-status" name="status" class="ad-input">@foreach (['investigating', 'identified', 'monitoring'] as $k)<option value="{{ $k }}">{{ StatusIncident::STATUSES[$k] }}</option>@endforeach</select></div>
                </div>
                <label class="ad-label" for="si-msg" style="margin-top: 10px;">Public message</label>
                <textarea id="si-msg" class="ad-input" name="message" required maxlength="2000" rows="3"></textarea>
                <label class="ad-label" for="si-reason" style="margin-top: 10px;">Internal reason (audit log — not public)</label>
                <input id="si-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500">
                <button type="submit" class="ad-btn" style="margin-top: 12px;">Post publicly</button>
            </form>
        </div>
    @endif
</x-admin.shell>
