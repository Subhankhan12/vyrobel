<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Marketing\MarketingSitemap;
use Illuminate\Http\Response;

/** ⚠️ VY-29 — `/sitemap.xml`: the marketing pages a crawler may index ({@see MarketingSitemap}). No session, no cookie, no lastmod (no honest date exists). */
final class MarketingSitemapController
{
    public function __invoke(): Response
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach (MarketingSitemap::urls() as $url) {
            $lines[] = '  <url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
        }
        $lines[] = '</urlset>';

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
