<?php

declare(strict_types=1);

namespace App\Support\TeamChat;

use App\Models\Guest;
use App\Models\TeamMessage;
use Illuminate\Database\Eloquent\Builder;

/**
 * D-TC-PII (2026-11-11) — WHAT A GUEST'S ERASURE DOES TO TEAM CHAT. Staff may write about a guest in a team conversation
 * ("room 12's guest, Ines Walk, wants a late check-out — ines@…, +351 910 …"). A team message is not linked to a guest, so
 * erasure cannot find it by a key; it can only look for the guest's own words. {@see Guest::anonymize()} calls
 * {@see scrub()} BEFORE it nulls the guest's name, email and phone (they are needed to be found), inside its transaction.
 *
 * ⚠️ WHAT IT REMOVES — only what it can be SURE is this person, replacing each match with {@see REMOVED} and keeping
 * the rest of the message (so a colleague's conversation still reads):
 *   · the FULL NAME — first and last together, in either order ("Ines Walk", "Walk Ines", "Walk, Ines"), whatever the
 *     capitals and accents ("INÉS WALK" matches "Ines Walk"); a name written across a line break matches too;
 *   · the exact EMAIL address, whatever the capitals;
 *   · the PHONE number's digits, with any spaces, dots, dashes or brackets between them (and a leading + or 00) — and, when
 *     the stored number is long enough to be distinctive, its last nine digits alone, so a national form written with a
 *     trunk 0 ("0910 000 123" for "+351 910 000 123") is found too.
 *
 * ⚠️ WHAT IT DELIBERATELY DOES NOT DO — never a single name on its own. A first name or a surname matched alone would take
 * "Ana", "Anna", "Walk" out of every message that happens to contain them (false positives are other people's words,
 * deleted). So a guest with only one name recorded is scrubbed by email and phone only.
 *
 * ⚠️ THE HONEST LIMIT — it cannot catch "Ms Walk", a bare first name ("Ines wants…"), a nickname, a misspelling, "the guest
 * in 12", a phone written without its country code and shorter than the nine-digit tail, or a name that does not decompose
 * to plain letters (ł, ø, ß — compared as they are, so "Łukasz" does not match "Lukasz"). A person who needs more removed
 * is helped by the author's own delete, or by the founder's retention decision (OPEN.md D-TC-PII). It also leaves the
 * conversation's own TITLE and the bell entries that quote it untouched.
 *
 * Each changed message keeps its row, gets `redacted_at` (the page then says part of it was removed) and a fresh
 * `updated_at`, so a screen that already holds it receives the cleaned text on its next poll. Nothing the guest wrote is
 * ever copied anywhere: the caller records only HOW MANY messages were changed.
 */
final class TeamChatErasure
{
    /** What replaces a removed name, email or number — punctuation only, so it needs no language. */
    public const REMOVED = '[…]';

    /** A phone number is looked for only if it has at least this many digits (a shorter one would match ordinary numbers). */
    private const PHONE_MIN_DIGITS = 7;

    /** The last digits of a long number that are matched on their own (a national form without its country code). */
    private const PHONE_TAIL_DIGITS = 9;

    /**
     * Scrub this guest from the team messages of THEIR OWN workspace. Returns how many messages were changed.
     * Call it while the guest's name, email and phone are still on the model.
     */
    public function scrub(Guest $guest): int
    {
        $first = trim((string) $guest->first_name);
        $last = trim((string) $guest->last_name);
        $email = trim((string) $guest->email);
        $digits = (string) preg_replace('/\D+/', '', (string) $guest->phone);
        $digits = strlen($digits) >= self::PHONE_MIN_DIGITS ? $digits : '';

        $patterns = array_values(array_filter([
            $first !== '' && $last !== '' ? $this->namePattern($first, $last) : null,
            $email !== '' ? $this->emailPattern($email) : null,
            $digits !== '' ? $this->phonePattern($digits) : null,
        ]));
        if ($patterns === []) {
            return 0;
        }

        $changed = 0;
        $this->candidates($guest, $first, $last, $email, $digits !== '')->chunkById(200, function ($messages) use ($patterns, &$changed): void {
            /** @var TeamMessage $message */
            foreach ($messages as $message) {
                $text = (string) $message->body;
                $clean = $this->redact($text, $patterns);
                if ($clean === $text) {
                    continue;
                }
                $message->forceFill(['body' => $clean, 'redacted_at' => now()])->save();   // `updated_at` moves: open screens get it
                $changed++;
            }
        });

        return $changed;
    }

