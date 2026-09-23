{{-- TERMS — built 2026-09-23 at the founder's explicit instruction, alongside the privacy page.

     ⚠️ SAME PROVENANCE, SAME DISCIPLINE. Drafted from `docs/legal/TERMS-STRUCTURE.md`, which rated the
     design's own draft section by section and found FOUR clauses factually wrong against the code:
       · billing described as per-unit and prorated — it is neither;
       · a data-processing agreement described as accompanying the terms — it does not exist;
       · published incidents and planned-maintenance notice — there is no status page and no incident process;
       · an export right on termination — export is real, but there is NO deletion path at all.
     Every one of those is corrected here rather than repeated, and where the correction is an absence, the
     absence is printed. A terms page that promises an SLA nobody operates is worse than one that admits
     there is none.

     ⚠️ Nine commercial terms remain genuine business decisions (liability cap, governing law, notice period
     and so on). They are listed at the foot of the page as outstanding rather than invented, because a
     number guessed here would be quoted back at the founder later. --}}
<x-marketing.shell
    title="Terms of Service — Vyrobel"
    description="The terms on which Vyrobel is provided — written against what the software actually does, and explicit about the commercial terms still to be settled."
    canonical="/terms">
<div data-screen-label="Marketing — Terms" style="min-height:100vh;font-family:var(--vy-sans);color:var(--vy-ink);background:linear-gradient(135deg,#F6C89B 0%,#F1B896 20%,#EBD9CF 46%,#D9D6D2 70%,#E7DCD4 100%);background-attachment:fixed;position:relative;overflow-x:clip;">
  <main id="vy-main" style="position:relative;padding-top:74px;">

    <section style="max-width:900px;margin:0 auto;padding:64px 28px 24px;">
      <div style="font-size:11.5px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--vy-accent-text);margin-bottom:16px;">Legal</div>
      <h1 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:46px;line-height:1.1;letter-spacing:-.035em;">Terms of Service</h1>
      <p style="margin:20px 0 0;font-size:17px;line-height:1.62;color:var(--vy-muted);">The terms on which Vyrobel is provided — written against what the product actually does today.</p>

      <div style="margin-top:28px;padding:18px 20px;border:1px solid rgba(178,106,8,.35);background:rgba(255,248,235,.75);border-radius:12px;">
        <p style="margin:0;font-size:14.5px;line-height:1.6;"><strong>Draft — pending legal review.</strong> This page has not been reviewed by a lawyer and is not legal advice. Several commercial terms are still to be settled; they are listed at the foot of this page rather than guessed at.</p>
        <p style="margin:10px 0 0;font-size:13px;color:var(--vy-muted);">Last updated {{ now()->format('j F Y') }}.</p>
      </div>
    </section>

    <section style="max-width:900px;margin:0 auto;padding:0 28px 80px;font-size:15.5px;line-height:1.7;">

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">1 · This agreement</h2>
      <p style="color:var(--vy-muted);">These terms govern your use of Vyrobel. By creating an account you accept them. <em>Pending:</em> the contracting legal entity and its registered address are to be confirmed by counsel.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">2 · Your account</h2>
      <p style="color:var(--vy-muted);">You are responsible for the accounts you create and for what the people holding them do. Access inside a workspace is governed by roles, and a role grants only what it is given.</p>
      <p style="color:var(--vy-muted);">Two-factor authentication is available and we recommend it. It is <strong>opt-in per person</strong> — an administrator cannot currently require it of their team, and we would rather say so than imply a control that does not exist.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">3 · Subscription and billing</h2>
      <p style="color:var(--vy-muted);">Vyrobel is offered on a subscription, beginning with a free trial that starts when your workspace is created. When the trial ends, continued access to the back office requires an active subscription.</p>
      <p style="color:var(--vy-muted);">Plans are <strong>flat-rate</strong> — they are not billed per unit, and there is no mid-cycle proration. <em>Pending:</em> published prices, billing periods and the treatment of a failed payment are still to be set.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">4 · Acceptable use</h2>
      <p style="color:var(--vy-muted);">Do not attempt to reach another customer's data, probe or disrupt the service, or use it unlawfully. Separation between workspaces is enforced structurally in the software, not merely by policy; attempting to cross it is a breach of these terms as well as a technical failure.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">5 · Your data</h2>
      <p style="color:var(--vy-muted);">Your business data remains yours. We process it to provide the service and as described in our <a href="/privacy" style="color:var(--vy-accent-text);">privacy page</a>.</p>
      <p style="color:var(--vy-muted);"><strong>Pending, and material:</strong> where you use Vyrobel to handle your guests' personal data, we act as your processor — and that relationship requires an agreement under Article 28 which <strong>is not yet in place</strong>. It is being prepared.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">6 · Availability and support</h2>
      <p style="color:var(--vy-muted);">We aim to keep Vyrobel available and to fix faults promptly. <strong>We do not offer a service-level agreement, a published uptime commitment, a status page or an incident-notification process</strong> — none of those exist today, and committing to them here would be a promise nobody operates.</p>
      <p style="color:var(--vy-muted);">Support is by email at <a href="mailto:sales@vyrobel.com" style="color:var(--vy-accent-text);">sales@vyrobel.com</a>.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">7 · Payments and third-party services</h2>
      <p style="color:var(--vy-muted);">Card payments are processed by Stripe under Stripe's own terms. We never receive or store a card number. Where you connect an external service to Vyrobel, that service's terms govern it and we are not responsible for what it does with data you direct to it.</p>
      <p style="color:var(--vy-muted);"><em>Note:</em> no online-travel-agency channel can be connected today, so nothing is currently transmitted to one.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">8 · Ending the agreement</h2>
      <p style="color:var(--vy-muted);">You may stop using Vyrobel at any time, and you can export your data from the product while you have access.</p>
      <p style="color:var(--vy-muted);"><strong>Be aware of the distinction:</strong> closing an account today withdraws access and leaves the workspace read-only. <strong>It does not delete the data.</strong> A deletion path and a defined post-termination window are still to be built and settled; until they are, we will handle a deletion request by hand on request.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">9 · Changes to these terms</h2>
      <p style="color:var(--vy-muted);">We may update these terms. <em>Pending:</em> the mechanism for notifying you of a material change in advance is not yet built, and the notice period is one of the terms still to be settled below.</p>

      <div style="margin-top:52px;padding:20px 22px;border:1px solid rgba(178,106,8,.35);background:rgba(255,248,235,.75);border-radius:12px;">
        <h2 style="font-family:var(--vy-display);font-size:19px;margin:0 0 10px;">Commercial terms still to be settled</h2>
        <p style="margin:0 0 10px;font-size:14.5px;color:var(--vy-muted);">These are business decisions, not oversights, and they are listed rather than invented:</p>
        <p style="margin:0;font-size:14.5px;color:var(--vy-muted);">Limitation of liability · uptime commitment · governing law and jurisdiction · notice period for changes · price-change terms · what happens to data after termination, and when · treatment of a failed payment · indemnities · how sub-processor changes are notified · whether account closure becomes true deletion.</p>
      </div>

    </section>
  </main>
  <x-marketing.footer />
</div>
</x-marketing.shell>
