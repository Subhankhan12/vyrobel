{{-- FOR HOTELS — front-website Gate 3.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel For Hotels.dc.html` — its own semantic markup and
     inline styles kept verbatim, transformed only where it must be (dc-runtime wiring removed; palette and
     font literals bound to `<x-design.tokens />`; design links → the pending-aware `<x-marketing.nav-link>`;
     `<image-slot>` → the placeholder component). The SEO values are the design's OWN `<helmet>`.

     ⚠️ THIS PAGE MAKES NO CLAIM ABOUT A NAMED COMPETITOR — which is why it could be built this gate while
     `Alternatives` and `Compare` could not (see `docs/audits/FRONT-GATE3-HONESTY-AUDITS-2026-08-21.md`). --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Hotel management software with a direct booking engine | Vyrobel"
    description="Vyrobel for independent hotels and boutique properties: front desk and arrivals board, rack calendar, housekeeping, maintenance, rates and RevPAR reporting, plus a commission-free direct booking engine."
    canonical="/for-hotels">
<div data-screen-label="Marketing — For hotels" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 60px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -100px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 720px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 12px; font-weight: 600; margin-bottom: 20px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">For independent hotels</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 50px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">Hotel software that runs the desk, the floor and the direct channel</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Built for properties between fifteen and two hundred keys — big enough that the shift matters, small enough that nobody has time for a six-month implementation.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 26px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">Start free</a>
          <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">Book a demo</x-marketing.nav-link>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 60px;">
      <div data-reveal="" style="position: relative; border-radius: 24px; overflow: hidden; box-shadow: 0 22px 60px rgba(150,90,50,.22); aspect-ratio: 16 / 6.5;">
        <x-marketing.image-slot brief="Drop a wide photo — the lobby, a room, or the facade" radius="0" />
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="max-width: 560px; margin-bottom: 32px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink);">The three o'clock problem</h2>
      </div>
      <div data-three="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Fourteen arrivals, nine rooms ready</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">The desk phones housekeeping, housekeeping checks a printed list, and a guest stands in the lobby while two people reconcile the same building.</p>
        </div>
        <div data-reveal="" data-reveal-delay="80" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">A fifth of the rate, gone</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Guests who stayed last summer rebook through the same marketplace, because the alternative was a contact form and an email two days later.</p>
        </div>
        <div data-reveal="" data-reveal-delay="160" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">RevPAR by feel</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">You know last month was good. Proving it — by channel, by room type, against the same month last year — takes an afternoon and three exports.</p>
        </div>
      </div>
    </section>

    <section data-sec="" id="hotelops" style="position: relative; overflow: hidden; margin: 0 0 70px; padding: 78px 0 82px; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -130px; left: 6%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -150px; right: 5%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
      
      <div data-reveal="" style="position: relative; max-width: 560px; margin-bottom: 32px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-3); margin-bottom: 14px;">What you get</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: #fff;">The parts a hotel actually uses daily</h2>
      </div>
      <div data-quad="" style="position: relative; display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">An arrivals board the desk trusts</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Everyone due in and out on a live timeline, with room-ready status coming straight off the housekeeping board. Check in, take the balance, hand over the key.</p>
        </div>
        <div data-reveal="" data-reveal-delay="70" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v13H3z"></path><path d="M8 7V4h8v3M3 12h18"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Housekeeping on the floor's phones</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Rooms move dirty → cleaning → inspected with a drag. Guest-waiting rooms pin to the top, elapsed time shows against expected, and the desk sees it all live.</p>
        </div>
        <div data-reveal="" data-reveal-delay="140" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">A booking engine on your own site</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Room types, rate plans, weekend uplift and deposits — on a storefront in your brand, taking cards, feeding the same calendar the desk works from.</p>
        </div>
        <div data-reveal="" data-reveal-delay="210" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V9M10 18V5M16 18v-6M22 18H2"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Occupancy, ADR and RevPAR on tap</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">By room type, by channel, over any range you choose, with peak months flagged and a PDF your board or your bank will accept without reformatting.</p>
        </div>
      </div>
    </div>
    </section>

    <section data-sec="" id="shifts" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="max-width: 620px; margin-bottom: 30px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">Three shifts, one record</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">A hotel does not close, so neither does the handover</h2>
        <p style="margin: 15px 0 0; font-size: 16px; line-height: 1.64; color: var(--vy-muted);">Villas and apartments hand over by message. A hotel hands over in person, three times a day, and every one of those moments is where something gets dropped.</p>
      </div>

      <div data-shifts="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <div data-reveal="" style="display: flex; flex-direction: column; padding: 26px 26px; border-radius: 20px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 14px 38px rgba(150,90,50,.13);">
          <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <span style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Night</span>
            <span style="font-size: 11.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums;">23:00 – 07:00</span>
          </div>
          <div style="height: 4px; border-radius: 999px; background: rgba(150,90,50,.13); margin-bottom: 18px; overflow: hidden;">
            <div style="height: 100%; width: 33%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent));"></div>
          </div>
          <div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); margin-bottom: 9px; line-height: 1.32;">One person, the whole building</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">Late arrivals let themselves in with a code released the afternoon before. The night audit runs on its own; nobody prints a report at four in the morning to close the day.</p>
        </div>
        <div data-reveal="" data-reveal-delay="70" style="display: flex; flex-direction: column; padding: 26px 26px; border-radius: 20px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 14px 38px rgba(150,90,50,.13);">
          <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <span style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Morning</span>
            <span style="font-size: 11.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums;">07:00 – 15:00</span>
          </div>
          <div style="height: 4px; border-radius: 999px; background: rgba(150,90,50,.13); margin-bottom: 18px; overflow: hidden;">
            <div style="height: 100%; width: 66%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent));"></div>
          </div>
          <div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); margin-bottom: 9px; line-height: 1.32;">Departures, then the turn</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">Nine checkouts drop onto the housekeeping board as they are recorded. The board orders itself by who arrives first, so the busiest rooms are cleaned first without anyone deciding.</p>
        </div>
        <div data-reveal="" data-reveal-delay="140" style="display: flex; flex-direction: column; padding: 26px 26px; border-radius: 20px; background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.78); box-shadow: 0 14px 38px rgba(150,90,50,.13);">
          <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <span style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: var(--vy-ink);">Afternoon</span>
            <span style="font-size: 11.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums;">15:00 – 23:00</span>
          </div>
          <div style="height: 4px; border-radius: 999px; background: rgba(150,90,50,.13); margin-bottom: 18px; overflow: hidden;">
            <div style="height: 100%; width: 100%; border-radius: 999px; background: linear-gradient(90deg,var(--vy-accent-3),var(--vy-accent));"></div>
          </div>
          <div style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink); margin-bottom: 9px; line-height: 1.32;">Fourteen arrivals, no queue</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">Eleven completed check-in from home. Reception hands over keys to rooms it already knew were ready, because guest-ready reached the desk the second housekeeping marked it.</p>
        </div>
      </div>

      <div data-reveal="" style="margin-top: 18px; padding: 22px 26px; border-radius: 18px; background: rgba(255,252,248,.5); border: 1px solid rgba(255,255,255,.75); display: flex; flex-wrap: wrap; align-items: center; gap: 14px;">
        <span style="width: 30px; height: 30px; border-radius: 10px; background: rgba(217,102,46,.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg width="15" height="15" fill="none" stroke="var(--vy-accent)" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span>
        <span style="flex: 1; min-width: 260px; font-size: 14.5px; line-height: 1.62; color: var(--vy-muted);">Nothing on this page is a handover note. Room state, folios, tickets and messages carry forward on their own, so the outgoing shift explains only what a system cannot.</span>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 56px 48px;">
        <div data-orb="" style="position: absolute; top: -120px; right: 12%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 23s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <div data-nums="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; margin-bottom: 40px;">
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="96" data-suffix=" keys">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">A Lisbon property running the whole operation on Vyrobel</div>
            </div>
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="41" data-suffix="%">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Of their nights now booked direct, up from 12%</div>
            </div>
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="18" data-suffix=" min">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Average room turnaround, visible per cleaner</div>
            </div>
          </div>
          <blockquote style="margin: 0; padding-top: 32px; border-top: 1px solid rgba(255,247,240,.16); font-size: 18px; line-height: 1.66; color: #fff; max-width: 760px;">"Housekeeping runs itself now. The board tells the team what's urgent, and a maintenance ticket takes the unit off sale without anyone remembering to."</blockquote>
          <div style="margin-top: 18px; font-size: 13.5px; color: rgba(255,247,240,.6);">Petar Novak · Front desk manager, 96 keys · Lisbon</div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
        <div data-reveal="left">
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Also here when you need it</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Group bookings for a wedding block. Walk-ins at midnight. A guest inbox that covers every channel. Shift scheduling for the whole team. Owner statements, if some of your keys belong to someone else.</p>
          <x-marketing.nav-link key="features" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">See every feature <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </div>
        <div data-reveal="right" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 22px; padding: 32px 30px;">
          <h3 style="margin: 0 0 12px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">What would you keep?</h3>
          <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.66; color: var(--vy-muted);">Work out what your current commission bill would look like if direct became the default channel.</p>
          <x-marketing.nav-link key="home" fragment="#calculator" style="display: inline-flex; align-items: center; gap: 9px; padding: 13px 22px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14.5px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.3); transition: transform .2s ease;">Open the calculator</x-marketing.nav-link>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