    /**
     * The messages worth reading in PHP: those a LIKE finds a trace in. LIKE is case- and accent-insensitive on this
     * database's collation, so it only narrows — the exact decision is the regular expression, in PHP.
     *
     * @return Builder<TeamMessage>
     */
    private function candidates(Guest $guest, string $first, string $last, string $email, bool $phone): Builder
    {
        return TeamMessage::query()->where('organization_id', $guest->organization_id)
            ->whereNull('deleted_at')->whereNotNull('body')
            ->where(function ($q) use ($first, $last, $email, $phone): void {
                if ($first !== '' && $last !== '') {
                    // a full name contains BOTH its names (a surname alone, or a first name alone, is never a reason to read it)
                    $q->orWhere(fn ($n) => $n->where('body', 'like', $this->like($first))->where('body', 'like', $this->like($last)));
                }
                if ($email !== '') {
                    $q->orWhere('body', 'like', $this->like($email));
                }
                if ($phone) {
                    $q->orWhere(fn ($n) => $n->whereRaw("body REGEXP '[0-9]'"));   // digits may be split by spaces: read every message that has any
                }
            });
    }

    /** @param list<string> $patterns */
    private function redact(string $text, array $patterns): string
    {
        // read the text with its accents as separate marks (é = e + ´), so "Inés" and "Ines" are the same letters
        $decomposed = \Normalizer::normalize($text, \Normalizer::FORM_D);
        $clean = is_string($decomposed) ? $decomposed : $text;
        foreach ($patterns as $pattern) {
            $out = preg_replace($pattern, self::REMOVED, $clean);
            $clean = is_string($out) ? $out : $clean;
        }
        if ($clean === (is_string($decomposed) ? $decomposed : $text)) {
            return $text;   // nothing found: the message is left byte for byte as it was
        }
        $composed = \Normalizer::normalize($clean, \Normalizer::FORM_C);

        return is_string($composed) ? $composed : $clean;
    }

    /** "First Last", "Last First" and "Last, First" — each name's letters with any accent marks, the names apart by white space. */
    private function namePattern(string $first, string $last): string
    {
        $a = $this->nameRegex($first);
        $b = $this->nameRegex($last);

        return '/(?<![\p{L}\p{N}\p{Mn}])(?:'.$a.'\s+'.$b.'|'.$b.'\s*,?\s+'.$a.')(?![\p{L}\p{N}\p{Mn}])/iu';
    }

    /** One name (maybe several words) as a pattern that ignores capitals and accents: each letter, then any marks on it. */
    private function nameRegex(string $name): string
    {
        $plain = (string) \Normalizer::normalize($name, \Normalizer::FORM_D);
        $plain = (string) preg_replace('/\p{Mn}+/u', '', $plain);
        $words = preg_split('/\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('\s+', array_map(function (string $word): string {
            $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return implode('', array_map(fn (string $c): string => preg_quote($c, '/').'\p{Mn}*', $letters));
        }, $words));
    }

    private function emailPattern(string $email): string
    {
        $plain = (string) \Normalizer::normalize($email, \Normalizer::FORM_D);

        return '/(?<![A-Za-z0-9._%+\-])'.preg_quote($plain, '/').'(?![A-Za-z0-9_\-]|\.[A-Za-z0-9])/iu';
    }

    /** The digits, with any of space . - ( ) between them; a long number's last nine digits are matched on their own too. */
    private function phonePattern(string $digits): string
    {
        $between = '[\s().\-]*';
        $join = fn (string $d): string => implode($between, str_split($d));
        $full = '(?:\+|00)?'.$join($digits);
        $tail = strlen($digits) > self::PHONE_TAIL_DIGITS ? '|0?'.$join(substr($digits, -self::PHONE_TAIL_DIGITS)) : '';

        return '/(?<![0-9])(?:'.$full.$tail.')(?![0-9])/u';
    }

    /** A LIKE pattern for "contains this", its own % _ and \ made literal. */
    private function like(string $needle): string
    {
        return '%'.addcslashes($needle, '%_\\').'%';
    }
}
