{{-- SECURITY — front-website Gate 7.

     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel Security.dc.html`, and then CORRECTED against the code.
     The Gate 7 doc-truth audit rated 29 claims on this page: 9 TRUE, 11 PARTIAL, 7 FALSE, 2 UNVERIFIABLE.
     Every FALSE and PARTIAL one is rewritten in the converter (transform 7), never here — a post-generation
     patch is undone by the next generation, silently, which this project has now proven three times.

     ⚠️ CORRECTED, NEVER SOFTENED. Replacing a false specific with "we take security seriously" would leave a
     reader knowing less than before; a security page that says less is not safer, it is just vaguer. Where
     the truth is a limitation — no 2FA enforcement, no hosting yet, no account deletion, no retention timer
     — the limitation is what the page prints.

     ⚠️ THE PAGE'S RESTRAINT IS KEPT: it makes no claim about certifications, penetration testing, uptime, an
     SLA or backups, and every one of those would have been false. An absent claim is a correct claim.

     ⚠️ ONE FINDING HERE IS A PRODUCT DEFECT, NOT A COPY DEFECT, and is reported to the founder rather than
     papered over: `/app/dashboard` carries no permission gate, so its "Revenue today" tile renders for every
     back-office role including Housekeeping. The sentence "a housekeeper sees rooms, not revenue" is cut
     back to the two clauses that ARE enforced. --}}
@php
    $testimonialQuotes = [];
@endphp
<x-marketing.shell
    title="Security at Vyrobel — what is built, and what is not yet"
    description="How Vyrobel protects operator and guest data: encrypted PII, structural tenant isolation, an audit trail on money, and a plain account of the parts that are not built yet."
    canonical="/security">
<div data-screen-label="Marketing — Security" data-vy-rail="" style="min-height: 100vh; font-family: var(--vy-sans); color: var(--vy-ink); background: linear-gradient(135deg, #F6C89B 0%, #F1B896 20%, #EBD9CF 46%, #D9D6D2 70%, #E7DCD4 100%); background-attachment: fixed; position: relative; overflow-x: clip;">

  <a href="#vy-main" style="position: absolute; left: -9999px; top: 12px; z-index: 900; padding: 12px 18px; border-radius: 12px; background: var(--vy-ink); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;">Skip to content</a>

  <main id="vy-main" style="position: relative; padding-top: 74px;">

    <section data-sec="" style="position: relative; max-width: 1240px; margin: 0 auto; padding: 74px 28px 54px;">
      <div data-orb="" style="position: absolute; top: -40px; left: -100px; width: 420px; height: 420px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .45; animation: vyDrift 24s ease-in-out infinite; pointer-events: none;"></div>
      <div style="position: relative; max-width: 700px;">
        <div style="font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent-text); margin-bottom: 16px; animation: vyFadeUp .7s cubic-bezier(.22,.7,.3,1) both;">Security &amp; compliance</div>
        <h1 data-h1="" style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 50px; line-height: 1.08; letter-spacing: -.035em; color: var(--vy-ink); text-wrap: balance; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .06s both;">You hold your guests' passports. We take that seriously.</h1>
        <p style="margin: 22px 0 0; font-size: 17.5px; line-height: 1.62; color: var(--vy-muted); max-width: 620px; animation: vyFadeUp .8s cubic-bezier(.22,.7,.3,1) .13s both;">Hospitality software handles identity documents, card payments and home addresses. Vyrobel treats data minimisation as an architectural decision rather than a policy page. ⚠️ Vyrobel has not launched yet. This page describes what is built and tested, and says plainly where something is not done.</p>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 80px;">
      <div data-sgrid="" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
        <article data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="3"></rect><path d="M11 18h2"></path></svg></span>
          <h2 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17.5px; font-weight: 600; color: var(--vy-ink);">Two-factor authentication</h2>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Available on operator, owner and platform-admin accounts, and mandatory for platform admins. ⚠️ It is opt-in per person: there is no way for an administrator to require it of a role, including roles that take payments or change payout details. Guest accounts have no 2FA. Recovery codes, trusted devices, and a real list of your active sessions — you can end all other sessions at once, after re-entering your password.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span>
          <h2 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17.5px; font-weight: 600; color: var(--vy-ink);">Roles that actually restrict</h2>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">An owner sees their own units, never another owner’s — a row-level scope that fails closed rather than falling open, and an owner account cannot leave the owner portal. An accountant sees the ledger, not the guest list.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18l-7-4-7 4z"></path><path d="M9 9h6"></path></svg></span>
          <h2 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17.5px; font-weight: 600; color: var(--vy-ink);">Audit trail on money</h2>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Refunds, payments, rate-plan changes and payout transitions are logged with who did what and when — a record you can hand to an owner or an auditor. ⚠️ Three things are not in it yet: per-unit rate overrides, owner payout preferences, and granting a role to a person.</p>
        </article>
        <article data-reveal="" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"></path></svg></span>
          <h2 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17.5px; font-weight: 600; color: var(--vy-ink);">Encryption and isolation</h2>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Sensitive fields are encrypted with the application key — guest email and phone, owner tax and bank details, identity documents, two-factor secrets. The application forces HTTPS and marks its cookies secure. Tenant isolation is structural: a global scope on every read, and a guard that refuses a write with no tenant context. ⚠️ Hosting is not chosen yet, so there is no data-residency commitment to give you — we would rather publish one before launch than imply it now.</p>
        </article>
        <article data-reveal="" data-reveal-delay="60" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h10M4 18h6"></path></svg></span>
          <h2 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17.5px; font-weight: 600; color: var(--vy-ink);">Data minimisation</h2>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">We collect what a stay requires and no more. Identity documents are encrypted and hard-deleted 30 days after checkout by a scheduled job — a fixed default applied everywhere, not a per-jurisdiction legal period.</p>
        </article>
        <article data-reveal="" data-reveal-delay="120" style="background: rgba(255,252,248,.6); backdrop-filter: blur(20px) saturate(1.15); -webkit-backdrop-filter: blur(20px) saturate(1.15); border: 1px solid rgba(255,255,255,.76); border-radius: 20px; padding: 28px 26px; box-shadow: 0 12px 34px rgba(150,90,50,.13); transition: transform .3s ease, box-shadow .3s ease;">
          <span style="display: inline-flex; width: 40px; height: 40px; border-radius: 13px; background: rgba(217,102,46,.13); align-items: center; justify-content: center; margin-bottom: 16px;"><svg width="19" height="19" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path></svg></span>
          <h2 style="margin: 0 0 9px; font-family: var(--vy-display); font-size: 17.5px; font-weight: 600; color: var(--vy-ink);">Guest anonymisation</h2>
          <p style="margin: 0; font-size: 14px; line-height: 1.65; color: var(--vy-muted);">Erasure is real and thorough: personal details are cleared while the financial record survives, so your reports stay accurate. ⚠️ It runs on request — yours or the guest’s — not on a timer. There is no automatic retention sweep yet.</p>
        </article>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="" style="background: rgba(255,252,248,.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255,255,255,.75); border-radius: 24px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 44px 42px;">
        <div data-two="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 44px;">
          <div>
            <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Your rights as a controller</h2>
            <p style="margin: 0 0 16px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">You are the data controller for your guests; we're the processor. That means we act on your instruction, and we never use your guests’ data for anything but running your business. ⚠️ The paperwork that should come with that — the written contract governing how we handle your guests’ data, and the list of every company we rely on to run the service — is not written yet. We would rather tell you that than let you assume otherwise.</p>
            <p style="margin: 0; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Corrections are self-service in every realm, and a guest can delete their own account from the guest portal. ⚠️ Owners and operators ask us to erase, and a guest cannot yet export their own data — two gaps we are closing.</p>
          </div>
          <div>
            <h2 style="margin: 0 0 16px; font-family: var(--vy-display); font-weight: 600; font-size: 28px; line-height: 1.16; letter-spacing: -.03em; color: var(--vy-ink);">Leaving is a feature</h2>
            <p style="margin: 0 0 16px; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">Export in open formats — CSV, PDF and XLSX — with no support request. Cancelling makes your workspace read-only at the end of the period you paid for. ⚠️ Deleting a whole account, and the schedule that would follow it, is not built: today you ask us and we do it by hand.</p>
            <p style="margin: 0; font-size: 15.5px; line-height: 1.68; color: var(--vy-muted);">We'd rather you stayed because the product earns it.</p>
          </div>
        </div>
      </div>
    </section>

    <section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 90px;">
      <div data-reveal="scale" style="position: relative; overflow: hidden; border-radius: 30px; background: linear-gradient(160deg, var(--vy-dark), #251C17); box-shadow: 0 30px 80px rgba(60,35,20,.36); padding: 60px 48px; text-align: center;">
        <div data-orb="" style="position: absolute; bottom: -120px; right: 22%; width: 360px; height: 360px; border-radius: 50%; background: var(--vy-accent-3); filter: blur(70px); opacity: .38; animation: vyDrift 23s ease-in-out infinite;"></div>
        <div style="position: relative;">
          <h2 style="margin: 0; font-family: var(--vy-display); font-weight: 600; font-size: 36px; line-height: 1.12; letter-spacing: -.03em; color: #fff; text-wrap: balance;">Planning a security review?</h2>
          <p style="margin: 18px auto 0; max-width: 500px; font-size: 16.5px; line-height: 1.62; color: rgba(255,247,240,.74);">Ask us what exists today. Right now the honest answer is: this page, and the engineers’ account of what is built. The formal documents a procurement team will ask for are not written, and we will tell you which are ready rather than send you one that is not.</p>
          <div style="margin-top: 28px;">
            <x-marketing.nav-link key="contact" style="display: inline-flex; align-items: center; gap: 9px; padding: 15px 28px; border-radius: 14px; background: linear-gradient(140deg,var(--vy-accent),var(--vy-accent-text)); color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; box-shadow: 0 16px 36px rgba(194,65,12,.44); transition: transform .22s ease, box-shadow .22s ease;">Ask us where they stand</x-marketing.nav-link>
          </div>
        </div>
      </div>
    </section>
  </main>

</div>
</x-marketing.shell>
