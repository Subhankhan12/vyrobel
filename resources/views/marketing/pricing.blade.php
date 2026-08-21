{{-- PRICING — front-website Gate 2.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel Pricing.dc.html` — its own semantic markup and
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
    title="Pricing — per-unit plans for hotels, apartments and short-stays | Vyrobel"
    description="Vyrobel pricing: simple per-unit plans for property managers. Every plan includes the commission-free direct booking engine, the owner portal and the guest stay app. Monthly or annual, no setup fee."
    canonical="/pricing">
<div data-screen-label="Marketing — Pricing" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <div data-printhead="" style="display: none; border-bottom: 1pt solid #B8ADA5; padding-bottom: 8pt; margin-bottom: 14pt;">
      <div style="font-family: var(--vy-display); font-size: 15pt; font-weight: 600; color: var(--vy-accent);">Vyrobel</div>
      <div style="font-size: 8.5pt; color: var(--vy-muted); margin-top: 3pt;">Property management platform · Pricing summary · <span data-printdate=""></span></div>
      <div style="font-size: 8pt; color: var(--vy-muted); margin-top: 2pt;">Prices are per unit per month, illustrative and exclusive of VAT. For a firm quote: sales@vyrobel.com · vyrobel.com/pricing</div>
    </div>

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 44px; text-align: center;">
      <div data-orb="" style="position: absolute; top: -50px; left: 30%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .42; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 660px; margin: 0 auto;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8F2F09; margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Pricing</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 52px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">One price per unit. The direct channel pays for it.</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Every plan includes the booking engine, the owner portal and the guest app. Figures below are illustrative — we'll quote your portfolio properly.</p>

        <div style="display: inline-flex; align-items: center; gap: 6px; margin-top: 30px; padding: 5px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); box-shadow: 0 8px 22px rgba(150,90,50,.12); animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <button data-cycle="monthly" style="padding: 10px 22px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .25s ease;">Monthly</button>
          <button data-cycle="annual" style="padding: 10px 22px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .25s ease; display: inline-flex; align-items: center; gap: 8px;">Annual <span style="padding: 2px 7px; border-radius: 6px; background: rgba(217,102,46,.16); color: #8F2F09; font-size: 10.5px; font-weight: 600;">−15%</span></button>
        </div>

        <div role="group" aria-label="Display currency" style="display: flex; justify-content: center; gap: 8px; margin-top: 16px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .26s both;">
          <button data-curbtn="EUR" aria-pressed="true" style="padding: 7px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .22s ease;">€ EUR</button>
          <button data-curbtn="GBP" aria-pressed="false" style="padding: 7px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .22s ease;">£ GBP</button>
          <button data-curbtn="USD" aria-pressed="false" style="padding: 7px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .22s ease;">$ USD</button>
        </div>

        <div data-noprint="" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px; margin-top: 26px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .32s both;">
          <button style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,.85); background: rgba(255,252,248,.62); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: transform .22s ease, box-shadow .22s ease;">
            <svg width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 11l5 5 5-5M4 21h16"></path></svg>
            Download pricing as PDF
          </button>
          <x-marketing.nav-link key="contact" fragment="#form" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,.85); background: rgba(255,252,248,.62); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); text-decoration: none; transition: transform .22s ease, box-shadow .22s ease;">
            <svg width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4zM4 7l8 6 8-6"></path></svg>
            Request a formal quote
          </x-marketing.nav-link>
        </div>
        <div data-noprint="" style="text-align: center; font-size: 12px; color: var(--vy-muted); margin-top: 12px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .38s both;">Prints as a one-page summary with the plan you're viewing, in your selected currency.</div>
      </div>
    </section>

    <section data-sec="" aria-labelledby="vy-plans-h" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
      <h2 id="vy-plans-h" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0;">Plans and prices</h2>
      <div data-plans="" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; align-items: stretch; padding-top: 14px;">

        <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; padding: 30px 26px; box-shadow: 0 14px 40px rgba(150,90,50,.14); display: flex; flex-direction: column;">
          <div data-planname="starter" style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Starter</div>
          <div style="font-size: 13px; color: var(--vy-muted); margin-top: 5px;">1 property, up to 10 units</div>
          <div style="margin: 24px 0 4px; display: flex; align-items: baseline; gap: 5px;">
            <span style="font-family: var(--vy-display); font-size: 42px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.035em; font-variant-numeric: tabular-nums;"><span data-cur="">€</span><span data-price="" data-plan="starter">6</span></span>
            <span style="font-size: 13px; color: var(--vy-muted);">/ unit / mo</span>
          </div>
          <div data-cyclenote="" style="font-size: 12px; color: var(--vy-muted); margin-bottom: 18px;">Billed monthly</div>
          <ul style="margin: 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px;">
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Direct booking storefront</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Reservations &amp; front desk</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Calendar &amp; housekeeping</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Guest stay portal</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Up to 3 team members</li>
          </ul>
          <a data-plancta="starter" href="Vyrobel Signup.dc.html" style="margin-top: auto; text-align: center; padding: 13px; border-radius: 13px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14px; font-weight: 500; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease;">Start free</a>
        </div>

        <div data-reveal="" data-reveal-delay="70" style="position: relative; z-index: 1; background: rgba(255,252,248,.74); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1.5px solid rgba(217,102,46,.42); border-radius: 22px; padding: 30px 26px; box-shadow: 0 22px 56px rgba(194,65,12,.22); display: flex; flex-direction: column;">
          <span style="position: absolute; top: -13px; left: 26px; z-index: 2; white-space: nowrap; padding: 5px 12px; border-radius: 999px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 11px; font-weight: 600; letter-spacing: .04em; box-shadow: 0 8px 20px rgba(194,65,12,.34);">Most popular</span>
          <div data-planname="growth" style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Growth</div>
          <div style="font-size: 13px; color: var(--vy-muted); margin-top: 5px;">10–60 units, owners included</div>
          <div style="margin: 24px 0 4px; display: flex; align-items: baseline; gap: 5px;">
            <span style="font-family: var(--vy-display); font-size: 42px; font-weight: 600; color: #8F2F09; letter-spacing: -.035em; font-variant-numeric: tabular-nums;"><span data-cur="">€</span><span data-price="" data-plan="growth">5</span></span>
            <span style="font-size: 13px; color: var(--vy-muted);">/ unit / mo</span>
          </div>
          <div data-cyclenote="" style="font-size: 12px; color: var(--vy-muted); margin-bottom: 18px;">Billed monthly</div>
          <ul style="margin: 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px;">
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-ink); font-weight: 500;"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Everything in Starter</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Owner portal, statements &amp; payouts</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Maintenance &amp; work orders</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Channel manager &amp; reports</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Unlimited team &amp; shift scheduling</li>
          </ul>
          <a data-plancta="growth" href="Vyrobel Signup.dc.html" style="margin-top: auto; text-align: center; padding: 13px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.3); transition: transform .2s ease, box-shadow .2s ease;">Start free</a>
        </div>

        <div data-reveal="" data-reveal-delay="140" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; padding: 30px 26px; box-shadow: 0 14px 40px rgba(150,90,50,.14); display: flex; flex-direction: column;">
          <div data-planname="pro" style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Pro</div>
          <div style="font-size: 13px; color: var(--vy-muted); margin-top: 5px;">60–250 units, multi-property</div>
          <div style="margin: 24px 0 4px; display: flex; align-items: baseline; gap: 5px;">
            <span style="font-family: var(--vy-display); font-size: 42px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.035em; font-variant-numeric: tabular-nums;"><span data-cur="">€</span><span data-price="" data-plan="pro">4</span></span>
            <span style="font-size: 13px; color: var(--vy-muted);">/ unit / mo</span>
          </div>
          <div data-cyclenote="" style="font-size: 12px; color: var(--vy-muted); margin-bottom: 18px;">Billed monthly</div>
          <ul style="margin: 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px;">
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-ink); font-weight: 500;"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Everything in Growth</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Multi-property switching</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Granular roles &amp; audit trail</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Insights, goals &amp; tax centre</li>
            <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: var(--vy-muted);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Priority support</li>
          </ul>
          <a data-plancta="pro" href="Vyrobel Contact.dc.html" style="margin-top: auto; text-align: center; padding: 13px; border-radius: 13px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14px; font-weight: 500; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease;">Talk to us</a>
        </div>

        <div data-reveal="" data-reveal-delay="210" style="background: linear-gradient(160deg, var(--vy-dark), #251C17); border-radius: 22px; padding: 30px 26px; box-shadow: 0 22px 56px rgba(60,35,20,.3); display: flex; flex-direction: column; position: relative; overflow: hidden;">
          <div style="position: absolute; top: -70px; right: -50px; width: 200px; height: 200px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(60px); opacity: .34;"></div>
          <div style="position: relative; display: flex; flex-direction: column; flex: 1;">
            <div data-planname="enterprise" style="font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Enterprise</div>
            <div style="font-size: 13px; color: rgba(255,247,240,.6); margin-top: 5px;">250+ units, multi-brand groups</div>
            <div style="margin: 24px 0 4px;"><span style="font-family: var(--vy-display); font-size: 34px; font-weight: 600; color: #fff; letter-spacing: -.035em;">Custom</span></div>
            <div style="font-size: 12px; color: rgba(255,247,240,.7); margin-bottom: 18px;">Annual agreement</div>
            <ul style="margin: 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px;">
              <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: #fff; font-weight: 500;"><svg width="16" height="16" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Everything in Pro</li>
              <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: rgba(255,247,240,.72);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Custom management agreements</li>
              <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: rgba(255,247,240,.72);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Guided migration &amp; onboarding</li>
              <li style="display: flex; gap: 10px; font-size: 13.5px; line-height: 1.5; color: rgba(255,247,240,.72);"><svg width="16" height="16" fill="none" stroke="var(--vy-accent-3)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="M20 6L9 17l-5-5"></path></svg>Named contact &amp; SLA</li>
            </ul>
            <a data-plancta="enterprise" href="Vyrobel Contact.dc.html" style="margin-top: auto; text-align: center; padding: 13px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.4); transition: transform .2s ease, box-shadow .2s ease;">Talk to sales</a>
          </div>
        </div>
      </div>

      <div data-portfolionote="" style="display: none; margin-top: 20px; text-align: center; font-family: var(--vy-display); font-size: 15px; font-weight: 500; color: #8F2F09;"></div>

      <div data-reveal="" style="margin-top: 22px; text-align: center; font-size: 13px; color: var(--vy-muted);">No setup fee · No commission on direct bookings · Card processing is billed by your payment provider</div>
    </section>

    <!-- ROI -->
    <section data-sec="" id="calculator" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 90px; scroll-margin-top: 96px;">
      <div data-orb="" style="position: absolute; top: -30px; right: -90px; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .4; animation: vyDrift 26s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; max-width: 620px; margin-bottom: 34px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8F2F09; margin-bottom: 14px;">Worth it?</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink);">Check the subscription against the commission</h2>
        <p style="margin: 16px 0 0; font-size: 16px; line-height: 1.64; color: var(--vy-muted);">For most portfolios the plan costs a fraction of one month's OTA commission. Set your numbers and see.</p>
      </div>
      <div data-reveal="" style="position: relative;">
        <x-marketing.roi-placeholder />
      </div>
    </section>

    <!-- COMPARISON -->
    <section data-sec="" id="compare-table" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="max-width: 620px; margin-bottom: 30px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.03em; color: var(--vy-ink);">What's in each plan</h2>
        <p style="margin: 14px 0 0; font-size: 15.5px; line-height: 1.64; color: var(--vy-muted);">Grouped by what it changes for you. Everything in a lower plan is in every plan above it.</p>
      </div>
      <div data-reveal="" data-cmpscroll="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 8px 10px 16px;">
        <sc-raw-table data-cmp="" style="width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 660px;">
          <sc-raw-caption style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Feature comparison across the Starter, Growth, Pro and Enterprise plans</sc-raw-caption>
          <sc-raw-thead>
            <sc-raw-tr>
              <sc-raw-th scope="col" style="text-align: left; padding: 16px 16px; font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); position: sticky; top: 74px; z-index: 3; background: rgba(250,241,233,.96); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">Capability</sc-raw-th>
              <sc-raw-th scope="col" style="text-align: center; padding: 16px 12px; font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); background: rgba(250,241,233,.96); position: sticky; top: 74px; z-index: 3; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">Starter</sc-raw-th>
              <sc-raw-th scope="col" style="text-align: center; padding: 16px 12px; font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: #8F2F09; background: linear-gradient(rgba(217,102,46,.08), rgba(217,102,46,.08)), rgba(250,241,233,.96); position: sticky; top: 74px; z-index: 3; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">Growth<span style="display: block; font-family: var(--vy-sans); font-size: 9.5px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: #8F2F09; margin-top: 3px;">Most chosen</span></sc-raw-th>
              <sc-raw-th scope="col" style="text-align: center; padding: 16px 12px; font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); background: rgba(250,241,233,.96); position: sticky; top: 74px; z-index: 3; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">Pro</sc-raw-th>
              <sc-raw-th scope="col" style="text-align: center; padding: 16px 12px; font-family: var(--vy-display); font-size: 14px; font-weight: 600; color: var(--vy-ink); background: rgba(250,241,233,.96); position: sticky; top: 74px; z-index: 3; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">Enterprise</sc-raw-th>
            </sc-raw-tr>
          </sc-raw-thead>
          <sc-raw-tbody>
            <sc-raw-tr>
              <sc-raw-th colspan="5" scope="colgroup" style="text-align: left; padding: 26px 16px 12px; border-top: 1px solid rgba(150,90,50,.16);">
                <span style="display: block; font-family: var(--vy-display); font-size: 15px; font-weight: 600; letter-spacing: -.01em; color: var(--vy-ink);">Bookings &amp; front desk</span>
                <span style="display: block; font-size: 12.5px; font-weight: 400; color: var(--vy-muted); margin-top: 3px;">Everything you need to sell and run a stay.</span>
              </sc-raw-th>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Commission-free direct bookings</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Your own storefront, no commission on what it takes</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Reservations, front desk &amp; calendar</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Booking engine that cannot double-book, arrivals board, rack calendar</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Guest stay portal &amp; online check-in</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Registration, ID capture, door codes released on your schedule</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Channel manager (OTA sync)</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Airbnb and Booking.com, mapped per unit and rate plan</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr>
              <sc-raw-th colspan="5" scope="colgroup" style="text-align: left; padding: 26px 16px 12px; border-top: 1px solid rgba(150,90,50,.16);">
                <span style="display: block; font-family: var(--vy-display); font-size: 15px; font-weight: 600; letter-spacing: -.01em; color: var(--vy-ink);">Operations</span>
                <span style="display: block; font-size: 12.5px; font-weight: 400; color: var(--vy-muted); margin-top: 3px;">The daily work, and the inventory it protects.</span>
              </sc-raw-th>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Housekeeping board</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Four states, auto-balanced assignment, proof photos</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Maintenance &amp; work orders</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Tickets that take a unit off sale across every channel</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Shift scheduling &amp; coverage</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Drag shifts, see coverage gaps before they happen</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr>
              <sc-raw-th colspan="5" scope="colgroup" style="text-align: left; padding: 26px 16px 12px; border-top: 1px solid rgba(150,90,50,.16);">
                <span style="display: block; font-family: var(--vy-display); font-size: 15px; font-weight: 600; letter-spacing: -.01em; color: var(--vy-ink);">Owners &amp; money</span>
                <span style="display: block; font-size: 12.5px; font-weight: 400; color: var(--vy-muted); margin-top: 3px;">What makes the owner relationship a product rather than a spreadsheet.</span>
              </sc-raw-th>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Owner portal, statements &amp; payouts</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Statements that assemble themselves; owners log in instead of emailing</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Reports, exports &amp; VAT summaries</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Occupancy, ADR, RevPAR, tax summary, CSV and PDF</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr>
              <sc-raw-th colspan="5" scope="colgroup" style="text-align: left; padding: 26px 16px 12px; border-top: 1px solid rgba(150,90,50,.16);">
                <span style="display: block; font-family: var(--vy-display); font-size: 15px; font-weight: 600; letter-spacing: -.01em; color: var(--vy-ink);">Scale &amp; control</span>
                <span style="display: block; font-size: 12.5px; font-weight: 400; color: var(--vy-muted); margin-top: 3px;">What changes once more than one person and one property are involved.</span>
              </sc-raw-th>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Multi-property switching</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Several properties in one account, each with its own timezone and tax</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Granular roles &amp; audit trail</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">Permissions down to the individual action, with who did what</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
            <sc-raw-tr data-cmprow="" style="border-top: 1px solid rgba(150,90,50,.09); transition: background .18s ease;">
              <sc-raw-th scope="row" style="text-align: left; padding: 15px 16px; font-weight: 400;">
                <span style="display: block; font-size: 13.5px; color: var(--vy-ink);">Guided migration &amp; named contact</span>
                <span style="display: block; font-size: 12px; line-height: 1.5; color: var(--vy-muted); margin-top: 2px;">A person who knows your portfolio, through cutover and after</span>
              </sc-raw-th>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Starter</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px; background: rgba(217,102,46,.05);"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Growth</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-block; width: 12px; height: 2px; border-radius: 2px; background: rgba(120,70,40,.26);"></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Not included in Pro</span></sc-raw-td>
              <sc-raw-td style="text-align: center; padding: 15px 12px;"><span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 21px; height: 21px; border-radius: 7px; background: rgba(217,102,46,.14);"><svg width="12" height="12" fill="none" stroke="var(--vy-accent-text)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span><span style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Included in Enterprise</span></sc-raw-td>
            </sc-raw-tr>
          </sc-raw-tbody>
        </sc-raw-table>
      </div>
    </section>

    <!-- FAQ -->
    <section data-sec="" style="max-width: 880px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="margin-bottom: 28px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.03em; color: var(--vy-ink);">Billing questions</h2>
      </div>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">What counts as a unit?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Anything you sell a night of: a hotel room, an apartment, a villa. If a villa is sold whole, that's one unit — not one per bedroom.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Is there a free trial?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Yes — start with one property, no card. We'll import your bookings during the trial so you're judging the real thing, not a demo dataset.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Do owners or guests pay anything?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">No. Owner portal access and the guest app are included in your plan. You pay per unit; nobody else is billed.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">What happens in the off-season?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Seasonal portfolios can scale units down between seasons on monthly billing. Annual plans price the whole year against your peak count, which usually works out cheaper.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Can we cancel?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Monthly plans cancel at the end of the month, with no exit fee. You can export your bookings, statements and guest data in full before you go.</p>
        </details>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 62px 48px; text-align: center;">
        <div data-orb="" style="position: absolute; bottom: -130px; right: 20%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 23s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 38px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Want the number for your portfolio?</h2>
          <p style="margin: 18px auto 0; max-width: 520px; font-size: 16.5px; line-height: 1.62; color: rgba(255,247,240,.74);">Tell us how many units you run and where they are. We'll come back with a real quote and the commission you'd stop paying.</p>
          <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 30px;">
            <x-marketing.nav-link key="contact" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Get a quote</x-marketing.nav-link>
            <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,247,240,.12); border: 1px solid rgba(255,247,240,.24); color: #fff; font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, background .22s ease;">Start free instead</a>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
