<?php

declare(strict_types=1);

namespace App\Support\Help;

/**
 * THE ONE owner Help knowledge base (Wave D · Ask Vyrobel · Phase 1) — the real, curated FAQ content, extracted
 * from the Help page so the FAQ blade AND the grounded assistant read the SAME source (anti-drift; the assistant
 * can only ever ground on what the FAQ already tells owners). REAL content only — never invented policy, numbers,
 * or commitments (#39). Each article links a real owner screen. `keywords` are search synonyms derived from the
 * real content (never new claims).
 *
 * ⚠️ Anything account-specific ("what's MY payout") is deliberately ABSENT — the KB holds only generic help, so the
 * retriever returns nothing to build a specific figure from, and the assistant escalates to the real relay.
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
}
