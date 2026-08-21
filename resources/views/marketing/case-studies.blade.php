{{-- SCENARIOS INDEX — front-website Gate 3b, ruling (A).

     ⚠️ THIS IS A REWRITE, NOT A BADGE. The design drew this as a CUSTOMER STORY about a named business —
     one of D1's three invented, unconsented customers. A case study is by definition a claim about a real
     customer, so labelling fabricated specifics would only make them LOOK made-honest. The page is
     therefore rebuilt as an explicitly ILLUSTRATIVE SCENARIO under six mandatory conditions: no invented
     business identity · no attributed quotes · no quantified ACHIEVED outcomes · no offer that presupposes
     a real customer · a PROMINENT label in the page's own voice · not presented as a "case study".

     ⚠️ Guarded by MarketingScenarioTest, because the content ledger governs DATA and this is MARKUP —
     the structural gap Gate 3 found and D13 proved real. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Example scenarios — how operators use Vyrobel"
    description="Three illustrative scenarios, not customers: a multi-island villa manager, a small short-stay apartment operator, and an independent hotel. What changes, and what it takes."
    canonical="/case-studies"
    :index="false">
<div data-screen-label="Marketing — Example scenarios" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 50px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -100px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 700px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Illustrative scenarios</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 50px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Three example scenarios, three reasons for switching</h1>
        <p style="margin: 14px 0 0; padding: 11px 15px; border-radius: 13px; background: rgba(217,102,46,.12); border: 1px solid rgba(217,102,46,.26); font-size: 13.5px; line-height: 1.55; color: var(--vy-ink); max-width: 640px;" data-scenario-label>⚠️ <strong>An illustrative scenario, not a customer.</strong> Vyrobel is new and has no published customer stories yet. This describes how the product is used by an operation of this shape, composed from onboarding conversations. Nothing here is a claim about a real business, and no figure is a measured result.</p>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Including the parts that were awkward.</p>
      </div>
    </section>

    <!-- SCENARIO 1 -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 70px;">
      <article data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 26px; box-shadow: 0 18px 50px rgba(150,90,50,.18); overflow: hidden;">
        <div data-story="" style="display: grid; grid-template-columns: 1.5fr 1fr;">
          <div style="padding: 44px 42px;">
            <div style="display: flex; align-items: center; gap: 13px; margin-bottom: 22px;">
              <span style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600;">01</span>
              <div>
                <div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">A villa management company</div>
                <div style="font-size: 12.5px; color: var(--vy-muted);">42 villas · 19 owners · across several islands</div>
              </div>
            </div>
            <h2 style="margin: 0 0 18px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Owner statements, moved into a portal</h2>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">Before</div>
            <p style="margin: 0 0 20px; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">A channel manager for distribution, a spreadsheet per owner for money, and a WhatsApp group for cleaning. Statements took two evenings a month, and every owner had a slightly different format because they'd each asked for a change at some point.</p>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">What changed</div>
            <p style="margin: 0 0 20px; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">Statements now generate themselves and owners read them in their own portal, alongside live occupancy and the payout date. Owner stays get blocked by the owners rather than relayed by text. Repairs over €200 go for approval before they hit a statement, which ended the arguments about deductions.</p>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">The awkward part</div>
            <p style="margin: 0; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">Two older owners didn't want a login and still ask for the PDF by email. The portal exports it, so that's fine — but the team had assumed everyone would move across, and they didn't.</p>
          </div>
          <div data-side="" style="background: linear-gradient(160deg, var(--vy-dark), #251C17); padding: 44px 38px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -80px; right: -60px; width: 240px; height: 240px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(60px); opacity: .38;"></div>
            <div style="position: relative;">
              <div style="margin-top: 30px; padding-top: 22px; border-top: 1px solid rgba(255,247,240,.16);">
                <x-marketing.nav-link key="story-cyclades" style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--vy-accent-3); text-decoration: none;">Vyrobel for villas <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
              </div>
            </div>
          </div>
        </div>
      </article>
    </section>

    <!-- SCENARIO 2 -->
    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 70px;">
      <div data-orb="" style="position: absolute; top: 40px; right: -90px; width: 340px; height: 340px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .4; animation: vyDrift 26s ease-in-out infinite; pointer-events: none;"></div>
      <article data-reveal="" style="position: relative; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 26px; box-shadow: 0 18px 50px rgba(150,90,50,.18); overflow: hidden;">
        <div data-story="" style="display: grid; grid-template-columns: 1.5fr 1fr;">
          <div style="padding: 44px 42px;">
            <div style="display: flex; align-items: center; gap: 13px; margin-bottom: 22px;">
              <span style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600;">02</span>
              <div>
                <div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">A short-stay apartment operator</div>
                <div style="font-size: 12.5px; color: var(--vy-muted);">18 apartments · 4 buildings · one city</div>
              </div>
            </div>
            <h2 style="margin: 0 0 18px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Moving nights from marketplaces to direct</h2>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">Before</div>
            <p style="margin: 0 0 20px; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">Almost everything came through two marketplaces. A family business of two people was paying roughly the cost of a third salary in commission, and door codes were sent by hand from whoever's phone was nearest.</p>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">What changed</div>
            <p style="margin: 0 0 20px; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">They put the direct storefront on the domain they already owned and started pointing repeat guests at it in the checkout message. Online check-in and timed arrival info removed most of the day's messaging. Cleaning moved onto the board, ordered by who arrives first.</p>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">The awkward part</div>
            <p style="margin: 0; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">Direct share barely moved for the first two months. It only shifted once they made the post-checkout message a habit — the tool made it possible, but the behaviour change was theirs.</p>
          </div>
          <div data-side="" style="background: linear-gradient(160deg, var(--vy-dark), #251C17); padding: 44px 38px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -80px; right: -60px; width: 240px; height: 240px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(60px); opacity: .38;"></div>
            <div style="position: relative;">
              <div style="margin-top: 30px; padding-top: 22px; border-top: 1px solid rgba(255,247,240,.16);">
                <x-marketing.nav-link key="story-rotterdam" style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--vy-accent-3); text-decoration: none;">Vyrobel for apartments <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
              </div>
            </div>
          </div>
        </div>
      </article>
    </section>

    <!-- SCENARIO 3 -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
      <article data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 26px; box-shadow: 0 18px 50px rgba(150,90,50,.18); overflow: hidden;">
        <div data-story="" style="display: grid; grid-template-columns: 1.5fr 1fr;">
          <div style="padding: 44px 42px;">
            <div style="display: flex; align-items: center; gap: 13px; margin-bottom: 22px;">
              <span style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-peach),var(--vy-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--vy-display); font-size: 16px; font-weight: 600;">03</span>
              <div>
                <div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">An independent hotel</div>
                <div style="font-size: 12.5px; color: var(--vy-muted);">96 keys · one property</div>
              </div>
            </div>
            <h2 style="margin: 0 0 18px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Housekeeping and maintenance on one board</h2>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">Before</div>
            <p style="margin: 0 0 20px; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">A legacy PMS the front desk tolerated and nobody else could open. Housekeeping worked from a printed sheet, maintenance from a notebook, and a room out of order stayed sellable until someone remembered to block it — which occasionally it wasn't.</p>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">What changed</div>
            <p style="margin: 0 0 20px; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">The floor moved onto the board on phones, and the desk stopped phoning to ask what was ready. Work orders now take a room off sale the moment they're opened. Rate plans and reporting moved in at the same time, so the monthly RevPAR pack stopped being a manual export.</p>
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">The awkward part</div>
            <p style="margin: 0; font-size: 15.5px; line-height: 1.7; color: var(--vy-muted);">Migrating years of historical reservations wasn't clean. They imported the forward book and kept the old system read-only for a year rather than trying to bring everything across.</p>
          </div>
          <div data-side="" style="background: linear-gradient(160deg, var(--vy-dark), #251C17); padding: 44px 38px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -80px; right: -60px; width: 240px; height: 240px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(60px); opacity: .38;"></div>
            <div style="position: relative;">
              <div style="margin-top: 30px; padding-top: 22px; border-top: 1px solid rgba(255,247,240,.16);">
                <x-marketing.nav-link key="story-lisbon" style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--vy-accent-3); text-decoration: none;">Vyrobel for hotels <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
              </div>
            </div>
          </div>
        </div>
      </article>
    </section>
  </main>

</div>
</x-marketing.shell>
