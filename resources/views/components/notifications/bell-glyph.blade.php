{{-- The bell GLYPH + unread BADGE — the visual core of the top-bar bell, extracted so the two bell MODES
     (the Alpine popover used by the operator/owner shells, and the plain LINK used by the guest shell, which
     loads no Alpine) render byte-identically. ONE definition of the mark; the modes differ only in what wraps
     it and what happens on click (§N.8f).

     Matches the decoded Owner Dashboard prototype: an 18px / 1.8-stroke bell, and the unread badge INSIDE the
     circle with the 140deg #E8703A→#C2410C gradient and a 1.5px #FBEEE3 ring. Badge offsets read the
     `--vy-bell-badge-*` custom properties so a shell can tune them to its own bell diameter.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@props(['unread' => 0])
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
@if ($unread > 0)<span aria-label="{{ $unread }} unread" style="position:absolute;top:var(--vy-bell-badge-top,9px);right:var(--vy-bell-badge-right,10px);min-width:15px;height:15px;padding:0 3px;border-radius:30px;background:linear-gradient(140deg,#E8703A,#C2410C);color:#fff;font-size:9px;font-weight:600;display:flex;align-items:center;justify-content:center;border:1.5px solid #FBEEE3;box-sizing:border-box">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
