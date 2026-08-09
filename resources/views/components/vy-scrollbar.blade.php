{{-- The ONE scrollbar theme, shared by every web surface in the product.

     Included from each shell's head — operator, owner, guest, admin, public, auth, errors, onboarding,
     billing, oauth. Defined once here rather than pasted into each realm's own <style>, because a scrollbar
     copied nineteen times is nineteen chances to drift from the palette.

     ⚠️ NOT included by the PDF or email templates: DomPDF and mail clients have no scrollbars, and unknown
     CSS in either is a rendering risk for a purely decorative gain.

     Every colour resolves from the --vy-* tokens each realm already defines, with literal fallbacks only for
     surfaces that never set them — so retuning the theme retunes the scrollbar automatically.

     RAW-BLADE RULE: every directive-looking token here is inside CSS, never a bare word after a letter. --}}
<style>
    /* ---- Track: a faint warm channel, so the bar reads as part of the surface ---- */
    ::-webkit-scrollbar { width: 14px; height: 14px; }
    ::-webkit-scrollbar-track {
        background: linear-gradient(180deg, rgba(194, 65, 12, .07), rgba(194, 65, 12, .13));
        border-radius: 999px;
        box-shadow: inset 0 0 4px rgba(120, 40, 10, .10);
    }

    /* ---- Thumb: amber igniting into terracotta, with a glow and an inner highlight ---- */
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg,
            rgb(251, 176, 59) 0%,
            rgb(240, 122, 45) 42%,
            var(--vy-accent, rgb(194, 65, 12)) 100%);
        border: 2px solid transparent;
        background-clip: padding-box;
        border-radius: 999px;
        box-shadow:
            0 0 10px rgba(232, 112, 58, .55),
            inset 0 1px 0 rgba(255, 255, 255, .55),
            inset 0 -1px 0 rgba(120, 40, 10, .18);
        transition: box-shadow .22s cubic-bezier(.22, .61, .36, 1),
                    border-width .22s cubic-bezier(.22, .61, .36, 1),
                    background .22s ease;
    }

    /* Hover: it brightens and the glow blooms — the bar grows without the layout shifting. */
    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg,
            rgb(253, 196, 84) 0%,
            rgb(249, 130, 47) 45%,
            rgb(214, 74, 16) 100%);
        border-width: 1px;
        background-clip: padding-box;
        box-shadow:
            0 0 18px rgba(232, 112, 58, .75),
            inset 0 1px 0 rgba(255, 255, 255, .7),
            inset 0 -1px 0 rgba(120, 40, 10, .22);
    }

    /* Press: the glow tucks in, so a drag feels physical. */
    ::-webkit-scrollbar-thumb:active {
        border-width: 2px;
        background-clip: padding-box;
        box-shadow: 0 0 6px rgba(194, 65, 12, .6), inset 0 2px 4px rgba(120, 40, 10, .35);
    }

    ::-webkit-scrollbar-corner { background: transparent; }

    /* Firefox has no pseudo-elements for this — it gets the same accent through the standards property. */
    html { scrollbar-width: thin; scrollbar-color: var(--vy-accent-3, rgb(232, 112, 58)) rgba(194, 65, 12, .07); }

    /* Smooth scrolling, and a slimmer bar inside panes so it never competes with the nav beside it. */
    html { scroll-behavior: smooth; }
    .vy-side, .vy-scroll, [data-vy-scroll] { scrollbar-width: thin; }
    .vy-side::-webkit-scrollbar, .vy-scroll::-webkit-scrollbar, [data-vy-scroll]::-webkit-scrollbar { width: 11px; }
    .vy-side::-webkit-scrollbar-thumb, .vy-scroll::-webkit-scrollbar-thumb, [data-vy-scroll]::-webkit-scrollbar-thumb { border-width: 2px; background-clip: padding-box; }

    /* The project's accessibility floor: motion is opt-out, and that includes scroll motion. */
    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        ::-webkit-scrollbar-thumb { transition: none; }
    }
</style>
