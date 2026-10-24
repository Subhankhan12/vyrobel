{{-- THE SECURITY PAGE KIT — one stylesheet for BOTH Security pages, the back office's and the owner portal's
     (2026-10-24, the founder's design: the score ring, the warm cards, the switches, three columns). Both shells load the
     same tokens (x-design.tokens), so one sheet styles both pages the same; every class here starts `vy-sx` and lives only
     under `.vy-sx`, so nothing on another page can be reached by it.

     ⚠️ This sheet ships inside the page, so its CSS is page text: no comments in the <style> below, and never a component
     tag anywhere in this file outside this Blade comment (Blade compiles one even inside CSS — it broke /app/search). --}}
<style>
    .vy-sx { --sx-card: rgba(255, 252, 248, 0.58); --sx-line: rgba(120, 70, 40, 0.10); --sx-tint: rgba(217, 102, 46, 0.12);
        --sx-tint-soft: rgba(217, 102, 46, 0.08); --sx-tint-line: rgba(232, 112, 58, 0.30); --sx-field-line: rgb(158, 124, 104);
        --sx-dark: rgb(124, 45, 18); }
    .vy-sx-in { animation: vySxIn .45s cubic-bezier(.22, .61, .36, 1) both; }
    @keyframes vySxIn { from { opacity: 0; } to { opacity: 1; } }

    .vy-sx-card { background: var(--sx-card); -webkit-backdrop-filter: blur(22px) saturate(1.15); backdrop-filter: blur(22px) saturate(1.15);
        border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: 0 14px 40px rgba(150, 90, 50, .14), inset 0 1px 0 rgba(255, 255, 255, .65);
        padding: 20px 22px; min-width: 0; box-sizing: border-box; transition: box-shadow .28s ease, border-color .28s ease; }
    .vy-sx-card:hover { box-shadow: 0 22px 54px rgba(150, 90, 50, .2), inset 0 1px 0 rgba(255, 255, 255, .75); border-color: rgba(255, 255, 255, .98); }
    .vy-sx-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .vy-sx-card-title { margin: 0; font-family: var(--vy-display); font-size: 16px; font-weight: 600; letter-spacing: -.01em; color: var(--vy-ink); }
    .vy-sx-card-sub { margin: 3px 0 0; font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); }

    .vy-sx-hero { display: flex; align-items: center; flex-wrap: wrap; gap: 20px 24px; padding: 20px 24px; margin-bottom: 16px; }
    .vy-sx-hero-id { display: flex; align-items: center; gap: 18px; flex: 1 1 320px; min-width: 0; }
    .vy-sx-ring { position: relative; width: 112px; height: 112px; flex: none; }
    .vy-sx-ring svg { display: block; }
    .vy-sx-ring-mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .vy-sx-ring-n { font-family: var(--vy-display); font-size: 29px; font-weight: 600; line-height: 1; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
    .vy-sx-ring-of { margin-top: 3px; font-size: 9px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint); }
    .vy-sx-tier-chip { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 8px; padding: 3px 11px 3px 9px; border-radius: 30px;
        background: linear-gradient(var(--sx-tint), var(--sx-tint)), rgba(255, 252, 248, .92); }
    .vy-sx-tier-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--vy-accent-3); box-shadow: 0 0 0 3px rgba(232, 112, 58, .18); }
    .vy-sx-tier { font-size: 11.5px; font-weight: 600; color: var(--vy-accent-text); }
    .vy-sx-hero-title { margin: 0; font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; line-height: 1.15; color: var(--vy-ink); }
    .vy-sx-hero-cap { margin: 4px 0 0; max-width: 360px; font-size: 13px; line-height: 1.45; color: var(--vy-muted); }
    .vy-sx-tiles { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; flex: 1.4 1 440px; min-width: 0; }
    .vy-sx-tile { display: flex; flex-direction: column; align-items: flex-start; padding: 13px 14px; border-radius: 15px; background: rgba(255, 255, 255, .55);
        border: 1px solid rgba(120, 70, 40, .12); color: inherit; text-decoration: none; box-sizing: border-box; min-width: 0;
        transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease; }
    .vy-sx-tile:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(150, 90, 50, .16); background: rgba(255, 255, 255, .82); border-color: rgba(120, 70, 40, .2); }
    .vy-sx-tile:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
    .vy-sx-tile-n { font-family: var(--vy-display); font-size: 22px; font-weight: 600; letter-spacing: -.02em; line-height: 1; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
    .vy-sx-tile-l { margin-top: 4px; font-size: 11.5px; color: var(--vy-muted); }

    .vy-sx-ico { width: 28px; height: 28px; flex: none; border-radius: 9px; display: grid; place-items: center; background: var(--sx-tint); color: var(--vy-accent-text); }
    .vy-sx-tile .vy-sx-ico { margin-bottom: 9px; }
    .vy-sx-ico.is-round { width: 32px; height: 32px; border-radius: 50%; }
    .vy-sx-ico.is-lg { width: 40px; height: 40px; border-radius: 50%; background: rgba(120, 70, 40, .08); }

    .vy-sx-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(320px, 100%), 1fr)); gap: 16px; align-items: start; }
    .vy-sx-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

    .vy-sx-rows { margin: 0; padding: 0; list-style: none; }
    .vy-sx-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--sx-line); }
    .vy-sx-rows > :last-child { border-bottom: 0; }
    .vy-sx-row-main { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1 1 auto; }
    .vy-sx-row-title { margin: 0; display: inline-flex; align-items: center; flex-wrap: wrap; gap: 7px; font-size: 13.5px; font-weight: 600; color: var(--vy-ink); overflow-wrap: anywhere; }
    .vy-sx-row-meta { font-size: 12px; line-height: 1.45; color: var(--vy-muted); overflow-wrap: anywhere; }
    .vy-sx-row-act { display: flex; align-items: center; gap: 8px; flex: none; }
    .vy-sx-block { border-bottom: 1px solid var(--sx-line); }
    .vy-sx-block > .vy-sx-row { border-bottom: 0; }
    .vy-sx-block:last-of-type { border-bottom: 0; }
    .vy-sx-sub-rows { margin: 0 0 12px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px; }
    .vy-sx-item { display: flex; align-items: center; gap: 12px; padding: 11px 13px; border-radius: 14px; background: rgba(255, 255, 255, .55); border: 1px solid rgba(120, 70, 40, .10); }
    .vy-sx-item .vy-sx-row-main { flex: 1 1 auto; }
    .vy-sx-empty { padding: 4px 0 2px; font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); }
    .vy-sx-empty strong { display: block; font-size: 13px; font-weight: 600; color: var(--vy-ink); }
    p.vy-sx-empty { margin: 0 0 14px; }
    .vy-sx-note { margin: 8px 0 0; font-size: 12px; line-height: 1.5; color: var(--vy-muted); }
    .vy-sx-note.is-warn { padding: 9px 12px; border-radius: 12px; background: rgba(178, 106, 8, .10); color: rgb(110, 62, 3); }
    .vy-sx-mono { font-variant-numeric: tabular-nums; letter-spacing: .01em; }

    .vy-sx-chip { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 10.5px; font-weight: 600; letter-spacing: .02em;
        background: var(--sx-tint); color: var(--vy-accent-text); }
    .vy-sx-chip.is-ok { background: rgba(26, 127, 75, .12); color: rgb(18, 99, 58); }
    .vy-sx-chip.is-quiet { background: rgba(120, 70, 40, .08); color: var(--vy-muted); }
    .vy-sx-chip.is-warn { background: rgba(178, 106, 8, .14); color: rgb(110, 62, 3); }
    .vy-sx-pts { font-size: 12px; font-weight: 700; color: var(--vy-accent-text); white-space: nowrap; font-variant-numeric: tabular-nums; }

    .vy-sx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; padding: 8px 16px; border-radius: 30px; border: 1px solid transparent;
        font-family: inherit; font-size: 12.5px; font-weight: 600; line-height: 1.2; white-space: nowrap; cursor: pointer; text-decoration: none; box-sizing: border-box;
        transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, opacity .16s ease; }
    .vy-sx-btn:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
    .vy-sx-btn:disabled, .vy-sx-btn[aria-disabled="true"] { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }
    .vy-sx-btn--grad { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
    .vy-sx-btn--grad:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(194, 65, 12, .32); }
    .vy-sx-btn--dark { background: var(--sx-dark); color: #fff; box-shadow: 0 8px 18px rgba(124, 45, 18, .28); }
    .vy-sx-btn--dark:hover:not(:disabled) { transform: translateY(-1px); background: rgb(105, 37, 14); }
    .vy-sx-btn--soft { background: rgba(255, 255, 255, .78); border-color: rgba(120, 70, 40, .16); color: var(--vy-ink); }
    .vy-sx-btn--soft:hover:not(:disabled) { transform: translateY(-1px); background: #fff; border-color: rgba(120, 70, 40, .28); box-shadow: 0 8px 18px rgba(150, 90, 50, .14); }
    .vy-sx-btn--line { background: rgba(255, 255, 255, .45); border-color: var(--sx-tint-line); color: var(--vy-accent-text); }
    .vy-sx-btn--line:hover:not(:disabled) { background: rgba(255, 255, 255, .85); transform: translateY(-1px); }
    .vy-sx-btn--danger { background: rgb(166, 46, 22); color: #fff; }
    .vy-sx-btn--danger:hover:not(:disabled) { background: rgb(140, 36, 16); }
    .vy-sx-btn--text { min-height: 0; padding: 4px 2px; background: none; border: 0; color: var(--vy-muted); font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }
    .vy-sx-btn--sm { min-height: 32px; padding: 6px 13px; font-size: 12px; }

    .vy-sx-switch { position: relative; width: 40px; height: 24px; flex: none; padding: 0; border: 0; border-radius: 30px; background: rgba(120, 70, 40, .26); cursor: pointer; transition: background .16s ease; }
    .vy-sx-switch::after { content: ""; position: absolute; top: 4px; left: 4px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 2px 6px rgba(60, 30, 10, .25); transition: left .16s ease; }
    .vy-sx-switch[aria-checked="true"] { background: var(--vy-grad); }
    .vy-sx-switch[aria-checked="true"]::after { left: 20px; }
    .vy-sx-switch:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
    .vy-sx-switch:disabled, .vy-sx-switch[aria-disabled="true"] { opacity: .45; cursor: not-allowed; }

    .vy-sx-alert { display: flex; gap: 14px; padding: 18px; border-radius: 18px; background: linear-gradient(var(--sx-tint-soft), var(--sx-tint-soft)), rgba(255, 252, 248, .7);
        border: 1px solid var(--sx-tint-line); box-shadow: 0 14px 40px rgba(150, 90, 50, .12); }
    .vy-sx-alert-title { margin: 0; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-accent-text); }
    .vy-sx-alert-text { margin: 5px 0 0; font-size: 13px; line-height: 1.55; color: var(--vy-ink); }
    .vy-sx-alert-acts { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }

    .vy-sx-boost { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
    .vy-sx-boost-row { display: flex; align-items: center; gap: 12px; padding: 11px 12px 11px 13px; border-radius: 15px; background: rgba(255, 255, 255, .6); border: 1px solid rgba(255, 255, 255, .8);
        box-shadow: 0 6px 16px rgba(150, 90, 50, .08); }
    .vy-sx-boost-text { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .vy-sx-boost-label { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
    .vy-sx-boost-desc { font-size: 11.5px; line-height: 1.4; color: var(--vy-muted); }
    .vy-sx-boost-act { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex: none; }
    .vy-sx-done { display: flex; align-items: center; gap: 11px; margin-top: 14px; padding: 13px 16px; border-radius: 14px; background: var(--sx-tint-soft); border: 1px solid var(--sx-tint-line);
        font-size: 13px; font-weight: 600; color: var(--vy-accent-text); }

    .vy-sx-avatar { width: 36px; height: 36px; flex: none; border-radius: 11px; display: grid; place-items: center; background: var(--sx-tint); color: var(--vy-accent-text); font-size: 12px; font-weight: 700; }

    .vy-sx-codes { margin: 0 0 12px; padding: 14px; border-radius: 14px; border: 1px solid var(--sx-tint-line); background: rgba(255, 255, 255, .6); }
    .vy-sx-codes strong { display: block; font-size: 13.5px; color: var(--vy-ink); }
    .vy-sx-codes p { margin: 4px 0 11px; font-size: 12px; line-height: 1.5; color: var(--vy-muted); }
    .vy-sx-codes-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .vy-sx-codes code, .vy-sx-key { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; letter-spacing: .04em; color: var(--vy-ink); background: rgba(120, 70, 40, .06);
        padding: 6px 10px; border-radius: 8px; text-align: center; word-break: break-all; }
    .vy-sx-setup { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-start; margin: 0 0 12px; }
    .vy-sx-qr { width: 156px; height: 156px; flex: none; padding: 8px; box-sizing: border-box; background: #fff; border: 1px solid rgba(120, 70, 40, .14); border-radius: 12px; }
    .vy-sx-qr svg { width: 100%; height: 100%; }
    .vy-sx-inline-form { display: flex; gap: 8px; align-items: flex-start; max-width: 360px; }
    .vy-sx-inline-form .vy-input { flex: 1 1 auto; }
    .vy-sx-status { margin: 0 0 12px; padding: 10px 13px; border-radius: 12px; background: var(--sx-tint-soft); font-size: 13px; color: var(--vy-ink); }

    .vy-sx-flash { margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; font-size: 13.5px; font-weight: 500; }
    .vy-sx-flash.is-ok { background: rgba(26, 127, 75, .1); color: rgb(18, 99, 58); border: 1px solid rgba(26, 127, 75, .22); }
    .vy-sx-flash.is-err { background: rgba(166, 46, 22, .08); color: rgb(140, 36, 16); border: 1px solid rgba(166, 46, 22, .22); }

    .vy-sx-field { display: flex; flex-direction: column; gap: 7px; margin-bottom: 14px; }
    .vy-sx-field:last-child { margin-bottom: 0; }
    .vy-sx-label { font-size: 10.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-faint); }
    .vy-sx .vy-input { width: 100%; height: 42px; box-sizing: border-box; padding: 0 14px; border-radius: 12px; border: 1px solid var(--sx-field-line); background: #fff;
        font-family: inherit; font-size: 14px; color: var(--vy-ink); box-shadow: none; }
    .vy-sx .vy-input:focus { outline: none; border-color: var(--vy-accent); box-shadow: 0 0 0 3px rgba(232, 112, 58, .22); }
    .vy-sx-err { font-size: 12px; font-weight: 500; color: var(--vy-accent-text); }
    .vy-sx-errors { margin: 0 0 14px; padding: 10px 13px; border-radius: 12px; background: rgba(166, 46, 22, .08); font-size: 12.5px; font-weight: 500; color: rgb(140, 36, 16); }
    .vy-sx-errors div + div { margin-top: 4px; }
    .vy-sx-hint { margin: -4px 0 14px; font-size: 11.5px; line-height: 1.45; color: var(--vy-muted); }
    .vy-sx-pair { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .vy-sx-pair .vy-sx-field { margin-bottom: 14px; }

    .vy-sx .vy-pw { position: relative; display: flex; width: 100%; }
    .vy-sx .vy-pw .vy-pw-input { width: 100%; padding-right: 44px; }
    .vy-sx .vy-pw-eye { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; display: grid; place-items: center; border: 0; background: transparent;
        color: var(--vy-muted); cursor: pointer; border-radius: 10px; transition: color .14s ease, background .14s ease; }
    .vy-sx .vy-pw-eye:hover { color: var(--vy-accent-text); background: rgba(194, 65, 12, .08); }
    .vy-sx .vy-pw-eye:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 1px; }

    .vy-sx-scrim { position: fixed; inset: 0; z-index: 90; display: grid; place-items: center; padding: 16px; box-sizing: border-box; background: rgba(43, 35, 32, .34);
        -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
    .vy-sx-dialog { width: min(460px, 100%); max-height: calc(100dvh - 32px); overflow: auto; box-sizing: border-box; border-radius: 22px; background: rgb(255, 251, 248);
        border: 1px solid rgba(255, 255, 255, .9); box-shadow: 0 30px 80px rgba(60, 30, 10, .28); outline: none; }
    .vy-sx-dialog-head { display: flex; align-items: flex-start; gap: 13px; padding: 22px 22px 0; }
    .vy-sx-dialog-head .vy-sx-ico { width: 40px; height: 40px; border-radius: 12px; }
    .vy-sx-dialog-head .vy-sx-ico.is-danger { background: rgba(166, 46, 22, .1); color: rgb(140, 36, 16); }
    .vy-sx-dialog-text { flex: 1 1 auto; min-width: 0; }
    .vy-sx-dialog-title { margin: 0; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); }
    .vy-sx-dialog-sub { margin: 4px 0 0; font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); }
    .vy-sx-x { flex: none; width: 32px; height: 32px; display: grid; place-items: center; border: 0; border-radius: 10px; background: transparent; color: var(--vy-muted); cursor: pointer; }
    .vy-sx-x:hover { background: rgba(120, 70, 40, .08); color: var(--vy-ink); }
    .vy-sx-x:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 1px; }
    .vy-sx-dialog-body { padding: 18px 22px; }
    .vy-sx-dialog-body > .vy-sx-errors:first-child { margin-top: 0; }
    .vy-sx-dialog-body.vy-sx-pre { padding-bottom: 0; }
    .vy-sx-gap { margin-bottom: 12px; }
    .vy-sx-dialog-foot { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; padding: 0 22px 22px; }

    .vy-sx-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

    @media (max-width: 900px) {
        .vy-sx-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); flex-basis: 100%; }
    }
    @media (max-width: 560px) {
        .vy-sx-hero, .vy-sx-card { padding: 18px; }
        .vy-sx-hero-id { gap: 14px; }
        .vy-sx-row { gap: 10px; }
        .vy-sx-boost-row { gap: 10px; padding: 11px; }
        .vy-sx-ring { width: 92px; height: 92px; }
        .vy-sx-ring svg { width: 92px; height: 92px; }
        .vy-sx-pair { grid-template-columns: minmax(0, 1fr); }
        .vy-sx-alert { flex-direction: column; }
        .vy-sx-dialog-foot .vy-sx-btn { flex: 1 1 auto; }
    }
    @media (prefers-reduced-motion: reduce) {
        .vy-sx-in { animation: none; }
        .vy-sx-tile, .vy-sx-btn, .vy-sx-switch, .vy-sx-switch::after, .vy-sx-card { transition: none; }
        .vy-sx-tile:hover, .vy-sx-btn:hover { transform: none; }
    }
</style>
