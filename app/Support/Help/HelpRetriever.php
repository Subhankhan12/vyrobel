<?php

declare(strict_types=1);

namespace App\Support\Help;

/**
 * Deterministic grounding retrieval over the {@see HelpKnowledgeBase} (Wave D · Ask Vyrobel · Phase 1).
 *
 * ⚠️ This is the ARCHITECTURAL grounding gate: the assistant may ONLY call an LLM with content this returns.
 * A question with no relevant help article scores below {@see self::MIN_SCORE} → an EMPTY result → the caller
 * escalates deterministically to the real relay WITHOUT ever invoking an LLM. So an out-of-scope or
 * account-specific question ("what's MY payout") can never be handed a figure to repeat (#39/#28).
 *
 * The scorer is a simple, free, fully-testable token-overlap over each article's keywords+question+answer —
 * no embeddings API (the corpus is 7 curated items). A future embeddings backend can slot behind this interface.
 */
final class HelpRetriever
{
    /**
     * Minimum weighted overlap score for an article to count as relevant. A single query token matching an
     * article's keywords or question (weight 2) clears it; a lone common word in an answer body (weight 1) does not.
     */
    public const MIN_SCORE = 2;

    /** Query words too common to carry topic meaning — dropped before scoring so they never match. */
    private const STOPWORDS = [
        'a', 'an', 'the', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'is', 'are', 'am', 'be', 'do', 'does',
        'did', 'how', 'what', 'when', 'where', 'why', 'who', 'which', 'can', 'could', 'will', 'would', 'should',
        'i', 'me', 'my', 'mine', 'you', 'your', 'yours', 'we', 'it', 'this', 'that', 'these', 'those', 'with',
        'about', 'get', 'got', 'have', 'has', 'from', 'if', 'so', 'as', 'at', 'by', 'any', 'some',
    ];

    /**
     * Top-k help articles relevant to $question, most-relevant first, each with its score. Empty if nothing
     * clears {@see self::MIN_SCORE} — the signal for the caller to escalate rather than answer.
     *
     * @return list<array{id: string, question: string, answer: string, route: string, label: string, score: int}>
     */
    public function retrieve(string $question, int $k = 3): array
    {
        $queryTokens = $this->tokenize($question);

        if ($queryTokens === []) {
            return [];
        }

        $scored = [];

        foreach (HelpKnowledgeBase::articles() as $article) {
            $score = $this->score($queryTokens, $article);

            if ($score >= self::MIN_SCORE) {
                $scored[] = [
                    'id' => $article['id'],
                    'question' => $article['question'],
                    'answer' => $article['answer'],
                    'route' => $article['route'],
                    'label' => $article['label'],
                    'score' => $score,
                ];
            }
        }

        // Deterministic order: score desc, then id asc to break ties stably.
        usort($scored, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'] ?: strcmp($a['id'], $b['id']);
        });

        return array_slice($scored, 0, max(0, $k));
    }

    /**
     * Weighted overlap: a distinct query token found in the article's keywords or question scores 2 (a strong
     * topical hit); found only in the answer body scores 1. Each query token counts at most once.
     *
     * @param  list<string>  $queryTokens
     * @param  array{id: string, keywords: string, question: string, answer: string, route: string, label: string}  $article
     */
    private function score(array $queryTokens, array $article): int
    {
        $strong = $this->tokenize($article['keywords'].' '.$article['question']);
        $weak = $this->tokenize($article['answer']);

        $strongSet = array_flip($strong);
        $weakSet = array_flip($weak);

        $score = 0;

        foreach ($queryTokens as $token) {
            if (isset($strongSet[$token])) {
                $score += 2;
            } elseif (isset($weakSet[$token])) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * Lowercase, split on non-alphanumerics, drop stopwords + very short tokens, and lightly singularize
     * (trailing "s") so "payouts"/"payout" and "fees"/"fee" match. Deterministic; returns distinct tokens.
     *
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $lower = mb_strtolower($text);
        $parts = preg_split('/[^a-z0-9]+/', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) < 2 || in_array($part, self::STOPWORDS, true)) {
                continue;
            }

            // Light singularization: "payouts" → "payout"; leave short words ("is" already gone) alone.
            if (mb_strlen($part) > 3 && str_ends_with($part, 's')) {
                $part = mb_substr($part, 0, -1);
            }

            $tokens[$part] = true;
        }

        return array_keys($tokens);
    }
}
