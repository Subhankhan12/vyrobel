{{-- DEC-6 — "Delete my account" on a Security page: a REQUEST that a person answers within a month, never an instant delete
     (the lawyer's answer on financial records comes first). Shared by the staff and the owner Security page, so the two cannot
     drift. The dialog it opens is <x-security.deletion-dialog>, placed OUTSIDE the cards (a card's entrance animation makes a
     fixed dialog inside it position against the card). Needs `deleteOpen` in the page's Alpine state. --}}
@props(['request' => null])
@php $when = fn ($at) => $at?->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M Y')); @endphp
<section class="vy-sx-card vy-sx-in" id="sx-delete" aria-labelledby="vy-sx-del-t">
    <h2 class="vy-sx-card-title" id="vy-sx-del-t">{{ __('components/security/deletion-card.heading') }}</h2>
    @if ($request !== null)
        <p class="vy-sx-card-sub" data-deletion-state="{{ $request->status }}">
            {{ $request->status === 'in_progress'
                ? __('components/security/deletion-card.in_progress', ['date' => $when($request->received_at), 'due' => $when($request->due_at)])
                : __('components/security/deletion-card.received', ['date' => $when($request->received_at), 'due' => $when($request->due_at)]) }}
        </p>
    @else
        <p class="vy-sx-card-sub">{{ __('components/security/deletion-card.sub') }}</p>
        <div class="vy-sx-alert-acts">
            <button type="button" class="vy-sx-btn vy-sx-btn--line" @click="deleteOpen = true"><x-security.icon name="x" /> {{ __('components/security/deletion-card.button') }}</button>
        </div>
    @endif
</section>
