{{-- FOR VILLAS — front-website Gate 3.

     ⚠️ REPRODUCED FROM THE DESIGN MASTER `docs/design/front/Vyrobel For Villas.dc.html` — its own semantic markup and
     inline styles kept verbatim, transformed only where it must be (dc-runtime wiring removed; palette and
     font literals bound to `<x-design.tokens />`; design links → the pending-aware `<x-marketing.nav-link>`;
     `<image-slot>` → the placeholder component). The SEO values are the design's OWN `<helmet>`.

     ⚠️ THIS PAGE MAKES NO CLAIM ABOUT A NAMED COMPETITOR — which is why it could be built this gate while
     `Alternatives` and `Compare` could not (see `docs/audits/FRONT-GATE3-HONESTY-AUDITS-2026-08-21.md`). --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Villa &amp; vacation rental management software with owner portal | Vyrobel"
    description="Vyrobel for villa and vacation rental managers: commission-free direct bookings, per-villa calendars and owner blocks, automated owner statements and payouts, cleaning turnarounds and guest check-in."
    canonical="/for-villas">
<div data-screen-label="Marketing — For villas" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 60px;">
      <div data-orb="" style="position: absolute; top: -40px; right: -100px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 720px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; background: rgba(217,102,46,.13); color: var(--vy-accent-text); font-size: 12px; font-weight: 600; margin-bottom: 20px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">For villa &amp; vacation rental managers</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 50px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">You manage other people's houses. The software should make that obvious.</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); max-width: 600px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Villa management is two businesses at once: filling weeks for guests, and answering to the owners whose houses they are. Vyrobel is the only part of your stack that takes the second one seriously.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .2s both;">
          <a href="{{ route('signup') }}" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 26px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 14px 32px rgba(194,65,12,.34); transition: transform .22s ease, box-shadow .22s ease;">Start free</a>
          <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 14px 24px; border-radius: 14px; background: rgba(255,252,248,.62); border: 1px solid rgba(255,255,255,.85); color: var(--vy-ink); font-size: 15px; font-weight: 500; text-decoration: none; box-shadow: 0 8px 24px rgba(150,90,50,.12); transition: transform .22s ease, box-shadow .22s ease;">Book a demo</x-marketing.nav-link>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 60px;">
      <div data-reveal="" style="position: relative; border-radius: 24px; overflow: hidden; box-shadow: 0 22px 60px rgba(150,90,50,.22); aspect-ratio: 16 / 6.5;">
        <x-marketing.image-slot brief="Drop a wide photo — a villa, terrace, or pool" radius="0" />
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="max-width: 560px; margin-bottom: 32px;">
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink);">What the end of the month looks like</h2>
      </div>
      <div data-three="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">One spreadsheet per owner</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Bookings copied in by hand, commission calculated in a formula nobody else understands, and a PDF emailed with a nervous covering note.</p>
        </div>
        <div data-reveal="" data-reveal-delay="80" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">"Can I have the house in August?"</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">Owner stays arrive by text message and live in your head until someone blocks the dates — usually after a guest has already booked them.</p>
        </div>
        <div data-reveal="" data-reveal-delay="160" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px;">
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink);">A €340 repair, unexplained</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">The pool pump was fixed in July. The owner sees the deduction in August and asks what it was, and you go looking for the invoice.</p>
        </div>
      </div>
    </section>

    <section data-sec="" id="villaops" style="position: relative; overflow: hidden; margin: 0 0 70px; padding: 78px 0 82px; background: linear-gradient(165deg,var(--vy-dark) 0%,#2A211B 52%,#221A15 100%);">
      <div style="position: absolute; top: -130px; left: 6%; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(92px); opacity: .26; pointer-events: none;"></div>
      <div style="position: absolute; bottom: -150px; right: 5%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent); filter: blur(92px); opacity: .22; pointer-events: none;"></div>
      <div data-bandinner="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 0 28px;">
      
      <div data-reveal="" style="position: relative; max-width: 560px; margin-bottom: 32px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-3); margin-bottom: 14px;">What you get</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: #fff;">Built around the owner relationship</h2>
      </div>
      <div data-quad="" style="position: relative; display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
        <div data-reveal="" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-7-4-7 4z"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Statements that write themselves</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Gross, commission, cleaning, repairs, net payout — per villa, generated monthly as PDF and CSV, with every booking and every expense clickable behind the number.</p>
        </div>
        <div data-reveal="" data-reveal-delay="70" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M3 10h18M8 2v4M16 2v4"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Owners block their own dates</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">They open their calendar, drag the week they want — one-off or recurring — and the villa stops selling those nights everywhere. No text message, no double booking.</p>
        </div>
        <div data-reveal="" data-reveal-delay="140" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">Expenses approved before they land</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">A repair over the threshold you set goes to the owner with the work order attached. They approve it, and only then does it reach the statement.</p>
        </div>
        <div data-reveal="" data-reveal-delay="210" style="background: rgba(255,255,255,.055); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 30px 28px; transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 22px rgba(194,65,12,.26);"><svg width="19" height="19" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M8 13h8"></path></svg></span>
          <h3 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 18px; font-weight: 600; color: #fff;">A storefront for the whole portfolio</h3>
          <p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: rgba(255,247,240,.72);">Guests browse your villas, filter by dates and party size, see the per-night breakdown and book direct — with a waitlist when the week they wanted is gone.</p>
        </div>
      </div>
    </div>
    </section>

    <section data-sec="" id="ownerweeks" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="" style="max-width: 640px; margin-bottom: 30px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 14px;">The owner’s own weeks</div>
        <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 32px; line-height: 1.14; letter-spacing: -.03em; color: var(--vy-ink); text-wrap: balance;">It is still their house, and they will want to use it</h2>
        <p style="margin: 15px 0 0; font-size: 16px; line-height: 1.64; color: var(--vy-muted);">Most owners are slightly embarrassed to ask. Raise it first — the ones who feel they have to negotiate for a week in their own villa are the ones who leave.</p>
      </div>

      <div data-reveal="" style="padding: 30px 32px; border-radius: 24px; background: rgba(255,252,248,.6); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.8); box-shadow: 0 16px 44px rgba(150,90,50,.15);">
        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 14px; margin-bottom: 18px;">
          <span style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; color: var(--vy-ink);">Villa Coralis · 2026</span>
          <span style="font-size: 12px; color: var(--vy-muted);">Owner blocks stop the villa selling on every channel</span>
        </div>

        <div data-yeargrid="" style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 6px; margin-bottom: 18px;">
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: rgba(120,70,40,.09); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: var(--vy-muted);">Jan</div>
            <div style="font-size: 9.5px; color: var(--vy-muted); margin-top: 3px;">quiet</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: rgba(120,70,40,.09); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: var(--vy-muted);">Feb</div>
            <div style="font-size: 9.5px; color: var(--vy-muted); margin-top: 3px;">quiet</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: rgba(217,102,46,.16); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: var(--vy-accent-text);">Mar</div>
            <div style="font-size: 9.5px; color: var(--vy-accent-text); margin-top: 3px;">let</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: rgba(217,102,46,.16); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: var(--vy-accent-text);">Apr</div>
            <div style="font-size: 9.5px; color: var(--vy-accent-text); margin-top: 3px;">let</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: #fff;">May</div>
            <div style="font-size: 9.5px; color: rgba(255,247,240,.7); margin-top: 3px;">peak</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: #fff;">Jun</div>
            <div style="font-size: 9.5px; color: rgba(255,247,240,.7); margin-top: 3px;">peak</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: linear-gradient(160deg,var(--vy-dark),#251C17); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: #fff;">Jul</div>
            <div style="font-size: 9.5px; color: rgba(255,247,240,.55); margin-top: 3px;">theirs</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: #fff;">Aug</div>
            <div style="font-size: 9.5px; color: rgba(255,247,240,.7); margin-top: 3px;">peak</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: #fff;">Sep</div>
            <div style="font-size: 9.5px; color: rgba(255,247,240,.7); margin-top: 3px;">peak</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: rgba(217,102,46,.16); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: var(--vy-accent-text);">Oct</div>
            <div style="font-size: 9.5px; color: var(--vy-accent-text); margin-top: 3px;">let</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: rgba(120,70,40,.09); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: var(--vy-muted);">Nov</div>
            <div style="font-size: 9.5px; color: var(--vy-muted); margin-top: 3px;">quiet</div>
          </div>
          <div style="padding: 14px 6px 12px; border-radius: 12px; background: linear-gradient(160deg,var(--vy-dark),#251C17); text-align: center;">
            <div style="font-size: 11px; font-weight: 600; color: #fff;">Dec</div>
            <div style="font-size: 9.5px; color: rgba(255,247,240,.55); margin-top: 3px;">theirs</div>
          </div>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 18px; padding-top: 16px; border-top: 1px solid rgba(150,90,50,.12);">
          <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--vy-muted);"><span style="width: 12px; height: 12px; border-radius: 4px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text));"></span>Peak season, let</span>
          <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--vy-muted);"><span style="width: 12px; height: 12px; border-radius: 4px; background: rgba(217,102,46,.16);"></span>Shoulder, let</span>
          <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--vy-muted);"><span style="width: 12px; height: 12px; border-radius: 4px; background: linear-gradient(160deg,var(--vy-dark),#251C17);"></span>Owner staying</span>
          <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--vy-muted);"><span style="width: 12px; height: 12px; border-radius: 4px; background: rgba(120,70,40,.09);"></span>Quiet, available</span>
        </div>
      </div>

      <div data-ownernotes="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 18px;">
        <div data-reveal="" style="padding: 24px 24px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <div style="font-family: var(--vy-display); font-size: 15.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 8px;">They book it themselves</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">From their own portal, with whatever notice you agreed. No email to you, no waiting for a reply, no sense of asking permission.</p>
        </div>
        <div data-reveal="" data-reveal-delay="70" style="padding: 24px 24px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <div style="font-family: var(--vy-display); font-size: 15.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 8px;">It comes off sale everywhere</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">An owner block behaves exactly like a booking: Airbnb, Booking.com and your storefront all stop offering the dates in the same second.</p>
        </div>
        <div data-reveal="" data-reveal-delay="140" style="padding: 24px 24px; border-radius: 18px; background: rgba(255,252,248,.55); border: 1px solid rgba(255,255,255,.75);">
          <div style="font-family: var(--vy-display); font-size: 15.5px; font-weight: 600; color: var(--vy-ink); margin-bottom: 8px;">The statement shows why</div>
          <p style="margin: 0; font-size: 14px; line-height: 1.66; color: var(--vy-muted);">A month with owner weeks in it earns less, and the statement says so plainly rather than leaving them to wonder whether you undersold the villa.</p>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 84px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 56px 48px;">
        <div data-orb="" style="position: absolute; top: -120px; left: 14%; width: 380px; height: 380px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 23s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <div data-nums="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; margin-bottom: 40px;">
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="42" data-suffix=" villas">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Across the Cyclades, 19 different owners</div>
            </div>
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="0" data-suffix=" hrs">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Spent building owner statements last month</div>
            </div>
            <div>
              <div style="font-family: var(--vy-display); font-size: 40px; font-weight: 600; color: #fff; letter-spacing: -.03em; font-variant-numeric: tabular-nums;"><span data-count="58" data-suffix="%">0</span></div>
              <div style="margin-top: 8px; font-size: 13.5px; color: rgba(255,247,240,.66);">Of peak-season nights now booked direct</div>
            </div>
          </div>
          <blockquote style="margin: 0; padding-top: 32px; border-top: 1px solid rgba(255,247,240,.16); font-size: 18px; line-height: 1.66; color: #fff; max-width: 760px;">"We went from three tools and a shared spreadsheet to one screen. The part I didn't expect: our owners stopped calling at month end, because they can just look."</blockquote>
          <div style="margin-top: 18px; font-size: 13.5px; color: rgba(255,247,240,.6);">Chara Melis · Ops director, 42 units · Cyclades</div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
        <div data-reveal="left">
          <h2 style="margin: 0 0 14px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">And the operational side</h2>
          <p style="margin: 0 0 18px; font-size: 16px; line-height: 1.68; color: var(--vy-muted);">Changeover cleaning scheduled off the booking calendar. Pool and garden work as recurring jobs. Guest check-in with ID capture before they land. Arrival info — gate code, wifi, the trick with the shutters — released the morning they arrive.</p>
          <x-marketing.nav-link key="features" style="display: inline-flex; align-items: center; gap: 7px; font-size: 14.5px; font-weight: 600; color: var(--vy-accent-text); text-decoration: none;">See every feature <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></x-marketing.nav-link>
        </div>
        <div data-reveal="right" style="background: rgba(255,252,248,.58); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 22px; padding: 32px 30px;">
          <h3 style="margin: 0 0 12px; font-family: var(--vy-display); font-size: 19px; font-weight: 600; color: var(--vy-ink);">Winning the owner pitch</h3>
          <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.66; color: var(--vy-muted);">Managers tell us the owner portal closes new houses. Being able to show a prospective owner exactly what they'd see, every month, is the whole pitch.</p>
          <x-marketing.nav-link key="contact" fragment="#demo" style="display: inline-flex; align-items: center; gap: 9px; padding: 13px 22px; border-radius: 13px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 14.5px; font-weight: 600; text-decoration: none; box-shadow: 0 12px 28px rgba(194,65,12,.3); transition: transform .2s ease;">See the owner portal</x-marketing.nav-link>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
