{{-- ABOUT — front-website Gate 7.

     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel About.dc.html`, MINUS ITS TEAM. Vyrobel is one person: 863
     of 863 commits by a single author. The design presented four named staff — and two of them, "Elena
     Kastellis" and "Joris van Dijk", are already in the honesty ledger as invented, unconsented CUSTOMER
     testimonial authors, while a third, "Amara Reyes", is a recombination of two more. The block is DELETED
     in the converter, not renamed: renaming an invented co-founder does not remove one, it invents a
     different one. The D13 rename transform is explicitly scoped away from this page for that reason.

     ⚠️ "The problem we kept watching" named D1's three invented customers — the Cyclades manager, the
     Rotterdam family, the Lisbon hotel — as businesses observed first-hand. Those are the same three
     unconsented fictions whose case-study pages were refused in Gate 3 and rebuilt as labelled illustrative
     scenarios. De-specified to the pattern, which is the part that was ever true.

     ⚠️ WHAT SURVIVES IS THE STRONGEST PART OF THE PAGE and is genuinely true: why the product exists, what
     it is built to do, and four principles — two of which ("owners see performance, never a guest's personal
     details"; "guest data is minimised and anonymised") are checkable product properties the audit
     confirmed. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="About Vyrobel — why it exists and what it is built to do"
    description="Why Vyrobel exists, what it is built to do for operators and owners, and the principles it is built on."
    canonical="/about">
<div data-screen-label="Marketing — About" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 60px;">
      <div data-orb="" style="position: absolute; top: -40px; right: -80px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 760px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">About us</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 52px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">We think the people who host guests should own the relationship with them</h1>
        <p style="margin: 24px 0 0; font-size: 18px; line-height: 1.64; color: var(--vy-muted); max-width: 640px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Vyrobel started from a simple observation: the best-run properties were spending their margin on distribution and their evenings on spreadsheets, while the software they paid for served neither problem.</p>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: start;">
        <div data-reveal="left">
          <h2 style="margin: 0 0 18px; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">The problem we kept watching</h2>
          <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.7; color: var(--vy-muted);">A villa manager, a family running a handful of apartments, a small independent hotel. Different scale, same three complaints: the OTAs take a fifth of every booking, the operation runs on group chats, and the owners want a report nobody has time to build.</p>
          <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.7; color: var(--vy-muted);">The tooling on offer solved one of those at a time. A channel manager. A cleaning app. A reporting add-on. Every new tool added an integration to babysit, and none of them made the guest more likely to book with you next time.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.7; color: var(--vy-muted);">We built Vyrobel to solve them together, because in a property business they were never separate problems.</p>
        </div>
        <div data-reveal="right" style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 36px 34px;">
          <div style="font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 18px;">What we're building toward</div>
          <div style="display: flex; flex-direction: column; gap: 22px;">
            <div>
              <h3 style="margin: 0 0 7px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Give operators their channel back</h3>
              <p style="margin: 0; font-size: 14.5px; line-height: 1.62; color: var(--vy-muted);">A direct booking should be the easy default, not a side project you'll get to after the season.</p>
            </div>
            <div>
              <h3 style="margin: 0 0 7px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Make owners trust the numbers</h3>
              <p style="margin: 0; font-size: 14.5px; line-height: 1.62; color: var(--vy-muted);">Transparency isn't a nicer PDF. It's the owner seeing the same figures you do, the moment they exist.</p>
            </div>
            <div>
              <h3 style="margin: 0 0 7px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Let the stay run itself</h3>
              <p style="margin: 0; font-size: 14.5px; line-height: 1.62; color: var(--vy-muted);">Guests should never have to email you for a door code. Your team should never have to send one manually.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- VALUES -->
    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-orb="" style="position: absolute; top: 40px; left: -80px; width: 340px; height: 340px; border-radius: 50%; background: var(--vy-peach); filter: blur(70px); opacity: .42; animation: vyDrift 26s ease-in-out infinite; pointer-events: none;"></div>
      <div data-reveal="" style="position: relative; max-width: 560px; margin-bottom: 36px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">How we work</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink);">Four things we don't compromise on</h2>
      </div>
      <div data-trio="" style="position: relative; display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Honest by default</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">If a cost belongs to the operator and not the owner, the software says so. We'd rather show an awkward number than a flattering one.</p>
        </div>
        <div data-reveal="" data-reveal-delay="70" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Built for a Saturday</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Every screen is designed for the busiest changeover day, on a phone, by someone who has thirty seconds. If it only works when it's quiet, it doesn't work.</p>
        </div>
        <div data-reveal="" data-reveal-delay="140" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Privacy is structural</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Owners see performance, never a guest's personal details. Guest data is minimised and anonymised on a schedule, because GDPR is a design constraint, not a checkbox.</p>
        </div>
        <div data-reveal="" data-reveal-delay="210" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V9l8-5 8 5v11"></path><path d="M9 20v-6h6v6"></path></svg></span>
          <h3 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: var(--vy-ink);">Your data is yours</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Export everything, any time, in formats that open. We'd rather earn the next month than trap you in the last one.</p>
        </div>
      </div>
    </section>

    <!-- TEAM -->

    <!-- VISION -->
    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 64px 52px;">
        <div data-orb="" style="position: absolute; top: -120px; right: 10%; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .4; animation: vyDrift 22s ease-in-out infinite;"></div>
        <div style="position: relative; max-width: 700px;">
          <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,247,240,.55); margin-bottom: 18px;">Where this goes</div>
          <h2 style="margin: 0 0 20px; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.14; letter-spacing: -.03em; color: #fff; text-wrap: balance;">A generation of independent operators who don't depend on anyone's marketplace</h2>
          <p style="margin: 0 0 30px; font-size: 16.5px; line-height: 1.68; color: rgba(255,247,240,.74);">The properties worth staying in are usually the ones run by people, not portfolios. Those businesses deserve software that makes the direct relationship the strongest one they have — with their guests, and with the owners who trust them.</p>
          <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <x-marketing.nav-link key="contact" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 26px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Get in touch</x-marketing.nav-link>
            <x-marketing.nav-link key="product" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 24px; border-radius: 14px; background: rgba(255,247,240,.12); border: 1px solid rgba(255,247,240,.24); color: #fff; font-size: 15px; font-weight: 500; text-decoration: none; transition: transform .22s ease, background .22s ease;">See the platform</x-marketing.nav-link>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
