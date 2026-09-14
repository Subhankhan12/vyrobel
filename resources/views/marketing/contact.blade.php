{{-- CONTACT — front-website Gate 7. ⚠️ THE FORM IS REAL.

     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel Contact.dc.html`, whose submit was an `sc-camel-on-click` handler binding
     — a React handler that flipped a local flag. No <form>, no action, no endpoint. Shipping it would have
     been a control that promises and does nothing (#39), and `key="contact"` appears 21 times across nine
     PUBLISHED pages, so it is the destination of nearly every call to action the site makes.

     ⚠️ IT NOW POSTS to `marketing.contact.store`: validated, throttled per IP, honeypotted, and written to
     `marketing_contact_messages` INSIDE a transaction BEFORE any mail is attempted. The row is the system of
     record and the email is a notification, because MailSafetyGuard downgrades every sending transport to
     `log` outside production — a mail-only form would silently destroy every message sent before go-live.

     ⚠️ THE DEMO BOOKER IS REMOVED (D5, still open). Its day picker, time slots and "a calendar invite is on
     its way to your inbox" would have booked nothing: there is no scheduling package, no meeting provider
     and no invite generator in the product. A picker that names a time and books nothing is worse than a
     form that does not send. The right rail's "Book a demo instead" card went with it — removing a feature
     and leaving its advertisement is how a dead control survives a cleanup.

     ⚠️ NO SUPPORT WIDGET (D4, open): it would be the first third-party request on a site that deliberately
     makes none. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Contact Vyrobel — tell us how your properties run"
    description="Write to Vyrobel about pricing, migrating from another PMS, or whether it fits how you operate. A real person reads every message."
    canonical="/contact">
<div data-screen-label="Marketing — Contact" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <!-- DEMO SCHEDULER -->

    <section data-sec="" id="message" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 40px 28px 90px; scroll-margin-top: 90px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -100px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div data-orb="" style="position: absolute; bottom: 60px; right: -90px; width: 360px; height: 360px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .55; animation: vyDrift 27s ease-in-out infinite reverse; pointer-events: none;"></div>

      <div style="position: relative; max-width: 660px; margin-bottom: 46px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px;">Or just write to us</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 40px; line-height: 1.1; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance;">Tell us how your properties run</h1>
        <p style="margin: 20px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted);">A real person reads this. Send us your unit count and where you operate, and you’ll get an honest answer about whether Vyrobel fits — including when it doesn’t.</p>
      </div>

      <div data-clayout="" style="position: relative; display: grid; grid-template-columns: 1.35fr .85fr; gap: 24px; align-items: start;">

        <div data-reveal="" style="background: rgba(255,252,248,.62); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); border-radius: 24px; box-shadow: 0 18px 50px rgba(150,90,50,.18), inset 0 1px 0 rgba(255,255,255,.65); padding: 38px 36px;">

          <span id="demo" aria-hidden="true"></span><x-marketing.contact-form />

        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">

          <div data-reveal="" data-reveal-delay="140" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14); padding: 28px 26px;">
            <div style="font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: var(--vy-muted); margin-bottom: 18px;">Direct lines</div>
            <div style="display: flex; flex-direction: column; gap: 18px;">
              <div style="display: flex; gap: 13px;">
                <span style="flex-shrink: 0; width: 34px; height: 34px; border-radius: 11px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M4 7l8 6 8-6"></path></svg></span>
                <div>
                  <div style="font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">sales@vyrobel.com</div>
                  <div style="font-size: 12px; color: var(--vy-muted); margin-top: 2px;">Pricing, demos and migrations</div>
                </div>
              </div>
              <div style="display: flex; gap: 13px;">
                <span style="flex-shrink: 0; width: 34px; height: 34px; border-radius: 11px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4l3 2"></path></svg></span>
                <div>
                  <div style="font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">sales@vyrobel.com</div>
                  <div style="font-size: 12px; color: var(--vy-muted); margin-top: 2px;">Anything else</div>
                </div>
              </div>
              <div style="display: flex; gap: 13px;">
                <span style="flex-shrink: 0; width: 34px; height: 34px; border-radius: 11px; background: rgba(217,102,46,.13); display: flex; align-items: center; justify-content: center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></span>
                <div>
                  <div style="font-size: 13.5px; font-weight: 500; color: var(--vy-ink);">Remote, in the EU</div>
                  <div style="font-size: 12px; color: var(--vy-muted); margin-top: 2px;">Serving operators across the EU</div>
                </div>
              </div>
            </div>
          </div>

          <div data-reveal="" data-reveal-delay="200" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 22px; box-shadow: 0 14px 40px rgba(150,90,50,.14); padding: 26px;">
            <h2 style="margin: 0 0 8px; font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Already a customer?</h2>
            <p style="margin: 0 0 14px; font-size: 13.5px; line-height: 1.6; color: var(--vy-muted);">Signed-in operators and owners have a help centre inside the app, and owners can ask a question from it.</p>
            <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">Log in to get help <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
