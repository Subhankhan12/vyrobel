{{-- AN INTENTIONAL IMAGE PLACEHOLDER (front-website Gate 2 · D8 OPEN).

     ⚠️ THERE ARE NO PHOTOGRAPHS. The design ships `<image-slot>` placeholders carrying a photo BRIEF
     ("Drop your hero property photo", "A pool, terrace or view"…) and D8 — source, shoot or licence — is
     unruled. ⚠️ Substituting stock photography or generating an image is the founder's call, not this
     build's, so this renders the brief as a deliberate, token-consistent placeholder that reads as
     "photography pending", never as a broken or empty box.

     ⚠️ NO ALT-TEXT LIE: it is not an <img>, carries no alt describing a picture that does not exist, and is
     aria-hidden with the brief exposed as visible text — a screen reader is told the truth by the copy
     itself. --}}
@props(['brief' => '', 'radius' => 20, 'shape' => 'rounded'])
<div class="vy-mk-slot" role="img" aria-label="Photography pending: {{ $brief ?: 'image' }}"
     style="border-radius: {{ $shape === 'rect' ? '0' : $radius.'px' }};">
    <span class="vy-mk-slot-mark" aria-hidden="true">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"></rect><circle cx="9" cy="10" r="1.6"></circle><path d="M4 17l5-5 4 4 3-3 4 4"></path></svg>
    </span>
    <span class="vy-mk-slot-brief">{{ $brief }}</span>
    <span class="vy-mk-slot-note">Photography pending</span>
</div>
