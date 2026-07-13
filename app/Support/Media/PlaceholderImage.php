<?php

declare(strict_types=1);

namespace App\Support\Media;

use RuntimeException;

/**
 * Generates a real, warm-gradient JPEG (via GD) for DEMO photo placeholders — an actual viewable file,
 * never a hotlinked or fabricated URL. Used by the demo seeder so the storefront/unit cards look
 * populated; production photos are real operator uploads.
 */
final class PlaceholderImage
{
    /**
     * @param  array{0:int,1:int,2:int}  $top  top gradient colour (RGB)
     * @param  array{0:int,1:int,2:int}  $bottom  bottom gradient colour (RGB)
     * @return string JPEG bytes
     */
    public static function jpeg(string $title, string $subtitle = '', array $top = [232, 112, 58], array $bottom = [194, 65, 12], int $width = 1200, int $height = 800): string
    {
        if (! \function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('GD extension is required to generate placeholder images.');
        }

        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            throw new RuntimeException('Could not allocate image.');
        }

        // Vertical warm gradient.
        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $t);
            $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $t);
            $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $t);
            $line = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, 0, $y, $width, $y, $line);
        }

        // Centered label text (built-in font — no TTF dependency), white on the gradient.
        $white = imagecolorallocate($img, 255, 255, 255);
        $font = 5;
        $tw = imagefontwidth($font) * strlen($title);
        imagestring($img, $font, (int) (($width - $tw) / 2), (int) ($height / 2 - 20), $title, $white);
        if ($subtitle !== '') {
            $sw = imagefontwidth($font) * strlen($subtitle);
            imagestring($img, $font, (int) (($width - $sw) / 2), (int) ($height / 2 + 6), $subtitle, $white);
        }

        ob_start();
        imagejpeg($img, null, 82);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }
}
