{{-- ADMIN 01 · Phase 1 — THE reason field every console write carries (validated by App\Support\Platform\AdminReason).
     One partial so it looks and behaves the same on every form. --}}
<div style="margin-top: 14px;">
    <label class="ad-label" for="{{ $id ?? 'reason' }}">{{ __('admin/partials/reason.label') }}</label>
    <input class="ad-input" id="{{ $id ?? 'reason' }}" name="reason" required minlength="5" maxlength="500"
           value="{{ old('reason') }}" placeholder="{{ $placeholder ?? __('admin/partials/reason.placeholder') }}">
    <p class="ad-reason-hint" style="margin-top: 5px;">{{ __('admin/partials/reason.hint') }}</p>
</div>
