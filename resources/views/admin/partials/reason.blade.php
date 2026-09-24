{{-- ADMIN 01 · Phase 1 — THE reason field every console write carries (validated by App\Support\Platform\AdminReason).
     One partial so it looks and behaves the same on every form. --}}
<div style="margin-top: 14px;">
    <label class="ad-label" for="{{ $id ?? 'reason' }}">Reason</label>
    <input class="ad-input" id="{{ $id ?? 'reason' }}" name="reason" required minlength="5" maxlength="500"
           value="{{ old('reason') }}" placeholder="{{ $placeholder ?? 'Why are you making this change?' }}">
    <p class="ad-reason-hint" style="margin-top: 5px;">Recorded in the audit log. Don't put passwords, guest details, cards or IBANs here.</p>
</div>
