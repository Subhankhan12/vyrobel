<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * THE COOKIE DISCLOSURE AND THE STORED CHOICE (front-website Gate 6, ruling D11).
 *
 * ⚠️⚠️ THE PROBLEM THIS SOLVES IS NOT "WE NEED A BANNER". Vyrobel sets exactly two cookies on an anonymous
 * visit — a session and a CSRF token — plus one opt-in security cookie after a two-factor challenge. There
 * is **no analytics, no marketing, no third-party cookie and no cross-site tracking anywhere in the
 * product**, which is verifiable and is asserted by the guard beside this class.
 *
 * ⚠️ SO A DIALOG OFFERING AN "ANALYTICS" OR "MARKETING" TOGGLE WOULD BE THE #39 SHAPE — a control that
 * promises and does nothing — and it would be WORSE than no dialog at all: a visitor who switches it off
 * believes they opted out, and the first analytics snippet added later without reading that choice
 * overrides them silently. That is the `MAIL_MAILER=log` failure family: no test fails, nothing logs, and
 * the harm is invisible until someone happens to look.
 *
 * ⚠️⚠️ WHAT IS BUILT INSTEAD. The notice states exactly what is set today, says plainly that there is
 * nothing non-essential to refuse, and records the visitor's answer anyway — **because the answer is
 * forward-binding**. {@see allows()} is the ONE function any future non-essential cookie must consult, and
 * `CookieConsentForwardGuardTest` (CI Gate 7) reds the build if a cookie is ever added outside
 * {@see CATALOGUE}, or if a non-essential one is added without a writer that calls `allows()`.
 * ⚠️ Without that guard this would be theatre; the guard is the feature.
 *
 * ⚠️ NO THIRD-PARTY CONSENT PLATFORM. The marketing site self-hosts its fonts precisely so that a visitor's
 * browser makes no third-party request (F6-6); a CMP would reintroduce exactly what that decision removed —
 * and it would do so in the name of privacy.
 *
 * ⚠️ NO JAVASCRIPT. The notice is a server-rendered form with two submit buttons. It works with scripting
 * off, cannot set anything before the visitor acts (the browser posts, the server sets the cookie), and has
 * no client state a persistent worker could leak between visitors.
 */
final class CookieConsent
{
    /** The stored choice. ⚠️ Strictly necessary by definition: it exists to honour a privacy preference. */
    public const COOKIE = 'vy_cookie_choice';

    /**
     * The disclosure version this choice was recorded against.
     *
     * ⚠️ GDPR expects consent to be DEMONSTRABLE, not merely collected — what was agreed, and when. Bumping
     * this when the disclosure materially changes invalidates old records and re-prompts, which is the
     * whole point of storing it: a choice made against a different set of facts is not a choice about these.
     */
    public const VERSION = 1;

    public const LIFETIME_DAYS = 365;

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    /**
     * ⚠️ EVERY COOKIE THIS APPLICATION SETS. Enumerated from the code and verified against a real response
     * by `CookieConsentForwardGuardTest` — the disclosure and the wire are two independent sources, and the
     * guard requires them to agree. A cookie added without an entry here reds the build.
     *
     * `category`: 'essential' — required to deliver a service the visitor asked for, or to keep it secure.
     *             Anything else is NON-ESSENTIAL and may not be set before {@see allows()} returns true.
     * `always`:   true if an ordinary anonymous visit sets it; false if it appears only when the visitor
     *             asks for something. ⚠️ A DATA FLAG, not a sentence to parse — the notice states two
     *             different counts and both are derived from here, so neither can drift from the table
     *             beneath them. The first version of the notice printed "3 cookies", which was neither the
     *             catalogue size (4) nor what a visitor actually gets (2). It was caught by looking at a
     *             screenshot, not by any assertion — a fabricated number in the chrome of the very gate
     *             about not fabricating things.
     *
     * @var array<string, array{category: string, always: bool, purpose: string, lifetime: string, set_when: string}>
     */
    public const CATALOGUE = [
        'vyrobel-session' => [
            'category' => 'essential',
            'always' => true,
            'purpose' => 'Keeps your session — the sign-in that follows you between pages, and the basket of a booking in progress.',
            'lifetime' => '2 hours',
            'set_when' => 'On your first page view.',
        ],
        'XSRF-TOKEN' => [
            'category' => 'essential',
            'always' => true,
            'purpose' => 'Protects every form on the site from cross-site request forgery. Without it a form you submit here could be forged from another site.',
            'lifetime' => '2 hours',
            'set_when' => 'On your first page view.',
        ],
        'vy_td' => [
            'category' => 'essential',
            'always' => false,
            'purpose' => 'Remembers a device you chose to trust, so two-factor authentication does not ask again on it.',
            'lifetime' => '30 days',
            'set_when' => 'Only if you tick “trust this device” at a two-factor prompt. Never otherwise.',
        ],
        'vy_lang' => [   // I18N-01 — App\Http\Middleware\SetVisitorLocale
            'category' => 'essential',
            'always' => false,
            'purpose' => 'Remembers the language you chose on our public pages, so the next page is in it too.',
            'lifetime' => '365 days',
            'set_when' => 'Only when you choose a language. Never otherwise.',
        ],
        self::COOKIE => [
            'category' => 'essential',
            'always' => false,
            'purpose' => 'Remembers the answer you gave on this notice, so it is not shown again and so the answer can be honoured.',
            'lifetime' => '365 days',
            'set_when' => 'Only when you answer this notice.',
        ],
    ];

