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
     * The OWNER articles in ENGLISH — the corpus {@see HelpRetriever} scores and hands to Ask Vyrobel. It stays English
     * in every language on purpose (I18N-01): the retriever matches English words (`[a-z0-9]` tokens, English
     * stopwords and synonyms), and what the assistant is grounded on is the founder's decision, not a translation's.
     * What a PERSON reads comes from {@see translatedArticles()} / {@see translatedArticle()}, matched by `id`.
     *
     * @return list<array{id: string, keywords: string, question: string, answer: string, route: string, label: string}>
     */
    public static function articles(): array
    {
        return self::ownerArticles('en');
    }

    /**
     * The OWNER articles in the reader's language — the owner Help page's FAQ. Same ids, routes and keywords as
     * {@see articles()}; only the question, answer and label are translated.
     *
     * @return list<array{id: string, keywords: string, question: string, answer: string, route: string, label: string}>
     */
    public static function translatedArticles(): array
    {
        return self::ownerArticles(null);
    }

    /**
     * One OWNER article in the reader's language, by id (how the owner Help page shows Ask Vyrobel's citations), or
     * null for an id the catalogue does not define.
     *
     * @return array{id: string, keywords: string, question: string, answer: string, route: string, label: string}|null
     */
    public static function translatedArticle(string $id): ?array
    {
        foreach (self::translatedArticles() as $article) {
            if ($article['id'] === $id) {
                return $article;
            }
        }

        return null;
    }

    /**
     * @param  string|null  $locale  null = the reader's language
     * @return list<array{id: string, keywords: string, question: string, answer: string, route: string, label: string}>
     */
    private static function ownerArticles(?string $locale): array
    {
        return [
            [
                'id' => 'pay',
                'keywords' => 'payout paid payment paid bank account money earnings when receive disbursement transfer',
                'question' => __('php/support/help/help_knowledge_base.pay_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.pay_answer', [], $locale),
                'route' => 'owner.payout-method',
                'label' => __('php/support/help/help_knowledge_base.pay_label', [], $locale),
            ],
            [
                'id' => 'statement',
                'keywords' => 'statement gross revenue net commission fees adjustments breakdown read understand',
                'question' => __('php/support/help/help_knowledge_base.statement_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.statement_answer', [], $locale),
                'route' => 'owner.archive',
                'label' => __('php/support/help/help_knowledge_base.statement_label', [], $locale),
            ],
            [
                'id' => 'fees',
                'keywords' => 'fees commission charge management agreement terms cost rate percentage',
                'question' => __('php/support/help/help_knowledge_base.fees_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.fees_answer', [], $locale),
                'route' => 'owner.agreement',
                'label' => __('php/support/help/help_knowledge_base.fees_label', [], $locale),
            ],
            [
                'id' => 'tax',
                'keywords' => 'tax vat obligations tax id nif annual summary accountant withholding',
                'question' => __('php/support/help/help_knowledge_base.tax_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.tax_answer', [], $locale),
                'route' => 'owner.tax',
                'label' => __('php/support/help/help_knowledge_base.tax_label', [], $locale),
            ],
            [
                'id' => 'block',
                'keywords' => 'block dates own stay personal hold calendar unavailable close reserve',
                'question' => __('php/support/help/help_knowledge_base.block_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.block_answer', [], $locale),
                'route' => 'owner.calendar',
                'label' => __('php/support/help/help_knowledge_base.block_label', [], $locale),
            ],
            [
                'id' => 'unit',
                'keywords' => 'add unit new property listing request setup another',
                'question' => __('php/support/help/help_knowledge_base.unit_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.unit_answer', [], $locale),
                'route' => 'owner.add-unit',
                'label' => __('php/support/help/help_knowledge_base.unit_label', [], $locale),
            ],
            [
                'id' => 'refund',
                'keywords' => 'refund guest cancelled chargeback ledger balance affects deducted',
                'question' => __('php/support/help/help_knowledge_base.refund_question', [], $locale),
                'answer' => __('php/support/help/help_knowledge_base.refund_answer', [], $locale),
                'route' => 'owner.balance',
                'label' => __('php/support/help/help_knowledge_base.refund_label', [], $locale),
            ],
        ];
    }

    /**
     * The GUEST knowledge set, in the reader's language. Same contract as {@see articles()} plus a `category` for
     * grouping (translated) and its `category_code` (the same in every language — compare that, never the label).
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
     * @return list<array{id: string, keywords: string, question: string, answer: string, route: string, label: string, category_code: string, category: string}>
     */
    public static function guestArticles(): array
    {
        return [
            [
                'id' => 'checkin',
                'category_code' => 'before_you_arrive',
                'category' => self::category('before_you_arrive'),
                'keywords' => 'check in online checkin arrival details passport id documents pre check-in early',
                'question' => __('php/support/help/help_knowledge_base.guest_checkin_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_checkin_answer'),
                'route' => 'guest.trips',
                'label' => __('php/support/help/help_knowledge_base.guest_checkin_label'),
            ],
            [
                'id' => 'door-code',
                'category_code' => 'before_you_arrive',
                'category' => self::category('before_you_arrive'),
                // ⚠️ interpolated from the constant — see the docblock.
                'keywords' => 'door code key entry wifi password access arrival info directions get in',
                'question' => __('php/support/help/help_knowledge_base.guest_door_code_question'),
                'answer' => trans_choice('php/support/help/help_knowledge_base.guest_door_code_answer', ArrivalPack::LEAD_DAYS, ['count' => ArrivalPack::LEAD_DAYS]),
                'route' => 'guest.trips',
                'label' => __('php/support/help/help_knowledge_base.guest_door_code_label'),
            ],
            [
                'id' => 'pay-balance',
                'category_code' => 'payments',
                'category' => self::category('payments'),
                'keywords' => 'pay balance outstanding card payment stripe secure charge remaining owed',
                'question' => __('php/support/help/help_knowledge_base.guest_pay_balance_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_pay_balance_answer'),
                'route' => 'guest.payments',
                'label' => __('php/support/help/help_knowledge_base.guest_pay_balance_label'),
            ],
            [
                'id' => 'receipts',
                'category_code' => 'payments',
                'category' => self::category('payments'),
                'keywords' => 'receipt invoice vat statement pdf download document expenses proof',
                'question' => __('php/support/help/help_knowledge_base.guest_receipts_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_receipts_answer'),
                'route' => 'guest.payments',
                'label' => __('php/support/help/help_knowledge_base.guest_receipts_label'),
            ],
            [
                'id' => 'credit',
                'category_code' => 'payments',
                'category' => self::category('payments'),
                'keywords' => 'credit wallet promo code voucher discount balance apply redeem',
                'question' => __('php/support/help/help_knowledge_base.guest_credit_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_credit_answer'),
                'route' => 'guest.wallet',
                'label' => __('php/support/help/help_knowledge_base.guest_credit_label'),
            ],
            [
                'id' => 'change-booking',
                'category_code' => 'your_booking',
                'category' => self::category('your_booking'),
                // ⚠️ no refund window stated — it is per-property. See the docblock.
                'keywords' => 'change dates cancel cancellation amend modify move booking refund policy add ons extras',
                'question' => __('php/support/help/help_knowledge_base.guest_change_booking_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_change_booking_answer'),
                'route' => 'guest.trips',
                'label' => __('php/support/help/help_knowledge_base.guest_change_booking_label'),
            ],
            [
                'id' => 'contact',
                'category_code' => 'your_booking',
                'category' => self::category('your_booking'),
                'keywords' => 'contact host property reach message speak talk question ask help someone',
                'question' => __('php/support/help/help_knowledge_base.guest_contact_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_contact_answer'),
                'route' => 'guest.messages',
                'label' => __('php/support/help/help_knowledge_base.guest_contact_label'),
            ],
            [
                'id' => 'review',
                'category_code' => 'after_your_stay',
                'category' => self::category('after_your_stay'),
                'keywords' => 'review rating feedback stars write rate stay comment',
                'question' => __('php/support/help/help_knowledge_base.guest_review_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_review_answer'),
                'route' => 'guest.trips',
                'label' => __('php/support/help/help_knowledge_base.guest_review_label'),
            ],
            [
                'id' => 'account',
                'category_code' => 'your_account',
                'category' => self::category('your_account'),
                'keywords' => 'password change email profile details update account login security',
                'question' => __('php/support/help/help_knowledge_base.guest_account_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_account_answer'),
                'route' => 'guest.account',
                'label' => __('php/support/help/help_knowledge_base.guest_account_label'),
            ],
            [
                'id' => 'delete',
                'category_code' => 'your_account',
                'category' => self::category('your_account'),
                // ⚠️ states BOTH halves on purpose: it is reversible for 14 days, and financial records are
                // kept in anonymized form. `DO-G9-2` logged that the delete screen itself does not say the
                // second half; stating it here is accurate, and this entry must not drift from that fact.
                'keywords' => 'delete account close remove data gdpr erase privacy undo',
                'question' => __('php/support/help/help_knowledge_base.guest_delete_question'),
                'answer' => __('php/support/help/help_knowledge_base.guest_delete_answer'),
                'route' => 'guest.account',
                'label' => __('php/support/help/help_knowledge_base.guest_delete_label'),
            ],
        ];
    }

    /**
     * A guest category's name, by its code — in the reader's language, or in $locale. The help page's `?category=` value
     * is the ENGLISH name (what the links have always carried), so a link works in every language.
     */
    public static function category(string $code, ?string $locale = null): string
    {
        return match ($code) {
            'before_you_arrive' => __('php/support/help/help_knowledge_base.category_before_you_arrive', [], $locale),
            'payments' => __('php/support/help/help_knowledge_base.category_payments', [], $locale),
            'your_booking' => __('php/support/help/help_knowledge_base.category_your_booking', [], $locale),
            'after_your_stay' => __('php/support/help/help_knowledge_base.category_after_your_stay', [], $locale),
            'your_account' => __('php/support/help/help_knowledge_base.category_your_account', [], $locale),
            default => $code,
        };
    }
}
