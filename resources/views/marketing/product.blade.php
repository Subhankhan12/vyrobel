{{-- PRODUCT — front-website Gate 2.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel Product.dc.html` — its own semantic markup and
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
    title="The Vyrobel platform — one system for operators, owners and guests"
    description="A guided tour of the Vyrobel property management platform: the operator app that runs daily operations, the owner portal that ends month-end reporting, and the guest experience that brings bookings direct."
    canonical="/product">
<div data-screen-label="Marketing — Product" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 70px;">
      <div data-orb="" style="position: absolute; top: -40px; right: -80px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 23s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 780px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">The platform</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 54px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">One system for the people who run the property, the people who own it, and the people who stay in it</h1>
        <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.64; color: var(--vy-muted); max-width: 640px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Vyrobel replaces the stack most short-stay and hotel businesses are held together by: a channel manager here, a spreadsheet there, a cleaning WhatsApp group, and a month-end evening spent rebuilding owner reports from scratch.</p>
      </div>
    </section>

    <!-- problem -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="max-width: 560px; margin-bottom: 34px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink);">What it costs to run on stitched-together tools</h2>
      </div>
      <div data-probs="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,252,248,.55); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 20px; padding: 26px;">
          <div style="font-family: var(--vy-display); font-size: 30px; font-weight: 600; color: var(--vy-accent-text); letter-spacing: -.03em; margin-bottom: 12px;">15–18%</div>
          <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Handed to the OTAs</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Every booking that could have come direct pays a commission you never see again — on a guest who already knows your name.</p>
        </div>
        <div data-reveal="" data-reveal-delay="90" style="background: rgba(255,252,248,.55); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 20px; padding: 26px;">
          <div style="font-family: var(--vy-display); font-size: 30px; font-weight: 600; color: var(--vy-accent-text); letter-spacing: -.03em; margin-bottom: 12px;">4+</div>
          <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Systems that disagree</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">The calendar says one thing, the cleaning sheet another, the accountant a third. Someone spends their morning reconciling all three.</p>
        </div>
        <div data-reveal="" data-reveal-delay="180" style="background: rgba(255,252,248,.55); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 20px; padding: 26px;">
          <div style="font-family: var(--vy-display); font-size: 30px; font-weight: 600; color: var(--vy-accent-text); letter-spacing: -.03em; margin-bottom: 12px;">Month-end</div>
          <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Owners in the dark</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Owners wait for a PDF you build by hand, then call to ask what a line means. Trust erodes over formatting, not performance.</p>
        </div>
      </div>
    </section>

    <!-- operators -->
    <section data-sec="" id="operators" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 90px; scroll-margin-top: 100px;">
      <div data-orb="" style="position: absolute; top: 60px; left: -90px; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .45; animation: vyDrift 26s ease-in-out infinite; pointer-events: none;"></div>
      <div data-split="" style="position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 54px; align-items: center;">
        <div data-reveal="left">
          <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 13px; border-radius: 999px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 12px; font-weight: 600; margin-bottom: 18px;">Experience 1 · Operators</div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Your team opens one app and the day is already organised</h2>
          <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">The operator app is where reservations, the front desk, the rack calendar, housekeeping, maintenance, rates, messaging and reports live together. Switch between properties from the top bar. Give each role exactly the access it needs — a cleaner sees their rooms, a manager sees the money.</p>
          <div style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: var(--vy-muted);"><strong style="color: var(--vy-ink); font-weight: 600;">A live day.</strong> Arrivals, departures and in-house on one timeline, with check-in, check-out and payment collection on the desk.</span></div>
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M3 10h18"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: var(--vy-muted);"><strong style="color: var(--vy-ink); font-weight: 600;">A calendar that protects itself.</strong> Drag a stay to move it, block a unit for maintenance, and availability updates everywhere at once.</span></div>
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V9M10 18V5M16 18v-6M22 18H2"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: var(--vy-muted);"><strong style="color: var(--vy-ink); font-weight: 600;">Numbers you can act on.</strong> Occupancy, ADR and RevPAR by property and channel, exportable to PDF or CSV for the board or the accountant.</span></div>
          </div>
        </div>
        <div data-reveal="right" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 18px 50px rgba(150,90,50,.18); padding: 22px;" role="img" aria-label="Illustrative operator rack calendar with bookings across units and nights">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <span style="font-family: var(--vy-display); font-size: 14.5px; font-weight: 600; color: var(--vy-ink);">Rack calendar · August</span>
            <span style="font-size: 11.5px; color: var(--vy-muted);">4 units</span>
          </div>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <div style="display: grid; grid-template-columns: 78px 1fr; gap: 10px; align-items: center;">
              <span style="font-size: 11.5px; color: var(--vy-muted);">Azure</span>
              <div style="display: flex; gap: 3px; height: 28px;">
                <div style="flex: 3; border-radius: 7px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></div>
                <div style="flex: 1; border-radius: 7px; background: rgba(150,90,50,.1);"></div>
                <div style="flex: 4; border-radius: 7px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></div>
                <div style="flex: 2; border-radius: 7px; background: rgba(150,90,50,.1);"></div>
              </div>
            </div>
            <div style="display: grid; grid-template-columns: 78px 1fr; gap: 10px; align-items: center;">
              <span style="font-size: 11.5px; color: var(--vy-muted);">Coralis</span>
              <div style="display: flex; gap: 3px; height: 28px;">
                <div style="flex: 2; border-radius: 7px; background: rgba(150,90,50,.1);"></div>
                <div style="flex: 5; border-radius: 7px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></div>
                <div style="flex: 3; border-radius: 7px; background: rgba(150,90,50,.1);"></div>
              </div>
            </div>
            <div style="display: grid; grid-template-columns: 78px 1fr; gap: 10px; align-items: center;">
              <span style="font-size: 11.5px; color: var(--vy-muted);">Apt 204</span>
              <div style="display: flex; gap: 3px; height: 28px;">
                <div style="flex: 4; border-radius: 7px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></div>
                <div style="flex: 2; border-radius: 7px; background: repeating-linear-gradient(45deg, rgba(150,90,50,.22), rgba(150,90,50,.22) 4px, rgba(150,90,50,.1) 4px, rgba(150,90,50,.1) 8px);"></div>
                <div style="flex: 4; border-radius: 7px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></div>
              </div>
            </div>
            <div style="display: grid; grid-template-columns: 78px 1fr; gap: 10px; align-items: center;">
              <span style="font-size: 11.5px; color: var(--vy-muted);">Apt 108</span>
              <div style="display: flex; gap: 3px; height: 28px;">
                <div style="flex: 1; border-radius: 7px; background: rgba(150,90,50,.1);"></div>
                <div style="flex: 6; border-radius: 7px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></div>
                <div style="flex: 3; border-radius: 7px; background: rgba(150,90,50,.1);"></div>
              </div>
            </div>
          </div>
          <div style="display: flex; gap: 16px; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(150,90,50,.13); font-size: 11px; color: var(--vy-muted);">
            <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 3px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></span>Booked</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(150,90,50,.1);"></span>Available</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 3px; background: repeating-linear-gradient(45deg, rgba(150,90,50,.22), rgba(150,90,50,.22) 3px, rgba(150,90,50,.1) 3px, rgba(150,90,50,.1) 6px);"></span>Out of service</span>
          </div>
        </div>
      </div>
    </section>

    <!-- owners -->
    <section data-sec="" id="owners" style="position: relative; overflow: hidden; margin: 0 0 90px; padding: 84px 0 88px; scroll-margin-top: 0; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -130px; left: 6%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -150px; right: 5%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
      <div data-split-rev="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 54px; align-items: center;">
        <div data-reveal="left" style="order: 2;">
          <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 13px; border-radius: 999px; background: rgba(232,112,58,.2); color: var(--vy-accent-3); font-size: 12px; font-weight: 600; margin-bottom: 18px;">Experience 2 · Owners</div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.14; letter-spacing: -.03em; color: #fff; text-wrap: balance;">The report you used to build by hand, live and always current</h2>
          <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.68; color: rgba(255,247,240,.72);">Owners get their own login — not a shared inbox thread. Inside: this month's earnings, occupancy per unit, the bookings behind the number, every expense you've charged and why, the payout on its way and the ones already paid. Statements generate as PDF and CSV automatically.</p>
          <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 22px;">
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(232,112,58,.2); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-7-4-7 4z"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: rgba(255,247,240,.72);"><strong style="color: #fff; font-weight: 600;">Statements &amp; payouts.</strong> Monthly statements, payout runs, holds, retries and a tax centre with VAT and withholding.</span></div>
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(232,112,58,.2); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: rgba(255,247,240,.72);"><strong style="color: #fff; font-weight: 600;">Approvals, not surprises.</strong> Maintenance costs and rate changes go to the owner for approval before they land on a statement.</span></div>
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(232,112,58,.2); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: rgba(255,247,240,.72);"><strong style="color: #fff; font-weight: 600;">Scoped by design.</strong> Owners see performance and their own bookings — never another owner's data, never a guest's personal details.</span></div>
          </div>
          <x-marketing.nav-link key="features" fragment="#owners" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-3); text-decoration: none;">Owner features in detail <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </div>
        <div data-reveal="right" style="order: 1; background: rgba(255,255,255,.06); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.13); border-radius: 22px; box-shadow: 0 14px 40px rgba(0,0,0,.24); padding: 24px;" role="img" aria-label="Illustrative owner dashboard with earnings, occupancy and next payout">
          <div style="font-family: var(--vy-display); font-size: 14.5px; font-weight: 600; color: #fff; margin-bottom: 18px;">Your portfolio · this month</div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
            <div style="background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.15); border-radius: 14px; padding: 14px;"><div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,247,240,.55); margin-bottom: 6px;">Earnings</div><div style="font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums;">€8,412</div></div>
            <div style="background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.15); border-radius: 14px; padding: 14px;"><div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,247,240,.55); margin-bottom: 6px;">Occupancy</div><div style="font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums;">88%</div></div>
          </div>
          <div style="display: flex; align-items: flex-end; gap: 7px; height: 76px; padding: 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.13); border-radius: 14px; margin-bottom: 14px; box-sizing: content-box;">
            <div style="flex: 1; height: 42%; border-radius: 5px; background: rgba(217,102,46,.32);"></div>
            <div style="flex: 1; height: 56%; border-radius: 5px; background: rgba(217,102,46,.4);"></div>
            <div style="flex: 1; height: 48%; border-radius: 5px; background: rgba(217,102,46,.32);"></div>
            <div style="flex: 1; height: 70%; border-radius: 5px; background: rgba(217,102,46,.5);"></div>
            <div style="flex: 1; height: 84%; border-radius: 5px; background: linear-gradient(180deg,var(--vy-accent-3),var(--vy-accent));"></div>
            <div style="flex: 1; height: 100%; border-radius: 5px; background: linear-gradient(180deg,var(--vy-accent-3),var(--vy-accent));"></div>
          </div>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 13px 15px; border-radius: 14px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.22);">
            <span style="font-size: 12.5px; color: rgba(255,247,240,.72);">Next payout · 5 Aug</span>
            <span style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-accent-3); font-variant-numeric: tabular-nums;">€8,412.60</span>
          </div>
        </div>
      </div>
    </div>
    </section>

    <!-- guests -->
    <section data-sec="" id="guests" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 90px; scroll-margin-top: 100px;">
      <div data-orb="" style="position: absolute; bottom: 0; right: -70px; width: 340px; height: 340px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .55; animation: vyDrift 22s ease-in-out infinite; pointer-events: none;"></div>
      <div data-split="" style="position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 54px; align-items: center;">
        <div data-reveal="left">
          <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 13px; border-radius: 999px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 12px; font-weight: 600; margin-bottom: 18px;">Experience 3 · Guests</div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Book direct, check in online, arrive knowing the door code</h2>
          <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Guests browse your properties on a storefront that looks like your brand, book and pay in a few steps, then get a stay portal instead of an email chain. Online check-in captures ID securely, arrival info releases at the right moment, and add-ons turn into revenue you weren't collecting.</p>
          <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 22px;">
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M7 10h4"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: var(--vy-muted);"><strong style="color: var(--vy-ink); font-weight: 600;">Online check-in with ID capture.</strong> Documents collected securely before arrival, so the desk is a greeting, not paperwork.</span></div>
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 8h14M5 16h14"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: var(--vy-muted);"><strong style="color: var(--vy-ink); font-weight: 600;">Add-ons, credits and a wallet.</strong> Late checkout, transfers and extras at booking or mid-stay, with credit that carries to the next trip.</span></div>
            <div style="display: flex; gap: 13px;"><span style="flex-shrink: 0; width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M4 7l8 6 8-6"></path></svg></span><span style="font-size: 14.5px; line-height: 1.58; color: var(--vy-muted);"><strong style="color: var(--vy-ink); font-weight: 600;">One inbox.</strong> Guest messages from every channel land in the same thread your team already watches.</span></div>
          </div>
          <x-marketing.nav-link key="features" fragment="#guests" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">Guest features in detail <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </div>
        <div data-reveal="right" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 18px 50px rgba(150,90,50,.18); padding: 24px;" role="img" aria-label="Illustrative guest stay portal with check-in progress, door code and wifi">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div><div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">Your stay at Villa Azure</div><div style="font-size: 11.5px; color: var(--vy-muted); margin-top: 2px;">12–19 August · 4 guests</div></div>
            <span style="padding: 5px 10px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 11px; font-weight: 600;">In 8 days</span>
          </div>
          <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: var(--vy-muted); margin-bottom: 8px;"><span>Check-in complete</span><span>3 of 4 steps</span></div>
            <div style="height: 7px; border-radius: 999px; background: rgba(150,90,50,.14); overflow: hidden;"><div style="width: 75%; height: 100%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent));"></div></div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
            <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 13px;"><div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">Door code</div><div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); letter-spacing: .08em;">4 8 2 1</div></div>
            <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 13px;"><div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">Wifi</div><div style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">azure-guest</div></div>
          </div>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.78);">
            <span style="font-size: 12.5px; color: var(--vy-muted);">Wallet credit</span>
            <span style="font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums;">€25.00</span>
          </div>
        </div>
      </div>
    </section>

    <!-- one booking, end to end -->
    <section data-sec="" id="journey" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 90px; scroll-margin-top: 100px;">
      <div data-orb="" style="position: absolute; top: 10px; left: -90px; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .4; animation: vyDrift 27s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; max-width: 640px; margin-bottom: 32px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">One booking, end to end</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 38px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Follow a single reservation through all three</h2>
        <p style="margin: 16px 0 0; font-size: 16.5px; line-height: 1.64; color: var(--vy-muted);">The argument for one platform is easiest to see in one booking. Step through it — the label tells you who is holding it at each stage.</p>
      </div>

      <div data-reveal="" style="position: relative; padding: 32px 34px; border-radius: 26px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 18px 48px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65);">
        <div style="position: relative; margin-bottom: 28px;">
          <div style="position: absolute; left: 0; right: 0; top: 26px; height: 3px; border-radius: 999px; background: rgba(150,90,50,.14);"></div>
          <div data-stagefill="" style="position: absolute; left: 0; top: 26px; height: 3px; width: 0%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent)); transition: width .5s cubic-bezier(.22,.7,.3,1);"></div>
          <div data-stagerail="" role="tablist" aria-label="Stages of one booking" style="position: relative; display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; -webkit-overflow-scrolling: touch;">
          <button data-stagebtn="0" role="tab" aria-selected="true" tabindex="0" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Guest</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Search</span>
          </button>
          <button data-stagebtn="1" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Guest</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Book</span>
          </button>
          <button data-stagebtn="2" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Confirm</span>
          </button>
          <button data-stagebtn="3" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Guest</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Prepare</span>
          </button>
          <button data-stagebtn="4" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Arrive</span>
          </button>
          <button data-stagebtn="5" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Stay</span>
          </button>
          <button data-stagebtn="6" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Owner</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Settle</span>
          </button>
          <button data-stagebtn="7" role="tab" aria-selected="false" tabindex="-1" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); cursor: pointer; text-align: left; transition: all .24s cubic-bezier(.22,.7,.3,1);">
            <span data-stagewho="" style="font-size: 9.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Owner</span>
            <span style="font-family: var(--vy-display); font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Paid</span>
          </button>
          </div>
        </div>
        <div style="min-height: 190px;">
          <div data-stagepanel="0" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Guest · Search</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">A guest lands on your storefront</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">They found you on a marketplace, then searched your name. The calendar is the same one your front desk is looking at, so what they see is bookable.</p>
          </div>
          <div data-stagepanel="1" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Guest · Book</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">They pay on your own domain</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Card form, total shown before the payment step, confirmation within seconds. No commission leaves the building.</p>
          </div>
          <div data-stagepanel="2" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(232,112,58,.2); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Operator · Confirm</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">It appears on the board</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Not synced, not imported — the same reservation. Availability closes everywhere in the same instant, including on the channels.</p>
          </div>
          <div data-stagepanel="3" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Guest · Prepare</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Online check-in, days before</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Registration and ID from their sofa. The door code releases the afternoon before, once the balance is settled.</p>
          </div>
          <div data-stagepanel="4" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(232,112,58,.2); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Operator · Arrive</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">The room is ready before they are</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Housekeeping marked it guest-ready at 11:40. Reception never phoned upstairs to ask.</p>
          </div>
          <div data-stagepanel="5" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(232,112,58,.2); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Operator · Stay</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">One thread, whatever the channel</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Late checkout requested at five, accepted, and the charge lands on the folio without anyone opening a second system.</p>
          </div>
          <div data-stagepanel="6" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(120,70,40,.11); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 18px;">Owner · Settle</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">It reaches the statement on its own</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Gross, channel commission, your fee and the maintenance from Tuesday, with the work order attached. Nothing re-keyed.</p>
          </div>
          <div data-stagepanel="7" role="tabpanel" tabindex="0" >
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(120,70,40,.11); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 18px;">Owner · Paid</div>
            <h3 style="margin: 0 0 14px; font-family: var(--vy-display); font-size: 26px; font-weight: 600; letter-spacing: -.025em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">The fifth of the month</h3>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">A date the owner can see coming, a remittance advice they can download, and a reason on the dashboard if it ever holds.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- shared spine -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 46px 44px;">
        <div style="max-width: 620px; margin-bottom: 34px;">
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Three experiences, one source of truth</h2>
          <p style="margin: 0; font-size: 16px; line-height: 1.66; color: var(--vy-muted);">Because it's one platform, an action in one place is already true everywhere else. That's the part a stack of integrations never quite delivers.</p>
        </div>
        <div data-trio="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
          <div style="padding: 22px; border-radius: 18px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.8);">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">A guest books direct</div>
            <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">The unit leaves availability everywhere, housekeeping gets the turnaround, the owner's occupancy updates, and no commission is charged.</p>
          </div>
          <div style="padding: 22px; border-radius: 18px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.8);">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">A tap breaks</div>
            <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">The work order takes the unit out of service so it stops selling, and the repair cost appears on the owner's next statement — with their approval.</p>
          </div>
          <div style="padding: 22px; border-radius: 18px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.8);">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">You refund a guest</div>
            <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">The folio, the guest's credit note, the owner statement, the payout and the audit trail all move together. Nothing to remember.</p>
          </div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 64px 48px; text-align: center;">
        <div data-orb="" style="position: absolute; top: -110px; right: 16%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .4; animation: vyDrift 21s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 38px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">See it with your own properties in it</h2>
          <p style="margin: 18px auto 0; max-width: 520px; font-size: 16.5px; line-height: 1.62; color: rgba(255,247,240,.74);">Thirty minutes, your portfolio, real questions about how you actually work.</p>
          <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 30px;">
            <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Book a demo</x-marketing.nav-link>
            <x-marketing.nav-link key="features" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,247,240,.12); border: 1px solid rgba(255,247,240,.24); color: #fff; font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, background .22s ease;">Browse every feature</x-marketing.nav-link>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
