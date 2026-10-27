{{-- ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS: the one audited way into an organisation's back office. Read-only, 30 minutes,
     bannered on every page, audited page by page, and the organisation is told afterwards — who, when, why. --}}
<x-admin.shell active="support" :title="__('admin/break-glass/index.title')" :subtitle="__('admin/break-glass/index.subtitle')"
    :back="route('admin.support.index')" :back-label="__('admin/break-glass/index.back_label')">


    @if ($current)
        <div class="ad-card" style="margin-bottom: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/break-glass/index.open_heading') }}</h2>
            <p class="ad-stat-sub" style="margin: 0 0 12px;">
                {{ trans_choice('admin/break-glass/index.open_summary', $current->pages_viewed, ['organization' => $current->organization?->name, 'time' => $current->expires_at->utc()->format('H:i'), 'count' => $current->pages_viewed]) }}
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a class="ad-btn ad-btn--sm" href="{{ route('app.dashboard') }}">{{ __('admin/break-glass/index.go_to_back_office') }}</a>
                <form method="POST" action="{{ route('admin.break-glass.end') }}">
                    @csrf
                    <button type="submit" class="ad-btn ad-btn--danger ad-btn--sm">{{ __('admin/break-glass/index.end_now') }}</button>
                </form>
            </div>
        </div>
    @else
        <div class="ad-card" style="margin-bottom: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/break-glass/index.start_heading') }}</h2>
            <ul class="ad-stat-sub" style="margin: 0 0 14px; padding-left: 18px; line-height: 1.7;">
                <li>{!! __('admin/break-glass/index.rule_read_only') !!}</li>
                <li>{!! trans_choice('admin/break-glass/index.rule_minutes', $minutes, ['count' => e($minutes)]) !!}</li>
                <li>{{ __('admin/break-glass/index.rule_senior_user') }}</li>
                <li>{{ __('admin/break-glass/index.rule_recorded') }}</li>
            </ul>
            <form method="POST" action="{{ route('admin.break-glass.start') }}" data-guard-submit>
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                    <div>
                        <label class="ad-label" for="bg-org">{{ __('admin/break-glass/index.organisation_label') }}</label>
                        <select id="bg-org" name="organization_id" class="ad-input" required data-searchable>
                            <option value="">{{ __('admin/break-glass/index.choose_option') }}</option>
                            @foreach ($organizations as $id => $name)
                                <option value="{{ $id }}" @selected(old('organization_id') === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ad-label" for="bg-code">{{ __('admin/break-glass/index.code_label') }}</label>
                        <input id="bg-code" class="ad-input" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="10">
                    </div>
                </div>
                <label class="ad-label" for="bg-reason" style="margin-top: 12px;">{{ __('admin/break-glass/index.reason_label') }}</label>
                <input id="bg-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" value="{{ old('reason') }}" placeholder="{{ __('admin/break-glass/index.reason_placeholder') }}">
                <button type="submit" class="ad-btn ad-btn--danger" style="margin-top: 12px;">{{ __('admin/break-glass/index.open_button') }}</button>
            </form>
        </div>
    @endif

    <div class="ad-card" style="overflow-x: auto;">
        <h2 class="ad-panel-title">{{ __('admin/break-glass/index.recent_heading') }}</h2>
        @if ($recent->isEmpty())
            <div class="ad-empty">{{ __('admin/break-glass/index.empty_state') }}</div>
        @else
            <table class="ad-table">
                <thead><tr><th>{{ __('admin/break-glass/index.col_admin') }}</th><th>{{ __('admin/break-glass/index.col_organisation') }}</th><th>{{ __('admin/break-glass/index.col_reason') }}</th><th>{{ __('admin/break-glass/index.col_started') }}</th><th>{{ __('admin/break-glass/index.col_ended') }}</th><th class="ad-num">{{ __('admin/break-glass/index.col_pages') }}</th><th>{{ __('admin/break-glass/index.col_told') }}</th></tr></thead>
                <tbody>
                    @foreach ($recent as $s)
                        <tr>
                            <td>{{ $s->platformAdmin?->name ?? '—' }}</td>
                            <td>{{ $s->organization?->name ?? '—' }}</td>
                            <td style="max-width: 280px;">{{ $s->reason }}</td>
                            <td><x-admin.time :at="$s->started_at" /></td>
                            <td>{{ $s->ended_at ? $s->ended_at->utc()->format('H:i').' · '.$s->ended_reason : ($s->isOpen() ? __('admin/break-glass/index.ended_open') : __('admin/break-glass/index.ended_due')) }}</td>
                            <td class="ad-num">{{ $s->pages_viewed }}</td>
                            <td>{{ $s->tenant_notified_at ? __('admin/break-glass/index.told_yes') : __('admin/break-glass/index.told_not_yet') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin.shell>
