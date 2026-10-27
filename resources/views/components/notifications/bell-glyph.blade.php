{{-- The bell GLYPH + unread BADGE — the visual core of the top-bar bell, extracted so the two bell MODES
     (the Alpine popover used by the operator/owner shells, and the plain LINK used by the guest shell, which
     loads no Alpine) render byte-identically. ONE definition of the mark; the modes differ only in what wraps
     it and what happens on click (§N.8f).

     An 18px / 1.8-stroke bell (the decoded Owner Dashboard prototype), and the unread badge with the prototype's
     140deg #E8703A→#C2410C gradient.

     ⚠️ THE BADGE SITS ON THE CIRCLE'S OUTER TOP-RIGHT CORNER (2026-10-09, his report: "the notification icon is
     completely messed … no proper spacing"). The prototype put it INSIDE the circle, 9px from the top and 10px from
     the right — 🔬 measured: a 19px "9+" badge then sat on the 18px bell and hid it. Anchored to the corner it never
     touches the glyph, and the same offsets fit every shell's bell (36, 38 and 42px), so no shell tunes them; the
     `--vy-bell-badge-*` properties stay as the one place to move it.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@props(['unread' => 0])
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
{{-- ⚠️ ALWAYS RENDERED, HIDDEN AT 0 (team chat, 2026-10-06): the live pulse (partials/live-pulse) changes the number in
     place when a notification arrives, so there must be a badge to change; at 0 it is display:none (not announced). --}}
<span data-vy-bell-badge aria-label="{{ trans_choice('components/notifications/bell-glyph.unread', $unread, ['count' => $unread]) }}" style="position:absolute;top:var(--vy-bell-badge-top,-5px);right:var(--vy-bell-badge-right,-6px);min-width:19px;height:19px;padding:0 5px;border-radius:999px;background:linear-gradient(140deg,#E8703A,#C2410C);color:#fff;font-size:10px;font-weight:700;line-height:1;letter-spacing:-.01em;font-variant-numeric:tabular-nums;display:{{ $unread > 0 ? 'flex' : 'none' }};align-items:center;justify-content:center;border:2px solid #FFF8F2;box-shadow:0 3px 8px rgba(194,65,12,.35);box-sizing:border-box;pointer-events:none">{{ $unread > 9 ? '9+' : ($unread > 0 ? $unread : '') }}</span>
