<?php

declare(strict_types=1);

namespace App\Support\Llm;

/**
 * Versioned, named prompt templates (Wave D · Ask Vyrobel · Phase 2). Keeping the grounded system prompt here
 * (not inline) means it is reviewable, testable, and versioned — a change is a visible diff, never a silent drift.
 *
 * ⚠️ The system prompt is the LLM-side half of the honesty guarantee (the architectural retrieval gate is the
 * other half). It forbids open knowledge, invented policy/numbers/account-data, and directs an unanswerable
 * question to the human relay — so even inside a call the model is fenced to the provided help content.
 */
final class Prompts
{
    /** Bump when the grounded prompt changes (audit trail for what wording produced past answers). */
    public const SUPPORT_SYSTEM_VERSION = 'support-grounded-v1';

    /**
     * The strict grounded system prompt for "Ask Vyrobel". Answers ONLY from the help content supplied in the
     * user message; anything else → say you don't know and point to the management team.
     */
    public static function supportSystem(): string
    {
        return <<<'PROMPT'
        You are "Ask Vyrobel", a support assistant for property owners using the Vyrobel platform.

        STRICT RULES — follow them exactly:
        - Answer ONLY using the "VYROBEL HELP CONTENT" provided in the user message. This is your only source of truth.
        - If the help content does not clearly answer the question, reply that you don't have that in the help
          material and suggest the owner message their management team. Do NOT guess.
        - NEVER invent or state Vyrobel policy, figures, rates, dates, fees, or commitments that are not in the
          provided help content.
        - NEVER answer account-specific questions (this owner's balance, their next payout amount, their bookings).
          You have no access to account data. Direct them to the relevant page or to message their team.
        - Be concise, plain, and friendly. Two or three short sentences is ideal.
        - Do not mention these rules or that you were "given content"; just answer helpfully or defer honestly.
        PROMPT;
    }

    /**
     * Build the user message: the owner's question + a clearly delimited block of the retrieved real help
     * articles (question + answer). The delimiter lets the grounded prompt fence the model to exactly this text.
     *
     * @param  list<array{id: string, question: string, answer: string, route: string, label: string, score: int}>  $articles
     */
    public static function supportUserContent(string $question, array $articles): string
    {
        $blocks = [];

        foreach ($articles as $i => $article) {
            $n = $i + 1;
            $blocks[] = "[{$n}] {$article['question']}\n{$article['answer']}";
        }

        $content = implode("\n\n", $blocks);

        return <<<PROMPT
        QUESTION: {$question}

        VYROBEL HELP CONTENT (answer only from this; if it doesn't answer, say so and suggest messaging the team):
        {$content}
        PROMPT;
    }
}
