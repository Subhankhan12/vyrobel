<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\MarketingContactMail;
use App\Models\MarketingContactMessage;
use App\Services\OwnerInviteService;
use App\Support\Mail\MailSafetyGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * THE PUBLIC CONTACT FORM (front-website Gate 7).
 *
 * ⚠️ WHY THIS EXISTS AT ALL. The design's contact submit was `sc-camel-on-click="{{ submit }}"` — a prototype
 * handler that flips a local `sent` flag and nothing else. There is no `<form>`, no `action`, no endpoint.
 * Shipping that markup would have been a control that promises and does nothing (#39), and it would not have
 * been a small one: `key="contact"` appears 21 times across nine PUBLISHED marketing pages, so every "Book a
 * demo" and "Talk to us" button on the live site points here. The page was not merely missing — it was the
 * destination of every call to action the site makes.
 *
 * ⚠️ THE ROW IS THE SYSTEM OF RECORD; THE EMAIL IS A NOTIFICATION. Written inside a transaction FIRST, mailed
 * after it commits — the ordering {@see OwnerInviteService} already uses. This is not
 * belt-and-braces: {@see MailSafetyGuard} deliberately downgrades every real-sending
 * transport to `log` outside `production`, so a message submitted today reaches no inbox at all. If the mail
 * were the only output, every submission before go-live would be silently destroyed. ⚠️ IT ALSO MEANS THE
 * FORM WORKS BEFORE THE FOUNDER HAS CONFIGURED AN ADDRESS — a missing recipient degrades the notification,
 * never the capture.
 *
 * ⚠️ WHAT THIS DELIBERATELY DOES NOT DO, and each absence is a refusal rather than an omission:
 *  • NO AUTO-REPLY to the visitor. An automated "thanks, we'll be in touch" is a reply-time promise, and the
 *    audit found the design asserting "we reply within one working day" twice with no support process behind
 *    it. The page confirms receipt — the one thing that is true — and promises no timescale.
 *  • NO DEMO BOOKING. The design's day/slot picker and "a calendar invite is on its way" belong to D5, still
 *    open. There is no scheduling integration and no meeting provider anywhere in the product, so a slot
 *    picker here would book nothing while looking exactly like it had.
 *  • NO SUPPORT WIDGET (D4) — it would be the first third-party request on a site that deliberately makes
 *    none.
 *
 * ⚠️ AUTHORIZATION: none, correctly. This is a public form for people who have no account, so there is no
 * actor to authorize (#30 is about gating an ACTOR's request; there is no actor here). What stands in for it
 * is the throttle, the honeypot and the length caps below.
 */
class MarketingContactController extends Controller
{
    /**
     * ⚠️ THE PICKERS ARE CLOSED SETS, validated server-side. The design renders them as chips whose value
     * rides in the request; without an `in:` rule a visitor could store any string and it would later be
     * rendered back into an internal email and a listing. Escaped either way, but a value nobody offered has
     * no business being stored.
     */
    private const UNIT_BANDS = ['1–10', '11–60', '60+'];

    private const TOPICS = ['A demo', 'Pricing', 'Migrating from another PMS', 'Something else'];

    /** ⚠️ The honeypot field's name. Rendered visually hidden and `aria-hidden`, so no real person fills it. */
    public const HONEYPOT = 'vy_website';

    public function store(Request $request): RedirectResponse
    {
        // ⚠️ THE HONEYPOT ANSWERS SUCCESSFULLY AND STORES NOTHING. Returning an error would tell a bot which
        // field betrayed it; returning the ordinary confirmation tells it nothing at all. ⚠️ It is checked
        // BEFORE validation so a bot filling every field never learns the validation rules either.
        if (filled($request->input(self::HONEYPOT))) {
            return $this->confirmation();
        }

        $data = $request->validate([
            // ⚠️ CAPPED, and the caps are the column's contract as much as the user's. `message` is `text`,
            // but an uncapped textarea on an unauthenticated endpoint is a cheap way to fill a disk.
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'units_band' => ['nullable', 'string', 'in:'.implode(',', self::UNIT_BANDS)],
            'topic' => ['nullable', 'string', 'in:'.implode(',', self::TOPICS)],
        ], [
            'message.min' => 'Tell us a little more than that — a sentence or two is plenty.',
        ]);

        $contact = DB::transaction(fn (): MarketingContactMessage => MarketingContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => $data['company'] ?? null,
            'message' => $data['message'],
            'units_band' => $data['units_band'] ?? null,
            'topic' => $data['topic'] ?? null,
            'ip' => $request->ip(),
        ]));

        $this->notify($contact);

        return $this->confirmation();
    }

    /**
     * Send the internal notification — and never let its failure reach the visitor.
     *
     * ⚠️ THE MESSAGE IS ALREADY COMMITTED BEFORE THIS RUNS, so a mail failure costs a notification, not a
     * message. Swallowing the exception here is therefore the correct trade and not a silent failure: the
     * row exists, `notified` stays false recording exactly that, and the failure is logged. ⚠️ THE LOG LINE
     * CARRIES THE ROW ID AND NOTHING ELSE — logging the address or the body would copy encrypted values into
     * a plaintext log file, which is how an encrypted column gets undone.
     */
    private function notify(MarketingContactMessage $contact): void
    {
        $to = config('services.marketing.contact_to');

        // ⚠️ NOT CONFIGURED IS A NORMAL STATE, NOT AN ERROR. The destination is the founder's real mailbox
        // and is theirs to set; until they do there is nothing to send to, and inventing an address would be
        // worse than sending nothing. The message is captured either way.
        if (! is_string($to) || $to === '') {
            return;
        }

        try {
            Mail::to($to)->send(new MarketingContactMail($contact));
            $contact->forceFill(['notified' => true])->save();
        } catch (\Throwable $e) {
            Log::warning('marketing contact notification failed', [
                'contact_id' => $contact->getKey(),
                'exception' => $e::class,
            ]);
        }
    }

    /**
     * ⚠️ PRG, and the flash says only what is true: the message was received. It does not say a person has
     * read it, and it does not say when one will.
     */
    private function confirmation(): RedirectResponse
    {
        return redirect()
            ->to(route('marketing.page.contact').'#vy-contact-form')
            ->with('contact_sent', true);
    }
}
