{{-- SCENARIO · SERVICED APARTMENTS — front-website Gate 3b, ruling (A).

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
    title="How it works for 18 serviced apartments | Vyrobel example scenario"
    description="An illustrative scenario: a two-person business paying more in OTA commission than it paid itself, and what changes when the same guests arrive through its own booking page."
    canonical="/case-studies/rotterdam"
    :index="false">
<div data-screen-label="Marketing — Story: Rotterdam" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <div data-printhead="" style="display: none; border-bottom: 1pt solid #B8ADA5; padding-bottom: 7pt; margin-bottom: 12pt;">
      <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 12pt;">
        <span style="font-family: var(--vy-display); font-size: 14pt; font-weight: 600; color: var(--vy-accent);">Vyrobel</span>
        <span style="font-size: 8pt; color: var(--vy-faint);">Example scenario · 18 serviced apartments</span>
      </div>
      <div style="font-size: 7.5pt; color: var(--vy-muted); margin-top: 3pt;" data-printsub="">Illustrative scenario, composed from onboarding conversations · vyrobel.com/case-studies</div>
    </div>
    <article>

      <section data-sec="" style="position: relative; max-width: 1040px; margin: 0 auto; padding: 60px 28px 32px;">
        <div data-orb="" style="position: absolute; top: -40px; left: -160px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(74px); opacity: .34; animation: vyDrift 26s ease-in-out infinite; pointer-events: none;"></div>
        <div style="position: relative; max-width: 780px;">
          <nav data-crumbs="" aria-label="Breadcrumb" style="display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-bottom: 20px; font-size: 13px; color: var(--vy-muted);">
            <x-marketing.nav-link key="home" style="text-decoration: none; font-weight: 500;">Home</x-marketing.nav-link>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:.42;flex-shrink:0;"><path d="M9 6l6 6-6 6"></path></svg>
            <x-marketing.nav-link key="case-studies" style="text-decoration: none; font-weight: 500;">Example scenarios</x-marketing.nav-link>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:.42;flex-shrink:0;"><path d="M9 6l6 6-6 6"></path></svg>
            <span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Serviced apartments</span>
          </nav>
          <button data-printbtn="" data-noprint="" style="display: inline-flex; align-items: center; gap: 8px; margin-left: 14px; padding: 8px 15px; border-radius: 11px; border: 1px solid rgba(255,255,255,.82); background: rgba(255,252,248,.6); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; color: var(--vy-ink); cursor: pointer; vertical-align: middle; transition: transform .2s ease;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4v-6h16v6h-2M8 14h8v7H8z"></path></svg>
            Print this story
          </button>
          <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;"><span style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8F2F09;">Serviced apartments</span><span style="font-size: 12.5px; color: var(--vy-muted);">· 8 min read</span></div>
          <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 46px; line-height: 1.09; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance;">The year the commission became a salary</h1>
          <p style="margin: 14px 0 0; padding: 11px 15px; border-radius: 13px; background: rgba(217,102,46,.12); border: 1px solid rgba(217,102,46,.26); font-size: 13.5px; line-height: 1.55; color: var(--vy-ink); max-width: 640px;" data-scenario-label>⚠️ <strong>An illustrative scenario, not a customer.</strong> Vyrobel is new and has no published customer stories yet. This describes how the product is used by an operation of this shape, composed from onboarding conversations. Nothing here is a claim about a real business, and no figure is a measured result.</p>
          <p style="margin: 20px 0 0; font-size: 18px; line-height: 1.62; color: var(--vy-muted);">Two people, eighteen apartments, and an annual OTA bill that was larger than what either of them took home.</p>
        </div>
      </section>

      <section data-sec="" style="max-width: 1040px; margin: 0 auto; padding: 0 28px 36px;">
        <div data-reveal="" style="position: relative; border-radius: 22px; overflow: hidden; box-shadow: 0 20px 56px rgba(150,90,50,.2); aspect-ratio: 16 / 7;">
          <x-marketing.image-slot brief="Drop a wide photo — an apartment interior or the building exterior" radius="0" />
        </div>
      </section>

      <section data-sec="" style="max-width: 1040px; margin: 0 auto; padding: 0 28px 52px;">
        <div data-facts="" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
          <div data-reveal="" style="padding: 22px 22px; border-radius: 18px; background: rgba(255,252,248,.6); border: 1px solid rgba(255,255,255,.78);">
            <div data-printlabel="" style="font-size: 10.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 8px;">Portfolio</div>
            <div data-printnum="" style="font-family: var(--vy-display); font-size: 24px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.02em;">18 apartments</div>
            <div data-printsub="" style="font-size: 12.5px; color: var(--vy-muted); margin-top: 3px;">two buildings, all owned</div>
          </div>
          <div data-reveal="" data-reveal-delay="60" style="padding: 22px 22px; border-radius: 18px; background: rgba(255,252,248,.6); border: 1px solid rgba(255,255,255,.78);">
            <div data-printlabel="" style="font-size: 10.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 8px;">Team</div>
            <div data-printnum="" style="font-family: var(--vy-display); font-size: 24px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.02em;">2</div>
            <div data-printsub="" style="font-size: 12.5px; color: var(--vy-muted); margin-top: 3px;">plus a cleaning contractor</div>
          </div>
          <div data-reveal="" data-reveal-delay="120" style="padding: 22px 22px; border-radius: 18px; background: rgba(255,252,248,.6); border: 1px solid rgba(255,255,255,.78);">
            <div data-printlabel="" style="font-size: 10.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 8px;">Check-in</div>
            <div data-printnum="" style="font-family: var(--vy-display); font-size: 24px; font-weight: 600; color: var(--vy-ink); letter-spacing: -.02em;">Contactless</div>
            <div data-printsub="" style="font-size: 12.5px; color: var(--vy-muted); margin-top: 3px;">no front desk, ever</div>
          </div>
          <div data-reveal="" data-reveal-delay="180" style="padding: 22px 22px; border-radius: 18px; background: rgba(255,252,248,.6); border: 1.5px solid rgba(194,65,12,.42);">
            <div data-printlabel="" style="font-size: 11.5px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 8px;">Live in</div>
            <div data-printnum="" style="font-family: var(--vy-display); font-size: 24px; font-weight: 600; color: var(--vy-accent-text); letter-spacing: -.02em;">12 days</div>
            <div data-printsub="" style="font-size: 12.5px; color: var(--vy-muted); margin-top: 3px;">start to finish</div>
          </div>
        </div>
      </section>

      <section data-sec="" id="before" style="max-width: 780px; margin: 0 auto; padding: 0 28px 44px;">
        <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; letter-spacing: -.03em; color: var(--vy-ink);">Before: profitable on paper, thin in the bank</h2>
        <p style="margin: 0 0 18px; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">Occupancy was good. Reviews were good. Almost everything arrived through two marketplaces, and the commission on that volume was the single largest line in the business after the mortgage. They had a website; it had photographs and a contact form, and it converted approximately nobody.</p>
        <p style="margin: 0 0 18px; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">The reason was not mysterious. A guest who found the apartments on a marketplace, liked them, and searched the name landed on a page that asked them to send an email and wait. The marketplace had a button. The button won every time.</p>
      </section>

      <section data-sec="" id="switch" style="max-width: 780px; margin: 0 auto; padding: 0 28px 44px;">
        <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; letter-spacing: -.03em; color: var(--vy-ink);">The switch, and what came with it</h2>
        <p style="margin: 0 0 18px; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">Eighteen units and no separate owners is about as simple as a migration gets: twelve days, mostly spent on rate plans and photographing the apartments properly for the storefront. The booking engine went live on their own domain in week two, running from the same calendar as the marketplaces.</p>
        <p style="margin: 0 0 18px; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">The listings stayed. That was the part they had to be argued into and the part that made the year work: the marketplaces kept finding new guests, and the storefront stopped those guests paying a toll on their second visit.</p>
        <p style="margin: 0; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">Contactless arrival came along almost by accident. Online check-in with ID capture, a door code released the afternoon before, and wifi details in the same place meant the two of them stopped driving across the city to hand over keys.</p>
      </section>

      <section data-sec="" id="after" data-nopad="" style="position: relative; overflow: hidden; margin: 0 0 44px; padding: 66px 0 70px; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
        <div style="position: absolute; top: -120px; left: 8%; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(90px); opacity: .26; pointer-events: none;"></div>
        <div style="position: relative; max-width: 780px; margin: 0 auto; padding: 0 28px;">
          <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; letter-spacing: -.03em; color: #fff;">After: the same guests, a different door</h2>
          <p style="margin: 0 0 18px; font-size: 16.5px; line-height: 1.76; color: rgba(255,247,240,.78);">More of the same nights arrived through their own booking page instead of a marketplace. Total volume did not fall; the listings kept doing what listings do. What changed is which channel the repeat business came through, and repeat business at these apartments is a large share of the year.</p>
          <p style="margin: 0 0 26px; font-size: 16.5px; line-height: 1.76; color: rgba(255,247,240,.78);">The second effect was time. Contactless arrival removed most of the evening driving, and guests who could see their door code without asking stopped messaging at eleven at night to ask for it.</p>
          <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div style="padding: 22px 22px; border-radius: 18px; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.13);">
              <div data-printcallout="" style="font-family: var(--vy-display); font-size: 22px; font-weight: 600; color: var(--vy-accent-3); letter-spacing: -.02em; margin-bottom: 6px;">Key handovers gone</div>
              <div style="font-size: 14px; line-height: 1.62; color: rgba(255,247,240,.66);">Online check-in and timed door codes replaced the evening drive.</div>
            </div>
          </div>
        </div>
      </section>

      <section data-sec="" id="honest" style="max-width: 780px; margin: 0 auto; padding: 0 28px 48px;">
        <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; letter-spacing: -.03em; color: var(--vy-ink);">What was harder than expected</h2>
        <p style="margin: 0 0 18px; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">Direct bookings arrive without a marketplace's dispute process behind them, which means a chargeback is now their problem. It happened once, over a cancellation the guest claimed they had made and had not. They won it because the policy was on the booking page and the confirmation email, but it was an uncomfortable fortnight and it changed how carefully they write those two things.</p>
        <p style="margin: 0; font-size: 16.5px; line-height: 1.76; color: var(--vy-muted);">Photography also cost more than the software did in the first year. A storefront is only as convincing as its pictures, and theirs were marketplace-grade rather than good.</p>
      </section>

    <nav aria-label="Story navigation" data-sec="" data-inpagenav="" style="max-width: 980px; margin: 0 auto; padding: 0 28px 44px;">
      <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px;">
        <span style="font-size: 12.5px; color: var(--vy-muted);">Story 2 of 3</span>
        <x-marketing.nav-link key="case-studies" style="font-size: 13px; font-weight: 500; text-decoration: none;">All stories</x-marketing.nav-link>
      </div>
      <div data-pn="" style="display: flex; flex-wrap: wrap; gap: 14px;">
        <x-marketing.nav-link key="story-cyclades" style="flex: 1; min-width: 240px; display: flex;  align-items: center; gap: 16px; padding: 22px 24px; border-radius: 18px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 32px rgba(150,90,50,.12); text-decoration: none; color: inherit; transition: transform .26s cubic-bezier(.22,.7,.3,1), box-shadow .26s ease;">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M15 18l-6-6 6-6"></path></svg>
          <span style="min-width: 0;">
            <span style="display: block; font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 5px;">Previous</span>
            <span style="display: block; font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); line-height: 1.3;">Island villas</span>
            <span style="display: block; font-size: 12.5px; color: var(--vy-muted); margin-top: 3px;">42 villas</span>
          </span>
        </x-marketing.nav-link>
        <x-marketing.nav-link key="story-lisbon" style="flex: 1; min-width: 240px; display: flex; flex-direction: row-reverse; text-align: right; align-items: center; gap: 16px; padding: 22px 24px; border-radius: 18px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 12px 32px rgba(150,90,50,.12); text-decoration: none; color: inherit; transition: transform .26s cubic-bezier(.22,.7,.3,1), box-shadow .26s ease;">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M9 6l6 6-6 6"></path></svg>
          <span style="min-width: 0;">
            <span style="display: block; font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 5px;">Next</span>
            <span style="display: block; font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); line-height: 1.3;">City hotel</span>
            <span style="display: block; font-size: 12.5px; color: var(--vy-muted); margin-top: 3px;">96 keys</span>
          </span>
        </x-marketing.nav-link>
      </div>
    </nav>
    <section data-sec="" style="max-width: 1040px; margin: 0 auto; padding: 0 28px 90px;">
        <div data-reveal="" style="position: relative; overflow: hidden; border-radius: 24px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); padding: 38px 38px; box-shadow: 0 16px 44px rgba(150,90,50,.16); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px;">
          <div style="max-width: 560px;">
            <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 10px;">Illustrative story</div>
            <p style="margin: 0; font-size: 14.5px; line-height: 1.7; color: var(--vy-muted);">Composed from onboarding conversations with owner-operators of this size. Named customers and verified figures replace it at launch.</p>
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 11px;">
            <x-marketing.nav-link key="for-apartments" style="display: inline-flex; align-items: center; gap: 8px; padding: 13px 24px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14.5px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.32); transition: transform .22s ease;">Vyrobel for apartments</x-marketing.nav-link>
            <a href="ROI Calculator.dc.html" style="display: inline-flex; align-items: center; gap: 8px; padding: 13px 24px; border-radius: 13px; background: rgba(255,255,255,.66); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 14.5px; font-weight: 500; text-decoration: none; transition: transform .22s ease;">Work out your own numbers</a>
          </div>
        </div>
      </section>

    </article>
  </main>

    <div data-printfoot="" style="display: none; border-top: 1pt solid #B8ADA5; padding-top: 7pt; margin-top: 10pt;">
      <div style="font-size: 7.5pt; line-height: 1.5; color: var(--vy-muted);">Vyrobel · Property management for hotels, apartments and villas · sales@vyrobel.com · vyrobel.com<br>Figures in this story are illustrative. Named customers and verified numbers replace them at launch.</div>
    </div>

</div>
</x-marketing.shell>
