{{-- HOME — front-website Gate 2.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel Home.dc.html` — its own semantic markup and
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
    title="Vyrobel — Property management software with commission-free direct bookings"
    description="Vyrobel is all-in-one property management software for hotels, serviced apartments and short-stay operators: a commission-free direct booking engine, live front desk and housekeeping operations, and a transparent owner portal."
    canonical="/">
<div data-screen-label="Marketing — Home" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <!-- ═══ HERO ═══ -->
    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 78px 28px 92px; overflow: visible;">
      <div data-orb="" style="position: absolute; top: -60px; left: -120px; width: 460px; height: 460px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .5; animation: vyDrift 22s ease-in-out infinite; pointer-events: none;"></div>
      <div data-orb="" style="position: absolute; top: 180px; right: -100px; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .55; animation: vyDrift 27s ease-in-out infinite reverse; pointer-events: none;"></div>
      <div data-orb="" style="position: absolute; bottom: -80px; left: 42%; width: 340px; height: 340px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .6; animation: vyDrift 19s ease-in-out infinite; pointer-events: none;"></div>

      <div data-hero="" style="position: relative; display: grid; grid-template-columns: 1.05fr .95fr; gap: 56px; align-items: center;">

        <div>
          <div role="tablist" aria-label="Choose your view" style="display: inline-flex; align-items: center; gap: 4px; padding: 5px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.82); box-shadow: 0 8px 22px rgba(150,90,50,.12); margin-bottom: 24px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">
            <button role="tab" aria-selected="false" data-lensbtn="operator" style="padding: 9px 17px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .28s cubic-bezier(.22,.7,.3,1);">I run properties</button>
            <button role="tab" aria-selected="false" data-lensbtn="owner" style="padding: 9px 17px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .28s cubic-bezier(.22,.7,.3,1);">I own one</button>
            <button role="tab" aria-selected="false" data-lensbtn="guest" style="padding: 9px 17px; border-radius: 999px; border: none; background: transparent; font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; transition: all .28s cubic-bezier(.22,.7,.3,1);">I'm staying</button>
          </div>

          <!-- ── operator copy ── -->
          <div data-lenscopy="operator">
            <div style="display: inline-flex; align-items: center; gap: 9px; padding: 7px 14px 7px 9px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 6px 18px rgba(150,90,50,.12); font-size: 12.5px; font-weight: 500; color: var(--vy-muted);">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 7px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent));">
                <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
              </span>
              For hotels, serviced apartments, villas &amp; short-stays
            </div>

            <h1 data-h1="" style="margin: 22px 0 0; font-family: var(--vy-display); font-weight: 600; font-size: 60px; line-height: 1.06; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance;">
              Run your properties.<br>Delight your guests.<br><span style="background: linear-gradient(120deg, var(--vy-accent-3), var(--vy-accent)); -webkit-background-clip: text; background-clip: text; color: transparent;">Keep your commission.</span>
            </h1>

            <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.62; color: var(--vy-muted); max-width: 520px; text-wrap: pretty;">
              Vyrobel is the all-in-one property management platform that runs your front desk, housekeeping and maintenance in real time — and gives you a branded direct booking engine so the reservation, and the margin, stays yours.
            </p>

            <div data-heroroi="" style="margin-top: 30px; padding: 20px 22px; border-radius: 18px; background: rgba(255,252,248,.55); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 32px rgba(150,90,50,.13); max-width: 520px;">
              <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 14px;">
                <label for="vy-hero-units" style="font-size: 12.5px; font-weight: 500; color: var(--vy-muted);">If you manage <strong data-hu="" style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">24</strong> units…</label>
                <span style="text-align: right;">
                  <span data-hk="" style="display: block; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">€31,000</span>
                  <span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 3px;">commission kept a year</span>
                </span>
              </div>
              <input id="vy-hero-units" type="range" min="4" max="200" step="1" value="24" aria-label="Units you manage" style="width: 100%; accent-color: var(--vy-accent); cursor: pointer;">
              <div style="display: flex; justify-content: space-between; font-size: 10.5px; color: var(--vy-muted); margin-top: 5px;"><span>4</span><span>200 units</span></div>
              <a href="#calculator" style="display: inline-block; margin-top: 12px; font-size: 12.5px; font-weight: 500; text-decoration: none;">Refine with your own rate and occupancy →</a>
            </div>

            <button data-duskbtn="" aria-pressed="false" style="display: inline-flex; align-items: center; gap: 9px; margin-top: 22px; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.78); background: rgba(255,252,248,.5); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer;">
              <svg data-duskicon="" width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6L17 7M7 17l-1.4 1.4"></path></svg>
              <span data-dusklabel="">Daylight</span>
            </button>

            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px;">
              <a href="{{ route('signup') }}" style="position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">
                Start free
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                <span style="position: absolute; top: 0; left: 0; width: 30%; height: 100%; background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.5), rgba(255,255,255,0)); animation: vyShimmer 5.5s ease-in-out infinite;"></span>
              </a>
              <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">
                <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M8 3v4M16 3v4M3 11h18"></path></svg>
                Book a demo
              </x-marketing.nav-link>
            </div>

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 18px; margin-top: 26px; font-size: 13px; color: var(--vy-muted);">
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>No card required</span>
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>Live in a day</span>
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>GDPR-ready, hosted in the EU</span>
            </div>

            <div style="display: inline-flex; align-items: center; gap: 11px; margin-top: 24px; padding: 11px 16px 11px 13px; border-radius: 14px; background: rgba(255,252,248,.5); border: 1px solid rgba(255,255,255,.75);">
              <span style="position: relative; display: inline-flex; width: 9px; height: 9px;">
                <span style="position: absolute; inset: 0; border-radius: 50%; background: var(--vy-accent-3); opacity: .45; animation: vyPing 2.2s ease-out infinite;"></span>
                <span style="position: relative; width: 9px; height: 9px; border-radius: 50%; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></span>
              </span>
              <span style="font-size: 12.5px; color: var(--vy-muted);">Commission that stayed with operators since you opened this page <strong data-livecommission="" style="font-family: var(--vy-display); font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; margin-left: 3px;">€0.00</strong></span>
            </div>
          </div>

          <!-- ── owner copy ── -->
          <div data-lenscopy="owner" style="display: none;">
            <div style="display: inline-flex; align-items: center; gap: 9px; padding: 7px 14px 7px 9px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 6px 18px rgba(150,90,50,.12); font-size: 12.5px; font-weight: 500; color: var(--vy-muted);">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 7px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent));">
                <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
              </span>
              For owners whose property is professionally managed
            </div>

            <h2 data-h1="" style="margin: 22px 0 0; font-family: var(--vy-display); font-weight: 600; font-size: 60px; line-height: 1.06; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance;">
              Every night booked.<br>Every euro accounted for.<br><span style="background: linear-gradient(120deg, var(--vy-accent-3), var(--vy-accent)); -webkit-background-clip: text; background-clip: text; color: transparent;">No month-end phone call.</span>
            </h2>

            <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.62; color: var(--vy-muted); max-width: 520px; text-wrap: pretty;">
              Your manager runs Vyrobel; you get a portal of your own. Live occupancy and earnings, statements that build themselves, expenses you approve before they land, and the payout on its way — without asking anyone for a report.
            </p>

            <div data-heroroi="" style="margin-top: 30px; padding: 20px 22px; border-radius: 18px; background: rgba(255,252,248,.55); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 32px rgba(150,90,50,.13); max-width: 520px;">
              <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 14px;">
                <label for="vy-hero-units" style="font-size: 12.5px; font-weight: 500; color: var(--vy-muted);">If you manage <strong data-hu="" style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">24</strong> units…</label>
                <span style="text-align: right;">
                  <span data-hk="" style="display: block; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">€31,000</span>
                  <span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 3px;">commission kept a year</span>
                </span>
              </div>
              <input id="vy-hero-units" type="range" min="4" max="200" step="1" value="24" aria-label="Units you manage" style="width: 100%; accent-color: var(--vy-accent); cursor: pointer;">
              <div style="display: flex; justify-content: space-between; font-size: 10.5px; color: var(--vy-muted); margin-top: 5px;"><span>4</span><span>200 units</span></div>
              <a href="#calculator" style="display: inline-block; margin-top: 12px; font-size: 12.5px; font-weight: 500; text-decoration: none;">Refine with your own rate and occupancy →</a>
            </div>

            <button data-duskbtn="" aria-pressed="false" style="display: inline-flex; align-items: center; gap: 9px; margin-top: 22px; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.78); background: rgba(255,252,248,.5); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer;">
              <svg data-duskicon="" width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6L17 7M7 17l-1.4 1.4"></path></svg>
              <span data-dusklabel="">Daylight</span>
            </button>

            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px;">
              <x-marketing.nav-link key="product" fragment="#owners" style="position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">
                See the owner portal
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                <span style="position: absolute; top: 0; left: 0; width: 30%; height: 100%; background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.5), rgba(255,255,255,0)); animation: vyShimmer 5.5s ease-in-out infinite;"></span>
              </x-marketing.nav-link>
              <x-marketing.nav-link key="features" fragment="#owners" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">
                <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-7-4-7 4z"></path></svg>
                How statements work
              </x-marketing.nav-link>
            </div>

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 18px; margin-top: 26px; font-size: 13px; color: var(--vy-muted);">
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>Statements every month, automatically</span>
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>You approve the costs</span>
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>Your own dates, blocked in a click</span>
            </div>
          </div>

          <!-- ── guest copy ── -->
          <div data-lenscopy="guest" style="display: none;">
            <div style="display: inline-flex; align-items: center; gap: 9px; padding: 7px 14px 7px 9px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 6px 18px rgba(150,90,50,.12); font-size: 12.5px; font-weight: 500; color: var(--vy-muted);">
              <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 7px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent));">
                <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
              </span>
              For the people who actually stay
            </div>

            <h2 data-h1="" style="margin: 22px 0 0; font-family: var(--vy-display); font-weight: 600; font-size: 60px; line-height: 1.06; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance;">
              Book direct.<br>Check in from the plane.<br><span style="background: linear-gradient(120deg, var(--vy-accent-3), var(--vy-accent)); -webkit-background-clip: text; background-clip: text; color: transparent;">Arrive knowing the code.</span>
            </h2>

            <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.62; color: var(--vy-muted); max-width: 520px; text-wrap: pretty;">
              One link holds the whole stay. Book at the property's own rate, finish check-in before you travel, add a late checkout when plans change, and find the door code and wifi waiting the moment they're released.
            </p>

            <div data-heroroi="" style="margin-top: 30px; padding: 20px 22px; border-radius: 18px; background: rgba(255,252,248,.55); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 32px rgba(150,90,50,.13); max-width: 520px;">
              <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 14px;">
                <label for="vy-hero-units" style="font-size: 12.5px; font-weight: 500; color: var(--vy-muted);">If you manage <strong data-hu="" style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">24</strong> units…</label>
                <span style="text-align: right;">
                  <span data-hk="" style="display: block; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">€31,000</span>
                  <span style="display: block; font-size: 11px; color: var(--vy-muted); margin-top: 3px;">commission kept a year</span>
                </span>
              </div>
              <input id="vy-hero-units" type="range" min="4" max="200" step="1" value="24" aria-label="Units you manage" style="width: 100%; accent-color: var(--vy-accent); cursor: pointer;">
              <div style="display: flex; justify-content: space-between; font-size: 10.5px; color: var(--vy-muted); margin-top: 5px;"><span>4</span><span>200 units</span></div>
              <a href="#calculator" style="display: inline-block; margin-top: 12px; font-size: 12.5px; font-weight: 500; text-decoration: none;">Refine with your own rate and occupancy →</a>
            </div>

            <button data-duskbtn="" aria-pressed="false" style="display: inline-flex; align-items: center; gap: 9px; margin-top: 22px; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.78); background: rgba(255,252,248,.5); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); cursor: pointer;">
              <svg data-duskicon="" width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6L17 7M7 17l-1.4 1.4"></path></svg>
              <span data-dusklabel="">Daylight</span>
            </button>

            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px;">
              <x-marketing.nav-link key="try-booking" style="position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">
                Try the booking engine
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                <span style="position: absolute; top: 0; left: 0; width: 30%; height: 100%; background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.5), rgba(255,255,255,0)); animation: vyShimmer 5.5s ease-in-out infinite;"></span>
              </x-marketing.nav-link>
              <x-marketing.nav-link key="product" fragment="#guests" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">
                <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path></svg>
                See the guest app
              </x-marketing.nav-link>
            </div>

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 18px; margin-top: 26px; font-size: 13px; color: var(--vy-muted);">
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>The property's own rate</span>
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>Check in before you arrive</span>
              <span style="display: inline-flex; align-items: center; gap: 7px;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>No app to download</span>
            </div>
          </div>
        </div>

        <!-- ── mockups ── -->
        <div data-float="" style="position: relative;">

          <div data-lensmock="operator" role="img" aria-label="The Vyrobel operator dashboard: arrivals, departures, occupancy and today's revenue, playing through a changeover day">
            <div style="position: relative; display: flex; overflow: hidden; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 22px; box-shadow: 0 26px 70px rgba(150,90,50,.24), inset 0 1px 0 rgba(255,255,255,.7); animation: vyFadeUp .9s cubic-bezier(.22,.7,.3,1) .2s both;">

              <div data-mockrail="" style="flex-shrink: 0; width: 52px; padding: 14px 0; display: flex; flex-direction: column; align-items: center; gap: 4px; background: rgba(255,255,255,.42); border-right: 1px solid rgba(150,90,50,.1);">
                <span style="width: 26px; height: 26px; border-radius: 8px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 12px; font-weight: 700; color: #fff; margin-bottom: 10px;">V</span>
                <span style="width: 30px; height: 30px; border-radius: 9px; background: rgba(217,102,46,.15); display: flex; align-items: center; justify-content: center;">
                  <svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                </span>
                <span style="width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center;">
                  <svg width="14" height="14" fill="none" stroke="var(--vy-faint)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>
                </span>
                <span style="width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center;">
                  <svg width="14" height="14" fill="none" stroke="var(--vy-faint)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l-4-4 4-4M3 13h11M17 7l4 4-4 4M21 11H10"></path></svg>
                </span>
                <span style="width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center;">
                  <svg width="14" height="14" fill="none" stroke="var(--vy-faint)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"></path></svg>
                </span>
                <span style="width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center;">
                  <svg width="14" height="14" fill="none" stroke="var(--vy-faint)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20l4-9 5 4 4-8 5 13z"></path></svg>
                </span>
              </div>

              <div style="flex: 1; min-width: 0; padding: 14px 16px 16px;">

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px;">
                  <div style="display: flex; align-items: center; gap: 7px; flex: 1; min-width: 0; padding: 6px 10px; border-radius: 9px; background: rgba(255,255,255,.6); border: 1px solid rgba(150,90,50,.12);">
                    <svg width="11" height="11" fill="none" stroke="var(--vy-faint)" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
                    <span style="font-size: 10.5px; color: var(--vy-muted);">Search guests, units…</span>
                  </div>
                  <span style="position: relative; width: 26px; height: 26px; border-radius: 8px; background: rgba(255,255,255,.6); border: 1px solid rgba(150,90,50,.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="12" height="12" fill="none" stroke="var(--vy-faint)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 8-3 8h18s-3-1-3-8M13.7 21a2 2 0 01-3.4 0"></path></svg>
                    <span style="position: absolute; top: -3px; right: -3px; min-width: 13px; height: 13px; padding: 0 3px; box-sizing: border-box; border-radius: 999px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 8px; font-weight: 700; display: flex; align-items: center; justify-content: center;">3</span>
                  </span>
                  <span style="display: flex; align-items: center; gap: 6px; padding: 4px 9px 4px 4px; border-radius: 999px; background: rgba(255,255,255,.6); border: 1px solid rgba(150,90,50,.12); flex-shrink: 0;">
                    <span style="width: 19px; height: 19px; border-radius: 50%; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 9px; font-weight: 600; color: #fff;">MR</span>
                    <span data-mockuser="" style="font-size: 10.5px; font-weight: 500; color: var(--vy-ink);">Marco</span>
                  </span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px;">
                  <div>
                    <div style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink);">Good morning, Marco</div>
                    <div style="font-size: 10.5px; color: var(--vy-muted); margin-top: 2px;">Villa Azure &amp; 3 more · Tuesday, 4 August</div>
                  </div>
                  <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 10px; font-weight: 600; flex-shrink: 0;">
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); animation: vyPulse 1.8s ease-in-out infinite;"></span>Live
                  </span>
                </div>

                <div style="margin-bottom: 13px;">
                  <div style="display: flex; justify-content: space-between; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">
                    <span>11:00</span><span>The day so far</span><span>19:00</span>
                  </div>
                  <div style="position: relative; height: 4px; border-radius: 999px; background: rgba(150,90,50,.14);">
                    <div data-day-marker="" style="position: absolute; top: 0; left: 0; height: 100%; width: 8%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent)); transition: width .7s cubic-bezier(.22,.7,.3,1);"></div>
                  </div>
                </div>

                <div data-mockstats="" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 7px; margin-bottom: 13px;">
                  <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 11px; padding: 9px 8px;">
                    <div style="font-size: 8px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 4px;">Arrivals</div>
                    <div data-day-arr="" style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; letter-spacing: -.02em; transition: color .3s ease;">12</div>
                  </div>
                  <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 11px; padding: 9px 8px;">
                    <div style="font-size: 8px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 4px;">Departures</div>
                    <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; letter-spacing: -.02em;">9</div>
                  </div>
                  <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 11px; padding: 9px 8px;">
                    <div style="font-size: 8px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 4px;">In house</div>
                    <div data-day-ih="" style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; letter-spacing: -.02em; transition: color .3s ease;">84</div>
                  </div>
                  <div style="background: linear-gradient(140deg, rgba(232,112,58,.18), rgba(194,65,12,.12)); border: 1px solid rgba(217,102,46,.24); border-radius: 11px; padding: 9px 8px;">
                    <div style="font-size: 8px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 4px;">Occupancy</div>
                    <div data-day-occ="" style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; letter-spacing: -.02em;">92%</div>
                  </div>
                  <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 11px; padding: 9px 8px;">
                    <div style="font-size: 8px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 4px;">Work orders</div>
                    <div style="display: flex; align-items: baseline; gap: 4px;">
                      <span style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; letter-spacing: -.02em;">4</span>
                      <span style="font-size: 8.5px; font-weight: 500; color: var(--vy-accent);">2 urgent</span>
                    </div>
                  </div>
                </div>

                <div data-mockbody="" style="display: grid; grid-template-columns: 1.55fr 1fr; gap: 10px;">

                  <div style="background: rgba(255,255,255,.58); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 12px 13px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                      <span style="font-family: var(--vy-display); font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">Arrivals · today</span>
                      <span style="font-size: 9.5px; font-weight: 500; color: var(--vy-accent);">View all</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; padding: 7px 0; border-bottom: 1px solid rgba(150,90,50,.08);">
                      <span style="width: 22px; height: 22px; border-radius: 7px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 8.5px; font-weight: 600; color: #fff; flex-shrink: 0;">SL</span>
                      <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 10.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sofia Lindqvist</div>
                        <div style="font-size: 9px; color: var(--vy-muted);">Villa Azure · 4 nights</div>
                      </div>
                      <span style="font-size: 9.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums; flex-shrink: 0;">14:00</span>
                      <span style="padding: 2.5px 7px; border-radius: 999px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 8.5px; font-weight: 600; flex-shrink: 0;">In</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; padding: 7px 0; border-bottom: 1px solid rgba(150,90,50,.08);">
                      <span style="width: 22px; height: 22px; border-radius: 7px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 8.5px; font-weight: 600; color: #fff; flex-shrink: 0;">JO</span>
                      <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 10.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">James Okonkwo</div>
                        <div style="font-size: 9px; color: var(--vy-muted);">Villa Dunes · 2 nights</div>
                      </div>
                      <span style="font-size: 9.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums; flex-shrink: 0;">15:30</span>
                      <span style="padding: 2.5px 7px; border-radius: 999px; background: linear-gradient(140deg, rgba(232,112,58,.22), rgba(194,65,12,.18)); color: var(--vy-accent-text); font-size: 8.5px; font-weight: 600; flex-shrink: 0;">VIP</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; padding: 7px 0;">
                      <span style="width: 22px; height: 22px; border-radius: 7px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; font-family: var(--vy-display); font-size: 8.5px; font-weight: 600; color: #fff; flex-shrink: 0;">NF</span>
                      <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 10.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nadia Farouk</div>
                        <div style="font-size: 9px; color: var(--vy-muted);">Apt 102 · 6 nights</div>
                      </div>
                      <span style="font-size: 9.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums; flex-shrink: 0;">17:15</span>
                      <span style="padding: 2.5px 7px; border-radius: 999px; background: rgba(120,70,40,.09); color: var(--vy-muted); font-size: 8.5px; font-weight: 600; flex-shrink: 0;">Due</span>
                    </div>
                  </div>

                  <div style="position: relative; overflow: hidden; border-radius: 14px; background: linear-gradient(160deg,var(--vy-dark),#251C17); padding: 13px 14px;">
                    <div style="position: absolute; top: -34px; right: -24px; width: 110px; height: 110px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(38px); opacity: .38;"></div>
                    <div style="position: relative;">
                      <div style="font-size: 8.5px; letter-spacing: .08em; text-transform: uppercase; color: #B9ABA0; margin-bottom: 8px;">Tonight</div>
                      <div style="display: flex; align-items: baseline; gap: 5px;">
                        <span style="font-family: var(--vy-display); font-size: 24px; font-weight: 600; color: #fff; letter-spacing: -.02em; font-variant-numeric: tabular-nums;">84%</span>
                        <span style="font-size: 9px; color: #B9ABA0;">occupancy</span>
                      </div>
                      <div style="display: flex; align-items: flex-end; gap: 4px; height: 40px; margin-top: 12px;">
                        <div style="flex: 1; height: 46%; border-radius: 3px; background: rgba(232,112,58,.42);"></div>
                        <div style="flex: 1; height: 62%; border-radius: 3px; background: rgba(232,112,58,.5);"></div>
                        <div style="flex: 1; height: 54%; border-radius: 3px; background: rgba(232,112,58,.46);"></div>
                        <div style="flex: 1; height: 78%; border-radius: 3px; background: rgba(232,112,58,.62);"></div>
                        <div style="flex: 1; height: 84%; border-radius: 3px; background: linear-gradient(180deg,var(--vy-accent-3),var(--vy-accent));"></div>
                        <div style="flex: 1; height: 70%; border-radius: 3px; background: rgba(232,112,58,.54);"></div>
                        <div style="flex: 1; height: 92%; border-radius: 3px; background: rgba(232,112,58,.66);"></div>
                      </div>
                      <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,.1);">
                        <span style="font-size: 9px; color: #B9ABA0;">Kept this month</span>
                        <span data-day-comm="" style="font-family: var(--vy-display); font-size: 12.5px; font-weight: 600; color: var(--vy-accent-3); font-variant-numeric: tabular-nums;">€4,820</span>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <div data-day-card="" style="position: absolute; bottom: -26px; left: -34px; display: flex; align-items: center; gap: 11px; padding: 13px 16px; border-radius: 16px; background: rgba(255,252,248,.86); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.85); box-shadow: 0 16px 40px rgba(150,90,50,.22); animation: vyFadeUp .9s cubic-bezier(.22,.7,.3,1) .45s both;">
              <span data-day-icon="" style="width: 32px; height: 32px; border-radius: 10px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3 8 4-16 3 8h4"></path></svg>
              </span>
              <div>
                <div style="font-size: 11px; color: var(--vy-muted);"><span data-day-time="">11:40</span> · <span data-day-sub="">Departure logged</span></div>
                <div data-day-title="" style="font-family: var(--vy-display); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap;">Villa Azure · checked out</div>
              </div>
            </div>
          </div>

          <div data-lensmock="owner" style="display: none;" role="img" aria-label="Illustrative owner portal showing this month's earnings, occupancy and next payout">
            <div style="position: relative; background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 24px; box-shadow: 0 26px 70px rgba(150,90,50,.24), inset 0 1px 0 rgba(255,255,255,.7); padding: 22px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <div>
                  <div style="font-family: var(--vy-display); font-size: 14px; font-weight: 500; color: var(--vy-ink);">Your portfolio</div>
                  <div style="font-size: 11px; color: var(--vy-muted);">Villa Azure · Villa Coralis</div>
                </div>
                <span style="padding: 5px 10px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 11px; font-weight: 600;">July</span>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div style="background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 14px 13px;">
                  <div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">Earnings</div>
                  <div style="font-family: var(--vy-display); font-size: 22px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">€8,412</div>
                </div>
                <div style="background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 14px 13px;">
                  <div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">Occupancy</div>
                  <div style="font-family: var(--vy-display); font-size: 22px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">88%</div>
                </div>
              </div>

              <div style="background: rgba(255,255,255,.55); border: 1px solid rgba(255,255,255,.78); border-radius: 16px; padding: 16px; margin-bottom: 14px;">
                <div style="font-size: 12px; font-weight: 500; color: var(--vy-muted); margin-bottom: 14px;">This month, line by line</div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                  <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted);"><span>Gross revenue</span><span style="font-variant-numeric: tabular-nums; color: var(--vy-ink);">€11,240.00</span></div>
                  <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted);"><span>Management commission</span><span style="font-variant-numeric: tabular-nums; color: var(--vy-ink);">−€1,686.00</span></div>
                  <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted);"><span>Cleaning &amp; maintenance</span><span style="font-variant-numeric: tabular-nums; color: var(--vy-ink);">−€1,141.40</span></div>
                  <div style="height: 1px; background: rgba(150,90,50,.14);"></div>
                  <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--vy-ink);"><span>Net to you</span><span style="font-variant-numeric: tabular-nums;">€8,412.60</span></div>
                </div>
              </div>

              <div style="display: flex; align-items: center; justify-content: space-between; padding: 13px 15px; border-radius: 14px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.22);">
                <span style="font-size: 12.5px; color: var(--vy-muted);">Payout · 5 August</span>
                <span style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums;">€8,412.60</span>
              </div>
            </div>

            <div style="position: absolute; bottom: -26px; left: -34px; display: flex; align-items: center; gap: 11px; padding: 13px 16px; border-radius: 16px; background: rgba(255,252,248,.82); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.85); box-shadow: 0 16px 40px rgba(150,90,50,.22);">
              <span style="width: 32px; height: 32px; border-radius: 10px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
              </span>
              <div>
                <div style="font-size: 11px; color: var(--vy-muted);">Expense approved by you</div>
                <div style="font-family: var(--vy-display); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap;">Boiler service · €180.00</div>
              </div>
            </div>
          </div>

          <div data-lensmock="guest" style="display: none;" role="img" aria-label="Illustrative guest stay portal with check-in progress, door code and wifi">
            <div style="position: relative; background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 24px; box-shadow: 0 26px 70px rgba(150,90,50,.24), inset 0 1px 0 rgba(255,255,255,.7); padding: 22px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <div>
                  <div style="font-family: var(--vy-display); font-size: 14px; font-weight: 500; color: var(--vy-ink);">Your stay at Villa Azure</div>
                  <div style="font-size: 11px; color: var(--vy-muted);">12–19 August · 4 guests</div>
                </div>
                <span style="padding: 5px 10px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 11px; font-weight: 600;">In 8 days</span>
              </div>

              <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: var(--vy-muted); margin-bottom: 8px;"><span>Check-in complete</span><span>3 of 4 steps</span></div>
                <div style="height: 7px; border-radius: 999px; background: rgba(150,90,50,.14); overflow: hidden;"><div style="width: 75%; height: 100%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent));"></div></div>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 14px 13px;"><div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">Door code</div><div style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink); letter-spacing: .1em;">4 8 2 1</div></div>
                <div style="background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.8); border-radius: 14px; padding: 14px 13px;"><div style="font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 6px;">Wifi</div><div style="font-family: var(--vy-display); font-size: 14px; font-weight: 500; color: var(--vy-ink); margin-top: 3px;">azure-guest</div></div>
              </div>

              <div style="background: rgba(255,255,255,.55); border: 1px solid rgba(255,255,255,.78); border-radius: 16px; padding: 16px; margin-bottom: 12px;">
                <div style="font-size: 12px; font-weight: 500; color: var(--vy-muted); margin-bottom: 13px;">Added to your stay</div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                  <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted);"><span>Airport transfer, 14:20</span><span style="font-variant-numeric: tabular-nums; color: var(--vy-ink);">€45.00</span></div>
                  <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted);"><span>Late checkout, 14:00</span><span style="font-variant-numeric: tabular-nums; color: var(--vy-ink);">€30.00</span></div>
                </div>
              </div>

              <div style="display: flex; align-items: center; justify-content: space-between; padding: 13px 15px; border-radius: 14px; background: linear-gradient(140deg, rgba(232,112,58,.16), rgba(194,65,12,.1)); border: 1px solid rgba(217,102,46,.22);">
                <span style="font-size: 12.5px; color: var(--vy-muted);">Wallet credit</span>
                <span style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums;">€25.00</span>
              </div>
            </div>

            <div style="position: absolute; bottom: -26px; left: -34px; display: flex; align-items: center; gap: 11px; padding: 13px 16px; border-radius: 16px; background: rgba(255,252,248,.82); backdrop-filter: blur(18px) saturate(1.15); -webkit-backdrop-filter: blur(18px) saturate(1.15); border: 1px solid rgba(255,255,255,.85); box-shadow: 0 16px 40px rgba(150,90,50,.22);">
              <span style="width: 32px; height: 32px; border-radius: 10px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M4 7l8 6 8-6"></path></svg>
              </span>
              <div>
                <div style="font-size: 11px; color: var(--vy-muted);">From the property</div>
                <div style="font-family: var(--vy-display); font-size: 12.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap;">Your room is ready early</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ STATS BAND ═══ -->
    <x-marketing.stats-band />

    <!-- ═══ ROI CALCULATOR ═══ -->
    <section data-sec="" id="calculator" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 84px; scroll-margin-top: 96px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -90px; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .42; animation: vyDrift 25s ease-in-out infinite; pointer-events: none;"></div>
      <div data-reveal="" style="position: relative; max-width: 640px; margin-bottom: 40px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">Do the maths</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">What is the OTA commission actually costing you?</h2>
        <p style="margin: 18px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">Move the sliders to your portfolio. The number on the right is what stays in the business when direct bookings become the default rather than the exception.</p>
      </div>
      <div data-reveal="" style="position: relative;">
        <x-marketing.roi-placeholder />
      </div>
    </section>

    <!-- ═══ THREE EXPERIENCES ═══ -->
    <section data-sec="" id="audiences" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-orb="" style="position: absolute; top: 80px; right: 4%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .42; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; max-width: 660px; margin-bottom: 46px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">One platform, three experiences</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Every side of your business, on the same system</h2>
        <p style="margin: 18px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">Most property management software serves the operator and leaves everyone else guessing. Vyrobel gives your team, your owners and your guests their own purpose-built experience — sharing one source of truth, with the right privacy boundaries between them.</p>
      </div>

      <div data-trio="" style="position: relative; display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px;">
        <article data-reveal="" style="background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14), inset 0 1px 0 rgba(255,255,255,.65); padding: 30px 28px; transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent)); box-shadow: 0 10px 22px rgba(194,65,12,.28); margin-bottom: 20px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"></rect><path d="M3 9h18M8 4v5M8 14h4"></path></svg>
          </span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink);">Operators</h3>
          <p style="margin: 0 0 18px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Reservations, front desk, calendar, housekeeping, maintenance, rates and reports — the whole day runs from one place, in real time, across every property you manage.</p>
          <x-marketing.nav-link key="product" fragment="#operators" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">Explore the operator app <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </article>

        <article data-reveal="" data-reveal-delay="90" style="background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14), inset 0 1px 0 rgba(255,255,255,.65); padding: 30px 28px; transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent)); box-shadow: 0 10px 22px rgba(194,65,12,.28); margin-bottom: 20px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l9-6 9 6v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 21v-7h6v7"></path></svg>
          </span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink);">Owners</h3>
          <p style="margin: 0 0 18px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">A portal of their own: live occupancy and earnings, real statements, payout runs, approved expenses and blocked owner stays. No spreadsheets, no month-end phone calls, no guest data they shouldn't see.</p>
          <x-marketing.nav-link key="product" fragment="#owners" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">See the owner portal <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </article>

        <article data-reveal="" data-reveal-delay="180" style="background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14), inset 0 1px 0 rgba(255,255,255,.65); padding: 30px 28px; transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
          <span style="display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent)); box-shadow: 0 10px 22px rgba(194,65,12,.28); margin-bottom: 20px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path></svg>
          </span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink);">Guests</h3>
          <p style="margin: 0 0 18px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">A branded storefront to book direct, then a stay portal that handles online check-in, ID capture, door code and wifi, add-ons, messages and their wallet credit — without a single email chain.</p>
          <x-marketing.nav-link key="product" fragment="#guests" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">View the guest experience <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </article>
      </div>
    </section>

    <!-- ═══ HOW IT FITS TOGETHER ═══ -->
    <section data-sec="" id="fits" data-flowdiagram="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 84px; scroll-margin-top: 96px;">
      <div data-orb="" style="position: absolute; top: 40px; left: -90px; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(72px); opacity: .32; animation: vyDrift 28s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; max-width: 660px; margin-bottom: 40px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8F2F09; margin-bottom: 14px;">How it fits together</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">One booking, three people, no re-typing</h2>
        <p style="margin: 18px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">A guest books. Your team runs the stay. The owner sees what it earned. Each step feeds the next automatically — that is the whole product in one picture.</p>
      </div>

      <div data-reveal="" data-flowrow="" style="position: relative; display: flex; align-items: stretch; gap: 4px;">
          <div data-flownode="" style="position: relative; flex: 1; min-width: 0; padding: 26px 24px; border-radius: 22px; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 16px 42px rgba(150,90,50,.15); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
            <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: #8F2F09; margin-bottom: 10px;">Books &amp; stays</div>
            <div style="font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); margin-bottom: 16px;">The guest</div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 9px;">
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted);"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); flex-shrink: 0; margin-top: 6px;"></span>Books direct on your storefront, or arrives from a channel</li>
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted);"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); flex-shrink: 0; margin-top: 6px;"></span>Checks in online, ID captured, door code released</li>
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted);"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); flex-shrink: 0; margin-top: 6px;"></span>Asks for a late checkout in one thread</li>
            </ul>
          </div>
          <div data-flowarrow="" style="flex-shrink: 0; width: 118px; display: flex; flex-direction: column; align-items: center; gap: 8px; padding-top: 52px;">
            <div style="font-size: 11px; font-weight: 600; color: #8F2F09; text-align: center; line-height: 1.35;">Booking &amp; guest data</div>
            <svg width="100" height="16" fill="none" aria-hidden="true" style="overflow: visible;">
              <path d="M2 8h86" stroke="rgba(194,65,12,.28)" stroke-width="2" stroke-linecap="round" stroke-dasharray="5 5"></path>
              <path d="M2 8h86" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-dasharray="14 86" style="animation: vyFlowDash 2.6s linear infinite;"></path>
              <path d="M84 3l6 5-6 5" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
            </svg>
            <div style="font-size: 11px; color: var(--vy-muted); text-align: center; line-height: 1.4;">Availability, rate, deposit</div>
          </div>
          <div data-flownode="" style="position: relative; flex: 1; min-width: 0; padding: 26px 24px; border-radius: 22px; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 16px 42px rgba(150,90,50,.15); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
            <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: #8F2F09; margin-bottom: 10px;">Runs the day</div>
            <div style="font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); margin-bottom: 16px;">Your team</div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 9px;">
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted);"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); flex-shrink: 0; margin-top: 6px;"></span>Arrivals board already knows who is coming</li>
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted);"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); flex-shrink: 0; margin-top: 6px;"></span>Housekeeping board orders itself by who arrives first</li>
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted);"><span style="width: 5px; height: 5px; border-radius: 50%; background: var(--vy-accent); flex-shrink: 0; margin-top: 6px;"></span>A broken unit stops selling on every channel</li>
            </ul>
          </div>
          <div data-flowarrow="" style="flex-shrink: 0; width: 118px; display: flex; flex-direction: column; align-items: center; gap: 8px; padding-top: 52px;">
            <div style="font-size: 11px; font-weight: 600; color: #8F2F09; text-align: center; line-height: 1.35;">Revenue &amp; costs</div>
            <svg width="100" height="16" fill="none" aria-hidden="true" style="overflow: visible;">
              <path d="M2 8h86" stroke="rgba(194,65,12,.28)" stroke-width="2" stroke-linecap="round" stroke-dasharray="5 5"></path>
              <path d="M2 8h86" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-dasharray="14 86" style="animation: vyFlowDash 2.6s linear infinite;"></path>
              <path d="M84 3l6 5-6 5" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
            </svg>
            <div style="font-size: 11px; color: var(--vy-muted); text-align: center; line-height: 1.4;">Commission, repairs, refunds</div>
          </div>
          <div data-flownode="" style="position: relative; flex: 1; min-width: 0; padding: 26px 24px; border-radius: 22px; background: linear-gradient(160deg,var(--vy-dark),#251C17); border: 1px solid rgba(255,255,255,.1); box-shadow: 0 22px 52px rgba(60,35,20,.28); transition: transform .3s cubic-bezier(.22,.7,.3,1), box-shadow .3s ease;">
            <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: #F0894E; margin-bottom: 10px;">Sees the result</div>
            <div style="font-family: var(--vy-display); font-size: 21px; font-weight: 600; letter-spacing: -.02em; color: #fff; margin-bottom: 16px;">The owner</div>
            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 9px;">
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: rgba(255,247,240,.78);"><span style="width: 5px; height: 5px; border-radius: 50%; background: #F0894E; flex-shrink: 0; margin-top: 6px;"></span>Statement assembles itself from what happened</li>
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: rgba(255,247,240,.78);"><span style="width: 5px; height: 5px; border-radius: 50%; background: #F0894E; flex-shrink: 0; margin-top: 6px;"></span>Every deduction links to the thing that caused it</li>
              <li style="display: flex; gap: 9px; font-size: 13.5px; line-height: 1.55; color: rgba(255,247,240,.78);"><span style="width: 5px; height: 5px; border-radius: 50%; background: #F0894E; flex-shrink: 0; margin-top: 6px;"></span>Payout lands on a date they can see coming</li>
            </ul>
          </div>
      </div>

      <div data-reveal="" style="margin-top: 26px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; padding: 22px 26px; border-radius: 18px; background: rgba(255,252,248,.5); border: 1px solid rgba(255,255,255,.75);">
        <span style="font-size: 14px; line-height: 1.6; color: var(--vy-muted); max-width: 700px;">Nothing crosses a boundary by hand. The maintenance cost your cleaner photographed at eleven this morning is on the owner’s statement tonight, with the work order attached.</span>
        <x-marketing.nav-link key="product" style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; text-decoration: none; flex-shrink: 0;">See the full journey
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
        </x-marketing.nav-link>
      </div>
    </section>

    <!-- ═══ A DAY, HOUR BY HOUR ═══ -->
    <section data-sec="" id="day" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 84px; scroll-margin-top: 96px;">
      <div data-orb="" style="position: absolute; top: 20px; right: -80px; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .4; animation: vyDrift 27s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; max-width: 640px; margin-bottom: 34px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">A Tuesday in August</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Your day, hour by hour</h2>
        <p style="margin: 18px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">Scrub the rail. This is one property’s day and what Vyrobel is doing at each hour of it — mostly without anyone asking.</p>
      </div>

      <div data-reveal="" style="position: relative; padding: 34px 36px; border-radius: 26px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 18px 48px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65);">

        <div style="position: relative; margin-bottom: 30px;">
          <div style="position: absolute; left: 0; right: 0; top: 19px; height: 3px; border-radius: 999px; background: rgba(150,90,50,.14);"></div>
          <div data-hourfill="" style="position: absolute; left: 0; top: 19px; height: 3px; width: 50%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent)); transition: width .5s cubic-bezier(.22,.7,.3,1);"></div>
          <div data-hourrail="" role="tablist" aria-label="Hours of the day" style="position: relative; display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; -webkit-overflow-scrolling: touch;">
          <button data-hourbtn="0" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">07:00</button>
          <button data-hourbtn="1" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">09:00</button>
          <button data-hourbtn="2" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">11:00</button>
          <button data-hourbtn="3" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">13:00</button>
          <button data-hourbtn="4" role="tab" aria-selected="true" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">15:00</button>
          <button data-hourbtn="5" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">17:00</button>
          <button data-hourbtn="6" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">19:00</button>
          <button data-hourbtn="7" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">21:00</button>
          <button data-hourbtn="8" role="tab" aria-selected="false" style="flex-shrink: 0; padding: 9px 15px; border-radius: 999px; border: 1px solid rgba(255,255,255,.8); background: rgba(255,252,248,.55); font-family: var(--vy-display); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; font-variant-numeric: tabular-nums; transition: all .24s cubic-bezier(.22,.7,.3,1);">23:00</button>
          </div>
        </div>

        <div style="min-height: 210px;">
          <div data-hourpanel="0" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Housekeeping</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">07:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Housekeeping opens the board</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Fourteen rooms to turn. The board is already ordered by who arrives first, and the two with guests waiting are pinned to the top.</p>
          </div>
          <div data-hourpanel="1" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Front desk</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">09:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Departures clear, rooms go dirty</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Every checkout drops its room onto the board the second it is recorded at the desk. Nobody types a list.</p>
          </div>
          <div data-hourpanel="2" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Maintenance</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">11:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">A cracked shower screen becomes a ticket</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">A cleaner photographs it and marks the unit out of service from the room. It stops selling on every channel in the same second.</p>
          </div>
          <div data-hourpanel="3" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Rates</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">13:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Rates for the shoulder weeks go out</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">A weekend uplift is applied across September and pushed to Airbnb and Booking.com. The owner sees the proposal before it goes live.</p>
          </div>
          <div data-hourpanel="4" role="tabpanel" tabindex="0" style="">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Front desk</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">15:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Arrivals begin</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Eleven of today’s fourteen guests completed check-in from their sofa. Their door codes released at four this morning; the desk hands over keys it already knew were ready.</p>
          </div>
          <div data-hourpanel="5" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Messages</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">17:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">A guest asks about a late checkout</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">It lands in one thread with the booking beside it, whichever channel it came from. Accepting it adds the charge to the folio.</p>
          </div>
          <div data-hourpanel="6" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Reservations</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">19:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Tonight’s occupancy settles at 92%</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Two walk-ins took the last rooms at the rack rate, because the calendar knew they were free.</p>
          </div>
          <div data-hourpanel="7" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Storefront</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">21:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">Direct bookings for next month arrive</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Nobody is at the desk. The storefront takes them anyway, at full margin, from the same calendar.</p>
          </div>
          <div data-hourpanel="8" role="tabpanel" tabindex="0" style="display: none; ">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(217,102,46,.13); font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 18px;">Owners</div>
            <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 14px;">
              <span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; letter-spacing: -.03em; color: var(--vy-accent-text); font-variant-numeric: tabular-nums; line-height: 1;">23:00</span>
              <h3 style="margin: 0; font-family: var(--vy-display); font-size: 24px; font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink); line-height: 1.2; text-wrap: balance;">The owner statement updates itself</h3>
            </div>
            <p style="margin: 0; max-width: 640px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Today’s revenue, this morning’s maintenance cost and the commission on two OTA bookings are all on it. Nothing was re-keyed.</p>
          </div>
        </div>

        <div style="margin-top: 26px; padding-top: 20px; border-top: 1px solid rgba(150,90,50,.13); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
          <span style="font-size: 13px; color: var(--vy-muted);">Nine of these hours, and the operator touched four of them.</span>
          <x-marketing.nav-link key="features" style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; text-decoration: none;">See how each one works
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
          </x-marketing.nav-link>
        </div>
      </div>
    </section>

    <!-- ═══ PROPERTIES ═══ -->
    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-orb="" style="position: absolute; top: 40px; left: -70px; width: 340px; height: 340px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .5; animation: vyDrift 23s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 22px; margin-bottom: 34px;">
        <div style="max-width: 560px;">
          <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">The properties</div>
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Software is the quiet part. This is what your guests came for.</h2>
        </div>
        <x-marketing.nav-link key="case-studies" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none; padding-bottom: 6px;">Read the operators' stories <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
      </div>

      <div data-gallery="" style="position: relative; display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 16px;">
          <div style="position: relative; height: 340px; border-radius: 20px; overflow: hidden; box-shadow: 0 16px 44px rgba(150,90,50,.18);">
            <x-marketing.image-slot brief="Drop your hero property photo" radius="20" shape="rounded" />
            <figcaption style="position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; padding: 26px 18px 14px; background: linear-gradient(to top, rgba(30,22,17,.78), rgba(30,22,17,0)); pointer-events: none;">
              <span style="display: block; font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: #fff; letter-spacing: -.01em;">Villa Azure</span>
              <span style="display: block; font-size: 11.5px; color: rgba(255,247,240,.72); margin-top: 2px;">Paros, Cyclades</span>
            </figcaption>
          </div>
        <div style="display: grid; grid-template-rows: 1fr 1fr; gap: 16px;">
          <div style="position: relative; height: 162px; border-radius: 20px; overflow: hidden; box-shadow: 0 16px 44px rgba(150,90,50,.18);">
            <x-marketing.image-slot brief="A room or interior" radius="20" shape="rounded" />
            <figcaption style="position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; padding: 26px 18px 14px; background: linear-gradient(to top, rgba(30,22,17,.78), rgba(30,22,17,0)); pointer-events: none;">
              <span style="display: block; font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: #fff; letter-spacing: -.01em;">Apt 102</span>
              <span style="display: block; font-size: 11.5px; color: rgba(255,247,240,.72); margin-top: 2px;">Rotterdam</span>
            </figcaption>
          </div>
          <div style="position: relative; height: 162px; border-radius: 20px; overflow: hidden; box-shadow: 0 16px 44px rgba(150,90,50,.18);">
            <x-marketing.image-slot brief="A pool, terrace or view" radius="20" shape="rounded" />
            <figcaption style="position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; padding: 26px 18px 14px; background: linear-gradient(to top, rgba(30,22,17,.78), rgba(30,22,17,0)); pointer-events: none;">
              <span style="display: block; font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: #fff; letter-spacing: -.01em;">Villa Coralis</span>
              <span style="display: block; font-size: 11.5px; color: rgba(255,247,240,.72); margin-top: 2px;">Algarve</span>
            </figcaption>
          </div>
        </div>
        <div style="display: grid; grid-template-rows: 1fr 1fr; gap: 16px;">
          <div style="position: relative; height: 162px; border-radius: 20px; overflow: hidden; box-shadow: 0 16px 44px rgba(150,90,50,.18);">
            <x-marketing.image-slot brief="Breakfast, detail or staff" radius="20" shape="rounded" />
            <figcaption style="position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; padding: 26px 18px 14px; background: linear-gradient(to top, rgba(30,22,17,.78), rgba(30,22,17,0)); pointer-events: none;">
              <span style="display: block; font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: #fff; letter-spacing: -.01em;">Changeover, 11:40</span>
              <span style="display: block; font-size: 11.5px; color: rgba(255,247,240,.72); margin-top: 2px;">Villa Dunes</span>
            </figcaption>
          </div>
          <div style="position: relative; height: 162px; border-radius: 20px; overflow: hidden; box-shadow: 0 16px 44px rgba(150,90,50,.18);">
            <x-marketing.image-slot brief="The neighbourhood" radius="20" shape="rounded" />
            <figcaption style="position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; padding: 26px 18px 14px; background: linear-gradient(to top, rgba(30,22,17,.78), rgba(30,22,17,0)); pointer-events: none;">
              <span style="display: block; font-family: var(--vy-display); font-size: 13.5px; font-weight: 600; color: #fff; letter-spacing: -.01em;">Alfama</span>
              <span style="display: block; font-size: 11.5px; color: rgba(255,247,240,.72); margin-top: 2px;">Lisbon</span>
            </figcaption>
          </div>
        </div>
      </div>

      <div data-reveal="" style="margin-top: 16px; font-size: 12.5px; color: var(--vy-muted);">Drag your own photography onto any frame — it stays put across reloads.</div>
    </section>

    <!-- ═══ FEATURE HIGHLIGHTS ═══ -->
    <section data-sec="" id="highlights" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 40px;">

      <div data-morphwrap="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: stretch;">

        <div data-morphtrack="" style="position: relative;">
        <div data-morphcol="" style="position: sticky; top: 104px; height: 620px;">
          <div data-morphcard="1" style="position: absolute; inset: 0; opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .55s cubic-bezier(.22,.7,.3,1), transform .6s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
            <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 26px 26px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 26px; box-shadow: 0 26px 70px rgba(150,90,50,.22), inset 0 1px 0 rgba(255,255,255,.7);">
              <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 16px;">Your storefront · vyrobel-demo.com</div>              <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 14px; margin-bottom: 4px;">
                <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Villa Azure</span>
                <span style="font-family: var(--vy-display); font-size: 22px; font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums;">€1,020</span>
              </div>
              <div style="font-size: 12.5px; color: var(--vy-faint); margin-bottom: 20px;">Sleeps 6 · Sea view · Paros &nbsp;|&nbsp; 4 nights, 12–16 Aug</div>
              <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-bottom: 18px;">                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">6</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">7</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">8</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">9</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">10</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">11</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">12</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">13</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">14</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">15</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">16</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">17</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-weight: 600;">18</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">19</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">20</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">21</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">22</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">23</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">24</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">25</span>
                <span style="height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-variant-numeric: tabular-nums; background: rgba(255,255,255,.6); color: var(--vy-faint);">26</span>
              </div>
              <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(150,90,50,.11);">
                <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted); padding: 4px 0;"><span>4 nights × €255</span><span style="font-variant-numeric: tabular-nums;">€1,020.00</span></div>
                <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted); padding: 4px 0;"><span>OTA commission</span><span style="font-variant-numeric: tabular-nums; color: var(--vy-accent-text); font-weight: 600;">€0.00</span></div>
                <div style="margin-top: 14px; padding: 12px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); text-align: center; font-size: 13.5px; font-weight: 600; color: #fff;">Confirm &amp; pay</div>
              </div>
            </div>
          </div>
          <div data-morphcard="2" style="position: absolute; inset: 0; opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .55s cubic-bezier(.22,.7,.3,1), transform .6s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
            <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 26px 26px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 26px; box-shadow: 0 26px 70px rgba(150,90,50,.22), inset 0 1px 0 rgba(255,255,255,.7);">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <span style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">Arrivals · today</span>
                <span style="flex-shrink: 0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: linear-gradient(140deg, rgba(232,112,58,.22), rgba(194,65,12,.16)); color: var(--vy-accent-text);">4 waiting</span>
              </div>
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 18px;">
                <div style="padding: 12px; border-radius: 12px; background: rgba(255,255,255,.6);"><div style="font-size: 9.5px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint);">Arrivals</div><div style="font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">14</div></div>
                <div style="padding: 12px; border-radius: 12px; background: rgba(255,255,255,.6);"><div style="font-size: 9.5px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-faint);">Departures</div><div style="font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums;">9</div></div>
                <div style="padding: 12px; border-radius: 12px; background: linear-gradient(140deg, rgba(232,112,58,.18), rgba(194,65,12,.12));"><div style="font-size: 9.5px; letter-spacing: .07em; text-transform: uppercase; color: var(--vy-accent-text);">In house</div><div style="font-family: var(--vy-display); font-size: 20px; font-weight: 600; color: var(--vy-accent-text); font-variant-numeric: tabular-nums;">84</div></div>
              </div>              <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                <span style="min-width: 0;"><span style="display: block; font-size: 13.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Sofia Lindqvist</span><span style="display: block; font-size: 11.5px; color: var(--vy-faint); margin-top: 2px;">Villa Azure · 14:00</span></span>
                <span style="flex-shrink: 0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: rgba(217,102,46,.14); color: var(--vy-accent-text);">Checked in</span>
              </div>              <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                <span style="min-width: 0;"><span style="display: block; font-size: 13.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">James Okonkwo</span><span style="display: block; font-size: 11.5px; color: var(--vy-faint); margin-top: 2px;">Villa Dunes · 15:30</span></span>
                <span style="flex-shrink: 0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: linear-gradient(140deg, rgba(232,112,58,.22), rgba(194,65,12,.16)); color: var(--vy-accent-text);">VIP</span>
              </div>              <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid rgba(150,90,50,.09);">
                <span style="min-width: 0;"><span style="display: block; font-size: 13.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nadia Farouk</span><span style="display: block; font-size: 11.5px; color: var(--vy-faint); margin-top: 2px;">Apt 102 · 6 nights</span></span>
                <span style="flex-shrink: 0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: rgba(120,70,40,.09); color: var(--vy-faint);">Due 17:15</span>
              </div>              <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0;">
                <span style="min-width: 0;"><span style="display: block; font-size: 13.5px; font-weight: 500; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Tomas Berg</span><span style="display: block; font-size: 11.5px; color: var(--vy-faint); margin-top: 2px;">Apt 210 · room not ready</span></span>
                <span style="flex-shrink: 0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: linear-gradient(140deg, rgba(232,112,58,.22), rgba(194,65,12,.16)); color: var(--vy-accent-text);">Waiting</span>
              </div>              <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(150,90,50,.11); font-size: 12px; color: var(--vy-muted);">Balance collected at the desk posts to the folio as you take it.</div>
            </div>
          </div>
          <div data-morphcard="3" style="position: absolute; inset: 0; opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .55s cubic-bezier(.22,.7,.3,1), transform .6s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
            <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 26px 26px; display: flex; flex-direction: column; background: rgba(255,252,248,.66); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); border-radius: 26px; box-shadow: 0 26px 70px rgba(150,90,50,.22), inset 0 1px 0 rgba(255,255,255,.7);">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <span style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">Housekeeping</span>
                <span style="flex-shrink: 0; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: rgba(120,70,40,.09); color: var(--vy-faint);">17 rooms</span>
              </div>
              <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                <div>
                  <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: var(--vy-accent);"></span><span style="font-size: 10.5px; font-weight: 600; color: var(--vy-ink);">Dirty</span><span style="font-size: 10.5px; color: var(--vy-faint);">5</span></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">210</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">guest waiting</div></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">114</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">2 nights</div></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">Dunes</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">deep clean</div></div>
                </div>
                <div>
                  <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: var(--vy-accent-3);"></span><span style="font-size: 10.5px; font-weight: 600; color: var(--vy-ink);">In progress</span><span style="font-size: 10.5px; color: var(--vy-faint);">4</span></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">102</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">Ana · 18m</div></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">118</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">Rui · 6m</div></div>
                </div>
                <div>
                  <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #B4795A;"></span><span style="font-size: 10.5px; font-weight: 600; color: var(--vy-ink);">Clean</span><span style="font-size: 10.5px; color: var(--vy-faint);">5</span></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">Azure</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">awaiting check</div></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">206</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">—</div></div>
                </div>
                <div>
                  <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: var(--vy-muted);"></span><span style="font-size: 10.5px; font-weight: 600; color: var(--vy-ink);">Inspected</span><span style="font-size: 10.5px; color: var(--vy-faint);">3</span></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">Coralis</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">guest-ready</div></div>
                  <div style="padding: 9px 10px; border-radius: 10px; background: rgba(255,255,255,.62); margin-bottom: 6px;"><div style="font-size: 11.5px; font-weight: 500; color: var(--vy-ink);">301</div><div style="font-size: 10px; color: var(--vy-faint); margin-top: 1px;">guest-ready</div></div>
                </div>
              </div>
              <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(150,90,50,.11); display: flex; align-items: center; gap: 10px;">
                <span style="width: 26px; height: 26px; border-radius: 8px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg width="14" height="14" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round"><path d="M12 3v18M3 12h18"></path></svg></span>
                <span style="font-size: 12px; line-height: 1.5; color: var(--vy-muted);"><strong style="font-weight: 600; color: var(--vy-ink);">Apt 118 — cracked shower screen.</strong> Out of service, off sale on every channel.</span>
              </div>
            </div>
          </div>
          <div data-morphcard="4" style="position: absolute; inset: 0; opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .55s cubic-bezier(.22,.7,.3,1), transform .6s cubic-bezier(.22,.7,.3,1); pointer-events: none;">
            <div style="height: 100%; box-sizing: border-box; overflow: hidden; padding: 26px 26px; display: flex; flex-direction: column; position: relative; background: linear-gradient(160deg, var(--vy-dark), #251C17); border: 1px solid rgba(255,255,255,.1); border-radius: 26px; box-shadow: 0 30px 76px rgba(60,35,20,.34);">
              <div style="position: absolute; top: -70px; right: -50px; width: 240px; height: 240px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(62px); opacity: .34;"></div>
              <div style="position: relative; display: flex; flex-direction: column; height: 100%;">
                <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #B9ABA0; margin-bottom: 14px;">Owner statement · July 2026</div>
                <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 22px;">
                  <span style="font-family: var(--vy-display); font-size: 34px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;">€8,412</span>
                  <span style="font-size: 12.5px; color: #B9ABA0;">net to you</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.09);">
                  <span><span style="display: block; font-size: 12.5px; color: rgba(255,247,240,.86);">Gross booking revenue</span><span style="display: block; font-size: 10.5px; color: #B9ABA0; margin-top: 1px;">18 stays</span></span>
                  <span style="font-size: 12.5px; font-weight: 500; color: #fff; font-variant-numeric: tabular-nums; flex-shrink: 0;">€11,240.00</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.09);">
                  <span><span style="display: block; font-size: 12.5px; color: rgba(255,247,240,.86);">Channel commission</span><span style="display: block; font-size: 10.5px; color: #B9ABA0; margin-top: 1px;">Booking.com, Airbnb</span></span>
                  <span style="font-size: 12.5px; font-weight: 500; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums; flex-shrink: 0;">−€1,048.60</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.09);">
                  <span><span style="display: block; font-size: 12.5px; color: rgba(255,247,240,.86);">Management fee · 15%</span><span style="display: block; font-size: 10.5px; color: #B9ABA0; margin-top: 1px;">on net</span></span>
                  <span style="font-size: 12.5px; font-weight: 500; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums; flex-shrink: 0;">−€1,528.71</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.09);">
                  <span><span style="display: block; font-size: 12.5px; color: rgba(255,247,240,.86);">Maintenance · AC, 14 Jul</span><span style="display: block; font-size: 10.5px; color: #B9ABA0; margin-top: 1px;">work order attached</span></span>
                  <span style="font-size: 12.5px; font-weight: 500; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums; flex-shrink: 0;">−€120.00</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0;">
                  <span><span style="display: block; font-size: 12.5px; color: rgba(255,247,240,.86);">Guest refund · RF-114</span><span style="display: block; font-size: 10.5px; color: #B9ABA0; margin-top: 1px;">boiler, 2nd night</span></span>
                  <span style="font-size: 12.5px; font-weight: 500; color: rgba(255,247,240,.7); font-variant-numeric: tabular-nums; flex-shrink: 0;">−€130.69</span>
                </div>
                <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.11); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                  <span style="font-size: 11.5px; color: #B9ABA0;">Paid 5 Aug · IBAN …4417</span>
                  <span style="padding: 5px 11px; border-radius: 999px; background: rgba(232,112,58,.22); font-size: 11px; font-weight: 600; color: #F0894E;">Built itself</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>

        <div data-morphtext="">
        <div data-morphstep="1" style="display: flex; flex-direction: column; justify-content: center; min-height: 64vh; padding: 32px 0;">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 9px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: #fff;">1</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Direct bookings</span>
          </div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">The commission-free booking engine that comes with the PMS</h2>
          <p style="margin: 0; max-width: 480px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Publish a branded storefront in your own voice, take real availability and real payments, and let the reservation land in the same system your front desk already works in. No separate direct-booking vendor, no sync delay, no commission.</p>
        </div>

        <div data-morphstep="2" style="display: flex; flex-direction: column; justify-content: center; min-height: 64vh; padding: 32px 0;">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 9px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: #fff;">2</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Front desk &amp; reservations</span>
          </div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">The whole day, on one board</h2>
          <p style="margin: 0; max-width: 480px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Arrivals, departures and in-house on a single screen, with a booking engine that cannot double-book, a guided wizard for new reservations, groups and walk-ins, and payment collected at the desk that posts straight to the folio.</p>
        </div>

        <div data-morphstep="3" style="display: flex; flex-direction: column; justify-content: center; min-height: 64vh; padding: 32px 0;">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 9px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: #fff;">3</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Housekeeping &amp; maintenance</span>
          </div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Operations that keep up with a turnover day</h2>
          <p style="margin: 0; max-width: 480px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">A live board your team works from their phones, workload balanced automatically, and a maintenance ticket that takes a unit off sale everywhere the moment it is raised — with its cost landing on the owner statement when it resolves.</p>
        </div>

        <div data-morphstep="4" style="display: flex; flex-direction: column; justify-content: center; min-height: 64vh; padding: 32px 0;">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 9px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); font-family: var(--vy-display); font-size: 12px; font-weight: 600; color: #fff;">4</span>
            <span style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text);">Owner transparency</span>
          </div>
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Stop building owner reports by hand</h2>
          <p style="margin: 0; max-width: 480px; font-size: 16.5px; line-height: 1.68; color: var(--vy-muted);">Statements that assemble themselves from bookings and expenses already recorded, payouts on a date the owner can see coming, and a portal they log into instead of emailing you. Owners see performance, never a guest’s private data.</p>
        </div>
        </div>
      </div>
    </section>

    <!-- ═══ HOW IT WORKS ═══ -->
    <section data-sec="" id="how" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 32px 28px 84px;">
      <div data-orb="" style="position: absolute; bottom: 40px; left: -60px; width: 380px; height: 380px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .55; animation: vyDrift 21s ease-in-out infinite; pointer-events: none;"></div>

      <div data-reveal="" style="position: relative; text-align: center; max-width: 620px; margin: 0 auto 52px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">How it works</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Live on Vyrobel in four steps</h2>
        <p style="margin: 18px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">No six-month implementation. Most operators are taking direct bookings the same week they start.</p>
      </div>

      <div data-steps="" style="position: relative; display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px;">
        <div data-reveal="" style="position: relative; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 20px; padding: 26px 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 15px; font-weight: 600; box-shadow: 0 8px 20px rgba(194,65,12,.28); margin-bottom: 18px;">1</div>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Add your properties</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Units, room types, rate plans and house rules. Import your existing bookings so nothing gets lost.</p>
        </div>
        <div data-reveal="" data-reveal-delay="80" style="position: relative; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 20px; padding: 26px 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 15px; font-weight: 600; box-shadow: 0 8px 20px rgba(194,65,12,.28); margin-bottom: 18px;">2</div>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Open your direct channel</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Publish the branded storefront, connect payments, and keep your OTA listings in sync alongside it.</p>
        </div>
        <div data-reveal="" data-reveal-delay="160" style="position: relative; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 20px; padding: 26px 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 15px; font-weight: 600; box-shadow: 0 8px 20px rgba(194,65,12,.28); margin-bottom: 18px;">3</div>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Run the day</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Front desk, housekeeping and maintenance on one live board, with roles that fit how your team works.</p>
        </div>
        <div data-reveal="" data-reveal-delay="240" style="position: relative; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 20px; padding: 26px 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-family: var(--vy-display); font-size: 15px; font-weight: 600; box-shadow: 0 8px 20px rgba(194,65,12,.28); margin-bottom: 18px;">4</div>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Delight guests &amp; owners</h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Online check-in and arrival info go out on time; statements and payouts reach owners without a spreadsheet.</p>
        </div>
      </div>

      <div data-reveal="" style="text-align: center; margin-top: 40px;">
        <x-marketing.nav-link key="how-it-works" style="display: inline-flex; align-items: center; gap: 8px; padding: 13px 24px; border-radius: 13px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14.5px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .2s ease, box-shadow .2s ease;">See the full onboarding walkthrough <svg width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
      </div>
    </section>

    <!-- ═══ MODULES (link band) ═══ -->
    <section data-sec="" id="modules" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <x-marketing.nav-link key="modules" data-reveal="" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 28px; padding: 36px 38px; border-radius: 24px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 16px 44px rgba(150,90,50,.15); text-decoration: none; color: inherit; transition: transform .26s cubic-bezier(.22,.7,.3,1), box-shadow .26s ease;">
        <div style="max-width: 640px;">
          <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">Modules</div>
          <h2 style="margin: 0 0 12px; font-family: var(--vy-display); font-weight: 600; font-size: 34px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Sixteen modules, and every one is in every plan</h2>
          <p style="margin: 0; font-size: 15.5px; line-height: 1.66; color: var(--vy-muted);">Reservations, front desk, calendar, housekeeping, maintenance, rates, reports, the owner portal, the guest storefront and the rest. Plans differ by portfolio size and support, not by which parts you are allowed to use.</p>
        </div>
        <span style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,255,255,.66); border: 1px solid rgba(255,255,255,.85); font-size: 14.5px; font-weight: 500; color: var(--vy-ink); flex-shrink: 0;">Browse the catalogue
          <svg width="16" height="16" fill="none" stroke="var(--vy-accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
        </span>
      </x-marketing.nav-link>
    </section>

    <!-- ═══ TESTIMONIALS — full-bleed dark band ═══ -->
    <x-marketing.testimonials :quotes="$testimonialQuotes" />

    <!-- ═══ PRICING TEASER ═══ -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="text-align: center; max-width: 600px; margin: 0 auto 44px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">Pricing</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 42px; line-height: 1.12; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">{{ \App\Support\Marketing\MarketingPricing::headline() }}</h2>
        <p style="margin: 18px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">Every plan includes the direct booking engine, the owner portal and the guest app. Illustrative pricing — talk to us for your portfolio.</p>
      </div>

      <div data-plans="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: stretch;">
        <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; padding: 30px 28px; box-shadow: 0 14px 40px rgba(150,90,50,.14); display: flex; flex-direction: column;">
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">{{ \App\Support\Marketing\MarketingPricing::tiers()[0]['name'] ?? '' }}</div>
          <div style="font-size: 13.5px; color: var(--vy-muted); margin-top: 5px;">One property account</div>
          <div style="margin: 22px 0 6px; display: flex; align-items: baseline; gap: 5px;"><span data-price data-plan="home-tier-0" style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.03em;">{{ \App\Support\Marketing\MarketingPricing::tiers()[0]['formatted'] ?? '' }}</span><span style="font-size: 13.5px; color: var(--vy-muted);">{{ \App\Support\Marketing\MarketingPricing::suffix() }}</span></div>
          <p style="margin: 12px 0 22px; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Direct bookings, front desk, calendar and housekeeping — one price, any number of units.</p>
          <x-marketing.nav-link key="pricing" style="margin-top: auto; text-align: center; padding: 12px; border-radius: 12px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14px; font-weight: 500; text-decoration: none; transition: transform .2s ease;">See what's included</x-marketing.nav-link>
        </div>

        <div data-reveal="" data-reveal-delay="90" style="position: relative; background: rgba(255,252,248,.72); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1.5px solid rgba(217,102,46,.42); border-radius: 22px; padding: 30px 28px; box-shadow: 0 22px 56px rgba(194,65,12,.22); display: flex; flex-direction: column;">
          <span style="position: absolute; top: -12px; left: 28px; padding: 5px 12px; border-radius: 999px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 11px; font-weight: 600; letter-spacing: .04em; box-shadow: 0 8px 20px rgba(194,65,12,.34);">Most popular</span>
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">{{ \App\Support\Marketing\MarketingPricing::tiers()[1]['name'] ?? '' }}</div>
          <div style="font-size: 13.5px; color: var(--vy-muted); margin-top: 5px;">Adds the channel manager</div>
          <div style="margin: 22px 0 6px; display: flex; align-items: baseline; gap: 5px;"><span data-price data-plan="home-tier-1" style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: var(--vy-accent-text); letter-spacing: -.03em;">{{ \App\Support\Marketing\MarketingPricing::tiers()[1]['formatted'] ?? '' }}</span><span style="font-size: 13.5px; color: var(--vy-muted);">{{ \App\Support\Marketing\MarketingPricing::suffix() }}</span></div>
          <p style="margin: 12px 0 22px; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Everything in the lower plan, plus the channel manager and unlimited team members.</p>
          <x-marketing.nav-link key="pricing" style="margin-top: auto; text-align: center; padding: 12px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.3); transition: transform .2s ease, box-shadow .2s ease;">Compare plans</x-marketing.nav-link>
        </div>

        <div data-reveal="" data-reveal-delay="180" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 22px; padding: 30px 28px; box-shadow: 0 14px 40px rgba(150,90,50,.14); display: flex; flex-direction: column;">
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Larger portfolios</div>
          <div style="font-size: 13.5px; color: var(--vy-muted); margin-top: 5px;">Multi-property groups</div>
          <div style="margin: 22px 0 6px; display: flex; align-items: baseline; gap: 5px;"><span style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.03em;">Talk</span><span style="font-size: 13.5px; color: var(--vy-muted);">to us</span></div>
          <p style="margin: 12px 0 22px; font-size: 14px; line-height: 1.62; color: var(--vy-muted);">Running several properties, or want the numbers checked against your portfolio before you commit? Talk to us.</p>
          <x-marketing.nav-link key="contact" style="margin-top: auto; text-align: center; padding: 12px; border-radius: 12px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14px; font-weight: 500; text-decoration: none; transition: transform .2s ease;">Talk to sales</x-marketing.nav-link>
        </div>
      </div>
    </section>

    <!-- ═══ CLOSING CTA ═══ -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 72px 56px; text-align: center;">
        <div data-ctaphoto="" style="position: absolute; inset: 0; z-index: 0;">
          <x-marketing.image-slot brief="Drop a wide property photo — it sits behind this section, darkened" shape="rect" />
        </div>
        <div style="position: absolute; inset: 0; z-index: 1; pointer-events: none; background: linear-gradient(160deg, rgba(58,46,39,.82), rgba(37,28,23,.9));"></div>
        <div data-orb="" style="position: absolute; top: -120px; left: 12%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .42; animation: vyDrift 20s ease-in-out infinite; z-index: 1;"></div>
        <div data-orb="" style="position: absolute; bottom: -140px; right: 8%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent-2); filter: blur(70px); opacity: .34; animation: vyDrift 25s ease-in-out infinite reverse; z-index: 1;"></div>
        <div style="position: relative; z-index: 2;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 44px; line-height: 1.1; letter-spacing: -.035em; color: #fff; text-wrap: balance;">Ready to take back your bookings?</h2>
          <p style="margin: 20px auto 0; max-width: 560px; font-size: 17px; line-height: 1.62; color: rgba(255,247,240,.74);">Start free with one property, or let us walk you through the whole platform on a 30-minute call. Either way, you'll see your first direct booking this week.</p>
          <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 34px;">
            <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Start free <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
            <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: rgba(255,247,240,.12); border: 1px solid rgba(255,247,240,.24); color: #fff; font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, background .22s ease;">Book a demo</x-marketing.nav-link>
          </div>
          <div style="margin-top: 22px; font-size: 13px; color: rgba(255,247,240,.55);">No card required · Cancel any time · Your data stays yours</div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
