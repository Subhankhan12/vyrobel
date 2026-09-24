<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PrivacyRequest;
use App\Models\User;
use App\Support\Platform\AdminReason;
use App\Support\Platform\PlatformQuery;
use App\Support\Privacy\PrivacyQueue;
use App\Support\Privacy\RetentionCoverage;
use App\Support\Privacy\SubProcessors;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ⚠️ ADMIN 01 · PHASE 4 — THE PRIVACY DESK: the live GDPR queue (the existing erasure machinery, watched), the request
 * register (tracked, with status and due date — fulfilled by the EXISTING mechanisms, never a new one), the retention
 * coverage meter (built, AWAITING the founder's ruling on what counts) and the sub-processor register.
 *
 * ⚠️ No guest is ever shown. An account holder is recorded by user id (looked up by email, the email itself not
 * stored); a relayed guest request by organisation only. Every register change needs a reason and is audited.
 */
class PrivacyController extends Controller
{
    public function __construct(private readonly PlatformQuery $platform) {}

    public function index(PrivacyQueue $queue, RetentionCoverage $coverage): View
    {
        [$live, $requests, $organizations] = $this->platform->read('platform.privacy.view', fn (): array => [
            $queue->compute(),
            PrivacyRequest::query()->with(['subjectUser:id,name', 'organization:id,name'])->orderByRaw("status in ('received','in_progress') desc")->orderBy('due_at')->limit(50)->get(),
            Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
        ], []);

        return view('admin.privacy.index', [
            'live' => $live,
            'requests' => $requests,
            'organizations' => $organizations,
            'coverage' => $coverage->compute(),
            'subProcessors' => SubProcessors::register(),
        ]);
    }

    /** RECORD a request that arrived by email, letter, phone or through a hotel. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(PrivacyRequest::KINDS))],
            'subject_kind' => ['required', Rule::in(array_keys(PrivacyRequest::SUBJECTS))],
            'email' => ['required_if:subject_kind,account_holder', 'nullable', 'email'],
            'organization_id' => ['required_if:subject_kind,relayed_guest', 'nullable', 'string'],
            'channel' => ['required', Rule::in(array_keys(PrivacyRequest::CHANNELS))],
            'received_on' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $reason = AdminReason::from($request);
        $back = redirect()->route('admin.privacy.index');

        $userId = null;
        $orgId = null;
        if ($data['subject_kind'] === 'account_holder') {
            $userId = User::query()->where('email', (string) $data['email'])->value('id');
            if ($userId === null) {
                return $back->withErrors(['email' => 'No account holder has that email address. (A hotel guest’s request is relayed — choose that instead.)'])->withInput($request->except('email'));
            }
        } else {
            $orgId = Organization::query()->whereKey((string) $data['organization_id'])->value('id');
            if ($orgId === null) {
                return $back->withErrors(['organization_id' => 'Choose the organisation the guest’s request is about.'])->withInput();
            }
        }

        $received = CarbonImmutable::parse((string) $data['received_on'], 'UTC')->startOfDay();

        $this->platform->mutate('platform.privacy.request_recorded', $reason, fn () => PrivacyRequest::create([
            'kind' => $data['kind'],
            'subject_kind' => $data['subject_kind'],
            'subject_user_id' => $userId,
            'related_organization_id' => $orgId,
            'channel' => $data['channel'],
            'status' => 'received',
            'received_at' => $received,
            'due_at' => $received->addMonthsNoOverflow(PrivacyRequest::DUE_MONTHS),
            'recorded_by' => (string) auth('admin')->id(),
        ]), ['kind' => $data['kind'], 'subject_kind' => $data['subject_kind']], $userId !== null ? 'user' : 'organization', (string) ($userId ?? $orgId));

        return $back->with('admin_notice', 'Request recorded — due by '.$received->addMonthsNoOverflow(PrivacyRequest::DUE_MONTHS)->format('j M Y').'.');
    }

    /** MOVE a request along: received → in progress → completed | refused. A reason each time. */
    public function update(Request $request, string $privacyRequest): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['in_progress', 'completed', 'refused'])]]);
        $reason = AdminReason::from($request);
        $row = PrivacyRequest::query()->findOrFail($privacyRequest);

        if (! $row->isOpen()) {
            return redirect()->route('admin.privacy.index')->withErrors(['status' => 'That request is already closed.']);
        }

        $closing = in_array($data['status'], ['completed', 'refused'], true);
        $this->platform->mutate('platform.privacy.request_'.$data['status'], $reason, fn () => $row->update([
            'status' => $data['status'],
            'closed_at' => $closing ? now() : null,
        ]), ['from' => $row->status], 'privacy_request', (string) $row->id);

        return redirect()->route('admin.privacy.index')->with('admin_notice', 'Request marked '.PrivacyRequest::STATUSES[$data['status']].'.');
    }
}
