{{-- INTEGRATIONS — front-website Gate 7.

     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel Integrations.dc.html`, then corrected in the converter.
     The audit classified every listed integration: Stripe (built, test-mode until the founder's live keys),
     accounting exports (real, CSV/PDF), connected apps (REAL — a full OAuth2 server with scoped, revocable,
     audited access), and the direct storefront's 0% commission (verified against the ledger).

     ⚠️ THE THREE OTA CHANNELS ARE NOT CONNECTABLE, so the page says so. `Channel::create` exists exactly once
     outside factories — in the demo seeder — so no operator can connect Booking.com, Airbnb or Expedia at
     all; Vrbo is not modelled. The channel-manager pipeline IS built and tested against a simulated client,
     and the page distinguishes that from an operating state rather than blurring the two.

     ⚠️ THE THREE "Synced" PILLS ARE NOW "Not connected" — which is what the product's own channels screen
     shows. `sync_jobs` and `webhook_events` are empty and `last_synced_at` is null: nothing here has ever
     synced, and OperatorChannels.php refuses the word for exactly that reason. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Integrations — what connects to Vyrobel today | Vyrobel"
    description="What Vyrobel connects to today: Stripe payments, accounting exports in CSV and PDF, and scoped third-party access over OAuth. OTA channel sync is built but not yet connectable."
    canonical="/integrations">
<div data-screen-label="Marketing — Integrations" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 54px;">
      <div data-orb="" style="position: absolute; top: -40px; right: -90px; width: 400px; height: 400px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 700px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Integrations &amp; channels</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 50px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Keep your channels. Stop depending on them.</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Vyrobel keeps one availability pool with a single write path, so it is built to drive channels without the calendars ever drifting apart. ⚠️ No OTA channel can be connected yet: what is live today is the direct storefront, Stripe payments, accounting exports and scoped third-party access.</p>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
      <div data-igrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <article data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Booking.com</span>
            <span style="padding: 4px 10px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 10.5px; font-weight: 600;">Channel</span>
          </div>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">⚠️ Not connectable yet. The sync pipeline, the rate push and the inbound-booking importer are built and tested against a simulated channel; a real connection needs a Channex partner account we have not set up.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Airbnb</span>
            <span style="padding: 4px 10px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 10.5px; font-weight: 600;">Channel</span>
          </div>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">⚠️ Not connectable yet, and Airbnb guest messaging is not built — the inbox carries in-app and logged conversations only.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Expedia &amp; Vrbo</span>
            <span style="padding: 4px 10px; border-radius: 8px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 10.5px; font-weight: 600;">Channel</span>
          </div>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">⚠️ Not connectable yet. Expedia is in our channel catalogue; Vrbo is not modelled at all.</p>
        </article>
        <article data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Stripe</span>
            <span style="padding: 4px 10px; border-radius: 8px; background: rgba(150,90,50,.11); color: var(--vy-muted); font-size: 10.5px; font-weight: 600;">Payments</span>
          </div>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Card payments on the storefront and at the desk, deposits, refunds and credit notes — all reflected in the folio and the owner statement.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Accounting exports</span>
            <span style="padding: 4px 10px; border-radius: 8px; background: rgba(150,90,50,.11); color: var(--vy-muted); font-size: 10.5px; font-weight: 600;">Finance</span>
          </div>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Statements and booking ledgers export as CSV and PDF; VAT returns and payout histories export as CSV. These are files you hand over — there is no accounting-package sync.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <span style="font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Connected apps</span>
            <span style="padding: 4px 10px; border-radius: 8px; background: rgba(150,90,50,.11); color: var(--vy-muted); font-size: 10.5px; font-weight: 600;">Platform</span>
          </div>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Grant an app scoped access, review exactly which permissions it holds, and disconnect it in one step — with the whole thing logged.</p>
        </article>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 44px 42px;">
        <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 44px; align-items: center;">
          <div>
            <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">One availability pool, not four calendars</h2>
            <p style="margin: 0 0 16px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Every channel would read the same inventory: there is one availability table, one write path, and taking a unit out of service removes it from that pool immediately. ⚠️ Pushing that state out to Booking.com or Airbnb is built, but not yet live.</p>
            <p style="margin: 0; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">That's the difference between a channel manager bolted onto a PMS and a platform that owns both.</p>
          </div>
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Direct storefront</span>
              <span style="font-size: 11.5px; color: var(--vy-accent-text); font-weight: 600;">0% commission</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 8px; height: 8px; border-radius: 50%; background: rgba(150,90,50,.35);"></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Booking.com</span>
              <span style="font-size: 11.5px; color: var(--vy-muted);">Not connected</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 8px; height: 8px; border-radius: 50%; background: rgba(150,90,50,.35);"></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Airbnb</span>
              <span style="font-size: 11.5px; color: var(--vy-muted);">Not connected</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; background: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.82);">
              <span style="width: 8px; height: 8px; border-radius: 50%; background: rgba(150,90,50,.35);"></span>
              <span style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">Expedia</span>
              <span style="font-size: 11.5px; color: var(--vy-muted);">Not connected</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 60px 48px; text-align: center;">
        <div data-orb="" style="position: absolute; top: -110px; left: 22%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 22s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Need a channel we haven't listed?</h2>
          <p style="margin: 18px auto 0; max-width: 500px; font-size: 16.5px; line-height: 1.62; color: rgba(255,247,240,.74);">Tell us which ones you’re on. We will tell you honestly where that channel stands rather than promise a connection date we cannot keep.</p>
          <div style="margin-top: 28px;">
            <x-marketing.nav-link key="contact" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Ask about your channels</x-marketing.nav-link>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
