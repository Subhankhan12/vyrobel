<?php

declare(strict_types=1);

namespace App\Support\Help;

use App\Services\AskVyrobelService;
use App\Support\Guest\ArrivalPack;

/**
 * THE ONE Help knowledge base — the real, curated FAQ content, held in code so every surface that shows help reads
 * the SAME source (anti-drift). REAL content only — never invented policy, numbers, or commitments (#39). Each
 * article links a real screen. `keywords` are search synonyms derived from the real content (never new claims).
 *
 * ⚠️ Anything account-specific ("what's MY payout") is deliberately ABSENT — the KB holds only generic help, so the
 * retriever returns nothing to build a specific figure from, and the assistant escalates to the real relay.
 *
 * TWO AUDIENCES, ONE ENGINE (2026-08-12):
 *   • {@see articles()}      — OWNER (Wave D · Ask Vyrobel). Read by the owner Help blade AND the grounded
 *                              {@see AskVyrobelService} via {@see HelpRetriever}, so the displayed
 *                              FAQ and any AI answer cannot drift apart.
 *   • {@see guestArticles()} — GUEST. Read by the guest Help centre. Added rather than forked into a parallel
 *                              class, so both sets keep ONE shape and ONE set of rules (§N.8f).
 *
 * ⚠️ THE GUEST SET IS DERIVED FROM THE ENGINE, NOT FROM THE PROTOTYPE. Every answer below was checked against the
 * code that implements it before it was written, and anything that could not be stated truthfully for EVERY guest
 * was omitted or turned into a pointer rather than guessed — see the notes on the individual entries.
 */
final class HelpKnowledgeBase
{
    /**
     * @return list<array{id: string, keywords: string, question: string, answer: string, route: string, label: string}>
     */
    public static function articles(): array
    {
        return [
            [
                'id' => 'pay',
                'keywords' => 'payout paid payment paid bank account money earnings when receive disbursement transfer',
                'question' => 'When and how do I get paid?',
                'answer' => 'Payouts are created when your monthly statement is finalized and sent to your default bank account. Add or change that account on your Payout method page.',
                'route' => 'owner.payout-method',
                'label' => 'Payout method',
            ],
            [
                'id' => 'statement',
                'keywords' => 'statement gross revenue net commission fees adjustments breakdown read understand',
                'question' => 'How do I read my statement?',
                'answer' => 'Each statement breaks your gross revenue down to the net paid to you — room revenue, commission, fees and any adjustments. Open any statement from your archive.',
                'route' => 'owner.archive',
                'label' => 'Statements',
            ],
            [
                'id' => 'fees',
                'keywords' => 'fees commission charge management agreement terms cost rate percentage',
                'question' => 'What does the management team charge?',
                'answer' => 'Your commission and any fees are set in your management agreement. You can review the effective terms anytime.',
                'route' => 'owner.agreement',
                'label' => 'Agreement',
            ],
            [
                'id' => 'tax',
                'keywords' => 'tax vat obligations tax id nif annual summary accountant withholding',
                'question' => 'What are my tax and VAT obligations?',
                'answer' => 'Add your tax ID (payouts are held until it’s on file) and download an annual summary for your accountant in the Tax Center. Nothing here is tax advice.',
                'route' => 'owner.tax',
                'label' => 'Tax center',
            ],
            [
                'id' => 'block',
                'keywords' => 'block dates own stay personal hold calendar unavailable close reserve',
                'question' => 'Can I block dates for my own stay?',
                'answer' => 'Yes — hold dates on any of your units from the Block calendar. Blocked nights won’t accept guest bookings.',
                'route' => 'owner.calendar',
                'label' => 'Block calendar',
            ],
            [
                'id' => 'unit',
                'keywords' => 'add unit new property listing request setup another',
                'question' => 'How do I add another unit?',
                'answer' => 'Send a request with the details and your management team will set it up — a new unit affects availability and billing, so it’s created on their side.',
                'route' => 'owner.add-unit',
                'label' => 'Add a unit',
            ],
            [
                'id' => 'refund',
                'keywords' => 'refund guest cancelled chargeback ledger balance affects deducted',
                'question' => 'A guest was refunded — how does that affect me?',
                'answer' => 'A refund is attributed back through your ledger, so it shows on the statement for the period it happened. Your running position reflects it on your Balance.',
                'route' => 'owner.balance',
                'label' => 'Balance',
            ],
        ];
    }

