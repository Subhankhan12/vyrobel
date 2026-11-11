{{-- ADMIN 01 · PHASE 5 — IN-APP ANNOUNCEMENTS to every tenant or chosen ones. Publishing and withdrawing are audited with
     a reason; tenants dismiss them for themselves. Plain text, shown escaped. --}}
@php
    use App\Models\PlatformAnnouncement;
    $me = auth('admin')->user();
    $canPublish = $me?->may('tenants.manage') ?? false;
@endphp
<x-admin.shell active="announcements" :title="__('admin/announcements.title')" :subtitle="__('admin/announcements.subtitle')">


    <div class="ad-card" style="overflow-x: auto;">
        @if ($announcements->isEmpty())
            <div class="ad-empty">{{ __('admin/announcements.empty_state') }}</div>
        @else
            <table class="ad-table">
                <thead><tr><th>{{ __('admin/announcements.col_announcement') }}</th><th>{{ __('admin/announcements.col_to') }}</th><th>{{ __('admin/announcements.col_from_until') }}</th><th>{{ __('admin/announcements.col_state') }}</th>@if ($canPublish)<th></th>@endif</tr></thead>
                <tbody>
                    @foreach ($announcements as $a)
                        <tr>
                            <td style="max-width: 360px;"><strong>{{ $a->title }}</strong><div style="font-size: 12px; color: var(--ad-muted);">{{ \Illuminate\Support\Str::limit($a->body, 140) }}</div></td>
                            <td>{{ $a->audience === 'all' ? __('admin/announcements.audience_all') : __('admin/announcements.audience_chosen_count', ['number' => count((array) $a->organization_ids)]) }}</td>
                            <td><x-admin.time :at="$a->starts_at" /> <span class="vy-flip-rtl">→</span> <x-admin.time :at="$a->ends_at" :empty="__('admin/announcements.no_end')" /></td>
                            <td>{{ $a->withdrawn_at ? __('admin/announcements.state_withdrawn') : ($a->isLive() ? __('admin/announcements.state_showing') : ($a->starts_at->isFuture() ? __('admin/announcements.state_scheduled') : __('admin/announcements.state_ended'))) }}</td>
                            @if ($canPublish)
                                <td>
                                    @if (! $a->withdrawn_at && ($a->isLive() || $a->starts_at->isFuture()))
                                        <form method="POST" action="{{ route('admin.announcements.withdraw', $a->id) }}" data-guard-submit style="display: flex; gap: 6px;">
                                            @csrf
                                            <input aria-label="{{ __('admin/announcements.reason_aria') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/announcements.withdraw_placeholder') }}" style="margin: 0; min-width: 160px;">
                                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('admin/announcements.withdraw_button') }}</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($canPublish)
        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/announcements.publish_heading') }}</h2>
            <form method="POST" action="{{ route('admin.announcements.store') }}" data-guard-submit>
                @csrf
                <label class="ad-label" for="an-title">{{ __('admin/announcements.title_label') }}</label>
                <input id="an-title" class="ad-input" name="title" required maxlength="120" value="{{ old('title') }}">
                <label class="ad-label" for="an-body" style="margin-top: 10px;">{{ __('admin/announcements.message_label') }}</label>
                <textarea id="an-body" class="ad-input" name="body" required maxlength="1000" rows="3">{{ old('body') }}</textarea>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                    <div><label class="ad-label" for="an-level">{{ __('admin/announcements.kind_label') }}</label><select id="an-level" name="level" class="ad-input">@foreach (array_keys(PlatformAnnouncement::LEVELS) as $k)<option value="{{ $k }}" @selected(old('level') === $k)>{{ PlatformAnnouncement::levelLabel($k) }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="an-start">{{ __('admin/announcements.show_from_label') }}</label><input id="an-start" type="datetime-local" name="starts_at" class="ad-input" required value="{{ old('starts_at', now()->utc()->format('Y-m-d\TH:i')) }}"></div>
                    <div><label class="ad-label" for="an-end">{{ __('admin/announcements.until_label') }}</label><input id="an-end" type="datetime-local" name="ends_at" class="ad-input" value="{{ old('ends_at') }}"></div>
                    <div><label class="ad-label" for="an-aud">{{ __('admin/announcements.to_label') }}</label><select id="an-aud" name="audience" class="ad-input"><option value="all" @selected(old('audience', 'all') === 'all')>{{ __('admin/announcements.option_every_organisation') }}</option><option value="chosen" @selected(old('audience') === 'chosen')>{{ __('admin/announcements.option_chosen_organisations') }}</option></select></div>
                </div>
                <label class="ad-label" for="an-orgs" style="margin-top: 10px;">{{ __('admin/announcements.chosen_organisations_label') }}</label>
                <select id="an-orgs" name="organization_ids[]" class="ad-input" multiple size="5" data-searchable data-placeholder="{{ __('admin/announcements.no_organisation_chosen') }}">@foreach ($organizations as $id => $name)<option value="{{ $id }}" @selected(in_array((string) $id, array_map('strval', (array) old('organization_ids', [])), true))>{{ $name }}</option>@endforeach</select>
                <label class="ad-label" for="an-reason" style="margin-top: 10px;">{{ __('admin/announcements.reason_label') }}</label>
                <input id="an-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/announcements.publish_reason_placeholder') }}">
                <button type="submit" class="ad-btn" style="margin-top: 12px;">{{ __('admin/announcements.publish_button') }}</button>
            </form>
        </div>
    @endif
</x-admin.shell>