    /**
     * ⚠️ THE FUNCTION THE FUTURE MUST CALL. Any non-essential cookie, script or beacon added later has to
     * pass through here, and CI Gate 7 exists to make that unavoidable.
     *
     * ⚠️ IT FAILS CLOSED. No stored choice, an unreadable one, or one recorded against an older disclosure
     * version all return FALSE for a non-essential category. Silence is not consent.
     */
    public static function allows(Request $request, string $category): bool
    {
        if ($category === 'essential') {
            return true;    // strictly necessary; no consent to seek and none to withhold
        }

        $choice = self::choice($request);

        return $choice !== null && $choice['choice'] === self::ACCEPTED;
    }

    /**
     * The recorded choice, or null when there is none this disclosure version can honour.
     *
     * @return array{choice: string, at: string, version: int}|null
     */
    public static function choice(Request $request): ?array
    {
        $raw = $request->cookie(self::COOKIE);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $value = (string) ($decoded['choice'] ?? '');
        $version = (int) ($decoded['version'] ?? 0);
        $at = (string) ($decoded['at'] ?? '');

        if (! in_array($value, [self::ACCEPTED, self::DECLINED], true)) {
            return null;
        }

        // ⚠️ A choice made against an older disclosure was a choice about different facts.
        if ($version !== self::VERSION) {
            return null;
        }

        return ['choice' => $value, 'at' => $at, 'version' => $version];
    }

    /** True when the visitor has not answered THIS version of the disclosure. */
    public static function shouldPrompt(Request $request): bool
    {
        return self::choice($request) === null;
    }

    /**
     * Build the cookie that records a choice.
     *
     * ⚠️ httpOnly: nothing on the page needs to read it, and the notice is rendered server-side — so it is
     * not exposed to script. ⚠️ `secure` follows the request rather than being hardcoded, so it is set on
     * HTTPS in production and still works on a plain-HTTP local host.
     */
    public static function record(string $choice, Request $request, ?CarbonImmutable $now = null): Cookie
    {
        $choice = $choice === self::ACCEPTED ? self::ACCEPTED : self::DECLINED;

        $payload = json_encode([
            'choice' => $choice,
            'version' => self::VERSION,
            'at' => ($now ?? CarbonImmutable::now())->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        return cookie(
            self::COOKIE,
            $payload,
            self::LIFETIME_DAYS * 24 * 60,
            '/',
            null,
            $request->isSecure(),
            true,        // httpOnly
            false,
            'lax',
        );
    }

    /**
     * The cookies disclosed on the notice, in catalogue order.
     *
     * @return array<string, array{category: string, purpose: string, lifetime: string, set_when: string}>
     */
    public static function disclosed(): array
    {
        return self::catalogue();
    }

    /**
     * ⚠️ THE CATALOGUE THROUGH A TYPED ACCESSOR, and the reason is not cosmetic. Read as a `const`, every
     * `category` is the literal 'essential' today, so static analysis correctly folds
     * {@see nonEssentialCategories()} to a constant [] and reports its body as dead — which it is, right
     * now, and which it must NOT be after the first non-essential cookie is added. The declared return
     * type says what the shape genuinely is: `category` is an open string, not the one value it happens
     * to hold. The const stays as the single place the data is written.
     *
     * @return array<string, array{category: string, always: bool, purpose: string, lifetime: string, set_when: string}>
     */
    public static function catalogue(): array
    {
        return self::CATALOGUE;
    }

    /**
     * The cookies an ordinary anonymous visit actually sets.
     *
     * @return array<string, array{category: string, always: bool, purpose: string, lifetime: string, set_when: string}>
     */
    public static function alwaysSet(): array
    {
        return array_filter(self::catalogue(), static fn (array $c): bool => $c['always']);
    }

    /**
     * The cookies that appear only when the visitor asks for something.
     *
     * @return array<string, array{category: string, always: bool, purpose: string, lifetime: string, set_when: string}>
     */
    public static function onRequestOnly(): array
    {
        return array_filter(self::catalogue(), static fn (array $c): bool => ! $c['always']);
    }

    /**
     * The NON-ESSENTIAL categories in use — ⚠️ empty today, and the notice says so in as many words.
     *
     * @return list<string>
     */
    public static function nonEssentialCategories(): array
    {
        $categories = [];

        foreach (self::catalogue() as $entry) {
            if ($entry['category'] !== 'essential' && ! in_array($entry['category'], $categories, true)) {
                $categories[] = $entry['category'];
            }
        }

        return $categories;
    }
}
