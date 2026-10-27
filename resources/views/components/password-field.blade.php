{{-- VYROBEL PASSWORD FIELD — a password input with a show/hide (eye) toggle (2026-10-21).

     A plain <input> that defaults to type=password; Alpine (ships with Livewire) toggles it to text and swaps the
     eye icon. With no JavaScript it is an ordinary, fully working password field — nothing is lost.

     Props: name · id · autocomplete · required (bool) · label (for the toggle's accessible name: "Show <label>";
     left unset, the toggle says "Show password" / "Hide password").
     Other attributes land on the input.

     RAW-BLADE RULE: every directive follows a space, a newline or a tag. --}}
@props([
    'name',
    'id' => null,
    'autocomplete' => 'current-password',
    'required' => false,
    'label' => null,
])
@php($fieldId = $id ?? ('vypw-'.\Illuminate\Support\Str::random(6)))
@php($vyPwShow = $label === null ? __('auth/common.show_password') : __('components/password-field.show_label', ['label' => $label]))
@php($vyPwHide = $label === null ? __('auth/common.hide_password') : __('components/password-field.hide_label', ['label' => $label]))
<div class="vy-pw" x-data="{ show: false }">
    <input type="password" name="{{ $name }}" id="{{ $fieldId }}" class="vy-input vy-pw-input"
           autocomplete="{{ $autocomplete }}" @if ($required) required @endif
           :type="show ? 'text' : 'password'" {{ $attributes }}>
    <button type="button" class="vy-pw-eye" @click="show = !show" tabindex="-1"
            :aria-label="show ? @js($vyPwHide) : @js($vyPwShow)" :aria-pressed="show.toString()">
        <svg x-show="!show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg x-show="show" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 5.1A9.5 9.5 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-3.3 4.2M6.6 6.6A17.6 17.6 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 3.4-.6"/></svg>
    </button>
</div>
