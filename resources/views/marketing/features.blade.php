{{-- FEATURES — front-website Gate 2.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel Features.dc.html` — its own semantic markup and
     inline styles, kept verbatim so the rendered page matches. Transformed only where it MUST be:
       · dc-runtime wiring (React refs/handlers/`sc-*`) REMOVED — state plumbing, not content (Gate 1's rule)
       · the palette + font literals BOUND to `<x-design.tokens />` — never a second palette (D6/E4/§N.8f)
       · design page links → `<x-marketing.nav-link>`, so an unbuilt page renders as a MARKED non-link
       · `<image-slot>` → `<x-marketing.image-slot>` (D8 open — never a stock photo)
       · the ROI calculator → an honest deferral; the ledger-driven sections → their components
     ⚠️ The SEO values are the design's OWN (from its `<helmet>`), not invented copy. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Features — booking engine, front desk, housekeeping, owner portal | Vyrobel"
    description="Every Vyrobel feature in detail: a commission-free direct booking engine, reservations and front desk, rack calendar, housekeeping and maintenance boards, rates and revenue reporting, the guest stay portal, and the owner statement and payout portal."
    canonical="/features">
<div data-screen-label="Marketing — Features" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 56px;">
      <div data-orb="" style="position: absolute; top: -30px; left: -100px; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 720px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Features</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 52px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Property management software that covers the whole operation</h1>
        <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.64; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">From the moment a guest finds you to the moment an owner is paid. Grouped the way a property business actually runs.</p>
        <nav aria-label="Feature sections" style="display: flex; flex-wrap: wrap; gap: 9px; margin-top: 30px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <a href="#bookings" style="padding: 9px 15px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .2s ease, background .2s ease;">Bookings &amp; front desk</a>
          <a href="#operations" style="padding: 9px 15px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .2s ease, background .2s ease;">Operations</a>
          <a href="#revenue" style="padding: 9px 15px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .2s ease, background .2s ease;">Revenue &amp; reports</a>
          <a href="#guests" style="padding: 9px 15px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .2s ease, background .2s ease;">Guest experience</a>
          <a href="#owners" style="padding: 9px 15px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .2s ease, background .2s ease;">Owners</a>
          <a href="#team" style="padding: 9px 15px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .2s ease, background .2s ease;">Team &amp; security</a>
        </nav>
      </div>
    </section>

    <!-- what each group looks like -->
    <section data-sec="" id="glance" style="max-width: 1240px; margin: 0 auto; padding: 30px 28px 20px;">
      <div data-fwrap="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: start;">
        <div data-fcol="" style="position: sticky; top: 104px; height: 470px;">
            <div data-fcard="1" style="position: absolute; inset: 0; opacity: 0; transform: translateY(14px) scale(.985); transition: opacity .5s cubic-bezier(.22,.7,.3,1), transform .55s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
              <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 24px 24px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 24px; box-shadow: 0 24px 64px rgba(150,90,50,.2), inset 0 1px 0 rgba(255,255,255,.7);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 16px;">New reservation · step 2 of 5</div>                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-bottom: 16px;">                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">9</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">10</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">11</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">12</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">13</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">14</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">15</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">16</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">17</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">18</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">19</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">20</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">21</span>
                  <span style="height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; background: rgba(255,255,255,.6); color: var(--vy-muted);">22</span>
                </div>                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="min-width: 0;"><span style="display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Villa Azure</span><span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px;">Sleeps 6 · free 12–16</span></span>
                  <span style="flex-shrink: 0; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums;">€255/night</span>
                </div>                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="min-width: 0;"><span style="display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Villa Coralis</span><span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px;">Sleeps 4 · free 12–16</span></span>
                  <span style="flex-shrink: 0; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums;">€210/night</span>
                </div>                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0;">
                  <span style="min-width: 0;"><span style="display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Apt 102</span><span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px;">Booked — not offered</span></span>
                  <span style="flex-shrink: 0; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums;">—</span>
                </div>                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(150,90,50,.11); font-size: 11.5px; color: var(--vy-muted);">Only units free for every night appear. Nothing offered here can double-book.</div>
              </div>
            </div>
            <div data-fcard="2" style="position: absolute; inset: 0; opacity: 0; transform: translateY(14px) scale(.985); transition: opacity .5s cubic-bezier(.22,.7,.3,1), transform .55s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
              <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 24px 24px; display: flex; flex-direction: column; position: relative; background: linear-gradient(160deg, var(--vy-dark), #251C17); border: 1px solid rgba(255,255,255,.1); border-radius: 24px; box-shadow: 0 28px 70px rgba(60,35,20,.32);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #B9ABA0; margin-bottom: 16px;">Housekeeping · 09:14</div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 7px; margin-bottom: 16px;">                  <div style="padding: 10px 9px; border-radius: 11px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.12);"><div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent);"></span><span style="font-size: 9.5px; color: #B9ABA0;">Dirty</span></div><div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">5</div></div>
                  <div style="padding: 10px 9px; border-radius: 11px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.12);"><div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent-3);"></span><span style="font-size: 9.5px; color: #B9ABA0;">In prog.</span></div><div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">4</div></div>
                  <div style="padding: 10px 9px; border-radius: 11px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.12);"><div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: #B4795A;"></span><span style="font-size: 9.5px; color: #B9ABA0;">Clean</span></div><div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">5</div></div>
                  <div style="padding: 10px 9px; border-radius: 11px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.12);"><div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: rgba(255,247,240,.5);"></span><span style="font-size: 9.5px; color: #B9ABA0;">Inspected</span></div><div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">3</div></div>
                </div>
                <div style="padding: 13px 14px; border-radius: 12px; background: rgba(232,112,58,.14); border: 1px solid rgba(232,112,58,.24);">
                  <div style="font-size: 12.5px; font-weight: 600; color: #F0894E; margin-bottom: 4px;">Apt 118 — out of service</div>
                  <div style="font-size: 11.5px; line-height: 1.55; color: rgba(255,247,240,.72);">Cracked shower screen, raised from the room with a photo. Off sale on every channel since 09:07.</div>
                </div>
                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.1); font-size: 11.5px; color: #B9ABA0;">Rooms with a guest waiting pin to the top on their own.</div>
              </div>
            </div>
            <div data-fcard="3" style="position: absolute; inset: 0; opacity: 0; transform: translateY(14px) scale(.985); transition: opacity .5s cubic-bezier(.22,.7,.3,1), transform .55s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
              <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 24px 24px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 24px; box-shadow: 0 24px 64px rgba(150,90,50,.2), inset 0 1px 0 rgba(255,255,255,.7);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 16px;">August · occupancy and rate</div>                <div style="display: flex; align-items: flex-end; gap: 5px; height: 92px; margin-bottom: 14px;">                  <div style="flex: 1; height: 46%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.3769230769230769);"></div>
                  <div style="flex: 1; height: 58%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.42307692307692313);"></div>
                  <div style="flex: 1; height: 52%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.4);"></div>
                  <div style="flex: 1; height: 71%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.47307692307692306);"></div>
                  <div style="flex: 1; height: 84%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.5230769230769231);"></div>
                  <div style="flex: 1; height: 92%; border-radius: 4px 4px 0 0; background: linear-gradient(180deg,var(--vy-accent-3),var(--vy-accent));"></div>
                  <div style="flex: 1; height: 88%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.5384615384615385);"></div>
                  <div style="flex: 1; height: 76%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.49230769230769234);"></div>
                  <div style="flex: 1; height: 64%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.4461538461538462);"></div>
                  <div style="flex: 1; height: 58%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.42307692307692313);"></div>
                  <div style="flex: 1; height: 49%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.38846153846153847);"></div>
                  <div style="flex: 1; height: 42%; border-radius: 4px 4px 0 0; background: rgba(217,102,46,0.3615384615384616);"></div>
                </div>                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="min-width: 0;"><span style="display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Occupancy</span><span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px;">peak, 6 Aug</span></span>
                  <span style="flex-shrink: 0; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums;">92%</span>
                </div>                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="min-width: 0;"><span style="display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">ADR</span><span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px;">up from €211</span></span>
                  <span style="flex-shrink: 0; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums;">€248</span>
                </div>                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0;">
                  <span style="min-width: 0;"><span style="display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">RevPAR</span><span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 1px;">the one that matters</span></span>
                  <span style="flex-shrink: 0; font-size: 12.5px; font-weight: 500; color: var(--vy-muted); font-variant-numeric: tabular-nums;">€228</span>
                </div>                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(150,90,50,.11); font-size: 11.5px; color: var(--vy-muted);">Units out of service are excluded from available nights.</div>
              </div>
            </div>
            <div data-fcard="4" style="position: absolute; inset: 0; opacity: 0; transform: translateY(14px) scale(.985); transition: opacity .5s cubic-bezier(.22,.7,.3,1), transform .55s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
              <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 24px 24px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 24px; box-shadow: 0 24px 64px rgba(150,90,50,.2), inset 0 1px 0 rgba(255,255,255,.7);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 16px;">Guest · two days before arrival</div>                <div style="display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="width: 20px; height: 20px; border-radius: 7px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
                  <span style="flex: 1; font-size: 13px; font-weight: 500; color: var(--vy-ink);">Registration</span>
                  <span style="font-size: 11.5px; color: var(--vy-muted);">Complete</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="width: 20px; height: 20px; border-radius: 7px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
                  <span style="flex: 1; font-size: 13px; font-weight: 500; color: var(--vy-ink);">ID verified</span>
                  <span style="font-size: 11.5px; color: var(--vy-muted);">Sofia L.</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <span style="width: 20px; height: 20px; border-radius: 7px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
                  <span style="flex: 1; font-size: 13px; font-weight: 500; color: var(--vy-ink);">House rules</span>
                  <span style="font-size: 11.5px; color: var(--vy-muted);">Accepted 14:02</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; padding: 9px 0;">
                  <span style="width: 20px; height: 20px; border-radius: 7px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
                  <span style="flex: 1; font-size: 13px; font-weight: 500; color: var(--vy-ink);">Balance</span>
                  <span style="font-size: 11.5px; color: var(--vy-muted);">Settled</span>
                </div>                <div style="margin-top: 14px; padding: 13px 14px; border-radius: 12px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.22);">
                  <div style="font-size: 11px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 5px;">Releases tomorrow, 16:00</div>
                  <div style="font-family: var(--vy-display); font-size: 21px; font-weight: 600; color: var(--vy-accent-text); letter-spacing: .1em;">4 8 2 1</div>
                </div>
                <div style="margin-top: auto; padding-top: 14px; font-size: 11.5px; color: var(--vy-muted);">A door code released on booking is a code that exists for months.</div>
              </div>
            </div>
            <div data-fcard="5" style="position: absolute; inset: 0; opacity: 0; transform: translateY(14px) scale(.985); transition: opacity .5s cubic-bezier(.22,.7,.3,1), transform .55s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
              <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 24px 24px; display: flex; flex-direction: column; position: relative; background: linear-gradient(160deg, var(--vy-dark), #251C17); border: 1px solid rgba(255,255,255,.1); border-radius: 24px; box-shadow: 0 28px 70px rgba(60,35,20,.32);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #B9ABA0; margin-bottom: 14px;">Owner statement · July</div>
                <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 18px;">
                  <span style="font-family: var(--vy-display); font-size: 30px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;">€8,412</span>
                  <span style="font-size: 12px; color: #B9ABA0;">net to you</span>
                </div>                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.09);"><span style="font-size: 12.5px; color: rgba(255,247,240,.84);">Gross revenue</span><span style="font-size: 12.5px; color: #fff; font-variant-numeric: tabular-nums;">€11,240.00</span></div>
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.09);"><span style="font-size: 12.5px; color: rgba(255,247,240,.84);">Channel commission</span><span style="font-size: 12.5px; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums;">−€1,048.60</span></div>
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.09);"><span style="font-size: 12.5px; color: rgba(255,247,240,.84);">Management fee</span><span style="font-size: 12.5px; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums;">−€1,528.71</span></div>
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.09);"><span style="font-size: 12.5px; color: rgba(255,247,240,.84);">Maintenance · AC</span><span style="font-size: 12.5px; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums;">−€120.00</span></div>
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 8px 0;"><span style="font-size: 12.5px; color: rgba(255,247,240,.84);">Guest refund</span><span style="font-size: 12.5px; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums;">−€130.69</span></div>                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.1); font-size: 11.5px; color: #B9ABA0;">Owners see performance. Never a guest’s private data.</div>
              </div>
            </div>
            <div data-fcard="6" style="position: absolute; inset: 0; opacity: 0; transform: translateY(14px) scale(.985); transition: opacity .5s cubic-bezier(.22,.7,.3,1), transform .55s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
              <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 24px 24px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 24px; box-shadow: 0 24px 64px rgba(150,90,50,.2), inset 0 1px 0 rgba(255,255,255,.7);">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 16px;">Roles · who sees what</div>                <div style="padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); opacity: 0.4;"></span><span style="font-size: 13px; font-weight: 500; color: var(--vy-ink);">Housekeeping</span></div>
                  <div style="font-size: 11px; color: var(--vy-muted); margin-top: 2px; padding-left: 13px;">Board, room detail, raise a ticket</div>
                </div>
                <div style="padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); opacity: 0.55;"></span><span style="font-size: 13px; font-weight: 500; color: var(--vy-ink);">Front desk</span></div>
                  <div style="font-size: 11px; color: var(--vy-muted); margin-top: 2px; padding-left: 13px;">Arrivals, folios, payments, guest data</div>
                </div>
                <div style="padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); opacity: 0.7;"></span><span style="font-size: 13px; font-weight: 500; color: var(--vy-ink);">Manager</span></div>
                  <div style="font-size: 11px; color: var(--vy-muted); margin-top: 2px; padding-left: 13px;">Above, plus rates, reports, statements</div>
                </div>
                <div style="padding: 9px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                  <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); opacity: 0.85;"></span><span style="font-size: 13px; font-weight: 500; color: var(--vy-ink);">Admin</span></div>
                  <div style="font-size: 11px; color: var(--vy-muted); margin-top: 2px; padding-left: 13px;">Above, plus settings, billing, security</div>
                </div>
                <div style="padding: 9px 0;">
                  <div style="display: flex; align-items: center; gap: 8px;"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-muted); opacity: .6;"></span><span style="font-size: 13px; font-weight: 500; color: var(--vy-ink);">Owner</span></div>
                  <div style="font-size: 11px; color: var(--vy-muted); margin-top: 2px; padding-left: 13px;">Their units only — never guest data</div>
                </div>                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(150,90,50,.11); font-size: 11.5px; color: var(--vy-muted);">Enforced by query scope, not by hiding page elements.</div>
              </div>
            </div>
        </div>
        <div>
          <div data-fstep="1" style="display: flex; flex-direction: column; justify-content: center; min-height: 58vh; padding: 26px 0;">
            <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 13px;">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 11.5px; font-weight: 600; color: #fff;">1</span>
              <span style="font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Bookings &amp; front desk</span>
            </div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.18; letter-spacing: -.028em; color: var(--vy-ink); text-wrap: balance;">A booking engine that cannot double-book</h2>
            <p style="margin: 0 0 18px; max-width: 460px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Availability is derived from the reservation set, not stored as a number that drifts. Only units free for every night are ever offered.</p>
            <a href="#bookings" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; align-self: flex-start;">Jump to the detail
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </a>
          </div>
          <div data-fstep="2" style="display: flex; flex-direction: column; justify-content: center; min-height: 58vh; padding: 26px 0;">
            <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 13px;">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 11.5px; font-weight: 600; color: #fff;">2</span>
              <span style="font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Operations</span>
            </div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.18; letter-spacing: -.028em; color: var(--vy-ink); text-wrap: balance;">A turnover day that keeps up with itself</h2>
            <p style="margin: 0 0 18px; max-width: 460px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">A live board your team works from their phones, and a maintenance ticket that takes a unit off sale everywhere the moment it is raised.</p>
            <a href="#operations" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; align-self: flex-start;">Jump to the detail
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </a>
          </div>
          <div data-fstep="3" style="display: flex; flex-direction: column; justify-content: center; min-height: 58vh; padding: 26px 0;">
            <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 13px;">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 11.5px; font-weight: 600; color: #fff;">3</span>
              <span style="font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Revenue &amp; reports</span>
            </div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.18; letter-spacing: -.028em; color: var(--vy-ink); text-wrap: balance;">The three numbers that describe a property</h2>
            <p style="margin: 0 0 18px; max-width: 460px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Occupancy, ADR and RevPAR read together. Units out of service leave the denominator, so a closure is not counted as a failure.</p>
            <a href="#revenue" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; align-self: flex-start;">Jump to the detail
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </a>
          </div>
          <div data-fstep="4" style="display: flex; flex-direction: column; justify-content: center; min-height: 58vh; padding: 26px 0;">
            <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 13px;">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 11.5px; font-weight: 600; color: #fff;">4</span>
              <span style="font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Guest experience</span>
            </div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.18; letter-spacing: -.028em; color: var(--vy-ink); text-wrap: balance;">A guest who arrives already checked in</h2>
            <p style="margin: 0 0 18px; max-width: 460px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Registration and ID days ahead, house rules accepted with a timestamp, and the door code released the afternoon before — not at booking.</p>
            <a href="#guests" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; align-self: flex-start;">Jump to the detail
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </a>
          </div>
          <div data-fstep="5" style="display: flex; flex-direction: column; justify-content: center; min-height: 58vh; padding: 26px 0;">
            <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 13px;">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 11.5px; font-weight: 600; color: #fff;">5</span>
              <span style="font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Owners</span>
            </div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.18; letter-spacing: -.028em; color: var(--vy-ink); text-wrap: balance;">Statements that assemble themselves</h2>
            <p style="margin: 0 0 18px; max-width: 460px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Gross to net from bookings and expenses already recorded, with each deduction linked to the thing that caused it.</p>
            <a href="#owners" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; align-self: flex-start;">Jump to the detail
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </a>
          </div>
          <div data-fstep="6" style="display: flex; flex-direction: column; justify-content: center; min-height: 58vh; padding: 26px 0;">
            <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 13px;">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 11.5px; font-weight: 600; color: #fff;">6</span>
              <span style="font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Team &amp; security</span>
            </div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.18; letter-spacing: -.028em; color: var(--vy-ink); text-wrap: balance;">Everyone sees exactly their own work</h2>
            <p style="margin: 0 0 18px; max-width: 460px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Roles from housekeeping to admin, and an owner boundary enforced at the data layer rather than by omitting page elements.</p>
            <a href="#team" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; text-decoration: none; align-self: flex-start;">Jump to the detail
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- BOOKINGS -->
    <section data-sec="" id="bookings" style="max-width: 1240px; margin: 0 auto; padding: 34px 28px 20px; scroll-margin-top: 96px;">
      <div data-reveal="" style="display: flex; align-items: center; gap: 14px; margin-bottom: 28px;">
        <span style="width: 42px; height: 42px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M8 3v4M16 3v4M3 11h18"></path></svg></span>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">Bookings &amp; front desk</h2>
      </div>
      <div data-fgrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M8 13h8"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Commission-free booking engine</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">A branded storefront with your photos, your policies and your prices. Real availability, real card payments, and no percentage skimmed off the top.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">A wizard that can't double-book</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Guided new reservations check availability, guest counts and rates as you go. Group bookings and walk-ins take the same guarded path.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Arrivals, departures, in-house</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">A day timeline with everyone due in and out. Check in, check out, collect the balance, and see at a glance who's still waiting on a room.</p>
        </article>
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M3 10h18M8 15h4"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Rack calendar with drag-to-move</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Every unit, every night, on one grid. Drag a stay to reschedule it, and out-of-order blocks pull the room off sale automatically.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="3"></rect><path d="M2 10h20"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Folio, payments and refunds</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Charges, add-ons, deposits and balances in one folio. Refund a guest and the credit note, statement and audit trail follow on their own.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 7V5a3 3 0 0 1 6 0v2M6 7h12l-1 13H7z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Channels alongside direct</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Keep your OTA listings in sync while you grow the direct share. Connection health, last-sync state and reconnect flows built in.</p>
        </article>
      </div>
    </section>

    <!-- OPERATIONS -->
    <section data-sec="" id="operations" style="position: relative; overflow: hidden; margin: 40px 0; padding: 78px 0 82px; scroll-margin-top: 0; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -130px; left: 6%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -150px; right: 5%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
      
      <div data-reveal="" style="position: relative; display: flex; align-items: center; gap: 14px; margin-bottom: 28px;">
        <span style="width: 42px; height: 42px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v13H3z"></path><path d="M8 7V4h8v3M3 12h18"></path></svg></span>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: #fff;">Operations</h2>
      </div>
      <div data-fgrid="" style="position: relative; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <article data-reveal="" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 20px; padding: 26px 24px; box-shadow: 0 14px 40px rgba(0,0,0,.24); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(232,112,58,.2); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="5" height="16" rx="2"></rect><rect x="10" y="4" width="5" height="10" rx="2"></rect><rect x="17" y="4" width="4" height="13" rx="2"></rect></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">Live housekeeping board</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: rgba(255,247,240,.72);">Dirty, in progress, clean, inspected. Drag a room across lanes, watch elapsed time against expected, and rooms with a guest waiting pin to the top.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 20px; padding: 26px 24px; box-shadow: 0 14px 40px rgba(0,0,0,.24); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(232,112,58,.2); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4l3 2"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">Auto-balanced assignment</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: rgba(255,247,240,.72);">Spread the day's rooms across the team in one action, or assign a whole lane to one person. Proof photos attach to the room, not a chat thread.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 20px; padding: 26px 24px; box-shadow: 0 14px 40px rgba(0,0,0,.24); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(232,112,58,.2); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 1-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 1 5.4-5.4z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">Maintenance work orders</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: rgba(255,247,240,.72);">Open, assign, start, resolve with a cost. Priorities and due dates on the card, SLA tracking behind it, and the whole board filterable by unit or assignee.</p>
        </article>
        <article data-reveal="" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 20px; padding: 26px 24px; box-shadow: 0 14px 40px rgba(0,0,0,.24); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(232,112,58,.2); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v15H4z"></path><path d="M4 10h16M9 5v15"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">Out-of-service protection</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: rgba(255,247,240,.72);">A ticket that takes a unit out of service stops it selling everywhere — and flags any booking already on it that needs moving by hand.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 20px; padding: 26px 24px; box-shadow: 0 14px 40px rgba(0,0,0,.24); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(232,112,58,.2); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"></circle><path d="M3 19a6 6 0 0 1 12 0M16 6a3 3 0 0 1 0 6"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">Schedules &amp; shifts</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: rgba(255,247,240,.72);">Build the week by dragging shifts onto people, fill open shifts from a pool, spot coverage gaps, and print or export the rota for the wall.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 20px; padding: 26px 24px; box-shadow: 0 14px 40px rgba(0,0,0,.24); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(232,112,58,.2); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v4M12 17v4M3 12h4M17 12h4"></path><circle cx="12" cy="12" r="4"></circle></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: #fff;">An action centre that nags</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: rgba(255,247,240,.72);">Everything waiting on a human — approvals, held payouts, unassigned rooms — in one strip you can snooze, dismiss or clear in bulk.</p>
        </article>
      </div>
    </div>
    </section>

    <!-- REVENUE -->
    <section data-sec="" id="revenue" style="max-width: 1240px; margin: 0 auto; padding: 60px 28px 20px; scroll-margin-top: 96px;">
      <div data-reveal="" style="display: flex; align-items: center; gap: 14px; margin-bottom: 28px;">
        <span style="width: 42px; height: 42px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V9M10 18V5M16 18v-6M22 18H2"></path></svg></span>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">Revenue &amp; reports</h2>
      </div>
      <div data-fgrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Rate plans &amp; per-night pricing</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Base rates, weekend uplift, per-night overrides and minimum stays — set them per unit and see the calendar reprice as you type.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"></rect><path d="M7 9h10M7 13h6"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Occupancy, ADR, RevPAR</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">The metrics your investors ask for, by property, unit and channel, over any custom date range — not just the months someone pre-built.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M4 19h16"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">PDF &amp; CSV that arrive ready</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Statements, VAT returns, booking exports and payout histories download clean — formatted for your accountant, not for re-typing.</p>
        </article>
      </div>
    </section>

    <!-- GUESTS -->
    <section data-sec="" id="guests" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 60px 28px 20px; scroll-margin-top: 96px;">
      <div data-orb="" style="position: absolute; bottom: -40px; left: -70px; width: 330px; height: 330px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .55; animation: vyDrift 23s ease-in-out infinite; pointer-events: none;"></div>
      <div data-reveal="" style="position: relative; display: flex; align-items: center; gap: 14px; margin-bottom: 28px;">
        <span style="width: 42px; height: 42px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path></svg></span>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">Guest experience</h2>
      </div>
      <div data-fgrid="" style="position: relative; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M7 10h4M7 14h8"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Online check-in with ID</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Guest details, house rules, arrival time and secure document capture — for the lead guest and everyone travelling with them.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="3"></rect><path d="M9 8h6M9 12h6"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Arrival info, timed</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Door code, wifi, directions and parking release at the right moment — not the day they booked, and not after they've called you.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v12H4z"></path><path d="M4 11h16M12 7v12"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Add-ons &amp; upsells</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Late checkout, airport transfers, breakfast, a bottle on arrival — offered at booking and mid-stay, charged to the same folio.</p>
        </article>
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="3"></rect><path d="M2 11h20"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Wallet, credits &amp; referrals</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Refunds and goodwill become credit that applies at checkout, gives guests a reason to come back, and stays fully traceable.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 1 1-3.2-6.4"></path><path d="M8 12h8"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Self-service changes</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Guests cancel or modify within the policy you set, join a waitlist when you're full, and see the refund maths before they confirm.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.4.8-4.7 4.3 1.3 6.3L12 17l-5.6 3 1.3-6.3L3 9.4l6.4-.8z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Messaging &amp; reviews</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Every channel's messages in one inbox with delivery states, plus review collection and rating trends per property.</p>
        </article>
      </div>
    </section>

    <!-- OWNERS -->
    <section data-sec="" id="owners" style="max-width: 1240px; margin: 0 auto; padding: 60px 28px 20px; scroll-margin-top: 96px;">
      <div data-reveal="" style="display: flex; align-items: center; gap: 14px; margin-bottom: 28px;">
        <span style="width: 42px; height: 42px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-4 7 4v13"></path></svg></span>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">Owners</h2>
      </div>
      <div data-fgrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-7-4-7 4z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Automatic monthly statements</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Gross revenue, commission, expenses, net payout — per unit and in total, with the bookings behind every line, as PDF and CSV.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="3"></rect><circle cx="12" cy="12" r="3"></circle></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Payout runs, holds and retries</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Scheduled payouts with a visible state: processing, paid, on hold, or failed with the reason. Owners set their method, currency and payout day.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Expense &amp; rate approvals</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Maintenance costs and proposed rate changes queue for the owner. They approve, decline or ask — and the statement reflects the answer.</p>
        </article>
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M3 10h18"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Owner stays &amp; blocks</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Owners block their own dates — one-off or recurring — straight from their calendar, and the unit stops selling those nights.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v6H4zM4 14h16v6H4z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Tax centre &amp; agreements</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">VAT details, withholding, annual summaries and filing deadlines, plus the management agreement with its commission terms and e-signature.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"></circle><path d="M3 19a6 6 0 0 1 12 0"></path><path d="M16 6a3 3 0 0 1 0 6"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Co-owners &amp; accountants</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Invite a co-owner, a property manager or an accountant with scoped access — they see the portfolio you allow, nothing else.</p>
        </article>
      </div>
    </section>

    <!-- TEAM & SECURITY -->
    <section data-sec="" id="team" style="max-width: 1240px; margin: 0 auto; padding: 60px 28px 40px; scroll-margin-top: 96px;">
      <div data-reveal="" style="display: flex; align-items: center; gap: 14px; margin-bottom: 28px;">
        <span style="width: 42px; height: 42px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"></path></svg></span>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">Team &amp; security</h2>
      </div>
      <div data-fgrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <article data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Roles &amp; permissions</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Front desk, housekeeping, maintenance, manager, owner, accountant — each role sees the screens and the numbers it should.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="3"></rect><path d="M11 18h2"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">2FA, sessions &amp; audit trail</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Two-factor authentication, visible active sessions, connected apps, and an operator audit trail for money-moving actions.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 26px 24px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 12px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Multi-property &amp; GDPR</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Switch between properties without switching accounts. Data minimisation and guest anonymisation are built in, not bolted on.</p>
        </article>
      </div>
      <div data-reveal="" style="margin-top: 30px; text-align: center;">
        <x-marketing.nav-link key="security" style="display: inline-flex; align-items: center; gap: 8px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">Read about security &amp; compliance <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 40px 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 62px 48px; text-align: center;">
        <div data-orb="" style="position: absolute; bottom: -130px; left: 20%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 22s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 38px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Not sure which parts you need?</h2>
          <p style="margin: 18px auto 0; max-width: 520px; font-size: 16.5px; line-height: 1.62; color: rgba(255,247,240,.74);">Browse the module catalog, or tell us how your portfolio runs and we'll map it for you.</p>
          <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 30px;">
            <x-marketing.nav-link key="modules" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">See all modules</x-marketing.nav-link>
            <x-marketing.nav-link key="pricing" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,247,240,.12); border: 1px solid rgba(255,247,240,.24); color: #fff; font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, background .22s ease;">Check pricing</x-marketing.nav-link>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
