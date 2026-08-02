<?php

declare(strict_types=1);

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * A tiny inline-SVG QR generator (BaconQrCode's SVG backend — needs no ext-imagick, unlike the raster
 * backends). Encodes an arbitrary string (a URL, here) as a self-contained <svg> for embedding with {!! !!}.
 *
 * There is no fabrication in a QR: it encodes EXACTLY the string given. The New Reservation done-screen
 * passes a real, guard-protected guest-portal check-in URL — the QR is a convenience deep-link, never a
 * self-check-in token that bypasses the guest guard.
 */
final class QrSvg
{
    public static function of(string $data, int $size = 200): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($data);
    }
}
