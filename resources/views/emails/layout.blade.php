{{-- Shared transactional-email layout (Phase 3 · Sub-gate 4). EMAIL-SAFE: table-based, inline CSS, web-safe font
     fallbacks, a bulletproof terracotta button (the frosted glass / flexbox of the on-screen design is not
     email-safe, so the brand is evoked with solid warm colours).
     Each email `@extends` this and fills `@section('content')` (the card body) + `@section('preheader')`.

     ⚠️ THIS NO LONGER MATCHES THE OWNER-INVITE EMAIL. `emails/owner-invite.blade.php` is a standalone document
     (it never extended this layout) and has been rebuilt to its own prototype: a 600px card with a gradient
     brand BAND and a footer BAND, versus this layout's 480px card with a plain brand lockup.

     CONTRAST (DO-INV-4, closed 2026-08-12). This layout's footer previously shipped a grey measuring 2.24 on
     the #F6EBE2 wash, and it carries the "Powered by Vyrobel" + "why you're receiving this" lines on every
     template that extends this file — so one declaration failed AA on 15 emails at once. Both footer lines are
     now #6C645F (4.94 on the #F6EBE2 wash). ⚠️ THE PLATES THIS FILE DEFINES, and what clears AA on them:
       body / footer wash  #F6EBE2  → #2B2320 13.12 · #5F5853 5.95 · #6C645F 4.94
       card                #FFFDFB  → #2B2320 15.17 · #5F5853 6.88 · #6C645F 5.71
     A new muted colour in this layout or in any template that extends it must be measured against ITS OWN
     plate — an inset panel (#FCF7F2 / #F8F1EA / #FBF1E9 / #F2E8DF) is darker than the card and moves the
     number. EMAIL-SAFE: literal hex only — never a --vy-* custom property (clients strip them).
     Guarded by tests/Feature/EmailContrastSweepTest.php; see docs/design/MISSING-BACKEND-BY-PAGE.md §EMAIL-SWEEP.

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
                                <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">Powered by Vyrobel · hotel management, done properly.</p>
                            @endif
                            {{-- ⚠️ THE FOOTER REASON (DO-EMAIL-9 · restructure, 2026-08-12).

                                 This line used to be a single hardcoded default — "…it relates to your booking
                                 with us." — inherited by every template that extends this file. That is FALSE on
                                 an account or security email: an owner changing their LOGIN EMAIL has no booking,
                                 and `owner-email-change` shipped exactly that claim, live. A shared layout's
                                 default copy is CONTENT, and it can be false (§N.14.8).

                                 ⚠️ REWORDING THE DEFAULT WOULD NOT HAVE FIXED IT. Whatever single sentence sat
                                 here would be wrong for some category, and the next email added would inherit it
                                 silently. So the reason is now DERIVED from the email's own category: a template
                                 declares a KEY, never a sentence, and the wording lives here. A false reason is
                                 not something a template can type — the same structural honesty the guest support
                                 reply-norm uses (a key into a catalogue, not free text).

                                 ⚠️ AND THE DEFAULT IS GENERIC-TRUE. A template that declares nothing gets a line
                                 that is true of ANY recipient, so a forgetful new email still cannot ship a false
                                 claim. An unrecognised key falls to the same safe line rather than rendering the
                                 raw key. That is the whole difference between this and a reword. --}}
                            @php
                                $vyReasons = [
                                    'booking' => 'You’re receiving this because it relates to your booking with us.',
                                    'security' => 'You’re receiving this because of a security change on your account.',
                                    // ⚠️ A NOMINATED ADDRESS, not necessarily an account holder. An email-change
                                    // confirmation is sent to the address being ADDED, whose reader may have no
                                    // account at all yet — so neither the booking line nor "your account" is
                                    // safely true of them. This says only what is certain: someone gave us this
                                    // address. (It is also what the admin template's one-off override said, kept
                                    // rather than flattened into the generic security line.)
                                    'email_change' => 'You’re receiving this because this address was given as the login email for a Vyrobel account.',
                                    'account' => 'You’re receiving this because it relates to your account with us.',
                                    'owner' => 'You’re receiving this because you own a property we manage.',
                                    'waitlist' => 'You’re receiving this because you asked to hear about these dates.',
                                    // The safe default: true of anyone we can legitimately email.
                                    'generic' => 'You’re receiving this because you have an account with us.',
                                ];
                                $vyReasonKey = trim($__env->yieldContent('footer_reason'));
                            @endphp
                            {{-- MARKETING vs TRANSACTIONAL. A marketing template fills `footer_marketing` with its
                                 own opt-in line AND a real signed opt-out link; a transactional one never does —
                                 a recipient cannot unsubscribe from a security notice about their own account, so
                                 offering them a link that pretends otherwise would be its own false claim. --}}
                            @hasSection('footer_marketing')
                                @yield('footer_marketing')
                            @else
                                <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">{{ $vyReasons[$vyReasonKey] ?? $vyReasons['generic'] }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
