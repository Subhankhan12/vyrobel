{{-- SUPER ADMIN — the authenticated PLATFORM console shell (sidebar + topbar + content).

     ⚠️ DESIGN RULING (founder, 2026-09-24) — THIS SHELL NOW WEARS THE PRODUCT'S OWN DESIGN SYSTEM.
     It shipped as a deliberately separate "control-room" palette (deep slate + indigo), recorded in
     DECISIONS.md, so the highest-privilege realm could never be mistaken for a tenant portal. The founder
     ruled the other way: the admin panel must look like the rest of Vyrobel — same colours, same icons,
     same animations, same buttons. So the `--ad-*` token names are KEPT (every `.ad-*` rule and all nine
     admin views keep working untouched) and their VALUES are remapped onto the canonical `--vy-*` palette
     from <x-design.tokens />. One indirection layer, no per-view churn.

     ⚠️ ORDERING: <x-design.tokens /> is included BEFORE this shell's own <style>, so the `--ad-*` mapping
     below can read `--vy-*` and any deliberate override here wins by cascade order. Moving it after would
     leave every `--ad-*` token resolving to nothing (and CSS would silently fall back to `inherit`).

     The viewer is resolved from the `admin` guard only. noindex.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@props(['active' => 'overview', 'title' => 'Overview', 'subtitle' => '', 'back' => null, 'backLabel' => 'Back'])
@php
    $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
    $adminName = $admin?->name ?? 'Platform admin';
    $initials = \Illuminate\Support\Str::of($adminName)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('');
    // The nav — [key, label, url, live, icon-path, capability]. The icon set is the SAME 24×24 stroked line
    // family the operator shell draws (fill:none, stroke:currentColor, width 1.6), so the two consoles read alike.
    // ⚠️ ADMIN 01 · Phase 1 — an item whose capability this admin's ROLE lacks is not shown at all: the nav asks
    //    the SAME matrix (PlatformAdminRole) the route gate asks, so a link can never lead to a 403.
    $nav = [
        ['health', 'Health', route('admin.dashboard'), true, 'M3 12h4l3-8 4 16 3-8h4', 'console.view'],
        ['overview', 'Overview', route('admin.overview'), true, 'M3 13h8V3H3zM13 21h8v-6h-8zM13 3v8h8V3zM3 21h8v-6H3z', 'console.view'],
        ['customers', 'Organisations', route('admin.customers.index'), true, 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', 'console.view'],
        ['triage', 'Triage', route('admin.customers.triage'), true, 'M4 5h16l-6 8v5l-4 2v-7z', 'console.view'],
        ['support', 'Support', route('admin.support.index'), true, 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4.9 4.9l4.3 4.3M14.8 14.8l4.3 4.3M14.8 9.2l4.3-4.3M4.9 19.1l4.3-4.3', 'support.use'],
        ['revenue', 'Revenue', route('admin.revenue'), true, 'M3 17l6-6 4 4 8-8M14 7h7v7', 'billing.view'],
        ['funnel', 'Signup funnel', route('admin.funnel'), true, 'M3 4h18l-7 8v6l-4 2v-8z', 'console.view'],
        ['privacy', 'Privacy desk', route('admin.privacy.index'), true, 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6zM9.5 12l2 2 3.5-4', 'console.view'],
        ['hotels', 'Statistics', route('admin.hotels'), true, 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6', 'console.view'],
        ['announcements', 'Announcements', route('admin.announcements.index'), true, 'M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1zM16 8a5 5 0 0 1 0 8', 'console.view'],
        ['status', 'Status page', route('admin.status.index'), true, 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2', 'console.view'],
        ['plans', 'Plans', route('admin.plans.index'), true, 'M3 7h18v12H3zM3 11h18M7 15h4', 'console.view'],
        ['switches', 'Kill switches', route('admin.switches.index'), true, 'M8 7h8a5 5 0 0 1 0 10H8A5 5 0 0 1 8 7zM16 14.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z', 'console.view'],
        ['audit', 'Audit log', route('admin.audit.index'), true, 'M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01', 'console.view'],
        ['admins', 'Admins', route('admin.admins.index'), true, 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM8.5 15.5c.6-1.5 2-2.5 3.5-2.5s2.9 1 3.5 2.5', 'admins.view'],
        ['activity', 'Security & activity', route('admin.activity.index'), true, 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', 'console.view'],
        ['integrations', 'Integrations', route('admin.integrations.index'), true, 'M12 3v4M12 17v4M3 12h4M17 12h4M6 6l3 3M15 15l3 3M18 6l-3 3M9 15l-3 3', 'integrations.view'],
    ];
    $nav = array_values(array_filter($nav, fn (array $item): bool => $admin?->may($item[5]) ?? false));
    $roleLabel = $admin?->roleEnum()?->label() ?? 'No role';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>{{ $title }} · Vyrobel Platform</title>
    {{-- The favicon now carries the product's terracotta, not the retired indigo. --}}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23C2410C'/%3E%3Ctext x='16' y='23' font-family='sans-serif' font-size='19' font-weight='700' fill='white' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
    {{-- Self-hosted Inter + Sora (audit F6-6) — no third-party font request; see public/fonts/fonts.css --}}
    <link href="/fonts/fonts.css" rel="stylesheet">
    {{-- ⚠️ THE CANONICAL PALETTE FIRST (DO-TOKEN-1, §N.8f) — this shell's own <style> follows. --}}
    <x-design.tokens />
    <style>
        :root {
            /* ⚠️ THE THREE DELIBERATE PER-SHELL TOKENS — declared HERE because they are NOT in the shared
               palette file. `--vy-glass-border`, `--vy-shadow` and `--vy-shadow-accent` are per-shell
               overrides by design (tokens.blade.php only carries what is IDENTICAL everywhere), so a shell
               that references them without declaring them resolves to nothing and CSS silently drops the
               whole declaration — no error, no console line, just a card with no border and no shadow.
               Caught by a token-resolution check, not by looking. Values copied verbatim from the operator
               shell, whose gradient this one now shares. */
            --vy-glass-border: rgba(255, 255, 255, 0.75);
            --vy-shadow: rgba(150, 90, 50, 0.16) 0 14px 40px, inset rgba(255, 255, 255, 0.65) 0 1px 0;
            --vy-shadow-accent: rgba(194, 65, 12, 0.30) 0 4px 12px;
            /* ---- The `--ad-*` → `--vy-*` mapping. NAMES kept, VALUES adopted. ------------------- */
            --ad-ink: var(--vy-ink); --ad-muted: var(--vy-muted); --ad-faint: var(--vy-faint);
            /* ⚠️ --ad-accent is the BRAND FILL (gradients, borders, focus rings, icons); --ad-accent-text
               is the same hue DARKENED for use AS TEXT on a glass card. They are not interchangeable:
               rgb(194,65,12) as text on the card plate reads below AA, which is exactly why the shared
               palette carries a separate --vy-accent-text. Every text role below uses the -text one. */
            --ad-accent: var(--vy-accent);
            --ad-accent-2: var(--vy-accent-2);
            --ad-accent-text: var(--vy-accent-text);
            --ad-accent-soft: rgba(232, 112, 58, 0.14);
            --ad-glass: var(--vy-glass); --ad-glass-2: rgba(255, 255, 255, 0.78);
            --ad-border: var(--vy-glass-border); --ad-border-strong: var(--vy-accent-border);
            --ad-grad: var(--vy-grad);
            /* ⚠️ --ad-danger WAS NEVER DECLARED. `.ad-pill--suspended` and `.ad-btn--danger` both read it,
               so both rendered with an INVALID colour and silently inherited the body ink — a "Suspended"
               chip that looked like ordinary text. Found while remapping; declared here, once. The value is
               the palette's existing danger ink (the operator shell's `.vy-acct-link--danger`). */
            --ad-danger: #8F3110;
            --ad-ok: var(--vy-green-text);
            --ad-shadow: var(--vy-shadow-sm);
            /* ⚠️ TEXT THAT SITS DIRECTLY ON THE PAGE GRADIENT, not on a glass card — the sidebar nav, its
               section label, the topbar subline. The card tokens do not apply there: the gradient is darker
               than a card, and --vy-muted measures below AA on it. These two values are the ones the
               operator and owner shells derived by SAMPLING their own composited plate (§N.4i), and this
               shell now paints the IDENTICAL `--vy-bg` gradient with the identical orbs, so they carry over
               exactly. ⚠️ If the page background is ever changed here, re-sample — do not re-use these. */
            --ad-on-grad: rgb(68, 59, 54);
            --ad-on-grad-soft: rgb(88, 78, 72);
            --ad-r-lg: 20px; --ad-r-md: var(--vy-r-md); --ad-r-sm: 10px; --ad-r-pill: var(--vy-r-pill);
            --ad-sans: var(--vy-sans);
            --ad-display: var(--vy-display);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: var(--ad-sans); color: var(--ad-ink); font-size: 15px; line-height: 1.5; min-height: 100vh;
            -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
            background: var(--vy-bg);
            background-attachment: fixed;
            position: relative; overflow-x: hidden;
        }
        /* ---- The product's own keyframes (lifted verbatim from the operator/owner shells) -------- */
        @keyframes adFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes adShimmer { 0% { opacity: .25; transform: translateX(-4%) rotate(28deg); } 50% { opacity: .45; transform: translateX(3%) rotate(28deg); } 100% { opacity: .25; transform: translateX(-4%) rotate(28deg); } }
        @keyframes adDrift { 0% { transform: translate(0, 0); } 50% { transform: translate(46px, 28px); } 100% { transform: translate(0, 0); } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; } }
        /* Ambient light streaks + floating colour orbs — the same recipe the operator and owner shells
           paint, behind everything (z-index 0), which is what makes the frosted glass read as glass. */
        .ad-amb { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .ad-amb > div { position: absolute; pointer-events: none; }
        .ad-amb-streak1 { top: -180px; right: -120px; width: 640px; height: 640px; background: radial-gradient(circle, rgba(255, 255, 255, .35), transparent 68%); filter: blur(10px); }
        .ad-amb-streak2 { top: 22%; left: -10%; width: 70%; height: 260px; background: linear-gradient(120deg, rgba(255, 240, 220, .5), transparent); transform: rotate(28deg); filter: blur(24px); animation: adShimmer 9s ease-in-out infinite; }
        .ad-amb-orb1 { top: 80px; left: 24%; width: 380px; height: 380px; background: var(--vy-orb-1); border-radius: 50%; filter: blur(70px); opacity: .55; animation: adDrift 14s ease-in-out infinite; }
        .ad-amb-orb2 { top: 470px; left: 36%; width: 320px; height: 320px; background: var(--vy-orb-2); border-radius: 50%; filter: blur(70px); opacity: .55; }
        .ad-amb-orb3 { top: 220px; right: 4%; width: 300px; height: 300px; background: var(--vy-orb-3); border-radius: 50%; filter: blur(70px); opacity: .55; }

        .ad-layout { display: grid; grid-template-columns: 236px minmax(0, 1fr); min-height: 100vh; position: relative; z-index: 1; }
        /* The frosted nav floats directly on the gradient — no panel background, no divider rule. */
        .ad-side { padding: 26px 16px 30px; display: flex; flex-direction: column; gap: 22px; position: sticky; top: 0; align-self: start; height: 100vh; overflow-y: auto; animation: adFadeUp .45s cubic-bezier(.22, .61, .36, 1) both; }
        .ad-brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; }
        .ad-brand-mark { width: 34px; height: 34px; border-radius: var(--ad-r-sm); background: var(--ad-grad); color: #fff; font-family: var(--ad-display); font-weight: 600; font-size: 17px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
        .ad-brand-name { font-family: var(--ad-display); font-weight: 600; font-size: 17px; letter-spacing: -0.01em; }
        .ad-brand-tag { font-size: 9.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--ad-accent-text); }
        .ad-nav { display: flex; flex-direction: column; gap: 3px; }
        .ad-nav-label { font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--ad-on-grad-soft); padding: 0 12px 6px; }
        .ad-nav a, .ad-nav .ad-soon { display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 500; color: var(--ad-on-grad); text-decoration: none; padding: 9px 12px; border-radius: var(--ad-r-md); transition: background .16s ease, color .16s ease; }
        .ad-nav .ad-ico { width: 16px; height: 16px; flex: none; }
        .ad-nav a:hover { background: rgba(255, 255, 255, 0.35); }
        /* The active item reads terracotta on frosted glass — operator/owner exact. */
        .ad-nav a.is-active { color: var(--ad-accent-text); background: rgba(255, 255, 255, 0.72); font-weight: 500; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 4px 14px rgba(150, 90, 50, 0.14); border: 1px solid rgba(255, 255, 255, 0.8); }
        .ad-nav a:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-nav .ad-soon { cursor: default; justify-content: space-between; }
        .ad-soon-tag { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ad-on-grad); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 2px 6px; }
        .ad-main { display: flex; flex-direction: column; min-width: 0; padding: 22px 30px 60px; }
        .ad-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 22px; }
        .ad-top-lead { min-width: 0; }
        /* ⚠️ THE TOPBAR TITLE *IS* THE PAGE h1 (operator convention). It used to be a 15px contextual label
           with every admin view then repeating the same words as its own in-content `.ad-h1` — invisible at
           15px, a blatant double-heading at the product's 28px. The views no longer repeat it; they pass
           their lead line as `subtitle` instead.
           ⚠️ `.ad-h1` / `.ad-lead` / `.ad-eyebrow` / `.ad-stat` are consequently used by NO view today. They
           are kept DEFINED on purpose: they are the classes a new admin page would reach for, and a shell
           that leaves them undefined is how `.ad-field-label` and `.vy-sr-only` came to be silently missing
           here. `.ad-stat-sub` is very much alive (11 uses) as in-card explanatory copy. */
        .ad-top-title { font-family: var(--ad-display); font-weight: 600; font-size: 28px; letter-spacing: -0.02em; margin: 0; }
        .ad-top-sub { font-size: 12.5px; color: var(--ad-on-grad); margin-top: 3px; }
        .ad-user { display: flex; align-items: center; gap: 12px; }
        /* The account chip — the operator topbar's frosted pill, same glass recipe. */
        .ad-chip { display: flex; align-items: center; gap: 9px; padding: 5px 5px 5px 14px; border-radius: var(--ad-r-pill); border: 1px solid var(--ad-border); background: var(--ad-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65); white-space: nowrap; transition: background .15s ease, border-color .15s ease; }
        a.ad-chip:hover { background: rgba(255, 255, 255, 0.72); }
        a.ad-chip.is-active { border-color: var(--ad-border-strong); }
        a.ad-chip:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        /* ⚠️ THE AVATAR IS THE CANONICAL FLAT PEACH PLATE (DO-RESTORE-5), not a gradient: a gradient has two
           worst cases at opposite ends and no single text colour clears both. Flat plate + --vy-on-peach ink
           measures 7.60. The old rule here was `#fff` on the indigo gradient; white on peach would be 1.9. */
        .ad-ava { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-avatar-bg); color: var(--vy-on-peach); font-size: 11.5px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .ad-chip-id { text-align: right; line-height: 1.2; }
        .ad-chip-name { display: block; font-size: 12.5px; font-weight: 500; color: var(--ad-ink); line-height: 1.25; }
        .ad-chip-role { display: block; font-size: 10.5px; color: var(--ad-muted); line-height: 1.2; }
        .ad-signout { background: rgba(255, 255, 255, 0.6); border: 1px solid var(--ad-border); color: var(--ad-ink); font-family: var(--ad-sans); font-size: 12.5px; font-weight: 600; cursor: pointer; padding: 8px 15px; border-radius: var(--ad-r-pill); box-shadow: var(--ad-shadow); transition: background .15s ease, transform .15s ease; }
        .ad-signout:hover { background: rgba(255, 255, 255, 0.85); transform: translateY(-1px); }
        .ad-signout:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        .ad-content { max-width: 1180px; width: 100%; animation: adFadeUp .6s cubic-bezier(.2,.7,.2,1) both; }
        .ad-h1 { font-family: var(--ad-display); font-weight: 600; font-size: 24px; letter-spacing: -0.02em; margin: 0 0 4px; }
        .ad-lead { color: var(--ad-on-grad); font-size: 14px; margin: 0 0 24px; }
        /* ---- Reusable surfaces — the product's frosted-glass card, verbatim ---------------------- */
        .ad-card { background: var(--ad-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--ad-border); border-radius: var(--ad-r-lg); box-shadow: var(--vy-shadow); padding: 22px 24px; }
        .ad-eyebrow { font-size: 10.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: var(--ad-accent-text); }
        .ad-stat { font-family: var(--ad-display); font-weight: 600; font-size: 40px; letter-spacing: -0.02em; margin: 8px 0 2px; font-variant-numeric: tabular-nums; }
        .ad-stat-sub { color: var(--ad-muted); font-size: 13px; }
        /* Metrics / KPIs */
        .ad-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .ad-kpi { background: var(--ad-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--ad-border); border-radius: var(--ad-r-lg); box-shadow: var(--vy-shadow); padding: 18px 20px; transition: transform .18s ease, box-shadow .18s ease; }
        .ad-kpi:hover { transform: translateY(-2px); }
        .ad-kpi-label { font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--ad-muted); }
        .ad-kpi-val { font-family: var(--ad-display); font-weight: 600; font-size: 30px; letter-spacing: -0.02em; margin: 9px 0 2px; font-variant-numeric: tabular-nums; }
        .ad-kpi-sub { color: var(--ad-faint); font-size: 12.5px; }
        .ad-grid-2 { display: grid; grid-template-columns: 1.3fr 1fr; gap: 18px; margin-top: 18px; }
        @media (max-width: 900px) { .ad-grid-2 { grid-template-columns: 1fr; } }
        .ad-panel-title { font-family: var(--ad-display); font-weight: 600; font-size: 15px; margin: 0 0 16px; }
        /* Mini bar chart — the product's warm bars, the peak in the accent gradient. */
        .ad-chart { display: flex; align-items: flex-end; gap: 10px; height: 130px; }
        .ad-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; min-width: 0; }
        .ad-bar { width: 100%; max-width: 34px; border-radius: 6px 6px 3px 3px; background: rgba(232, 112, 58, .30); transition: background .2s ease; min-height: 4px; }
        .ad-bar.is-peak { background: var(--ad-grad); }
        .ad-bar-label { font-size: 10.5px; color: var(--ad-faint); }
        .ad-bar-val { font-size: 11px; font-weight: 600; color: var(--ad-ink); }
        /* Distribution / breakdown rows */
        .ad-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 1px solid var(--vy-hairline); font-size: 13.5px; }
        .ad-row:first-child { border-top: none; }
        .ad-row-k { color: var(--ad-muted); }
        .ad-row-v { font-weight: 600; }
        /* Status pills — the operator `.vy-tag` tone family, same hues, same weights. */
        .ad-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: var(--ad-r-pill); background: rgba(166, 154, 146, .18); color: var(--ad-faint); }
        .ad-pill--active { background: rgba(52, 140, 90, .14); color: var(--vy-green-text); }
        .ad-pill--trialing { background: rgba(232, 112, 58, .14); color: var(--ad-accent-text); }
        .ad-pill--suspended { background: rgba(143, 49, 16, .12); color: var(--ad-danger); }
        .ad-pill--cancelled { background: rgba(166, 154, 146, .18); color: var(--ad-faint); }
        .ad-empty { text-align: center; color: var(--ad-muted); padding: 40px 20px; font-size: 13.5px; }
        /* Tables */
        .ad-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ad-table th { text-align: left; font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--ad-muted); padding: 0 14px 12px; }
        .ad-table td { padding: 13px 14px; border-top: 1px solid var(--vy-hairline); }
        .ad-table tbody tr { transition: background .14s ease; }
        .ad-table tbody tr:hover { background: rgba(255, 255, 255, 0.45); }
        .ad-table a.ad-cell-link { color: var(--ad-ink); font-weight: 600; text-decoration: none; }
        .ad-table a.ad-cell-link:hover { color: var(--ad-accent-text); }
        .ad-table a.ad-sort { color: inherit; text-decoration: none; white-space: nowrap; }
        .ad-table a.ad-sort:hover { color: var(--ad-accent-text); }
        .ad-bucket { display: grid; grid-template-columns: 1fr auto; gap: 6px 18px; align-items: baseline; }
        .ad-bucket-count { font-size: 28px; font-weight: 700; letter-spacing: -.02em; color: var(--ad-ink); }
        .ad-table .ad-num { text-align: right; font-variant-numeric: tabular-nums; color: var(--ad-muted); }
        /* Filters bar */
        .ad-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .ad-filters input, .ad-filters select { padding: 9px 12px; border-radius: var(--ad-r-sm); border: 1px solid var(--ad-border); background: rgba(255, 255, 255, 0.75); color: var(--ad-ink); font-family: var(--ad-sans); font-size: 13.5px; }
        .ad-filters input:focus, .ad-filters select:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
        .ad-filters input:focus-visible, .ad-filters select:focus-visible, .ad-table a:focus-visible, .ad-btn:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        /* Buttons — the product's pill buttons: gradient primary, frosted ghost, warm danger. */
        .ad-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 16px; border-radius: var(--ad-r-pill); font-family: var(--ad-sans); font-size: 13px; font-weight: 600; color: #fff; background: var(--ad-grad); border: 1px solid transparent; cursor: pointer; box-shadow: var(--vy-shadow-accent); transition: transform .15s ease, box-shadow .15s ease, background .15s ease; text-decoration: none; }
        .ad-btn:hover { transform: translateY(-1px); }
        .ad-btn--ghost { background: rgba(255, 255, 255, .6); color: var(--ad-ink); border-color: var(--ad-border); box-shadow: var(--ad-shadow); }
        .ad-btn--ghost:hover { background: rgba(255, 255, 255, .85); }
        .ad-btn--danger { background: rgba(143, 49, 16, .10); color: var(--ad-danger); border-color: rgba(143, 49, 16, .30); box-shadow: none; }
        .ad-btn--danger:hover { background: rgba(143, 49, 16, .16); }
        .ad-btn--sm { padding: 7px 13px; font-size: 12.5px; }
        /* Flash notice — success by default; the error modifier below replaces the dark-theme inline
           `color: #FBD5D5` the admin views were carrying (pale pink is invisible on a light wash). */
        .ad-flash { padding: 11px 15px; border-radius: var(--ad-r-md); background: rgba(52, 140, 90, .12); border: 1px solid rgba(52, 140, 90, .34); color: var(--vy-green-text); font-size: 13px; font-weight: 500; margin-bottom: 18px; }
        .ad-flash--error { background: rgba(143, 49, 16, .10); border-color: rgba(143, 49, 16, .30); border-left: 3px solid var(--ad-danger); color: var(--ad-danger); }
        /* A recessed panel (recovery codes, secret keys) — was a dark slate well; now warm cream. */
        .ad-well { background: rgba(255, 255, 255, 0.58); border: 1px solid var(--ad-border); border-radius: var(--ad-r-md); }
        /* Form fields */
        .ad-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--ad-muted); margin-bottom: 7px; }
        .ad-input { width: 100%; padding: 11px 13px; border-radius: var(--ad-r-sm); border: 1px solid var(--ad-border); background: rgba(255, 255, 255, 0.75); color: var(--ad-ink); font-family: var(--ad-sans); font-size: 14px; transition: border-color .15s ease, box-shadow .15s ease; }
        .ad-input::placeholder { color: var(--ad-faint); }
        .ad-input:focus { outline: none; border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
        .ad-input:focus-visible, input[type=checkbox]:focus-visible { outline: 2px solid var(--ad-accent); outline-offset: 2px; }
        input[type=checkbox] { accent-color: var(--ad-accent); width: 16px; height: 16px; }
        /* ---- Selects -------------------------------------------------------------------------
           ⚠️ A NATIVE SELECT IGNORES ALMOST EVERYTHING AROUND IT. `.ad-input` gave these a frosted
           background and a warm border, and the browser then drew an OS control inside it — grey gradient
           on macOS, a square arrow on Windows — at its own height, so the one control on a form that was
           NOT in the product's language was the dropdown. `appearance: none` is what surrenders the OS
           widget; without it every rule below is decoration the browser paints over.
           ⚠️ THE CHEVRON IS A BACKGROUND IMAGE, AND ITS COLOUR IS THEREFORE A LITERAL. A data: URI cannot
           read a custom property, so the two hexes below are `--vy-muted` rgb(95,88,83) = #5F5853 and
           `--vy-accent-text` rgb(147,42,7) = #932A07, written out. ⚠️ If either token is ever re-measured,
           these do NOT follow it — that is the one drift this file cannot prevent, so it is named here.
           The glyph is the same `M6 9l6 6 6-6` chevron the operator's account menu draws.
           ⚠️ `option` styling is honoured on Windows/Linux and IGNORED by macOS, which renders the popup
           natively. It is set anyway (it costs nothing and helps two of the three platforms), but the OPEN
           list is deliberately NOT claimed to be themed — that needs a JS listbox, which is not worth the
           accessibility debt on four dropdowns. */
        select.ad-input, .ad-filters select {
            appearance: none; -webkit-appearance: none; -moz-appearance: none;
            padding-right: 38px; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%235F5853' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 13px center; background-size: 14px 14px;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        select.ad-input:hover, .ad-filters select:hover { background-color: rgba(255, 255, 255, 0.92); border-color: var(--ad-border-strong); }
        /* Focus + open: the chevron picks up the accent, so the control reads active the way the nav and
           the buttons do. Same glyph, recoloured — NOT a second icon. */
        select.ad-input:focus, .ad-filters select:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23932A07' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        }
        select.ad-input:disabled, .ad-filters select:disabled { opacity: .55; cursor: not-allowed; }
        select.ad-input option, .ad-filters select option { background: #FFFDFB; color: var(--ad-ink); }
        /* Detail (definition list) */
        .ad-back { display: inline-flex; align-items: center; gap: 6px; color: var(--ad-on-grad); text-decoration: none; font-size: 12.5px; margin-bottom: 4px; }
        .ad-back:hover { color: var(--ad-accent-text); }
        .ad-dl { display: grid; grid-template-columns: 160px 1fr; gap: 12px 18px; font-size: 13.5px; }
        .ad-dl dt { color: var(--ad-muted); }
        .ad-dl dd { margin: 0; font-weight: 500; }
        @media (max-width: 820px) { .ad-layout { grid-template-columns: 1fr; } .ad-side { position: static; height: auto; flex-direction: row; overflow-x: auto; align-items: center; } .ad-nav { flex-direction: row; } .ad-nav-label { display: none; } .ad-main { padding: 18px; } }
        /* ---- ADMIN 01 · Phase 1 — the health board. Four states; grey is its own state, never green. ------ */
        .ad-health-summary { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .ad-health-count { display: inline-flex; align-items: center; gap: 7px; padding: 6px 13px; border-radius: var(--ad-r-pill); background: var(--ad-glass); border: 1px solid var(--ad-border); font-size: 12.5px; font-weight: 600; color: var(--ad-ink); }
        .ad-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
        .ad-dot--green { background: rgb(52, 140, 90); } .ad-dot--amber { background: var(--vy-amber-2); } .ad-dot--red { background: var(--ad-danger); } .ad-dot--grey { background: rgb(150, 142, 136); }
        .ad-health { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
        .ad-hcard { position: relative; background: var(--ad-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--ad-border); border-radius: var(--ad-r-lg); box-shadow: var(--vy-shadow); padding: 18px 20px 18px 24px; overflow: hidden; }
        .ad-hcard::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
        .ad-hcard--green::before { background: rgb(52, 140, 90); } .ad-hcard--amber::before { background: var(--vy-amber-2); } .ad-hcard--red::before { background: var(--ad-danger); } .ad-hcard--grey::before { background: rgb(150, 142, 136); } .ad-hcard--off::before { background: rgb(150, 142, 136); }
        .ad-hcard-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .ad-hcard-title { font-family: var(--ad-display); font-weight: 600; font-size: 14.5px; margin: 0; }
        .ad-hcard-headline { font-family: var(--ad-display); font-weight: 600; font-size: 17px; letter-spacing: -0.01em; margin: 10px 0 8px; }
        .ad-hcard--red .ad-hcard-headline { color: var(--ad-danger); }
        .ad-hcard-lines { list-style: none; margin: 0; padding: 0; font-size: 12.5px; color: var(--ad-muted); }
        .ad-hcard-lines li { padding: 3px 0; border-top: 1px solid var(--vy-hairline); overflow-wrap: anywhere; }
        .ad-hcard-lines li:first-child { border-top: none; }
        .ad-hcard-note { font-size: 11.5px; color: var(--ad-faint); margin: 10px 0 0; line-height: 1.5; }
        .ad-hpill { font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; padding: 3px 9px; border-radius: var(--ad-r-pill); white-space: nowrap; }
        .ad-hpill--green { background: rgba(52, 140, 90, .14); color: var(--vy-green-text); }
        .ad-hpill--amber { background: rgba(224, 146, 30, .16); color: var(--vy-amber-text); }
        .ad-hpill--red { background: rgba(143, 49, 16, .12); color: var(--ad-danger); }
        .ad-hpill--grey { background: rgba(150, 142, 136, .18); color: var(--ad-muted); }
        .ad-hpill--off { background: rgba(150, 142, 136, .18); color: var(--ad-muted); }
        /* The reason field every write carries — one look everywhere. */
        .ad-reason { display: block; width: 100%; margin: 8px 0; }
        .ad-reason-hint { font-size: 11px; color: var(--ad-faint); margin: -2px 0 8px; }
        /* ⚠️ ADMIN 03 — the phone-width and figure fixes, once, for every page:
           F4  the header stacks (admin chip above the title) instead of crushing the title to one word per line;
           F5  a card holding a table scrolls ITSELF, so the page never scrolls sideways; label/value lists shrink and
               long unbroken values (emails, slugs) wrap instead of pushing the page wider;
           F16 every figure uses tabular digits so columns line up; F17 a timestamp never breaks across two lines. */
        .ad-flash-list { margin: 0; padding-left: 18px; }
        .ad-card:has(table) { overflow-x: auto; }
        /* A <select> is as wide as its longest option — one long organisation name made the audit log's filter 442px
           on a 390px phone (found by the pre-walk). It may shrink to its row; the browser truncates the label. */
        .ad-filters select, .ad-filters input, select.ad-input { max-width: 100%; min-width: 0; }
        .ad-dl { grid-template-columns: minmax(0, 160px) minmax(0, 1fr); }
        .ad-dl dd, .ad-row-k, .ad-row-v, .ad-chip-name { overflow-wrap: anywhere; }
        .ad-row-v, .ad-bucket-count, .ad-bar-val, .ad-kpi-val, .ad-stat { font-variant-numeric: tabular-nums; }
        .ad-time { white-space: nowrap; }
        @media (max-width: 640px) {
            .ad-top { flex-direction: column-reverse; align-items: stretch; gap: 12px; }
            .ad-user { justify-content: flex-end; flex-wrap: wrap; }
            .ad-dl { grid-template-columns: minmax(0, 1fr); gap: 2px 0; }
            .ad-dl dd { margin-bottom: 10px; }
        }
        .ad-inline-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0; }
        .ad-inline-form .ad-input { width: auto; flex: 1 1 180px; padding: 8px 11px; font-size: 13px; }
        .ad-once { padding: 14px 16px; border-radius: var(--ad-r-md); background: rgba(224, 146, 30, .10); border: 1px solid rgba(224, 146, 30, .35); border-left: 3px solid var(--vy-amber-2); margin-bottom: 18px; font-size: 13px; }
        .ad-once code { display: block; margin-top: 8px; padding: 9px 11px; background: rgba(255,255,255,.75); border: 1px solid var(--ad-border); border-radius: var(--ad-r-sm); font-size: 12px; word-break: break-all; user-select: all; }
            /* ⚠️⚠️ DEFINED, NOT ASSUMED. A visually-hidden label whose class is undefined does not hide
           anything — it renders as stray text on the page. This shell had no `.vy-sr-only` while the
           operator, owner and guest shells each define it, so a label placed here would have SHOWN.
           Copied verbatim from those three: separate documents, one rule. */
        .vy-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>
    <x-vy-scrollbar />
</head>
<body>
    <div class="ad-amb" aria-hidden="true">
        <div class="ad-amb-streak1"></div>
        <div class="ad-amb-streak2"></div>
        <div class="ad-amb-orb1"></div>
        <div class="ad-amb-orb2"></div>
        <div class="ad-amb-orb3"></div>
    </div>
    <div class="ad-layout">
        <aside class="ad-side">
            <div class="ad-brand">
                <span class="ad-brand-mark">V</span>
                <span>
                    <div class="ad-brand-name">Vyrobel</div>
                    <div class="ad-brand-tag">Platform</div>
                </span>
            </div>
            {{-- ⚠️ "Platform console" is LOAD-BEARING TEXT, not decoration: PlatformAdminGate0Test's
                 security-group case asserts it to prove the console shell rendered for an enrolled admin.
                 It used to live as a per-page `.ad-eyebrow`; those were removed when the page title moved
                 into the topbar, so the sidebar's section label carries it now — the string stays true
                 rather than the assertion being weakened to match a redesign. --}}
            <nav class="ad-nav" aria-label="Platform console">
                <div class="ad-nav-label">Platform console</div>
                @foreach ($nav as [$key, $label, $url, $live, $icon, $capability])
                    @if ($live)
                        <a href="{{ $url }}" class="{{ $key === $active ? 'is-active' : '' }}" @if ($key === $active) aria-current="page" @endif>
                            <svg class="ad-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </a>
                    @else
                        <span class="ad-soon" aria-disabled="true" title="Coming soon">
                            <span style="display: inline-flex; align-items: center; gap: 10px;">
                                <svg class="ad-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                                {{ $label }}
                            </span>
                            <span class="ad-soon-tag">Soon</span>
                        </span>
                    @endif
                @endforeach
            </nav>
        </aside>
        <div class="ad-main">
            <header class="ad-top">
                <div class="ad-top-lead">
                    @if ($back)
                        <a class="ad-back" href="{{ $back }}">← {{ $backLabel }}</a>
                    @endif
                    <h1 class="ad-top-title">{{ $title }}</h1>
                    @if ($subtitle !== '')
                        <div class="ad-top-sub">{{ $subtitle }}</div>
                    @endif
                </div>
                <div class="ad-user">
                    {{-- The user chip links to the admin's own account page (profile · email · password · 2FA). --}}
                    <a class="ad-chip {{ $active === 'account' ? 'is-active' : '' }}" href="{{ route('admin.account.edit') }}" style="text-decoration: none; color: inherit;">
                        <span class="ad-chip-id">
                            <span class="ad-chip-name">{{ $adminName }}</span>
                            <span class="ad-chip-role">Platform · {{ $roleLabel }}</span>
                        </span>
                        <span class="ad-ava">{{ $initials }}</span>
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="ad-signout">Sign out</button>
                    </form>
                </div>
            </header>
            <main class="ad-content">
                {{-- ⚠️ ADMIN 03 · F12 — the notice and EVERY validation error, once, for every page. Eleven pages each carried
                     their own copy that showed only `$errors->first()`, so a form missing three fields named one. --}}
                @if (session('admin_notice'))
                    <div class="ad-flash" role="status">{{ session('admin_notice') }}</div>
                @endif
                @if ($errors->any())
                    <div class="ad-flash ad-flash--error" role="alert">
                        @if (count($errors->all()) === 1)
                            {{ $errors->first() }}
                        @else
                            <ul class="ad-flash-list">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    {{-- Audit fix P3 — the shared framework-free double-submit guard on the admin state-changing forms. --}}
    @include('partials.submit-guard')
</body>
</html>
