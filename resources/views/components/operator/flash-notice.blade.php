@props(['message', 'refused' => false])

{{--
    ⚠️⚠️ THE ONE PLACE A FOLIO/DESK OUTCOME IS RENDERED — refusal and success, decided here, not per view.

    ⚠️ WHY IT EXISTS (audit D4). `folio_notice` is a SINGLE flash key carrying BOTH outcomes, and all three
    views that render it hardcoded a CHECKMARK. So invariant #27 refusing an over-refund — "That refund was
    refused — only €X is still refundable" — arrived under a green tick. A desk clerk reading the tick tells
    the guest the refund went through, or retries it. The message was always correct; the ICON contradicted it.
    ⚠️ The refusal logic is untouched by this component. This is presentation over a working guard.

    ⚠️ TWO DEFECTS, BOTH FIXED HERE:
      (a) THE ICON — a refusal now renders an alert triangle, not a tick, and carries the word "Refused" in
          a visually-hidden span. ⚠️ NOT COLOUR ALONE: the glyph, the accessible text and the role all change,
          so it survives a monochrome display and a colour-blind reader alike.
      (b) THE ANNOUNCEMENT — `role="status"` PRESENT AT PAGE LOAD NEVER ANNOUNCES. A live region only speaks
          when its contents CHANGE while it is being observed; a server-rendered flash is already in the DOM
          when the screen reader arrives, so the old markup told such a user nothing at all.
          ⚠️ A refusal is therefore `role="alert"` AND focused on arrival (`tabindex="-1"` + `x-init`), which
          is what actually reaches the user: focus moves to the text, so it is read. Success keeps
          `role="status"` and does NOT steal focus — an interruption is warranted when an action FAILED,
          not when it worked.
--}}

@if ($refused)
    <div class="vy-toast vy-toast--refused vy-fade" role="alert" tabindex="-1"
         x-data x-init="$nextTick(() => $el.focus())">
        {{-- Alert triangle — deliberately a different SHAPE from the tick, not a recoloured one. --}}
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            <path d="M12 9v4"/><path d="M12 17h.01"/>
        </svg>
        <span class="vy-sr-only">Refused: </span>{{ $message }}
    </div>
@else
    <div class="vy-toast vy-fade" role="status">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        {{ $message }}
    </div>
@endif
