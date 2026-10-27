<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\I18n\Locales;
use App\Support\I18n\RtlCss;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Right-to-left pages (I18N-01 — Arabic and Persian): mirrors the page's own CSS on the way out (App\Support\I18n\RtlCss) —
 * the <style> blocks and style="" attributes of an HTML page, and the HTML Livewire sends back when a component updates (so a
 * re-render never snaps back to left-to-right). Nothing happens for a left-to-right language: an English response passes
 * through byte for byte. The <html dir="rtl"> itself comes from Locales::htmlAttributes().
 */
class MirrorForRtl
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Locales::isRtl(App::getLocale()) || $response->isRedirection()) {
            return $response;
        }
        $type = (string) $response->headers->get('Content-Type');
        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        if (str_contains($type, 'text/html')) {
            $response->setContent(RtlCss::flipHtml($content));
        } elseif (str_contains($type, 'json') && str_contains($content, '"html"')) {
            $data = json_decode($content, true);
            if (is_array($data)) {
                array_walk_recursive($data, function (&$value, $key): void {
                    if ($key === 'html' && is_string($value)) {
                        $value = RtlCss::flipHtml($value);
                    }
                });
                $response->setContent((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }

        return $response;
    }
}
