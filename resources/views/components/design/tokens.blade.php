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
            --vy-accent-border: rgba(232, 112, 58, 0.28);
            /* Amber */
            --vy-amber: rgb(178, 106, 8);
            --vy-amber-2: rgb(224, 146, 30);
            /* Surfaces */
            --vy-glass: rgba(255, 252, 248, 0.58);
            --vy-glass-2: rgba(255, 255, 255, 0.55);
            --vy-wash: #F6EBE2;
            --vy-hairline: rgba(120, 70, 40, 0.10);
            /* Gradients */
            --vy-grad: linear-gradient(140deg, #D14102, #C2410C);
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
