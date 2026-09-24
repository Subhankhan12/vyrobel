{{-- ADMIN 01 · PHASE 5 — IN-APP ANNOUNCEMENTS to every tenant or chosen ones. Publishing and withdrawing are audited with
     a reason; tenants dismiss them for themselves. Plain text, shown escaped. --}}
@php
    use App\Models\PlatformAnnouncement;
    $me = auth('admin')->user();
    $canPublish = $me?->may('tenants.manage') ?? false;
@endphp
<x-admin.shell active="announcements" title="Announcements" subtitle="A notice inside every tenant’s back office and owner portal — or chosen ones. Plain text; nothing is emailed.">

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="ad-flash ad-flash--error">{{ $errors->first() }}</div>
    @endif

    <div class="ad-card" style="overflow-x: auto;">
        @if ($announcements->isEmpty())
            <div class="ad-empty">No announcement has ever been published.</div>
        @else
            <table class="ad-table">
                <thead><tr><th>Announcement</th><th>To</th><th>From → until (UTC)</th><th>State</th>@if ($canPublish)<th></th>@endif</tr></thead>
                <tbody>
                    @foreach ($announcements as $a)
                        <tr>
                            <td style="max-width: 360px;"><strong>{{ $a->title }}</strong><div style="font-size: 12px; color: var(--ad-muted);">{{ \Illuminate\Support\Str::limit($a->body, 140) }}</div></td>
                            <td>{{ $a->audience === 'all' ? 'Every tenant' : count((array) $a->organization_ids).' chosen' }}</td>
                            <td>{{ $a->starts_at->utc()->format('j M H:i') }} → {{ $a->ends_at?->utc()->format('j M H:i') ?? 'no end' }}</td>
                            <td>{{ $a->withdrawn_at ? 'Withdrawn' : ($a->isLive() ? 'Showing' : ($a->starts_at->isFuture() ? 'Scheduled' : 'Ended')) }}</td>
                            @if ($canPublish)
                                <td>
                                    @if (! $a->withdrawn_at && ($a->isLive() || $a->starts_at->isFuture()))
                                        <form method="POST" action="{{ route('admin.announcements.withdraw', $a->id) }}" data-guard-submit style="display: flex; gap: 6px;">
                                            @csrf
                                            <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why withdraw?" style="margin: 0; min-width: 160px;">
                                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Withdraw</button>
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
            <h2 class="ad-panel-title">Publish an announcement</h2>
            <form method="POST" action="{{ route('admin.announcements.store') }}" data-guard-submit>
                @csrf
                <label class="ad-label" for="an-title">Title</label>
                <input id="an-title" class="ad-input" name="title" required maxlength="120" value="{{ old('title') }}">
                <label class="ad-label" for="an-body" style="margin-top: 10px;">Message (plain text)</label>
                <textarea id="an-body" class="ad-input" name="body" required maxlength="1000" rows="3">{{ old('body') }}</textarea>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                    <div><label class="ad-label" for="an-level">Kind</label><select id="an-level" name="level" class="ad-input">@foreach (PlatformAnnouncement::LEVELS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="ad-label" for="an-start">Show from (UTC)</label><input id="an-start" type="datetime-local" name="starts_at" class="ad-input" required value="{{ old('starts_at', now()->utc()->format('Y-m-d\TH:i')) }}"></div>
                    <div><label class="ad-label" for="an-end">Until (UTC, optional)</label><input id="an-end" type="datetime-local" name="ends_at" class="ad-input" value="{{ old('ends_at') }}"></div>
                    <div><label class="ad-label" for="an-aud">To</label><select id="an-aud" name="audience" class="ad-input"><option value="all">Every tenant</option><option value="chosen">Chosen organisations</option></select></div>
                </div>
                <label class="ad-label" for="an-orgs" style="margin-top: 10px;">Chosen organisations (when “Chosen”)</label>
                <select id="an-orgs" name="organization_ids[]" class="ad-input" multiple size="5">@foreach ($organizations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                <label class="ad-label" for="an-reason" style="margin-top: 10px;">Reason</label>
                <input id="an-reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is this going out?">
                <button type="submit" class="ad-btn" style="margin-top: 12px;">Publish</button>
            </form>
        </div>
    @endif
</x-admin.shell>
