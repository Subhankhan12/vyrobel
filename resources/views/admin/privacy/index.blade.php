{{-- ⚠️ ADMIN 01 · PHASE 4 — THE PRIVACY DESK. The live GDPR queue (the existing erasure machinery, watched) · the request
     register (tracked; fulfilled by the EXISTING mechanisms) · retention coverage (three buckets — the founder's ruling, DEC-4)
     · the sub-processor register. ⚠️ No guest is ever shown here. It makes the lawyer's job shorter; it does not replace it. --}}
@php
    use App\Models\PrivacyRequest;
    $me = auth('admin')->user();
    $canRecord = $me?->may('support.use') ?? false;
@endphp
<x-admin.shell active="privacy" :title="__('admin/privacy/index.title')" :subtitle="__('admin/privacy/index.subtitle')">


    {{-- ── 1 · The live erasure queue ─────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 class="ad-panel-title" style="margin: 0;">{{ __('admin/privacy/index.erasure_queue_heading') }}</h2>
            @if ($live['overdue'] > 0)
                <span class="ad-hpill ad-hpill--red">{{ trans_choice('admin/privacy/index.overdue_pill', $live['overdue'], ['count' => $live['overdue']]) }}</span>
            @else
                <span class="ad-hpill ad-hpill--green">{{ __('admin/privacy/index.nothing_overdue') }}</span>
            @endif
        </div>
        <p class="ad-stat-sub" style="margin: 8px 0 12px; line-height: 1.6;">
            {!! __('admin/privacy/index.erasure_intro', ['days' => e(\App\Services\GuestDeletionService::GRACE_DAYS)]) !!}
        </p>
        <div class="ad-row"><span class="ad-row-k">{{ __('admin/privacy/index.waiting_for_date') }}</span><span class="ad-row-v">{{ number_format($live['pending'] - $live['overdue']) }}</span></div>
        <div class="ad-row"><span class="ad-row-k">{{ __('admin/privacy/index.past_date_not_erased') }}</span><span class="ad-row-v">{{ number_format($live['overdue']) }}</span></div>
        {{-- ⚠️ ADMIN 03 · F3 — `?->format(…).' UTC' ?? '—'` printed a lone "UTC" when nothing was due: the null-safe call
             returns null, the concatenation turns it into " UTC", and `??` never fires. --}}
        <div class="ad-row"><span class="ad-row-k">{{ __('admin/privacy/index.next_due') }}</span><span class="ad-row-v">{{ $live['next_due'] ? __('admin/privacy/index.next_due_utc', ['datetime' => $live['next_due']->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M Y, H:i'))]) : __('admin/privacy/index.nothing_scheduled') }}</span></div>
        <div class="ad-row"><span class="ad-row-k">{{ __('admin/privacy/index.erased_last_30_days') }}</span><span class="ad-row-v">{{ number_format($live['erased_30d']) }}</span></div>
        @foreach ($live['by_org'] as $o)
            <div class="ad-row"><span class="ad-row-k">{{ $o['internal'] ? __('admin/privacy/index.org_internal', ['organization' => $o['organization']]) : $o['organization'] }}</span><span class="ad-row-v">{{ $o['overdue'] ? __('admin/privacy/index.org_pending_overdue', ['pending' => $o['pending'], 'overdue' => $o['overdue']]) : __('admin/privacy/index.org_pending', ['pending' => $o['pending']]) }}</span></div>
        @endforeach
        <p class="ad-reason-hint" style="margin-top: 10px;">{{ __('admin/privacy/index.owners_request_note') }}</p>
    </div>

    {{-- ── 2 · The request register ───────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">{{ __('admin/privacy/index.requests_heading') }}</h2>
        <p class="ad-stat-sub" style="margin: -6px 0 12px; line-height: 1.6;">
            {!! __('admin/privacy/index.requests_intro') !!}
        </p>
        @if ($requests->isEmpty())
            <div class="ad-empty">{{ __('admin/privacy/index.no_requests') }}</div>
        @else
            <table class="ad-table">
                <thead><tr><th>{{ __('admin/privacy/index.col_request') }}</th><th>{{ __('admin/privacy/index.about') }}</th><th>{{ __('admin/privacy/index.col_received') }}</th><th>{{ __('admin/privacy/index.col_due') }}</th><th>{{ __('common.status') }}</th>@if ($canRecord)<th></th>@endif</tr></thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td>{{ PrivacyRequest::kindLabel((string) $req->kind) }}<div style="font-size: 12px; color: var(--ad-faint);">{{ PrivacyRequest::channelLabel((string) $req->channel) }}</div></td>
                            <td>{{ $req->subject_kind === 'account_holder' ? ($req->subjectUser?->name ?? __('admin/privacy/index.an_account_holder')) : __('admin/privacy/index.guest_of', ['organization' => $req->organization?->name ?? __('admin/privacy/index.an_organisation')]) }}</td>
                            <td><x-admin.time :at="$req->received_at" date /></td>
                            <td style="{{ $req->isOpen() && $req->due_at->isPast() ? 'color: var(--ad-danger); font-weight: 600;' : '' }}"><x-admin.time :at="$req->due_at" date /></td>
                            <td>{{ PrivacyRequest::statusLabel((string) $req->status) }}</td>
                            @if ($canRecord)
                                <td>
                                    @if ($req->isOpen())
                                        <form method="POST" action="{{ route('admin.privacy.update', $req->id) }}" data-guard-submit style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            @csrf
                                            <select aria-label="{{ __('admin/privacy/index.new_status_label') }}" name="status" class="ad-input" style="margin: 0; width: auto;">
                                                @foreach (['in_progress', 'completed', 'refused'] as $k)
                                                    @if ($k !== $req->status)<option value="{{ $k }}">{{ PrivacyRequest::statusLabel($k) }}</option>@endif
                                                @endforeach
                                            </select>
                                            <input aria-label="{{ __('admin/privacy/index.reason') }}" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/privacy/index.what_was_done_placeholder') }}" style="margin: 0; min-width: 180px;">
                                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('admin/privacy/index.update_button') }}</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <details style="margin-top: 14px;">
            <summary style="cursor: pointer; font-weight: 600; font-size: 13px;">{{ __('admin/privacy/index.how_fulfilled_summary') }}</summary>
            <ul class="ad-stat-sub" style="margin: 10px 0 0; padding-left: 18px; line-height: 1.7;">
                <li>{!! __('admin/privacy/index.fulfil_hotel_guest', ['days' => e(\App\Services\GuestDeletionService::GRACE_DAYS)]) !!}</li>
                <li>{!! __('admin/privacy/index.fulfil_owner') !!}</li>
                <li>{!! __('admin/privacy/index.fulfil_staff') !!}</li>
                <li>{!! __('admin/privacy/index.fulfil_any_account') !!}</li>
            </ul>
        </details>

        @if ($canRecord)
            <form method="POST" action="{{ route('admin.privacy.store') }}" data-guard-submit style="margin-top: 16px; border-top: 1px solid var(--ad-border); padding-top: 14px;">
                @csrf
                <h3 class="ad-label" style="margin-bottom: 10px;">{{ __('admin/privacy/index.record_heading') }}</h3>
                {{-- ADMIN 03 · F19 — every field has a visible label (the placeholders were doing that job and were cut off at
                     desktop width); the grid's columns are wide enough for the longest option. --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px 12px;">
                    <div><label class="ad-label" for="pr-kind">{{ __('admin/privacy/index.kind_label') }}</label><select id="pr-kind" name="kind" class="ad-input" required>@foreach (array_keys(PrivacyRequest::KINDS) as $k)<option value="{{ $k }}">{{ PrivacyRequest::kindLabel($k) }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="pr-about">{{ __('admin/privacy/index.about') }}</label><select id="pr-about" name="subject_kind" class="ad-input" required>@foreach (array_keys(PrivacyRequest::SUBJECTS) as $k)<option value="{{ $k }}">{{ PrivacyRequest::subjectLabel($k) }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="pr-channel">{{ __('admin/privacy/index.received_by_label') }}</label><select id="pr-channel" name="channel" class="ad-input" required>@foreach (array_keys(PrivacyRequest::CHANNELS) as $k)<option value="{{ $k }}">{{ PrivacyRequest::channelLabel($k) }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="pr-on">{{ __('admin/privacy/index.received_on_label') }}</label><input id="pr-on" type="date" name="received_on" class="ad-input" required value="{{ now()->toDateString() }}"></div>
                    <div><label class="ad-label" for="pr-email">{{ __('admin/privacy/index.email_not_stored_label') }}</label><input id="pr-email" type="email" name="email" class="ad-input"></div>
                    <div><label class="ad-label" for="pr-org">{{ __('admin/privacy/index.organisation_for_guest_label') }}</label><select data-searchable id="pr-org" name="organization_id" class="ad-input"><option value="">{{ __('common.none') }}</option>@foreach ($organizations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                </div>
                <label class="ad-label" for="pr-reason" style="margin-top: 10px;">{{ __('admin/privacy/index.reason') }}</label>
                <input id="pr-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/privacy/index.reason_placeholder') }}">
                <button type="submit" class="ad-btn ad-btn--sm" style="margin-top: 10px;">{{ __('admin/privacy/index.record_button') }}</button>
            </form>
        @endif
    </div>

    {{-- ── 3 · Retention coverage — DEC-4 (2026-11-13): THREE BUCKETS, not one ratio ─────────────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 class="ad-panel-title" style="margin: 0;">{{ __('admin/privacy/index.retention_heading') }}</h2>
            <span class="ad-hpill ad-hpill--green">{{ __('admin/privacy/index.ruled_pill') }}</span>
        </div>
        <p class="ad-stat-sub" style="margin: 8px 0 12px; line-height: 1.6;">
            {!! __('admin/privacy/index.retention_intro') !!}
        </p>
        @if ($coverage['measurable'])
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/privacy/index.categories_counted') }}</span><span class="ad-row-v">{{ $coverage['categories'] }}</span></div>
            <div class="ad-row" data-bucket="pruned"><span class="ad-row-k">{{ __('admin/privacy/index.bucket_pruned') }}</span><span class="ad-row-v">{{ __('admin/privacy/index.part_of_total', ['part' => $coverage['buckets']['pruned'], 'total' => $coverage['categories']]) }}</span></div>
            <div class="ad-row" data-bucket="kept"><span class="ad-row-k">{{ __('admin/privacy/index.bucket_kept') }}</span><span class="ad-row-v">{{ __('admin/privacy/index.part_of_total', ['part' => $coverage['buckets']['kept'], 'total' => $coverage['categories']]) }}</span></div>
            @if ($coverage['buckets']['kept'] === 0)
                <p class="ad-reason-hint" style="margin: 2px 0 6px;">{{ __('admin/privacy/index.bucket_kept_empty') }}</p>
            @endif
            <div class="ad-row" data-bucket="none"><span class="ad-row-k">{{ __('admin/privacy/index.bucket_none') }}</span><span class="ad-row-v">{{ __('admin/privacy/index.part_of_total', ['part' => $coverage['buckets']['none'], 'total' => $coverage['categories']]) }}</span></div>
            <p class="ad-reason-hint" style="margin: 2px 0 6px;">{{ __('admin/privacy/index.bucket_none_partly', ['count' => $coverage['buckets']['partly']]) }}</p>
            <p class="ad-reason-hint" style="margin-top: 8px;">{{ __('admin/privacy/index.buckets_note') }}</p>
        @else
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/privacy/index.categories') }}</span><span class="ad-row-v"><span class="ad-hpill ad-hpill--grey">{{ __('admin/privacy/index.could_not_measure') }}</span></span></div>
            <p class="ad-reason-hint" style="margin-top: 6px;">{{ $coverage['reason'] }}</p>
        @endif
        <h3 class="ad-label" style="margin: 14px 0 6px;">{{ __('admin/privacy/index.enforced_heading') }}</h3>
        @foreach ($coverage['enforced'] as $table => $rule)
            <div class="ad-row"><span class="ad-row-k"><code>{{ $table }}</code></span><span class="ad-row-v" style="font-weight: 400;">{{ $rule }}</span></div>
        @endforeach
    </div>

    {{-- ── 4 · Sub-processors ─────────────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">{{ __('admin/privacy/index.sub_processors_heading') }}</h2>
        <p class="ad-stat-sub" style="margin: -6px 0 12px;">{!! __('admin/privacy/index.sub_processors_intro') !!}</p>
        <table class="ad-table">
            <thead><tr><th>{{ __('admin/privacy/index.col_who') }}</th><th>{{ __('admin/privacy/index.col_why') }}</th><th>{{ __('admin/privacy/index.col_what_they_get') }}</th><th>{{ __('admin/privacy/index.col_today') }}</th><th>{{ __('admin/privacy/index.col_agreement') }}</th></tr></thead>
            <tbody>
                @foreach ($subProcessors as $sp)
                    <tr>
                        <td><strong>{{ $sp['name'] }}</strong></td>
                        <td style="max-width: 220px;">{{ $sp['purpose'] }}</td>
                        <td style="max-width: 280px;">{{ $sp['data'] }}</td>
                        <td><span class="ad-hpill {{ $sp['active'] ? 'ad-hpill--green' : 'ad-hpill--grey' }}">{{ $sp['state'] }}</span></td>
                        <td>{{ $sp['agreement'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin.shell>
