{{-- FOR APARTMENTS — front-website Gate 3.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel For Apartments.dc.html` — its own semantic markup and
     inline styles kept verbatim, transformed only where it must be (dc-runtime wiring removed; palette and
     font literals bound to `<x-design.tokens />`; design links → the pending-aware `<x-marketing.nav-link>`;
     `<image-slot>` → the placeholder component). The SEO values are the design's OWN `<helmet>`.

     ⚠️ THIS PAGE MAKES NO CLAIM ABOUT A NAMED COMPETITOR — which is why it could be built this gate while
     `Alternatives` and `Compare` could not (see `docs/audits/FRONT-GATE3-HONESTY-AUDITS-2026-08-21.md`). --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Serviced apartment &amp; aparthotel management software | Vyrobel"
    description="Vyrobel for serviced apartments and short-stay operators: contactless check-in with door codes, multi-building calendars, cleaning and maintenance boards, long-stay rates and commission-free direct bookings."
    canonical="/for-apartments">
<div data-screen-label="Marketing — For apartments" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 60px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -100px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 720px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 12px; font-weight: 600; margin-bottom: 20px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">For serviced apartments &amp; short-stays</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 50px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">No reception, no keys, no one waiting in a lobby</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Serviced apartments are a distributed operation: units in several buildings, guests who let themselves in, and a cleaning team crossing the city. Vyrobel is built for running that from a phone.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 26px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">Start free</a>
          <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">Book a demo</x-marketing.nav-link>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 60px;">
      <div data-reveal="" style="position: relative; border-radius: 24px; overflow: hidden; box-shadow: 0 22px 60px rgba(150,90,50,.22); aspect-ratio: 16 / 6.5;">
        <x-marketing.image-slot brief="Drop a wide photo — an apartment interior or building" radius="0" />
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="max-width: 560px; margin-bottom: 32px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink);">Where the day leaks</h2>
      </div>
      <div data-three="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">"What's the door code?"</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Sent by hand, one message at a time, usually while you're driving. Occasionally to the wrong guest, occasionally too early.</p>
        </div>
        <div data-reveal="" data-reveal-delay="80" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">A cleaner in the wrong building</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Today's list changed at nine. The team is working from a screenshot of yesterday's, and the 3pm arrival is going into an unclean flat.</p>
        </div>
        <div data-reveal="" data-reveal-delay="160" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">Three portals, three inboxes</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">A guest asks about parking on one platform and late checkout on another. Both threads sit in apps somebody has to remember to open.</p>
        </div>
      </div>
    </section>

    <section data-sec="" id="aptops" style="position: relative; overflow: hidden; margin: 0 0 70px; padding: 78px 0 82px; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -130px; left: 6%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -150px; right: 5%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
      
      <div data-reveal="" style="position: relative; max-width: 560px; margin-bottom: 32px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-3); margin-bottom: 14px;">What you get</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: #fff;">A building you can run remotely</h2>
      </div>
      <div data-quad="" style="position: relative; display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="3"></rect><path d="M9 8h6M9 12h6"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Contactless check-in that runs on time</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Guests complete check-in and ID capture before arrival. The door code, wifi and building instructions release automatically at the hour you choose — to the right guest, every time.</p>
        </div>
        <div data-reveal="" data-reveal-delay="70" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v13H3z"></path><path d="M8 7V4h8v3M3 12h18"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">One live list for the cleaning round</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Today's turnarounds on the cleaner's phone, ordered by who's arriving first, updating as bookings change. Proof photos attach to the flat, and the flat goes guest-ready the moment it's inspected.</p>
        </div>
        <div data-reveal="" data-reveal-delay="140" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M4 7l8 6 8-6"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Every channel in one inbox</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Direct, Airbnb and Booking.com conversations in a single thread list, with the reservation beside each one, delivery states you can trust and a filter for guests arriving today.</p>
        </div>
        <div data-reveal="" data-reveal-delay="210" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V9M10 18V5M16 18v-6M22 18H2"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Rates for nights, weeks and months</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Weekend uplift for city breaks, minimum stays for the quiet season, and long-stay pricing for the corporate guest who wants six weeks. Set per unit, per building, or across the portfolio.</p>
        </div>
      </div>
    </div>
    </section>

    <section data-sec="" id="unattended" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="max-width: 640px; margin-bottom: 32px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">Arrival, unattended</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">Nobody meets the guest, so the timing has to be right</h2>
        <p style="margin: 15px 0 0; font-size: 16px; line-height: 1.64; color: var(--vy-muted);">A hotel can recover from a bad arrival at the desk. An apartment cannot, because there is no desk. Five things happen on a schedule, and none of them need you.</p>
      </div>

      <div style="position: relative; padding-left: 30px;">
        <div style="position: absolute; left: 9px; top: 12px; bottom: 12px; width: 2px; border-radius: 999px; background: linear-gradient(180deg, rgba(217,102,46,.5), rgba(217,102,46,.14));"></div>
        <div data-reveal="" data-unstep="" style="position: relative; padding: 0 0 26px 26px;">
          <span style="position: absolute; left: -30px; top: 4px; width: 20px; height: 20px; border-radius: 50%; background: rgba(255,252,248,.9); border: 2px solid rgba(217,102,46,.32); box-shadow: 0 4px 12px rgba(150,90,50,.18);"></span>
          <div style="font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 6px; font-variant-numeric: tabular-nums;">T−7 days</div>
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); margin-bottom: 7px; line-height: 1.28;">The invitation goes out</div>
          <p style="margin: 0; max-width: 620px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Registration, ID and house rules, on their own phone from wherever they are. One reminder if it is not done.</p>
        </div>
        <div data-reveal="" data-reveal-delay="60" data-unstep="" style="position: relative; padding: 0 0 26px 26px;">
          <span style="position: absolute; left: -30px; top: 4px; width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); border: 2px solid rgba(194,65,12,.4); box-shadow: 0 4px 12px rgba(150,90,50,.18);"></span>
          <div style="font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 6px; font-variant-numeric: tabular-nums;">T−1 day, 16:00</div>
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); margin-bottom: 7px; line-height: 1.28;">The door code releases</div>
          <p style="margin: 0; max-width: 620px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Only once check-in is complete and the balance is settled. A code released at booking is a code that exists on a stranger’s phone for three months.</p>
        </div>
        <div data-reveal="" data-reveal-delay="120" data-unstep="" style="position: relative; padding: 0 0 26px 26px;">
          <span style="position: absolute; left: -30px; top: 4px; width: 20px; height: 20px; border-radius: 50%; background: rgba(255,252,248,.9); border: 2px solid rgba(217,102,46,.32); box-shadow: 0 4px 12px rgba(150,90,50,.18);"></span>
          <div style="font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 6px; font-variant-numeric: tabular-nums;">T−1 day, 16:01</div>
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); margin-bottom: 7px; line-height: 1.28;">Everything else arrives with it</div>
          <p style="margin: 0; max-width: 620px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Wifi, the bin day, which buzzer, where to park, and a number that reaches a person. All in one message they can find again.</p>
        </div>
        <div data-reveal="" data-reveal-delay="180" data-unstep="" style="position: relative; padding: 0 0 26px 26px;">
          <span style="position: absolute; left: -30px; top: 4px; width: 20px; height: 20px; border-radius: 50%; background: rgba(255,252,248,.9); border: 2px solid rgba(217,102,46,.32); box-shadow: 0 4px 12px rgba(150,90,50,.18);"></span>
          <div style="font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 6px; font-variant-numeric: tabular-nums;">Arrival</div>
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); margin-bottom: 7px; line-height: 1.28;">They let themselves in</div>
          <p style="margin: 0; max-width: 620px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">Nobody drove across the city. The apartment was marked guest-ready that morning, and the system knew before they did.</p>
        </div>
        <div data-reveal="" data-reveal-delay="240" data-unstep="" style="position: relative; padding: 0 0 0 26px;">
          <span style="position: absolute; left: -30px; top: 4px; width: 20px; height: 20px; border-radius: 50%; background: rgba(255,252,248,.9); border: 2px solid rgba(217,102,46,.32); box-shadow: 0 4px 12px rgba(150,90,50,.18);"></span>
          <div style="font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 6px; font-variant-numeric: tabular-nums;">T+1 day</div>
          <div style="font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); margin-bottom: 7px; line-height: 1.28;">The code expires</div>
          <p style="margin: 0; max-width: 620px; font-size: 14.5px; line-height: 1.68; color: var(--vy-muted);">On checkout, automatically. The next guest gets a different one, and last week’s guest cannot come back.</p>
        </div>
      </div>

      <div data-reveal="" style="margin-top: 26px; padding: 24px 26px; border-radius: 20px; background: linear-gradient(160deg, var(--vy-dark), #251C17); position: relative; overflow: hidden; box-shadow: 0 20px 50px rgba(60,35,20,.28);">
        <div style="position: absolute; top: -70px; right: -40px; width: 220px; height: 220px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(60px); opacity: .3;"></div>
        <div style="position: relative; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px;">
          <div style="max-width: 560px;">
            <div style="font-family: var(--vy-display); font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 6px;">The evening drive is the cost nobody counts</div>
            <div style="font-size: 14px; line-height: 1.64; color: rgba(255,247,240,.74);">Two people, eighteen apartments, and most of a year of evenings spent handing over keys. That is the line item this section removes.</div>
          </div>
          <x-marketing.nav-link key="story-rotterdam" style="display: inline-flex; align-items: center; gap: 8px; padding: 13px 22px; border-radius: 13px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); color: #fff; font-size: 14px; font-weight: 500; text-decoration: none; flex-shrink: 0; transition: background .22s ease;">Read the Rotterdam story</x-marketing.nav-link>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 56px 48px;">
        <div data-orb="" style="position: absolute; top: -120px; right: 16%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 23s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <div data-nums="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; margin-bottom: 40px;">
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="18" data-suffix=" flats">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Across four buildings in Rotterdam, run by two people</div>
            </div>
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="52" data-suffix="%">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Of nights direct, from roughly a fifth a year ago</div>
            </div>
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="94" data-suffix="%">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Of guests complete check-in before they arrive</div>
            </div>
          </div>
          <blockquote style="margin: 0; padding-top: 32px; border-top: 1px solid rgba(255,247,240,.16); font-size: 18px; line-height: 1.66; color: #fff; max-width: 760px;">"Direct went from about a fifth of our nights to over half in a season. That's not a feature list, that's payroll."</blockquote>
          <div style="margin-top: 18px; font-size: 13.5px; color: rgba(255,247,240,.6);">Bram Hoekstra · Owner-operator, 18 apartments · Rotterdam</div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
        <div data-reveal="left">
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">If you also manage for owners</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Plenty of short-stay operators run a mix — some flats theirs, some managed on behalf of landlords. Vyrobel handles both in one portfolio: owned units keep their costs internal, managed units generate statements and payouts automatically.</p>
          <x-marketing.nav-link key="for-villas" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">See the owner side <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </div>
        <div data-reveal="right" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 22px; padding: 32px 30px;">
          <h3 style="margin: 0 0 12px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Start with one building</h3>
          <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.66; color: var(--vy-muted);">Run a single building for a month, keep the others where they are, and compare. We'll import the bookings either way.</p>
          <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 13px 22px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14.5px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.3); transition: transform .2s ease;">Start free</a>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
