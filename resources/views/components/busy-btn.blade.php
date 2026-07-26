{{-- Polish Batch 4 · GATE 1 — a submit button with a double-submit-safe BUSY state (front-line UX guard).
     Pairs with the shared vanilla guard (@include('partials.submit-guard')) — no framework dependency, so it works
     on the Livewire-less guest portal + public storefront too. Mark the parent <form> with `data-guard-submit`; on
     the FIRST submit the shared script disables this button + swaps its label to `data-busy-label`; a rapid SECOND
     submit is preventDefault'd → the action fires ONCE. A cancelled confirm() (defaultPrevented) never locks it.

     🔒 DEFENSE-IN-DEPTH on TOP of the server-side guards (payment idempotency #27/Batch-C nonce, guest-charge
     firstOrCreate, provisioning atomicity/rate-limit) — NOT a replacement. RAW-BLADE RULE: `@` follows a space/tag. --}}
@props(['busyLabel' => 'Processing…'])
<button {{ $attributes->merge(['type' => 'submit']) }} data-busy-label="{{ $busyLabel }}">{{ $slot }}</button>
