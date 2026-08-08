{{-- Shared transactional-email layout (Phase 3 · Sub-gate 4). EMAIL-SAFE: table-based, inline CSS, web-safe font
     fallbacks, a bulletproof terracotta button — the SAME brand as the shipped owner-invite email (the frosted
     glass / flexbox of the on-screen design is not email-safe, so the brand is evoked with solid warm colours).
     Each email `@extends` this and fills `@section('content')` (the card body) + `@section('preheader')`.

     BRAND-AWARE (#98): the optional `$brand` (App\Support\Branding\PropertyBrand) recolours the mark + swaps the
     logo/name + toggles the "Powered by Vyrobel" footer. Fails OPEN — no `$brand` ⇒ the shipped Vyrobel default,
     byte-identical to before. A Mailable passes it via `->with(['brand' => PropertyBrand::for($property)])`.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php $brand = $brand ?? \App\Support\Branding\PropertyBrand::default(); @endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', 'Vyrobel')</title>
</head>
<body style="margin:0; padding:0; background-color:#F6EBE2; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    {{-- Preheader (hidden preview snippet) --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#F6EBE2; font-size:1px; line-height:1px;">@yield('preheader')</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F6EBE2;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="width:480px; max-width:480px;">
                    {{-- Brand (#98 — recoloured mark / logo / name from $brand; Vyrobel default when unset) --}}
                    <tr>
                        <td align="left" style="padding:4px 8px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    @if ($brand->logoUrl !== null)
                                        <td><img src="{{ $brand->logoUrl }}" alt="{{ $brand->displayName }}" width="38" height="38" style="width:38px; height:38px; border-radius:10px; display:block; object-fit:cover;"></td>
                                    @else
                                        <td style="width:38px; height:38px; background-color:{{ $brand->accent }}; border-radius:10px; text-align:center; vertical-align:middle; color:#ffffff; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:19px; font-weight:700;">{{ $brand->initial }}</td>
                                    @endif
                                    <td style="padding-left:10px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:17px; font-weight:600; color:#2B2320;">{{ $brand->displayName }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color:#FFFDFB; border:1px solid #EFE3D9; border-radius:18px; padding:34px 34px 30px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:20px 8px;">
                            @if ($brand->poweredBy)
                                <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#A99E95;">Powered by Vyrobel · hotel management, done properly.</p>
                            @endif
                            {{-- MARKETING vs TRANSACTIONAL footer. A marketing template fills `unsubscribe` with a
                                 signed opt-out link; a transactional one never does, and correctly keeps the
                                 service-message line with no unsubscribe (a guest cannot opt out of their own
                                 booking confirmation). --}}
                            @hasSection('unsubscribe')
                                @yield('unsubscribe')
                            @else
                                <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#A99E95;">You’re receiving this because it relates to your booking with us.</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
