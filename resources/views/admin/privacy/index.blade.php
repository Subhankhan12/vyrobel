{{-- ⚠️ ADMIN 01 · PHASE 4 — THE PRIVACY DESK. The live GDPR queue (the existing erasure machinery, watched) · the request
     register (tracked; fulfilled by the EXISTING mechanisms) · retention coverage (built, AWAITING the founder's ruling)
     · the sub-processor register. ⚠️ No guest is ever shown here. It makes the lawyer's job shorter; it does not replace it. --}}
@php
    use App\Models\PrivacyRequest;
    $me = auth('admin')->user();
    $canRecord = $me?->may('support.use') ?? false;
@endphp
<x-admin.shell active="privacy" title="Privacy desk" subtitle="Data-subject requests, the erasure queue, retention coverage and who we share data with.">

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="ad-flash ad-flash--error">{{ $errors->first() }}</div>
    @endif

    {{-- ── 1 · The live erasure queue ─────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 class="ad-panel-title" style="margin: 0;">Guest erasure queue</h2>
            @if ($live['overdue'] > 0)
                <span class="ad-hpill ad-hpill--red">{{ $live['overdue'] }} overdue — the daily erasure job is not running</span>
            @else
                <span class="ad-hpill ad-hpill--green">Nothing overdue</span>
            @endif
        </div>
        <p class="ad-stat-sub" style="margin: 8px 0 12px; line-height: 1.6;">
            A guest who deletes their account is erased after a {{ \App\Services\GuestDeletionService::GRACE_DAYS }}-day, reversible window by the daily
            <code>guests:process-deletions</code> job. Counts and hotels only — never the guest.
        </p>
        <div class="ad-row"><span class="ad-row-k">Waiting for their date</span><span class="ad-row-v">{{ number_format($live['pending'] - $live['overdue']) }}</span></div>
        <div class="ad-row"><span class="ad-row-k">Past their date, not yet erased</span><span class="ad-row-v">{{ number_format($live['overdue']) }}</span></div>
        <div class="ad-row"><span class="ad-row-k">Next due</span><span class="ad-row-v">{{ $live['next_due']?->format('j M Y, H:i').' UTC' ?? '—' }}</span></div>
        <div class="ad-row"><span class="ad-row-k">Erased in the last 30 days</span><span class="ad-row-v">{{ number_format($live['erased_30d']) }}</span></div>
        @foreach ($live['by_org'] as $o)
            <div class="ad-row"><span class="ad-row-k">{{ $o['organization'] }}{{ $o['internal'] ? ' (internal)' : '' }}</span><span class="ad-row-v">{{ $o['pending'] }} pending{{ $o['overdue'] ? ' · '.$o['overdue'].' overdue' : '' }}</span></div>
        @endforeach
        <p class="ad-reason-hint" style="margin-top: 10px;">Owners’ “Request my data” is an instant self-service download and is not recorded anywhere — so it cannot be counted here.</p>
    </div>

    {{-- ── 2 · The request register ───────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">Requests received</h2>
        <p class="ad-stat-sub" style="margin: -6px 0 12px; line-height: 1.6;">
            Requests that reached Vyrobel by email, letter, phone or through a hotel. Tracked here with a one-month due date;
            fulfilled with the product’s existing tools. <strong>No free text is stored here</strong> — the reason for each step goes to the audit log.
        </p>
        @if ($requests->isEmpty())
            <div class="ad-empty">No request has been recorded.</div>
        @else
            <table class="ad-table">
                <thead><tr><th>Request</th><th>About</th><th>Received</th><th>Due</th><th>Status</th>@if ($canRecord)<th></th>@endif</tr></thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td>{{ PrivacyRequest::KINDS[$req->kind] ?? $req->kind }}<div style="font-size: 12px; color: var(--ad-faint);">{{ PrivacyRequest::CHANNELS[$req->channel] ?? $req->channel }}</div></td>
                            <td>{{ $req->subject_kind === 'account_holder' ? ($req->subjectUser?->name ?? 'an account holder') : 'A guest of '.($req->organization?->name ?? 'an organisation') }}</td>
                            <td>{{ $req->received_at->format('j M Y') }}</td>
                            <td style="{{ $req->isOpen() && $req->due_at->isPast() ? 'color: var(--ad-danger); font-weight: 600;' : '' }}">{{ $req->due_at->format('j M Y') }}</td>
                            <td>{{ PrivacyRequest::STATUSES[$req->status] ?? $req->status }}</td>
                            @if ($canRecord)
                                <td>
                                    @if ($req->isOpen())
                                        <form method="POST" action="{{ route('admin.privacy.update', $req->id) }}" data-guard-submit style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            @csrf
                                            <select aria-label="New status" name="status" class="ad-input" style="margin: 0; width: auto;">
                                                @foreach (['in_progress' => 'In progress', 'completed' => 'Completed', 'refused' => 'Refused'] as $k => $label)
                                                    @if ($k !== $req->status)<option value="{{ $k }}">{{ $label }}</option>@endif
                                                @endforeach
                                            </select>
                                            <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="What was done" style="margin: 0; min-width: 180px;">
                                            <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Update</button>
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
            <summary style="cursor: pointer; font-weight: 600; font-size: 13px;">How each request is fulfilled — with what already exists</summary>
            <ul class="ad-stat-sub" style="margin: 10px 0 0; padding-left: 18px; line-height: 1.7;">
                <li><strong>A hotel’s guest</strong> (export or delete): the hotel is the controller — pass it to them. The guest can also delete their own account from the guest portal ({{ \App\Services\GuestDeletionService::GRACE_DAYS }}-day window), and the hotel can erase a guest from its guest profile.</li>
                <li><strong>An owner</strong>, export: their own “Request my data” on the owner Security page.</li>
                <li><strong>Staff</strong>, export: <em>no self-service export exists.</em></li>
                <li><strong>Any account holder</strong>, delete: <em>no account deletion exists for any human account</em> — how and when is the founder’s ruling, with retention.</li>
            </ul>
        </details>

        @if ($canRecord)
            <form method="POST" action="{{ route('admin.privacy.store') }}" data-guard-submit style="margin-top: 16px; border-top: 1px solid var(--ad-border); padding-top: 14px;">
                @csrf
                <h3 class="ad-label" style="margin-bottom: 10px;">Record a request</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <select aria-label="Kind" name="kind" class="ad-input" required>@foreach (PrivacyRequest::KINDS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    <select aria-label="About" name="subject_kind" class="ad-input" required>@foreach (PrivacyRequest::SUBJECTS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    <select aria-label="Channel" name="channel" class="ad-input" required>@foreach (PrivacyRequest::CHANNELS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    <input aria-label="Received on" type="date" name="received_on" class="ad-input" required value="{{ now()->toDateString() }}">
                    <input aria-label="Account holder email" type="email" name="email" class="ad-input" placeholder="Account holder’s email (not stored)">
                    <select aria-label="Organisation" name="organization_id" class="ad-input"><option value="">Organisation (for a guest’s request)</option>@foreach ($organizations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                </div>
                <input aria-label="Reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Reason — e.g. email from the account holder, 24 Sep" style="margin-top: 10px;">
                <button type="submit" class="ad-btn ad-btn--sm" style="margin-top: 10px;">Record request</button>
            </form>
        @endif
    </div>

    {{-- ── 3 · Retention coverage — AWAITING A RULING ────────────────────────────────────────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 class="ad-panel-title" style="margin: 0;">Retention coverage</h2>
            <span class="ad-hpill ad-hpill--amber">Awaiting the founder’s ruling on what counts</span>
        </div>
        <p class="ad-stat-sub" style="margin: 8px 0 12px; line-height: 1.6;">
            “N of M data categories have a retention rule.” What counts as <em>having a rule</em> is for the founder and his lawyer to define —
            so this does not pick. It counts the categories, shows what the code actually enforces, and shows what two readings would give.
        </p>
        @if ($coverage['measurable'])
            <div class="ad-row"><span class="ad-row-k">Data categories (counted from the data inventory)</span><span class="ad-row-v">{{ $coverage['categories'] }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Strict reading — every store the category names is pruned by code</span><span class="ad-row-v">{{ $coverage['strict'] }} of {{ $coverage['categories'] }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Lenient reading — at least one store it names is pruned by code</span><span class="ad-row-v">{{ $coverage['lenient'] }} of {{ $coverage['categories'] }}</span></div>
            <p class="ad-reason-hint" style="margin-top: 8px;">Neither reading is the ruling, and both are approximate: a category’s stores are the real tables its inventory row mentions, including ones it mentions in passing.</p>
        @else
            <div class="ad-row"><span class="ad-row-k">Categories</span><span class="ad-row-v"><span class="ad-hpill ad-hpill--grey">Could not measure</span></span></div>
            <p class="ad-reason-hint" style="margin-top: 6px;">{{ $coverage['reason'] }}</p>
        @endif
        <h3 class="ad-label" style="margin: 14px 0 6px;">What the code enforces today, on a timer</h3>
        @foreach ($coverage['enforced'] as $table => $rule)
            <div class="ad-row"><span class="ad-row-k"><code>{{ $table }}</code></span><span class="ad-row-v" style="font-weight: 400;">{{ $rule }}</span></div>
        @endforeach
    </div>

    {{-- ── 4 · Sub-processors ─────────────────────────────────────────────────────────────────────────────────── --}}
    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">Sub-processors</h2>
        <p class="ad-stat-sub" style="margin: -6px 0 12px;">Who receives personal data because of Vyrobel. <strong>No agreement is on file with any of them yet.</strong></p>
        <table class="ad-table">
            <thead><tr><th>Who</th><th>Why</th><th>What they get</th><th>Today</th><th>Agreement</th></tr></thead>
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
