{{-- THE CANONICAL VYROBEL PALETTE — the ONE declaration of every token the shells SHARE (DO-TOKEN-1).

     ⚠️ WHY THIS EXISTS. Four shells — guest, owner, operator and the public storefront — each re-declared the
     whole palette, so every token had four independent definitions and any one of them could silently drift.
     That is not hypothetical: `--vy-amber-text` did exactly that (DO-EMAIL-4), ending up with two different
     values under one name. A shared token now has ONE definition here, and a shell cannot diverge it by
     editing its own copy, because it no longer has one. §N.8f, applied to the palette.

     ⚠️ WHAT BELONGS HERE: only tokens whose value is IDENTICAL in every shell that declares them (31 of them).
     A token that legitimately differs per shell stays in its shell as a DECLARED OVERRIDE — see below.

     ⚠️ THE DELIBERATE PER-SHELL OVERRIDES, preserved on purpose and NOT flattened into this file:
       · GUEST is deliberately softer and rounder than the back-office shells — smaller radii (`--vy-r-lg` 18px
         vs 20px, `--vy-r-sm` 9px vs 10px), a lighter `--vy-shadow` and `--vy-shadow-accent`, and a warm-brown
         `--vy-glass-border` instead of white. Five tokens, all moving in one direction: theming, not drift.
       · `--vy-on-grad` / `--vy-on-grad-soft` are MEASURED PER SHELL (§N.4i/§N.4j) against each shell's own page
         gradient — the owner and operator gradients differ, so one value cannot be AA on both. Unifying them
         would break contrast on one of the two.
     Flattening either family would be overreach dressed as rigour; both are ratified in §N.16.5.

     ⚠️ ORDERING MATTERS. Shells include this BEFORE their own <style>, so a shell's override wins by being
     later in the cascade. Moving this include after a shell's styles would silently undo every override.

     ⚠️ This file changes WHERE a token is declared, never its VALUE. --}}
<style>
    :root {
            /* Type */
            --vy-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --vy-display: 'Sora', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            /* Page + ink */
            --vy-bg: linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%);
            --vy-ink: rgb(43, 35, 32);
            --vy-muted: rgb(95, 88, 83);
            --vy-faint: rgb(108, 100, 95);
            --vy-dark: rgb(42, 33, 28);
            /* Accent */
            --vy-accent: rgb(194, 65, 12);
            --vy-accent-2: rgb(217, 102, 46);
            --vy-accent-3: rgb(232, 112, 58);
            --vy-accent-text: rgb(147, 42, 7);
            /* ⚠️ TEXT ON THE PEACH AVATAR GRADIENT (DO-RESTORE-4). Named rather than inlined so the six copies
               of the avatar concept cannot drift (§N.8f). The value is the ink colour rgb(43,35,32), chosen by
               measuring the plate the INITIALS actually sit on — rgb(242,194,170)..rgb(221,112,58) — where ink
               clears both ends at 9.58/4.73 while the ruling's warm brown #5C2E0B reaches only 3.49 at the
               dark end. The brown came from the gradient's LIGHT stop alone, and on a diagonal gradient the
               worst case for DARK text is the other end. ⚠️ The 4.73 margin is thin: a glyph reaching the
               declared dark stop #D9662E would be 4.32; flattening the background would remove that entirely.
               ⚠️ NOTE the prose above deliberately avoids writing a token name followed by a colon — the
               token-source guard parses `--vy-x:` inside a CSS comment as a real declaration, and this comment
               reddened it once. CSS comments SHIP and are scanned. */
            --vy-on-peach: rgb(43, 35, 32);
            /* ⚠️ THE AVATAR PLATE, FLAT (DO-RESTORE-5). The avatar family previously rode two different
               GRADIENTS, and a gradient has two worst cases at opposite ends: white fails at the light end,
               dark text at the dark end, so no single text colour cleared either gradient. A FLAT plate has
               ONE value and no end to hug. This is the wireframe peach's own light stop, so the avatars stay
               on-brand, and dark ink clears it at 7.60 — robust rather than the 4.32 the gradient left.
               ⚠️ SCOPE: this is the AVATAR surface only. The CTA gradient token is untouched and still
               carries the restored wireframe orange (DO-RESTORE-1a). */
            --vy-avatar-bg: #F0A57A;
            --vy-accent-border: rgba(232, 112, 58, 0.28);
            /* ⚠️ STAR RATING (Wave 4 · DO10-4). ⚠️ NOTE FOR THE NEXT EDITOR: do NOT write a component tag
               like x-star-rating in angle brackets in this comment — Blade compiles component tags ANYWHERE
               in a file, CSS comments included, and an unclosed one breaks the whole view (it cost a 500
               here). A CSS comment is not a Blade comment. The star gold existed as a measured literal in
               the star-rating component and was then hand-copied into four more view files — the exact §N.8f drift the
               token layer exists to stop, and the reason a contrast fix applied to the component silently
               missed the copies. The VALUES are unchanged and still the measured ones: filled #8F5A06 reads
               4.18 / 5.28 / 5.70 and empty #7A6E64 reads 3.58 / 4.52 / 4.88 on the three real plates (peach
               #FBD3BF · owner card #F8F4EF · light card #FFFDFB), empty always below filled so the hierarchy
               survives. ⚠️ EMAILS CANNOT USE THESE — mail clients strip custom properties, so
               emails/review-request.blade.php keeps the literal #8F5A06 deliberately. */
            --vy-star: rgb(143, 90, 6);
            --vy-star-empty: rgb(122, 110, 100);
            /* Amber */
            --vy-amber: rgb(178, 106, 8);
            --vy-amber-2: rgb(224, 146, 30);
            /* Surfaces */
            --vy-glass: rgba(255, 252, 248, 0.58);
            --vy-glass-2: rgba(255, 255, 255, 0.55);
            --vy-wash: #F6EBE2;
            --vy-hairline: rgba(120, 70, 40, 0.10);
            /* Gradients */
            --vy-grad: linear-gradient(140deg, #E8703A, #C2410C);
            --vy-grad-dark: linear-gradient(160deg, #3A2E27, #251C17);
            --vy-grad-peach: linear-gradient(90deg, #F0A57A, #C2410C);
            --vy-peach: rgb(240, 165, 122);
            --vy-orb-1: #E8703A;
            --vy-orb-2: #F0A57A;
            --vy-orb-3: #FBE3CE;
            /* Radii */
            --vy-r-md: 12px;
            --vy-r-pill: 30px;
            /* Shadows */
            --vy-shadow-sm: rgba(150, 90, 50, 0.12) 0 8px 22px, inset rgba(255, 255, 255, 0.6) 0 1px 0;
            --vy-shadow-dark: rgba(60, 30, 10, 0.28) 0 10px 30px;
            /* Other */
            --vy-amber-text: rgb(112, 66, 4);
            --vy-green-text: rgb(20, 84, 62);
    }
</style>
