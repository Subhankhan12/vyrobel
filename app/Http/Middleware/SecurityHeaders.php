<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The security response headers for every web response (audit 2026-08-07 · "no security headers of any kind").
 *
 * Before this, the application sent NONE — no CSP, no HSTS, no X-Frame-Options, no nosniff, no
 * Referrer-Policy — so the operator back office, owner portal and guest portal were all FRAMABLE, which
 * matters because those surfaces carry money actions (refunds, payout preferences, on-desk payments).
 *
 * ⚠️ THE POLICY BELOW IS BUILT FROM AN INVENTORY OF WHAT THE APP ACTUALLY LOADS, not from a template. A
 * copy-pasted "strict" CSP breaks this app in four specific ways, each verified against a real rendered page:
 *
 *   1. `style-src` MUST allow 'unsafe-inline'. The design system ships as inline <style> blocks in each shell
 *      plus ~97 inline `style="…"` attributes on a single operator page. Inline style ATTRIBUTES cannot carry
 *      a nonce or hash — there is no mechanism. Removing them is a design-system rewrite, not a header change.
 *   2. `script-src` MUST allow 'unsafe-inline' and 'unsafe-eval'. Livewire injects inline <script> blocks, and
 *      the Alpine it bundles evaluates directives with `new Function`. Tightening this needs Alpine's
 *      CSP-friendly build — a separate, larger piece of work. Honest note: with both relaxations, script-src
 *      is NOT meaningful XSS protection here; the real wins in this policy are frame-ancestors, object-src,
 *      base-uri and form-action.
 *   3. `frame-ancestors` MUST be 'self', NOT 'none'. The Email Previews page renders a SAME-ORIGIN iframe of
 *      `/app/email-previews/{key}` so the email's CSS cannot leak into /app. 'none' would make that framed
 *      response refuse to render and silently break the page. 'self' still blocks the actual threat —
 *      CROSS-origin framing, i.e. clickjacking. X-Frame-Options is SAMEORIGIN for the same reason.
 *   4. `img-src` MUST allow data: and blob:. The admin shell uses a data: SVG favicon, and the owner
 *      Add-Unit form previews uploads via `URL.createObjectURL` (a blob: URL).
 *
 * ⚠️ THE POLICY NOW PERMITS NO EXTERNAL ORIGIN AT ALL. Inter and Sora were self-hosted (audit F6-6), so
 * fonts.googleapis.com and fonts.gstatic.com were removed from style-src/font-src — they were the last two.
 * Every asset the app loads is same-origin, which is both the strongest posture and the simplest to reason
 * about: anything reaching for a third party is now a CSP violation by definition, not a judgement call.
 *
 * HSTS is sent ONLY over a genuinely secure connection: pinning HSTS against a plain-http host (a dev box on
 * localhost) would poison that hostname in the developer's browser for `max-age`, which is not undoable from
 * the server side.
 */
class SecurityHeaders
{
    /**
     * Content-Security-Policy, assembled from the inventory documented above.
     *
     * @return array<string, string>
     */
    private function policy(): array
    {
        return [
            'default-src' => "'self'",
            // See note 2 — Livewire inline scripts + Alpine's `new Function` evaluation.
            'script-src' => "'self' 'unsafe-inline' 'unsafe-eval'",
            // See note 1 — the inline design system + ~97 inline style attributes per page.
            'style-src' => "'self' 'unsafe-inline'",
            'font-src' => "'self' data:",
            // See note 4 — data: favicon, blob: upload previews.
            'img-src' => "'self' data: blob:",
            // Team chat (2026-10-06) — a voice note is played back from the browser's own recording (a blob: URL) before
            // it is sent; sent ones play from our own signed-in file route.
            'media-src' => "'self' blob:",
            'connect-src' => "'self'",
            // See note 3 — the same-origin email-preview iframe.
            'frame-src' => "'self'",
            'frame-ancestors' => "'self'",
            'form-action' => "'self'",
            'base-uri' => "'self'",
            'object-src' => "'none'",
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only decorate real web documents/redirects — never a file download, a PDF stream or a binary body,
        // where these headers are meaningless and a CSP could interfere with the viewer.
        $contentType = (string) $response->headers->get('Content-Type', '');
        $isDocument = $contentType === ''
            || str_contains($contentType, 'text/html')
            || str_contains($contentType, 'application/json');

        if (! $isDocument) {
            return $response;
        }

        $csp = collect($this->policy())
            ->map(fn (string $value, string $directive): string => $directive.' '.$value)
            ->implode('; ');

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Send the origin cross-site (useful for our own analytics/referrer logic) but never the path or
        // query — a booking or guest id in a Referer to a third party would be a real leak.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Nothing in this app uses these, so deny them outright rather than leaving them at the UA default — except the
        // microphone on the two Messages pages, where a voice note is recorded (team chat, 2026-10-06): allowed for this
        // site only, `(self)`, and nowhere else.
        $microphone = $request->routeIs('app.inbox', 'app.inbox.team') ? 'microphone=(self)' : 'microphone=()';
        $response->headers->set('Permissions-Policy', 'camera=(), '.$microphone.', geolocation=(), payment=(), usb=(), interest-cohort=()');

        // ⚠️ HSTS only when the connection is genuinely secure — see the class docblock.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
