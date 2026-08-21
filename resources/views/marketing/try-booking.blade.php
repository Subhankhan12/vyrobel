{{-- TRY BOOKING — the HONEST SANDBOX (front-website Gate 4, ruling D3 = (b)).

     ⚠️ The design's page simulated a booking on a FICTIONAL property and ended in a FABRICATED
     confirmation email, under a meta description promising "Make a real booking on a demo property".
     Nothing was booked and no such email existed. Every honesty transform lives in the CONVERTER, not
     here, because a post-generation patch is undone by the next generation (Gate 3b proved that twice).

     ⚠️ Guarded by tests/Feature/MarketingTryBookingTest.php — written in the same change as this comment. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Try the booking engine — a Vyrobel direct-booking preview"
    description="See exactly what your guests see: the real demo property, priced by the live engine, and the real confirmation email. ⚠️ A preview only — nothing is reserved, charged or sent. The live demo storefront is one click away."
    canonical="/try-booking">
<div data-screen-label="Marketing — Try booking" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 60px 28px 34px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -110px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div data-orb="" style="position: absolute; top: 160px; right: -100px; width: 360px; height: 360px; border-radius: 50%; background: #FBE3CE; filter: blur(70px); opacity: .55; animation: vyDrift 27s ease-in-out infinite reverse; pointer-events: none;"></div>

      <div style="position: relative; max-width: 660px;">
        <div style="display: inline-flex; align-items: center; gap: 9px; padding: 7px 14px 7px 9px; border-radius: 999px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 6px 18px rgba(150,90,50,.12); font-size: 12.5px; font-weight: 500; color: var(--vy-muted); margin-bottom: 20px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">
          <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 7px; background: linear-gradient(140deg, var(--vy-accent-3), var(--vy-accent));">
            <svg width="11" height="11" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
          </span>
          Sandbox · nothing is charged, no card needed
        </div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 46px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">See what a direct booking looks like</h1>
        <p style="margin: 20px 0 0; font-size: 17px; line-height: 1.62; color: var(--vy-muted); animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .12s both;">This is a preview of a direct booking on our real demo property: the rate the engine actually quotes, and the confirmation email a guest actually receives.</p>
      </div>
    </section>

    <x-marketing.sandbox-confirmation />
  </main>

</div>
</x-marketing.shell>
