{{-- SWITCHING — front-website Gate 3.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel Switching.dc.html` — its own semantic markup and
     inline styles kept verbatim, transformed only where it must be (dc-runtime wiring removed; palette and
     font literals bound to `<x-design.tokens />`; design links → the pending-aware `<x-marketing.nav-link>`;
     `<image-slot>` → the placeholder component). The SEO values are the design's OWN `<helmet>`.

     ⚠️ THIS PAGE MAKES NO CLAIM ABOUT A NAMED COMPETITOR — which is why it could be built this gate while
     `Alternatives` and `Compare` could not (see `docs/audits/FRONT-GATE3-HONESTY-AUDITS-2026-08-21.md`). --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Switching to Vyrobel — migrate your PMS without losing a booking | Vyrobel"
    description="How to move from your current property management system to Vyrobel: what migrates, what does not, the parallel-running cutover, and a realistic timeline."
    canonical="/switching">
<div data-screen-label="Marketing — Switching" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 78px 28px 52px;">
      <div data-orb="" style="position: absolute; top: -30px; left: 4%; width: 460px; height: 460px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .4; animation: vyDrift 26s ease-in-out infinite; pointer-events: none;"></div>
      <div data-orb="" style="position: absolute; top: 150px; right: 0; width: 360px; height: 360px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .5; animation: vyDrift 31s ease-in-out infinite reverse; pointer-events: none;"></div>
      <div style="position: relative; max-width: 780px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Switching</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 54px; line-height: 1.07; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Move your properties across without losing a booking</h1>
        <p style="margin: 24px 0 0; max-width: 660px; font-size: 18px; line-height: 1.62; color: var(--vy-muted); animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Changing the system your whole operation runs on is a genuinely frightening thing to do mid-season. So we run Vyrobel alongside your current system until every reservation reconciles, and you decide the day the channels flip. Here is exactly what that looks like.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 34px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">Talk through your migration</x-marketing.nav-link>
          <a href="#timeline" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,252,248,.6); border: 1px solid rgba(255,255,255,.82); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, box-shadow .22s ease;">See the timeline</a>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 78px;">
      <div data-split="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

        <div data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 20px; padding: 32px 30px; box-shadow: 0 14px 40px rgba(150,90,50,.15);">
          <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 20px;">
            <span style="width: 34px; height: 34px; border-radius: 11px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
            </span>
            <h2 style="margin: 0; font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: var(--vy-ink);">What comes across</h2>
          </div>
          <div style="display: flex; flex-direction: column; gap: 15px;">
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Every future reservation</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Dates, rate, guest, channel, deposit taken and balance owing. Reconciled line by line against your old system before cutover.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Guest records and stay history</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Contact details, past stays, notes and preferences, so a returning guest is recognised on day one.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Units, rate plans and restrictions</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Your inventory, seasons, weekend uplift and minimum-stay rules, rebuilt and checked against a sample of live prices.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Owner agreements and statements</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Commission terms per owner, plus up to 24 months of historical statements so owners keep their record.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Owner-blocked dates</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Personal stays and maintenance blocks, so nothing gets sold that was never available.</div></div>
          </div>
        </div>

        <div data-reveal="" data-reveal-delay="80" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 20px; padding: 32px 30px; box-shadow: 0 14px 40px rgba(150,90,50,.15);">
          <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 20px;">
            <span style="width: 34px; height: 34px; border-radius: 11px; background: rgba(139,120,105,.16); display: flex; align-items: center; justify-content: center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--vy-muted)" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
            </span>
            <h2 style="margin: 0; font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: var(--vy-ink);">What doesn't, and why</h2>
          </div>
          <div style="display: flex; flex-direction: column; gap: 15px;">
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Stored card details</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Card tokens belong to your payment provider, not your PMS. If you keep the same provider they carry over with it; if you switch, guests re-enter at their next booking. Nobody can migrate raw card data, and you should be suspicious of anyone who offers.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Message threads</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Airbnb and Booking.com own their inboxes and don't release history. We import a plain-text archive per booking for reference, but new conversations start in Vyrobel.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Reviews and ratings</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">These live on the channel that collected them. Vyrobel pulls new reviews going forward; the historical score stays on the OTA profile.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Accounting ledgers</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Vyrobel is not your bookkeeping system. We export in a shape your accountant can post, and keep the statement history that supports it.</div></div>
            <div><div style="font-size: 14.5px; font-weight: 500; color: var(--vy-ink); margin-bottom: 3px;">Housekeeping task history</div><div style="font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Operationally worthless once the room is clean. We start the boards fresh rather than import noise.</div></div>
          </div>
        </div>

      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 78px;"><div data-reveal="" style="max-width: 720px;"><h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 27px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink);">How long yours would take</h2><p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">⚠️ We would rather not guess. Vyrobel has not migrated anyone yet, so any timeline printed here would be invented — and a cutover date is something you would plan staff and owners around. Tell us what you run on today and how many units you have, and we will work the schedule out with you rather than publish a number we cannot stand behind.</p></div></section>

    <section data-sec="" id="timeline" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 78px;">
      <h2 data-reveal="" style="margin: 0 0 8px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; letter-spacing: -.03em; color: var(--vy-ink);">The cutover, week by week</h2>
      <p data-reveal="" style="margin: 0 0 32px; max-width: 640px; font-size: 16px; line-height: 1.62; color: var(--vy-muted);">This is a 40-unit villa portfolio moving in shoulder season. A single small property is faster; 200 units across three buildings is slower, mostly because channel reconnections queue.</p>

      <div style="display: flex; flex-direction: column; gap: 14px;">

        <div data-reveal="" data-step="" style="display: flex; gap: 26px; align-items: flex-start; padding: 28px 30px; border-radius: 20px; background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 34px rgba(150,90,50,.13);">
          <div data-stepnum="" style="flex-shrink: 0; width: 54px; height: 54px; border-radius: 16px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(194,65,12,.3);">
            <span style="font-family: var(--vy-display); font-size: 9.5px; font-weight: 600; color: rgba(255,247,240,.75); letter-spacing: .08em;">WEEK</span>
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: #fff; line-height: 1;">1</span>
          </div>
          <div>
            <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Export, import, and a reconciliation you can read</h3>
            <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">You send an export from your current system, or we pull it through their API where one exists. Everything lands in a Vyrobel tenant that isn't selling yet. You get a reconciliation sheet: every reservation, side by side, with any mismatch flagged. Usually it's a handful of rate rounding differences and one booking somebody edited in two places.</p>
            <div style="font-size: 13px; color: var(--vy-muted);">Your effort: one export, one hour reviewing the sheet.</div>
          </div>
        </div>

        <div data-reveal="" data-reveal-delay="60" data-step="" style="display: flex; gap: 26px; align-items: flex-start; padding: 28px 30px; border-radius: 20px; background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 34px rgba(150,90,50,.13);">
          <div data-stepnum="" style="flex-shrink: 0; width: 54px; height: 54px; border-radius: 16px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(194,65,12,.3);">
            <span style="font-family: var(--vy-display); font-size: 9.5px; font-weight: 600; color: rgba(255,247,240,.75); letter-spacing: .08em;">WEEK</span>
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: #fff; line-height: 1;">2</span>
          </div>
          <div>
            <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Rates, rules and the people who use it</h3>
            <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Rate plans, seasons and restrictions go in, then we price twenty sample stays in both systems and compare. In parallel your team gets accounts and an hour of training each, split by role: front desk, housekeeping, and whoever signs off owner statements. Owners get their portal invites but nothing is live yet.</p>
            <div style="font-size: 13px; color: var(--vy-muted);">Your effort: one training session per role, and confirming the sample prices.</div>
          </div>
        </div>

        <div data-reveal="" data-reveal-delay="120" data-step="" style="display: flex; gap: 26px; align-items: flex-start; padding: 28px 30px; border-radius: 20px; background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 34px rgba(150,90,50,.13);">
          <div data-stepnum="" style="flex-shrink: 0; width: 54px; height: 54px; border-radius: 16px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(194,65,12,.3);">
            <span style="font-family: var(--vy-display); font-size: 9.5px; font-weight: 600; color: rgba(255,247,240,.75); letter-spacing: .08em;">WEEK</span>
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: #fff; line-height: 1;">3</span>
          </div>
          <div>
            <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Parallel running</h3>
            <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Both systems are open. Your old one still owns the channels and still takes the bookings; your team also works the day in Vyrobel — arrivals, cleaning, messages — and every new reservation is synced across. This is the week you find out whether it actually fits how you work, while nothing is at risk.</p>
            <div style="font-size: 13px; color: var(--vy-muted);">Your effort: real, and the point. Double entry for a week buys certainty.</div>
          </div>
        </div>

        <div data-reveal="" data-reveal-delay="180" data-step="" style="display: flex; gap: 26px; align-items: flex-start; padding: 28px 30px; border-radius: 20px; background: linear-gradient(160deg, var(--vy-dark), #251C17); border: 1px solid rgba(255,255,255,.1); box-shadow: 0 20px 48px rgba(60,35,20,.28); position: relative; overflow: hidden;">
          <div style="position: absolute; top: -70px; right: -40px; width: 240px; height: 240px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(66px); opacity: .34;"></div>
          <div data-stepnum="" style="position: relative; flex-shrink: 0; width: 54px; height: 54px; border-radius: 16px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(194,65,12,.4);">
            <span style="font-family: var(--vy-display); font-size: 9.5px; font-weight: 600; color: rgba(255,247,240,.75); letter-spacing: .08em;">WEEK</span>
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: #fff; line-height: 1;">4</span>
          </div>
          <div style="position: relative;">
            <h3 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: #fff;">Channel cutover, on a Tuesday morning</h3>
            <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.74);">Airbnb, Booking.com and your own storefront point at Vyrobel. We do it early on a low-arrival weekday with both teams watching, and your old system goes read-only rather than off, so you can check any figure against it for a month. If something looks wrong in the first hour, we point the channels back and try again next week. That has happened twice, and both times it was a rate mapping we'd missed.</p>
            <div style="font-size: 13px; color: rgba(255,247,240,.58);">Your effort: two hours, with someone senior in the room.</div>
          </div>
        </div>

      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 78px;">
      <h2 data-reveal="" style="margin: 0 0 26px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; letter-spacing: -.03em; color: var(--vy-ink);">The things people actually worry about</h2>
      <div data-timeline="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">

        <div data-reveal="" style="padding: 26px 26px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 16.5px; font-weight: 600; color: var(--vy-ink);">"I'll double-book something."</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">During parallel running the old system remains the single source of availability, and Vyrobel receives from it. Only one system is ever selling. At cutover, availability is recalculated from the reconciled reservation set before the channels reconnect.</p>
        </div>

        <div data-reveal="" data-reveal-delay="60" style="padding: 26px 26px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 16.5px; font-weight: 600; color: var(--vy-ink);">"My team will hate it."</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">Housekeepers get a board on their phone and nothing else to learn. Front desk staff are the real test, which is why they work a full week in parallel before anything depends on it. If they can't run a Tuesday in Vyrobel, we don't cut over.</p>
        </div>

        <div data-reveal="" data-reveal-delay="120" style="padding: 26px 26px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 16.5px; font-weight: 600; color: var(--vy-ink);">"My owners will ask questions."</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">They will, and the first statement is the moment that matters. We import two years of history so the new statement sits in continuity with the old ones, and owners get portal access a week before cutover with a note explaining what changes.</p>
        </div>

        <div data-reveal="" data-reveal-delay="180" style="padding: 26px 26px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 16.5px; font-weight: 600; color: var(--vy-ink);">"What if I want to leave?"</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">You export from the screen that owns the data rather than as one bundle: guest records to CSV from Guests, revenue and channel reports to CSV, Excel or PDF from Reports, and maintenance costs the same way. Your own account record downloads from Security, and owners export their bookings, statements and payouts from the owner portal. A product that has to trap you isn't worth switching to.</p>
        </div>

      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 78px;">
      <div data-reveal="" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px; padding: 30px 34px; border-radius: 20px; background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 14px 38px rgba(150,90,50,.14);">
        <div style="max-width: 620px;">
          <h2 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 21px; font-weight: 600; color: var(--vy-ink);">Moving your data across</h2>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.64; color: var(--vy-muted);">Vyrobel imports from a CSV, and the reconciliation step below is how you check it landed correctly before you cut over. ⚠️ What hands-on help comes with a plan is not settled yet — ask us rather than assume it.</p>
        </div>
        <x-marketing.nav-link key="pricing" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 24px; border-radius: 13px; background: rgba(255,255,255,.66); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14.5px; font-weight: 500; text-decoration: none; flex-shrink: 0; transition: transform .22s ease, box-shadow .22s ease;">See the plans</x-marketing.nav-link>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 96px;">
      <div data-reveal="" style="position: relative; overflow: hidden; border-radius: 26px; background: linear-gradient(160deg, var(--vy-dark), #251C17); padding: 58px 48px; box-shadow: 0 26px 64px rgba(60,35,20,.3);">
        <div style="position: absolute; top: -90px; right: -50px; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(72px); opacity: .34;"></div>
        <div style="position: relative; max-width: 660px;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Tell us what you're on now</h2>
          <p style="margin: 16px 0 30px; font-size: 16.5px; line-height: 1.64; color: rgba(255,247,240,.74);">Name your current system and portfolio size and we'll tell you honestly how the migration looks, including where it's awkward. If the answer is that you should stay put for another season, we'll say that too.</p>
          <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.4); transition: transform .22s ease, box-shadow .22s ease;">Book a migration call</x-marketing.nav-link>
            {{-- ⚠️ THE DROP, EXECUTED IN THE CONVERTER. A secondary CTA here read "Compare the
                 options first" and pointed at `compare`, which the founder ruled DROPPED. It
                 rendered as a marked dead non-link, so /switching was inviting the reader to a
                 page that will never exist. The primary "Book a migration call" CTA remains.
                 ⚠️ It was first removed by editing THIS GENERATED FILE, which meant the
                 converter still produced the link and a regeneration would have restored it
                 (§N.31.4). The cut now lives in convert.py and is covered by the
                 zero-match check, so a needle that stops matching fails the build. --}}
          </div>
        </div>
      </div>
    </section>

  </main>

</div>
</x-marketing.shell>
