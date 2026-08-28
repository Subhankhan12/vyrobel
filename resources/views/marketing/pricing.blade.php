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
    title="Pricing — one price per property account | Vyrobel"
    description="Vyrobel pricing: one price per property account, whatever your unit count. Every plan includes the commission-free direct booking engine, the owner portal and the guest stay app. Monthly or annual, no setup fee."
    canonical="/pricing">
<div data-screen-label="Marketing — Pricing" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <div data-printhead="" style="display: none; border-bottom: 1pt solid #B8ADA5; padding-bottom: 8pt; margin-bottom: 14pt;">
      <div style="font-family: var(--vy-display); font-size: 15pt; font-weight: 600; color: var(--vy-accent);">Vyrobel</div>
      <div style="font-size: 8.5pt; color: var(--vy-muted); margin-top: 3pt;">Property management platform · Pricing summary · <span data-printdate=""></span></div>
      <div style="font-size: 8pt; color: var(--vy-muted); margin-top: 2pt;">{{ \App\Support\Marketing\MarketingPricing::priceNote() }} For a firm quote: sales@vyrobel.com · vyrobel.com/pricing</div>
    </div>

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 44px; text-align: center;">
      <div data-orb="" style="position: absolute; top: -50px; left: 30%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .42; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 660px; margin: 0 auto;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8F2F09; margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Pricing</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 52px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">{{ \App\Support\Marketing\MarketingPricing::headline() }}</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Every plan includes the booking engine, the owner portal and the guest app. Figures below are illustrative — we'll quote your portfolio properly.</p>

        <div style="display: inline-flex; align-items: center; gap: 6px; margin-top: 30px; padding: 5px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); box-shadow: 0 8px 22px rgba(150,90,50,.12); animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <button data-cycle="monthly" style="padding: 10px 22px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .25s ease;">Monthly</button>
          <button data-cycle="annual" style="padding: 10px 22px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .25s ease; display: inline-flex; align-items: center; gap: 8px;">Annual <span style="padding: 2px 7px; border-radius: 6px; background: rgba(217,102,46,.16); color: #8F2F09; font-size: 10.5px; font-weight: 600;">−15%</span></button>
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
      <x-marketing.plan-cards />

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
    <x-marketing.plan-comparison />

    <!-- FAQ -->
    <section data-sec="" style="max-width: 880px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="margin-bottom: 28px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; letter-spacing: -.03em; color: var(--vy-ink);">Billing questions</h2>
      </div>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">What counts as a unit?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">A unit is anything you sell a night of — a hotel room, an apartment, a villa. ⚠️ It does not change your price: the subscription is one figure per account whatever your unit count.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Is there a free trial?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Yes — start with one property, no card. We'll import your bookings during the trial so you're judging the real thing, not a demo dataset.</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">Do owners or guests pay anything?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">No. Owner portal access and the guest app are included in your plan. {{ \App\Support\Marketing\MarketingPricing::whoPays() }}</p>
        </details>
        <details data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 16px; padding: 20px 24px;">
          <summary style="font-family: var(--vy-display); font-size: 16px; font-weight: 500; color: var(--vy-ink); cursor: pointer; list-style: none;">What happens in the off-season?</summary>
          <p style="margin: 14px 0 0; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">The subscription does not move with your unit count, so a quiet season costs the same as a busy one. Annual billing is the cheaper of the two cycles.</p>
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
