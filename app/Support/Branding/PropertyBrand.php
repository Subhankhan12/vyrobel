<?php

declare(strict_types=1);

namespace App\Support\Branding;

use App\Models\Property;
use Illuminate\Support\Facades\Storage;

/**
 * The ONE resolved guest-facing brand for a property (task #98) — read by the shared email layout, the guest
 * portal shell, the public review page, and the guest Mailables' from-name/reply-to, so the settings LIVE
 * PREVIEW renders byte-identically to a real send (anti-drift; the mandate's "the preview = the real email").
 *
 * Fails OPEN: every getter falls back to today's global Vyrobel branding when the property has no brand set
 * (or no property is in scope), so an un-branded property renders exactly as before this feature existed.
 *
 * ⚠️ Postmark sender identity: the guest email FROM ADDRESS stays the fixed verified Vyrobel sender — a
 * per-property from-address would be refused without domain verification. Only the display FROM-NAME and the
 * REPLY-TO are per-property (both Postmark-safe). {@see fromName()} / {@see replyTo()}.
 */
final class PropertyBrand
{
    /** The shipped global Vyrobel accent (email `#C2410C`, guest shell `rgb(194,65,12)` — same colour). */
    public const DEFAULT_ACCENT = '#C2410C';

    public const DEFAULT_NAME = 'Vyrobel';

    private function __construct(
        public readonly string $accent,
        public readonly string $accentSoft,
        public readonly ?string $logoUrl,
        public readonly string $displayName,
        public readonly string $initial,
        public readonly string $fromName,
        public readonly ?string $replyTo,
        public readonly bool $poweredBy,
        public readonly bool $isCustom,
    ) {}

    /** Resolve the brand for a property (null → the Vyrobel default). */
    public static function for(?Property $property): self
    {
        if ($property === null) {
            return self::default();
        }

        $accentRaw = trim((string) $property->brand_accent);
        $accent = self::isHex($accentRaw) ? $accentRaw : self::DEFAULT_ACCENT;

        $name = trim((string) $property->name) !== '' ? (string) $property->name : self::DEFAULT_NAME;
        $fromName = trim((string) $property->brand_email_from_name);
        $replyTo = trim((string) $property->brand_reply_to);
        $logoPath = trim((string) $property->brand_logo_path);

        return new self(
            accent: $accent,
            accentSoft: self::soften($accent),
            logoUrl: $logoPath !== '' ? Storage::disk('public')->url($logoPath) : null,
            displayName: $name,
            initial: mb_strtoupper(mb_substr($name, 0, 1)),
            fromName: $fromName !== '' ? $fromName : $name,
            replyTo: $replyTo !== '' ? $replyTo : null,
            poweredBy: (bool) ($property->brand_powered_by ?? true),
            isCustom: self::isHex($accentRaw) || $logoPath !== '' || $fromName !== '' || $replyTo !== '',
        );
    }

    /** Today's global Vyrobel branding — the fail-open default. */
    public static function default(): self
    {
        return new self(
            accent: self::DEFAULT_ACCENT,
            accentSoft: self::soften(self::DEFAULT_ACCENT),
            logoUrl: null,
            displayName: self::DEFAULT_NAME,
            initial: 'V',
            fromName: self::DEFAULT_NAME,
            replyTo: null,
            poweredBy: true,
            isCustom: false,
        );
    }

    /** A `#rrggbb` (or `#rgb`) hex string check — never trust an arbitrary stored value into inline CSS. */
    private static function isHex(string $value): bool
    {
        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1;
    }

    /** A translucent tint of the accent for soft fills (8-digit hex alpha, ~20%). */
    private static function soften(string $hex): string
    {
        return (strlen($hex) === 7 ? $hex : self::DEFAULT_ACCENT).'33';
    }
}
