{{-- HOW IT WORKS — front-website Gate 2.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel How It Works.dc.html` — its own semantic markup and
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
    title="How it works — onboarding your properties onto Vyrobel"
    description="What setting up Vyrobel actually looks like: importing your units and bookings, opening your direct booking channel, running daily operations, and giving owners and guests their portals. Time to value, migration and FAQs."
    canonical="/how-it-works">
<div data-screen-label="Marketing — How it works" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 54px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -110px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 700px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Getting started</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 52px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Switching systems mid-season shouldn't be a gamble</h1>
        <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.64; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Here's exactly what the first week looks like: what we do, what you do, and when each part of the platform starts earning its place.</p>
      </div>
    </section>

    <!-- STEP 1 -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 30px;">
      <div data-steprow="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; padding: 30px 0;">
        <div data-reveal="left">
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px;">
            <span style="width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);">1</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Day one · about two hours</span>
          </div>
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Add your properties and import what exists</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Set up your units, room types, rate plans, house rules and cancellation policy. Bring your current bookings in from a CSV or a channel export so the calendar is accurate from the first minute — no parallel-running for a month.</p>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>We import your existing reservations for you</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Units, amenities, photos and pricing in one guided flow</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Nothing goes live until you say so</div>
          </div>
        </div>
        <div data-stepart="" data-reveal="right" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 18px 50px rgba(150,90,50,.18); padding: 24px;">
          <div style="font-family: var(--vy-display); font-size: 14.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 16px;">Setup progress</div>
          <div style="display: flex; flex-direction: column; gap: 11px;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center;"><svg width="13" height="13" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">4 units added</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 24px; height: 24px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center;"><svg width="13" height="13" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Rate plans configured</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.24);">
              <span style="width: 24px; height: 24px; border-radius: 8px; border: 2px solid rgba(217,102,46,.5);"></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Importing 128 bookings</span>
              <span style="font-size: 11.5px; color: var(--vy-accent-text); font-weight: 600;">62%</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.4); border: 1px solid rgba(255,255,255,.6);">
              <span style="width: 24px; height: 24px; border-radius: 8px; border: 2px solid rgba(150,90,50,.2);"></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-muted);">Publish storefront</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- STEP 2 -->
    <section data-sec="" id="timeline" style="position: relative; overflow: hidden; margin: 0 0 70px; padding: 78px 0 82px; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -130px; left: 6%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -150px; right: 5%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
      
      <div data-steprow="" style="position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; padding: 30px 0;">
        <div data-stepart="" data-reveal="left" style="background: rgba(255,255,255,.055); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 22px; box-shadow: 0 14px 40px rgba(0,0,0,.24); padding: 24px;">
          <div style="font-family: var(--vy-display); font-size: 14.5px; font-weight: 600; color: #fff; margin-bottom: 16px;">Channels</div>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 13px 14px; border-radius: 13px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.15);">
              <span style="width: 30px; height: 30px; border-radius: 9px; background: rgba(232,112,58,.2); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: var(--vy-accent);">D</span>
              <div style="flex: 1;"><div style="font-size: 13.5px; font-weight: 500; color: #fff;">Direct storefront</div><div style="font-size: 11px; color: rgba(255,247,240,.52);">0% commission</div></div>
              <span style="padding: 4px 10px; border-radius: 8px; background: rgba(232,112,58,.2); color: var(--vy-accent-3); font-size: 11px; font-weight: 600;">Live</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 13px 14px; border-radius: 13px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.15);">
              <span style="width: 30px; height: 30px; border-radius: 9px; background: rgba(150,90,50,.1); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: rgba(255,247,240,.72);">B</span>
              <div style="flex: 1;"><div style="font-size: 13.5px; font-weight: 500; color: #fff;">Booking.com</div><div style="font-size: 11px; color: rgba(255,247,240,.52);">Synced 2 min ago</div></div>
              <span style="padding: 4px 10px; border-radius: 8px; background: rgba(150,90,50,.1); color: rgba(255,247,240,.72); font-size: 11px; font-weight: 600;">Connected</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 13px 14px; border-radius: 13px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.15);">
              <span style="width: 30px; height: 30px; border-radius: 9px; background: rgba(150,90,50,.1); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: rgba(255,247,240,.72);">A</span>
              <div style="flex: 1;"><div style="font-size: 13.5px; font-weight: 500; color: #fff;">Airbnb</div><div style="font-size: 11px; color: rgba(255,247,240,.52);">Synced 2 min ago</div></div>
              <span style="padding: 4px 10px; border-radius: 8px; background: rgba(150,90,50,.1); color: rgba(255,247,240,.72); font-size: 11px; font-weight: 600;">Connected</span>
            </div>
          </div>
          <div style="margin-top: 14px; padding: 12px 14px; border-radius: 13px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.22); font-size: 12.5px; color: rgba(255,247,240,.72);">One availability pool. A direct booking closes the same night everywhere.</div>
        </div>
        <div data-reveal="right">
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px;">
            <span style="width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);">2</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,247,240,.55);">Day two · about an hour</span>
          </div>
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: #fff;">Open your direct channel</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: rgba(255,247,240,.72);">Publish the branded storefront, connect Stripe for card payments, and link your existing OTA listings so everything draws on the same availability. You don't have to leave the OTAs — you just stop depending on them.</p>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: rgba(255,247,240,.72);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Your domain, your brand, your policies</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: rgba(255,247,240,.72);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Card payments, deposits and refunds handled</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: rgba(255,247,240,.72);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Channel sync keeps the OTAs honest</div>
          </div>
        </div>
      </div>
    </div>
    </section>

    <!-- STEP 3 -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 30px;">
      <div data-steprow="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; padding: 30px 0;">
        <div data-reveal="left">
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px;">
            <span style="width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);">3</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Week one · with your team</span>
          </div>
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Bring the team onto the boards</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Invite your front desk, housekeepers and maintenance staff, each with a role that shows them only what they need. Most teams need one shift to stop asking where the list is — the boards look like the work they already do.</p>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Housekeeping board works on a phone</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Roles keep the money screens off the floor</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Shifts and coverage planned a week ahead</div>
          </div>
        </div>
        <div data-stepart="" data-reveal="right" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 18px 50px rgba(150,90,50,.18); padding: 24px;">
          <div style="font-family: var(--vy-display); font-size: 14.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 16px;">Team &amp; roles</div>
          <div style="display: flex; flex-direction: column; gap: 9px;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 30px; height: 30px; border-radius: 10px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--vy-display); font-size: 12px; font-weight: 600;">MR</span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Marco Reyes</span>
              <span style="font-size: 11.5px; color: var(--vy-muted);">Front desk</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 30px; height: 30px; border-radius: 10px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--vy-display); font-size: 12px; font-weight: 600;">IS</span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Ines Santos</span>
              <span style="font-size: 11.5px; color: var(--vy-muted);">Housekeeping</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 30px; height: 30px; border-radius: 10px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--vy-display); font-size: 12px; font-weight: 600;">AK</span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Andreas Kell</span>
              <span style="font-size: 11.5px; color: var(--vy-muted);">Maintenance</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 13px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.24);">
              <span style="width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.2); display: flex; align-items: center; justify-content: center;"><svg width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-accent-text); font-weight: 500;">Invite an owner or accountant</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- STEP 4 -->
    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 60px;">
      <div data-orb="" style="position: absolute; bottom: -20px; left: -60px; width: 340px; height: 340px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .55; animation: vyDrift 22s ease-in-out infinite; pointer-events: none;"></div>
      <div data-steprow="" style="position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; padding: 30px 0;">
        <div data-stepart="" data-reveal="left" style="background: linear-gradient(160deg, var(--vy-dark), #251C17); border-radius: 22px; box-shadow: 0 24px 60px rgba(60,35,20,.34); padding: 26px; position: relative; overflow: hidden;">
          <div style="position: absolute; top: -60px; right: -40px; width: 200px; height: 200px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(60px); opacity: .38;"></div>
          <div style="position: relative;">
            <div style="font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,247,240,.55); margin-bottom: 14px;">First month on Vyrobel</div>
            <div style="display: flex; flex-direction: column; gap: 14px;">
              <div style="display: flex; justify-content: space-between; align-items: center;"><span style="font-size: 13.5px; color: rgba(255,247,240,.72);">Direct bookings taken</span><span style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums;">31</span></div>
              <div style="display: flex; justify-content: space-between; align-items: center;"><span style="font-size: 13.5px; color: rgba(255,247,240,.72);">Commission not paid</span><span style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums;">€3,940</span></div>
              <div style="display: flex; justify-content: space-between; align-items: center;"><span style="font-size: 13.5px; color: rgba(255,247,240,.72);">Owner statements sent</span><span style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums;">6</span></div>
              <div style="display: flex; justify-content: space-between; align-items: center;"><span style="font-size: 13.5px; color: rgba(255,247,240,.72);">Hours spent building them</span><span style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff; font-variant-numeric: tabular-nums;">0</span></div>
            </div>
          </div>
        </div>
        <div data-reveal="right">
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px;">
            <span style="width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(194,65,12,.28);">4</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted);">Month one · it compounds</span>
          </div>
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Guests and owners get their own doors</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Online check-in goes out before arrival. Arrival info releases on schedule. At month end, statements and payouts run without anyone opening a spreadsheet — and your owners find out how they did by logging in, not by asking.</p>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Statements generate on your schedule</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Guests self-serve the questions you answer daily</div>
            <div style="display: flex; gap: 11px; font-size: 14.5px; color: var(--vy-muted);"><svg width="17" height="17" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="M20 6L9 17l-5-5"></path></svg>Every direct booking widens the margin</div>
          </div>
        </div>
      </div>
    </section>

    <!-- WHAT WE DO -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 44px 42px;">
        <h2 style="margin: 0 0 28px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -.03em; color: var(--vy-ink);">What we handle, and what stays with you</h2>
        <div data-trio="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
          <div style="padding: 24px; border-radius: 18px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.8);">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 12px;">We do</div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 9px;">
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Import your bookings and units</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Set up channels and payments with you</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Train your team on their screens</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Stay on hand through your first month-end</li>
            </ul>
          </div>
          <div style="padding: 24px; border-radius: 18px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.8);">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 12px;">You decide</div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 9px;">
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Your rates, policies and house rules</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Commission terms with each owner</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Which channels stay switched on</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">When the storefront goes live</li>
            </ul>
          </div>
          <div style="padding: 24px; border-radius: 18px; background: rgba(255,255,255,.5); border: 1px solid rgba(255,255,255,.8);">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 12px;">Time to value</div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 9px;">
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Day 1 — calendar is accurate</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Day 2 — taking direct bookings</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Week 1 — operations off WhatsApp</li>
              <li style="font-size: 14px; line-height: 1.55; color: var(--vy-muted);">Month 1 — first automatic statements</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section data-sec="" style="max-width: 880px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="margin-bottom: 30px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.03em; color: var(--vy-ink);">Common questions before starting</h2>
      </div>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Do we have to leave Booking.com and Airbnb?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">No. Most operators keep them running and use Vyrobel to grow the direct share alongside. The OTAs stay a distribution channel rather than the whole business — and one availability pool means no double-booking between them.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">What happens to bookings we already have?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">They come with you. We import your existing reservations with their dates, guests, balances and channel, so the calendar is right on day one and your team isn't checking two systems.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Can we start with one property?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Yes, and most do. Run a single property for a month, see how the direct share and the operations boards behave, then bring the rest of the portfolio across.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Do our owners have to learn a new tool?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Only if they want to. They get an invite email, set a password, and land on a dashboard showing this month's earnings and their next payout. Everything else — statements, expenses, tax documents — is where they'd expect it.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Where does our data live?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">In the EU, under GDPR, with data minimisation and guest anonymisation built into the platform. You can export everything at any time, and closing an account means the data goes with you.</p>
        </details>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 62px 48px; text-align: center;">
        <div data-orb="" style="position: absolute; top: -110px; left: 18%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .4; animation: vyDrift 21s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 38px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Start with one property this week</h2>
          <p style="margin: 18px auto 0; max-width: 520px; font-size: 16.5px; line-height: 1.62; color: rgba(255,247,240,.74);">Free to try, no card, and we'll import your bookings before you decide anything.</p>
          <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 30px;">
            <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Start free</a>
            <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,247,240,.12); border: 1px solid rgba(255,247,240,.24); color: #fff; font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, background .22s ease;">Talk to us first</x-marketing.nav-link>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
