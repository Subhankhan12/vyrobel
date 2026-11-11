{{-- PRIVACY — built 2026-09-23 at the founder's explicit, repeated instruction.

     ⚠️⚠️ THE REGISTER REFUSED THIS PAGE, AND THE REFUSAL IS WORTH READING BEFORE EDITING IT.
     `routes/web.php` recorded: "LEGAL DOCUMENTS. Their structure and a cited data inventory are prepared for
     counsel in docs/legal/; the text is not this pass's to draft, and a plausible-looking privacy policy is
     worse than none because the founder and their customers' auditors would rely on it."

     ⚠️ The founder overrode that twice, knowingly, having been told the risk, because Google will not publish
     an External OAuth consent screen without a privacy-policy URL. So this page exists — and the way it
     answers the original objection is by being FACTUAL rather than plausible:

       · every statement is taken from `docs/legal/DATA-INVENTORY.md` (472 lines, cited to path:line) and
         `docs/legal/PRIVACY-STRUCTURE.md`, which audited the design's own draft and found several of its
         assertions FALSE against the code;
       · where the truth is a limitation, the limitation is what the page prints — no hosting-region promise,
         no "encrypted at rest" claim, no sub-processor list that does not exist, no retention period the
         software does not implement;
       · the cookie table renders from `CookieConsent::catalogue()`, the LIVE CI-guarded catalogue, so it
         cannot drift from what the app actually sets. A copy would have drifted within a month.

     ⚠️ IT IS BANNERED AS A PENDING-REVIEW DRAFT, TOP AND BOTTOM. That is not decoration: three things still
     block a final policy — no Article 28 DPA, no sub-processor agreements, and no retention rule for many of
     the data categories in docs/legal/DATA-INVENTORY.md. ⚠️ No count is written here on purpose: the figure
     that used to sit here was typed once, by hand, and was already stale (see that file's gap section).
     Counsel replaces this text; it does not merely bless it. --}}
<x-marketing.shell
    title="Privacy at Vyrobel — what we collect, and what is not settled yet"
    description="What Vyrobel collects, why, where it lives and for how long — written from an audited data inventory, and plain about the parts that are still pending legal review."
    canonical="/privacy">
<div data-screen-label="Marketing — Privacy" style="min-height:100vh;font-family:var(--vy-sans);color:var(--vy-ink);background:linear-gradient(135deg,#F6C89B 0%,#F1B896 20%,#EBD9CF 46%,#D9D6D2 70%,#E7DCD4 100%);background-attachment:fixed;position:relative;overflow-x:clip;">
  <main id="vy-main" style="position:relative;padding-top:74px;">

    <section style="max-width:900px;margin:0 auto;padding:64px 28px 24px;">
      <div style="font-size:11.5px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--vy-accent-text);margin-bottom:16px;">Privacy</div>
      <h1 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:46px;line-height:1.1;letter-spacing:-.035em;">Privacy at Vyrobel</h1>
      <p style="margin:20px 0 0;font-size:17px;line-height:1.62;color:var(--vy-muted);">What we collect, why we hold it, where it lives and for how long — and, where a thing is not yet decided, that it is not yet decided.</p>

      <div style="margin-top:28px;padding:18px 20px;border:1px solid rgba(178,106,8,.35);background:rgba(255,248,235,.75);border-radius:12px;">
        <p style="margin:0;font-size:14.5px;line-height:1.6;color:var(--vy-ink);"><strong>Draft — pending legal review.</strong> This page is written from an audited inventory of what the software actually does. It has <strong>not</strong> been reviewed by a lawyer and is not legal advice. Three things are still outstanding and are named at the bottom of this page rather than glossed over.</p>
        <p style="margin:10px 0 0;font-size:13px;color:var(--vy-muted);">Last updated {{ now()->format('j F Y') }}.</p>
      </div>
    </section>

    <section style="max-width:900px;margin:0 auto;padding:0 28px 80px;font-size:15.5px;line-height:1.7;color:var(--vy-ink);">

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">1 · Who we are</h2>
      <p style="color:var(--vy-muted);">Vyrobel is hotel-management software. When an accommodation operator uses Vyrobel to manage their guests, <strong>the operator is the controller</strong> of that guest data and Vyrobel is the <strong>processor</strong> acting on their instructions. For the operator's own account data — the people who sign in, their roles and their activity — <strong>Vyrobel is the controller</strong>.</p>
      <p style="color:var(--vy-muted);"><em>Pending:</em> the registered legal entity, its address and company number are to be confirmed by counsel and will appear here.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">2 · What we collect</h2>
      <ul style="color:var(--vy-muted);padding-left:20px;">
        <li><strong>Guest data</strong> — name, contact details, stay dates, reservation and folio records, and, where an operator uses online check-in, identity-document images.</li>
        <li><strong>Operator, owner and staff data</strong> — name, work email, role, and activity in the product, including rostering and leave records.</li>
        <li><strong>Payment references</strong> — we never see or store a card number. Card payments run through Stripe's hosted checkout and we hold only the resulting reference.</li>
        <li><strong>Security and audit records</strong> — who changed what and when, including IP address and browser user-agent, held in append-only stores.</li>
        <li><strong>Enquiries</strong> — anything you type into a contact form, and booking enquiries that were started but never completed.</li>
        <li><strong>Sign-in identity</strong> — if you sign in with Google we store Google's stable account identifier and the email address on that account. We never receive your Google password.</li>
        <li><strong>Browser storage</strong> — in addition to the cookies listed below, the app keeps a booking draft and a recent-search list in your browser's local storage.</li>
      </ul>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">3 · Why we hold it</h2>
      <p style="color:var(--vy-muted);">To provide the product: to take and manage a booking, to run the front desk, to bill and account for it, to send the transactional messages a stay requires, and to keep the security record that tells an operator who did what. Guest marketing messages are sent <strong>only</strong> where a guest has given separate, recorded consent, and every one carries an unsubscribe.</p>
      <p style="color:var(--vy-muted);"><em>Pending:</em> the lawful basis for each category is to be set by counsel. We would rather leave this open than assert a basis we have not confirmed.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">4 · Where it lives, and for how long</h2>
      <p style="color:var(--vy-muted);">Vyrobel runs on shared hosting provided by Hostinger, and the database sits with it. <strong>We make no data-residency commitment yet</strong> — choosing a region is a decision that has not been taken, and we would rather say so than imply otherwise.</p>
      <p style="color:var(--vy-muted);">On encryption, precisely: traffic to the site is encrypted in transit (HTTPS, enforced). At rest, <strong>encryption is applied at the application level to specific sensitive columns</strong> — guest email addresses and phone numbers among them — and is <strong>not</strong> whole-disk or whole-database encryption. Guest names, stay dates and reservation details are not in that encrypted set.</p>
      <p style="color:var(--vy-muted);">Two retention rules are implemented and run daily: an erased guest's personal details are removed after a <strong>14-day</strong> reversible window, and uploaded identity documents are purged after <strong>30 days</strong>. That 30-day window is a product default, not a per-country legal period.</p>
      <p style="color:var(--vy-muted);"><em>Pending, and stated plainly:</em> most other categories have <strong>no retention rule yet</strong>. A period has to be chosen and then built, in that order, and we will not print a period the software does not enforce.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">5 · Who else processes it</h2>
      <p style="color:var(--vy-muted);">Today the product can transmit data to:</p>
      <ul style="color:var(--vy-muted);padding-left:20px;">
        <li><strong>Stripe</strong> — card payments and, for owner payouts, identity verification. Live card processing is not yet switched on.</li>
        {{-- MAIL 02 (2026-09-25) — email moved from Postmark to Hostinger's own mail, and this line now reads the SAME
             register the platform console reads (`SubProcessors`), so "active" follows the configured mailer instead
             of a sentence typed once. The static line it replaces named Postmark and said nothing was delivered —
             both became false the moment mail was switched on, and nothing would have gone red. --}}
        @php($mailProcessor = collect(\App\Support\Privacy\SubProcessors::register())->firstWhere('key', 'hostinger_email'))   {{-- by the row's stable key — its NAME is said in the reader's language --}}
        <li><strong>Hostinger (email)</strong> — transactional email: booking confirmations, account verification, password resets and invitations, sent from a vyrobel.com mailbox. It receives the recipient's address and the message itself.
          @if ($mailProcessor['active'])
            Active.
          @else
            <strong>Not yet active:</strong> no email is currently being delivered from this system.
          @endif
        </li>
        <li><strong>Google</strong> — only if you choose to sign in with Google, and only your identifier, email and basic profile.</li>
        <li><strong>Hostinger</strong> — hosting and storage.</li>
      </ul>
      <p style="color:var(--vy-muted);">Channel-manager and AI-assistant integrations exist in the product but are <strong>not connected</strong>, so no data reaches them.</p>
      <p style="color:var(--vy-muted);"><em>Pending:</em> each of the above needs a named entity, its processing location, a signed agreement under Article 28 and a transfer mechanism where processing happens outside the EEA. That work is not finished.</p>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">6 · Your rights, and which are self-service today</h2>
      <p style="color:var(--vy-muted);">Rights differ by who you are, and we would rather be specific than uniform:</p>
      <ul style="color:var(--vy-muted);padding-left:20px;">
        <li><strong>Correction</strong> — available in the product to guests, owners and operator staff.</li>
        <li><strong>Erasure</strong> — self-service for <strong>guests</strong>, with a 14-day reversible window. Owners and operator staff must ask us.</li>
        <li><strong>Access and portability</strong> — self-service for owners and operator staff. <strong>A guest cannot yet export their own data</strong>; ask us and we will do it.</li>
        <li><strong>Objection and restriction</strong> — no self-service mechanism exists yet; ask us.</li>
        <li><strong>Account closure</strong> — closing an account withdraws access and makes the workspace read-only. <strong>It is not deletion</strong>, and we would rather name that difference than blur it.</li>
      </ul>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">7 · Cookies</h2>
      <p style="color:var(--vy-muted);">Vyrobel sets <strong>{{ count(\App\Support\Privacy\CookieConsent::catalogue()) }}</strong> cookies. All of them are strictly necessary. There are no analytics cookies, no marketing cookies and no third-party cookies, and the site makes no third-party request of any kind. The table below is generated from the application's own catalogue, so it cannot drift from what is actually set.</p>
      <div style="overflow-x:auto;margin-top:16px;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <thead><tr style="text-align:left;border-bottom:1px solid rgba(0,0,0,.12);">
            <th style="padding:8px 10px;">Cookie</th><th style="padding:8px 10px;">Purpose</th><th style="padding:8px 10px;white-space:nowrap;">Lifetime</th><th style="padding:8px 10px;">Set when</th>
          </tr></thead>
          <tbody>
          @foreach (\App\Support\Privacy\CookieConsent::catalogue() as $cookieName => $cookieMeta)
            <tr style="border-bottom:1px solid rgba(0,0,0,.07);vertical-align:top;color:var(--vy-muted);">
              <td style="padding:10px;white-space:nowrap;"><code>{{ $cookieName }}</code></td>
              <td style="padding:10px;">{{ $cookieMeta['purpose'] }}</td>
              <td style="padding:10px;white-space:nowrap;">{{ $cookieMeta['lifetime'] }}</td>
              <td style="padding:10px;">{{ $cookieMeta['set_when'] }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <h2 style="font-family:var(--vy-display);font-size:24px;margin:44px 0 12px;letter-spacing:-.02em;">8 · Contact</h2>
      <p style="color:var(--vy-muted);">Write to <a href="mailto:sales@vyrobel.com" style="color:var(--vy-accent-text);">sales@vyrobel.com</a> for any privacy question or to exercise a right. A dedicated privacy address will be published here once counsel has settled the process.</p>

      <div style="margin-top:52px;padding:20px 22px;border:1px solid rgba(178,106,8,.35);background:rgba(255,248,235,.75);border-radius:12px;">
        <h2 style="font-family:var(--vy-display);font-size:19px;margin:0 0 10px;">What is still outstanding</h2>
        <p style="margin:0 0 10px;font-size:14.5px;color:var(--vy-muted);">We would rather list these than let their absence pass unnoticed:</p>
        <ol style="margin:0;padding-left:20px;font-size:14.5px;color:var(--vy-muted);">
          <li>An agreement under Article 28, which an operator needs before we may process their guests' data as a processor.</li>
          <li>Signed agreements and transfer mechanisms with each sub-processor named above.</li>
          <li>Retention periods for the categories that do not yet have one, chosen and then implemented.</li>
        </ol>
      </div>

    </section>
  </main>
  <x-marketing.footer />
</div>
</x-marketing.shell>