    /**
     * The GUEST knowledge set. Same contract as {@see articles()} plus a `category` for grouping.
     *
     * ⚠️ EVERY ANSWER WAS VERIFIED AGAINST THE CODE THAT IMPLEMENTS IT. Where the true answer is
     * per-property or per-booking, the entry POINTS at the screen that shows the real value instead of
     * stating a number that would be right for some guests and wrong for others — a FAQ that misstates the
     * product is worse than no FAQ.
     *
     * ⚠️ THE ARRIVAL WINDOW IS INTERPOLATED FROM {@see ArrivalPack::LEAD_DAYS}, never typed as a literal. The
     * repo has already caught a docblock claiming "48h" against a constant that says otherwise (`DO-G4-1`),
     * and a FAQ is exactly where that drift would mislead a guest standing at a door.
     *
     * DELIBERATELY OMITTED (honest absence, #39):
     *   • a cancellation-refund WINDOW — it is per-property (`PropertyPolicy`; `DO-G12-1` records that even the
     *     storefront's single figure over-promises the moment two properties differ). The entry points at the
     *     booking's own Manage page, which shows that booking's real policy.
     *   • anything about support hours, a phone line or a reply time — none of that exists in the product, and
     *     inventing it is the same class of false claim as `DO-EMAIL-9`.
     *
     * @return list<array{id: string, keywords: string, question: string, answer: string, route: string, label: string, category: string}>
     */
    public static function guestArticles(): array
    {
        return [
            [
                'id' => 'checkin',
                'category' => 'Before you arrive',
                'keywords' => 'check in online checkin arrival details passport id documents pre check-in early',
                'question' => 'How do I check in online?',
                'answer' => 'Open your booking and complete Online check-in — it takes a couple of minutes and lets the property prepare for you. It records that you have checked in online; the front desk still completes the official check-in when you arrive.',
                'route' => 'guest.trips',
                'label' => 'Your trips',
            ],
            [
                'id' => 'door-code',
                'category' => 'Before you arrive',
                // ⚠️ interpolated from the constant — see the docblock.
                'keywords' => 'door code key entry wifi password access arrival info directions get in',
                'question' => 'When do I get my door code and Wi-Fi details?',
                'answer' => 'Your entry code and Wi-Fi details appear on the Arrival info page '.ArrivalPack::LEAD_DAYS.' days before check-in, and stay available until you check out. Before then the page tells you the date they unlock — it is not a delay you need to ask about.',
                'route' => 'guest.trips',
                'label' => 'Your trips',
            ],
            [
                'id' => 'pay-balance',
                'category' => 'Payments',
                'keywords' => 'pay balance outstanding card payment stripe secure charge remaining owed',
                'question' => 'How do I pay the balance on my booking?',
                'answer' => 'Open Payments and pay from there. You are taken to Stripe\'s secure payment page to enter your card — Vyrobel never sees or stores your card details, so you enter them each time you pay.',
                'route' => 'guest.payments',
                'label' => 'Payments',
            ],
            [
                'id' => 'receipts',
                'category' => 'Payments',
                'keywords' => 'receipt invoice vat statement pdf download document expenses proof',
                'question' => 'Where do I find my receipt or invoice?',
                'answer' => 'Open Payments, then choose Receipt on the booking you need. From there you can download the receipt, a VAT invoice and a statement of account as PDFs. If money was refunded on that booking, a refund receipt and credit note appear too.',
                'route' => 'guest.payments',
                'label' => 'Payments',
            ],
            [
                'id' => 'credit',
                'category' => 'Payments',
                'keywords' => 'credit wallet promo code voucher discount balance apply redeem',
                'question' => 'How does my credit work?',
                'answer' => 'Credit lives in your Wallet. You can redeem a code there, and apply credit to a booking you have — you can never spend more credit than you hold. Credit that has expired stops counting towards your balance.',
                'route' => 'guest.wallet',
                'label' => 'Wallet',
            ],
            [
                'id' => 'change-booking',
                'category' => 'Your booking',
                // ⚠️ no refund window stated — it is per-property. See the docblock.
                'keywords' => 'change dates cancel cancellation amend modify move booking refund policy add ons extras',
                'question' => 'Can I change or cancel my booking?',
                'answer' => 'Open the booking and use Manage booking to request a date change, add extras, or cancel. These are requests: the property confirms them, so nothing changes until they do. Your booking\'s own cancellation terms are shown on that page.',
                'route' => 'guest.trips',
                'label' => 'Your trips',
            ],
            [
                'id' => 'contact',
                'category' => 'Your booking',
                'keywords' => 'contact host property reach message speak talk question ask help someone',
                'question' => 'How do I contact the property?',
                'answer' => 'Send them a message — start a new one in Messages and the property answers you in the same place. It is the direct line to the people looking after your stay.',
                'route' => 'guest.messages',
                'label' => 'Messages',
            ],
            [
                'id' => 'review',
                'category' => 'After your stay',
                'keywords' => 'review rating feedback stars write rate stay comment',
                'question' => 'How do I leave a review?',
                'answer' => 'Once your stay is finished, a Review option appears on that trip. You can review a completed stay once.',
                'route' => 'guest.trips',
                'label' => 'Your trips',
            ],
            [
                'id' => 'account',
                'category' => 'Your account',
                'keywords' => 'password change email profile details update account login security',
                'question' => 'How do I change my password or details?',
                'answer' => 'Everything about your account lives on the Account page — your details, your password and your email preferences.',
                'route' => 'guest.account',
                'label' => 'Account',
            ],
            [
                'id' => 'delete',
                'category' => 'Your account',
                // ⚠️ states BOTH halves on purpose: it is reversible for 14 days, and financial records are
                // kept in anonymized form. `DO-G9-2` logged that the delete screen itself does not say the
                // second half; stating it here is accurate, and this entry must not drift from that fact.
                'keywords' => 'delete account close remove data gdpr erase privacy undo',
                'question' => 'What happens if I delete my account?',
                'answer' => 'Deleting is scheduled, not instant: you have 14 days to undo it from the Account page. After that your personal details are permanently removed. Records the property must keep for accounting — what was booked and paid — remain, with your personal details stripped out.',
                'route' => 'guest.account',
                'label' => 'Account',
            ],
        ];
    }
}
