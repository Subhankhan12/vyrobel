{{-- ADMIN 01 · PHASE 2 — the confirmations a lifecycle form carries. Every one is ALSO enforced on the server: a
     direct POST that skips this markup is refused exactly the same way.
       · password     — the destructive two (suspend, archive).
       · guests       — guests in house or arriving → an explicit acknowledgement.
       · confirm name — a protected organisation (Vyrobel Internal) → its exact name typed. --}}
@if ($password ?? false)
    <label class="ad-label" for="{{ $id }}_pw">{{ __('admin/customers/partials/lifecycle-confirm.your_password') }}</label>
    <input id="{{ $id }}_pw" type="password" name="current_password" class="ad-input" autocomplete="current-password" required aria-label="{{ __('admin/customers/partials/lifecycle-confirm.your_password') }}">
@endif
@if (! ($noGuests ?? false) && $guests > 0)
    <label style="display: flex; gap: 8px; align-items: flex-start; font-size: 12.5px; margin: 10px 0 0; color: var(--ad-ink);">
        <input type="checkbox" name="acknowledge_guests" value="1" required style="margin-top: 2px;" aria-label="{{ __('admin/customers/partials/lifecycle-confirm.acknowledge_guests_label') }}">
        {{ trans_choice('admin/customers/partials/lifecycle-confirm.acknowledge_guests', $impact['in_house'], ['count' => $impact['in_house'], 'upcoming' => $impact['upcoming']]) }}
    </label>
@endif
@if ($confirmName)
    <label class="ad-label" for="{{ $id }}_confirm" style="margin-top: 10px;">{!! __('admin/customers/partials/lifecycle-confirm.protected_type_name', ['name' => e($org->name)]) !!}</label>
    <input id="{{ $id }}_confirm" class="ad-input" name="confirm_name" required autocomplete="off" aria-label="{{ __('admin/customers/partials/lifecycle-confirm.confirm_name_label') }}">
@endif
