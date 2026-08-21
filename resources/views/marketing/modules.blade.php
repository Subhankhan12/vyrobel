{{-- MODULES — front-website Gate 2.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel Modules.dc.html` — its own semantic markup and
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
    title="Modules — the full Vyrobel property management catalog"
    description="Every module in the Vyrobel PMS: reservations, front desk, calendar, housekeeping, maintenance, rates, reports, storefront, channel manager, messaging, reviews, guest wallet, team scheduling, owner portal, payouts and security."
    canonical="/modules">
<div data-screen-label="Marketing — Modules" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 44px;">
      <div data-orb="" style="position: absolute; top: -50px; right: -90px; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 25s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 700px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Module catalog</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 52px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Eighteen modules. One login. No integration project.</h1>
        <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.64; color: var(--vy-muted); max-width: 580px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Everything below ships as part of the platform — nothing here is a partner add-on you have to wire up yourself.</p>
      </div>

    </section>

    <section data-sec="" id="included" style="position: relative; overflow: hidden; margin: 0 0 62px; padding: 66px 0 70px; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -120px; left: 7%; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -140px; right: 5%; width: 340px; height: 340px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
        <div style="max-width: 680px; margin-bottom: 32px;">
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.14; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Every module is in every plan</h2>
          <p style="margin: 0; font-size: 16.5px; line-height: 1.68; color: rgba(255,247,240,.76);">There is no owner portal add-on, no booking engine upgrade, no channel manager sold separately. Plans differ by portfolio size and support, not by which parts of the product you are allowed to use.</p>
        </div>
        <div data-incl="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
          <div style="padding: 24px 24px; border-radius: 18px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.13);">
            <div style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 8px;">Nothing is a module you buy</div>
            <div style="font-size: 14.5px; line-height: 1.64; color: rgba(255,247,240,.7);">The quote you get is the invoice you pay. Feature-gating a product this operational just moves the cost to the moment you need something.</div>
          </div>
          <div style="padding: 24px 24px; border-radius: 18px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.13);">
            <div style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 8px;">They share one data model</div>
            <div style="font-size: 14.5px; line-height: 1.64; color: rgba(255,247,240,.7);">A maintenance ticket blocks the calendar and lands on the owner statement because they are the same system, not two products with a nightly sync.</div>
          </div>
          <div style="padding: 24px 24px; border-radius: 18px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.13);">
            <div style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 8px;">Roles decide what people see</div>
            <div style="font-size: 14.5px; line-height: 1.64; color: rgba(255,247,240,.7);">A housekeeper opens a board, an owner opens their portfolio, a manager opens everything. Same account, different door.</div>
          </div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 30px;">
      <div data-reveal="" style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; padding: 20px 22px; border-radius: 20px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 12px 34px rgba(150,90,50,.13);">

        <div style="display: flex; align-items: center; gap: 9px; flex: 1; min-width: 240px; padding: 10px 14px; border-radius: 13px; background: rgba(255,255,255,.62); border: 1px solid rgba(150,90,50,.16);">
          <svg width="15" height="15" fill="none" stroke="var(--vy-muted)" stroke-width="2.2" stroke-linecap="round" style="flex-shrink: 0;"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
          <input type="search" placeholder="Search eighteen modules — try &quot;payout&quot; or &quot;channel&quot;" aria-label="Search modules" style="flex: 1; min-width: 0; border: none; background: none; outline: none; font-family: var(--vy-sans); font-size: 14px; color: var(--vy-ink);">
        </div>

        <div role="group" aria-label="Filter by audience" style="display: flex; flex-wrap: wrap; gap: 8px;">
          <button data-modchip="all" aria-pressed="true" style="flex-shrink: 0; padding: 9px 16px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .24s cubic-bezier(.22,.7,.3,1);">All eighteen</button>
          <button data-modchip="operator" aria-pressed="false" style="flex-shrink: 0; padding: 9px 16px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .24s cubic-bezier(.22,.7,.3,1);">Operator</button>
          <button data-modchip="owner" aria-pressed="false" style="flex-shrink: 0; padding: 9px 16px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .24s cubic-bezier(.22,.7,.3,1);">Owner</button>
          <button data-modchip="guest" aria-pressed="false" style="flex-shrink: 0; padding: 9px 16px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .24s cubic-bezier(.22,.7,.3,1);">Guest</button>
        </div>

        <span data-modcount="" style="font-size: 12.5px; color: var(--vy-muted); white-space: nowrap;">16 modules</span>
      </div>

      <div data-modempty="" style="display: none; text-align: center; padding: 54px 20px;">
        <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); margin-bottom: 6px;">Nothing matches that</div>
        <div style="font-size: 13.5px; color: var(--vy-muted);">Every module is in every plan, so if it exists it is included — try a different word, or clear the filter.</div>
      </div>
    </section>

    <section data-sec="" id="bookings" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px; scroll-margin-top: 96px;">
      <div data-mgrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">

        <article data-reveal="" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M8 3v4M16 3v4M3 11h18"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Reservations</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Every booking in one searchable table with status, channel and balance. Open a reservation for the folio, guest details, documents and the full change history.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Guided new-reservation wizard</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Group bookings and walk-ins</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Bulk messages and exports</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="60" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V9l8-5 8 5v11"></path><path d="M9 20v-6h6v6"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Front desk</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Arrivals, departures and in-house on a live day timeline. Check guests in and out, take the outstanding balance, and see which rooms are still being cleaned.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>On-desk card and cash collection</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Early arrival and late checkout handling</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Guest-ready status from housekeeping</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="120" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M3 10h18M8 15h3"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Calendar &amp; availability</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">A rack calendar covering every unit and night, with drag-to-reschedule, hover detail on any stay, and blocks that remove inventory from sale.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Filter by unit, status or channel</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Occupancy heatmap by month</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Owner blocks, one-off or recurring</li>
          </ul>
        </article>

        <article data-reveal="" data-module="operator" id="operations" style="scroll-margin-top: 96px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v13H3z"></path><path d="M8 7V4h8v3M3 12h18"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Housekeeping</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">A board or list view of every room's state, with drag between lanes, elapsed-versus-expected timing, and proof photos attached to the room.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Assign all, or auto-balance the day</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Guest-waiting rooms pinned to the top</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Density toggle for phones on the floor</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="60" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 1-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 1 5.4-5.4z"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Maintenance</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Work orders from open to resolved, with priority, category, due dates and WIP limits per column. Resolving one records the cost and where it lands.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Out-of-service blocks protect inventory</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Costs flow to the owner as an expense</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Bulk assign, resolve and export</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="120" data-module="operator" id="revenue" style="scroll-margin-top: 96px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V9M10 18V5M16 18v-6M22 18H2"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Rates &amp; availability</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">A pricing grid across units and dates: base rate, weekend uplift, per-night overrides, minimum stay, and a clear view of what's booked versus open.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Bulk edit a date window</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Owner-approved rate changes</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Rich hover tooltips per night</li>
          </ul>
        </article>

        <article data-reveal="" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-7-4-7 4z"></path><path d="M9 8h6"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Reports &amp; insights</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Occupancy, ADR, RevPAR, revenue and booking-source mix over any range, with peak months highlighted and drill-downs into the underlying bookings.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Custom date ranges and presets</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Owner statements with expenses</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>PDF and CSV export throughout</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="60" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M4 7l8 6 8-6"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator · Guest</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Messaging &amp; inbox</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">One inbox for guest conversations across direct, Airbnb and Booking.com, with delivery states, channel filters and the reservation context alongside.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Pin, star, react and reply</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Filter by channel or arriving today</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Bulk send to a set of reservations</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="120" data-module="guest" id="guests" style="scroll-margin-top: 96px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M8 13h8"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Guest</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Direct booking storefront</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Your own branded site: browse properties, filter by dates and guests, see a villa in detail, and book with a date-range picker on live availability.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Per-night rate breakdown at checkout</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>House rules, deposits, card payment</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Waitlist when the dates are gone</li>
          </ul>
        </article>

        <article data-reveal="" data-module="guest" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M7 10h4M7 14h8"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Guest</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Stay portal &amp; check-in</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Guests manage their own trip: online check-in with ID verification, arrival time and transfers, house rules, documents, and a countdown to arrival.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Door code and wifi, released on time</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>QR check-in code and arrival info PDF</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Second-guest ID capture</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="60" data-module="guest" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="3"></rect><path d="M2 11h20"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Guest</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Wallet, credits &amp; add-ons</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">A guest balance that survives the stay. Refunds, goodwill and referral rewards become credit, applied at checkout with an expiry they can see.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Upsells at booking and mid-stay</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Referral landing page and rewards</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Credit notes and receipts as PDF</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="120" data-module="guest" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.4.8-4.7 4.3 1.3 6.3L12 17l-5.6 3 1.3-6.3L3 9.4l6.4-.8z"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Guest · Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Reviews &amp; reputation</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Collect reviews after checkout, watch rating trends per property, and give owners a read on how their villa is actually being received.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Per-unit rating breakdown</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Review feed with responses</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Trends owners can see too</li>
          </ul>
        </article>

        <article data-reveal="" data-module="owner" id="owners" style="scroll-margin-top: 96px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-4 7 4v13"></path><path d="M10 21v-6h4v6"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Owner</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Owner portal</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">The owner's own dashboard: earnings, occupancy, upcoming stays, per-unit performance and an action centre for anything waiting on them.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Bookings index with filters and export</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Occupancy and pricing insights</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Revenue goals with progress</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="60" data-module="owner" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="3"></rect><circle cx="12" cy="12" r="3"></circle></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Owner</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Statements &amp; payouts</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Monthly statements with a per-unit breakdown, remittance advice and the bookings behind them — plus payout history, schedule and preferences.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Processing, paid, on hold, failed states</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Method, currency and payout day</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Balance and withdrawals when paused</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="120" data-module="owner" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v6H4zM4 14h16v6H4z"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Owner</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Tax centre &amp; agreements</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">VAT details, withholding, annual summaries and filing deadlines an owner can mark as filed — alongside the management agreement and its terms.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>VAT return CSV export</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Commission terms, renewal, e-sign</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Expenses queued for approval</li>
          </ul>
        </article>

        <article data-reveal="" data-module="operator" id="team" style="scroll-margin-top: 96px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"></circle><path d="M3 19a6 6 0 0 1 12 0M16 6a3 3 0 0 1 0 6"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Team, schedule &amp; access</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Invite staff with the right role, build the week by dragging shifts onto people, fill open shifts, and print the rota for the back office wall.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Owner, manager and accountant invites</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Inline role changes</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Coverage warnings before the week starts</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="60" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 7V5a3 3 0 0 1 6 0v2M6 7h12l-1 13H7z"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">Operator</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Channel manager</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Keep Booking.com, Airbnb and the rest in step with your live availability, with visible connection health and a reconnect flow when a token expires.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Sync all now, with per-channel status</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Connect a new channel in a few steps</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Booking-source mix in reports</li>
          </ul>
        </article>

        <article data-reveal="" data-reveal-delay="120" data-module="operator" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"></path><path d="M9.5 12l1.8 1.8 3.4-3.6"></path></svg></span>
            <span style="font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted);">All roles</span>
          </div>
          <h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Security &amp; settings</h2>
          <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Two-factor authentication, password policy, active sessions, connected apps and a security score that tells each user what to fix next.</p>
          <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 7px;">
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Operator audit trail on money actions</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Currency, timezone and tax settings</li>
            <li style="font-size: 12.5px; color: var(--vy-muted); display: flex; gap: 8px;"><span style="color: var(--vy-accent);">·</span>Data export and account deletion</li>
          </ul>
        </article>
      </div>

      <div data-reveal="" style="margin-top: 44px; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px;">
        <x-marketing.nav-link key="pricing" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 26px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">See which plan includes what</x-marketing.nav-link>
        <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">Book a walkthrough</x-marketing.nav-link>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
