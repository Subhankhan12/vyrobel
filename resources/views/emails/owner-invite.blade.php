{{-- Owner invite email — matched to the "Owner Invite Email" prototype in EMAIL-SAFE form.

     EMAIL IS A HOSTILE RENDER TARGET, so the realm's disciplines change shape here:
     • NO `--vy-*` custom properties — email clients (Outlook especially) strip them. Every colour is a
       LITERAL that MUST equal the current token VALUE, or it silently drifts from the app:
         #2B2320 = --vy-ink · #5F5853 = --vy-muted · #6C645F = --vy-faint · #C2410C = --vy-accent
         #E8703A = --vy-accent-3 · #B03808 = --vy-accent-text · #F6EBE2 = --vy-wash
         gradient 140deg #E8703A→#C2410C = --vy-grad.
     • Nested <table> + inline styles only. No flex/grid, no <style> block, no JS, no backdrop-filter —
       the prototype's frosted glass cannot render, so the brand is carried by solid warm plates and one
       real gradient band.
     • TWO deliberate a11y divergences from the prototype, both measured, both surfaced in
       docs/design/MISSING-BACKEND-BY-PAGE.md §O-Invite:
       (1) the CTA's SOLID fallback is #C2410C (white 15px on it = 5.21), with the brand gradient layered
           over it as background-image for clients that support one.
           ⚠️ SUPERSEDED BY FOUNDER DECISION DO-RESTORE-1(a) (2026-08-14). DO-A11Y-3 had moved this light
           stop to #D14102 (white 4.70) because #E8703A gives white only 3.08 — below the 4.5 small-text bar.
           The founder reviewed the measured numbers and ruled to RESTORE the wireframe #E8703A, ACCEPTING
           white-on-CTA at ~3.08 as a deliberate brand decision. This is an ACCEPTED EXCEPTION, not a defect —
           do not "fix" it back. (An AA-clearing orange does exist, e.g. #D14102; the choice was brand, not
           feasibility.)
       (2) the prototype's muted greys (#8A8079 / #9C9089 / #A69A92 / #B7ADA4) are the pre-remediation
           palette and fail AA; they are rendered at the token values instead.
     • The prototype's per-feature line icons are inline SVG (unsupported in Outlook/Gmail), so the
       shipped check glyph is kept inside the prototype's tile geometry.
     • The prototype's "Get help" footer link has no honest destination — /owner/help is authed and the
       invitee has no account yet, and no public support URL is configured. It is omitted rather than
       pointed at a link that bounces to login. "Sign in" is the real public /login route.
     • The single-use token is the only secret in the link.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>You’re invited to {{ $orgName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F6EBE2; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    {{-- Preheader (hidden preview snippet) --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#F6EBE2; font-size:1px; line-height:1px;">
        Set your password to activate your Vyrobel {{ $accountLabel }} — this invitation expires in {{ \App\Services\OwnerInviteService::EXPIRY_DAYS }} days.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F6EBE2;">
        <tr>
            <td align="center" style="padding:32px 16px 40px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#FFFDFB; border:1px solid #EFE3D9; border-radius:20px;">

                    {{-- Brand header band. The gradient is the real --vy-grad. Contrast here was measured on
                         RENDERED PIXELS, not getComputedStyle — a computed probe scores white against the
                         `background-color` FALLBACK (#C2410C, 5.18) and never sees the gradient. The band's
                         lightest rendered pixel is #E66D38 → white = 3.18. The lockup is therefore set at
                         weight 700 so it is unambiguously WCAG "large text" (≥18.66px bold, 3.0 threshold);
                         at the prototype's 600 the large-text classification is arguable and the run would
                         sit under the 4.5 normal-text bar. The eyebrow IS normal-size text, which no point of
                         this gradient can carry at 4.5, so it sits on a solid #B03808 plate (6.15). --}}
                    <tr>
                        <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; background-image:linear-gradient(140deg, #E8703A, #C2410C); border-radius:20px 20px 0 0; padding:30px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="width:40px; height:40px; background-color:#D9662E; border-radius:12px; text-align:center; vertical-align:middle; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:21px; font-weight:700; color:#FFFFFF;">V</td>
                                    <td style="padding-left:11px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:20px; font-weight:700; letter-spacing:-.01em; color:#FFFFFF;">Vyrobel</td>
                                </tr>
                            </table>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin-top:10px;">
                                <tr>
                                    <td bgcolor="#B03808" style="background-color:#B03808; border-radius:20px; padding:5px 12px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:#FFFFFF;">{{ $eyebrow }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding:32px;">

                            {{-- Invited-by chip --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8F1EA; border:1px solid #EDDFD3; border-radius:14px; margin:0 0 22px;">
                                <tr>
                                    <td width="34" valign="middle" style="width:34px; padding:12px 0 12px 14px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="width:34px; height:34px; background-color:#C2410C; border-radius:10px; text-align:center; vertical-align:middle; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#FFFFFF;">&#8962;</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" style="padding:12px 14px 12px 11px;">
                                        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:#6C645F;">Invited by</p>
                                        <p style="margin:1px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; font-weight:600; color:#2B2320;">{{ $operator }}</p>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:0; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:25px; font-weight:600; letter-spacing:-.02em; line-height:1.25; color:#2B2320;">{{ $headline }}</h1>
                            <p style="margin:12px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14.5px; line-height:1.65; color:#5F5853;">Hi {{ $ownerName }}, {{ $intro }}</p>

                            {{-- Role-aware feature panel (owner: "In your portal" · accountant: "What you can
                                 access" · manager: "In the operator app") — honest descriptions, no per-account
                                 data. The prototype's per-feature line icons are inline SVG and would not render
                                 in Outlook/Gmail, so the shipped check glyph keeps the tile geometry. --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8F4EF; border:1px solid #EBE1D6; border-radius:16px; margin:22px 0 0;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <p style="margin:0 0 14px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#6C645F;">{{ $sectionTitle }}</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            @foreach ($features as $feature)
                                                <tr>
                                                    <td width="34" valign="middle" style="width:34px; padding:0 0 {{ $loop->last ? '0' : '13px' }};">
                                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                            <tr>
                                                                <td style="width:34px; height:34px; background-color:#F3DFD2; border-radius:10px; text-align:center; vertical-align:middle; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; font-weight:700; color:#B03808;">&#10003;</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td valign="middle" style="padding:0 0 {{ $loop->last ? '0' : '13px' }} 12px;">
                                                        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; font-weight:600; color:#2B2320;">{{ $feature['title'] }}</p>
                                                        <p style="margin:1px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; line-height:1.45; color:#5F5853;">{{ $feature['desc'] }}</p>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Primary CTA — full-width bulletproof button. The bgcolor fallback is SOLID
                                 #C2410C (white 5.21); the gradient rides over it as background-image. Its
                                 light stop is the wireframe #E8703A, where white measures ~3.08 — sub-AA and
                                 ACCEPTED per DO-RESTORE-1(a). --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;">
                                <tr>
                                    <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                                        <a href="{{ $acceptUrl }}" target="_blank" style="display:block; padding:16px 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#FFFFFF; text-decoration:none; border-radius:30px; text-align:center;">Set your password</a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Plaintext-link fallback + expiry --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8F4EF; border:1px solid #EBE1D6; border-radius:14px; margin:18px 0 0;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.55; color:#5F5853;">Or paste this link into your browser:</p>
                                        <p style="margin:4px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; line-height:1.5; word-break:break-all;"><a href="{{ $acceptUrl }}" style="color:#B03808; text-decoration:none;">{{ $acceptUrl }}</a></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 16px 14px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="border-top:1px solid #E3D6C9; padding-top:12px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.55; color:#5F5853;">This invite is single-use and expires on <strong style="color:#2B2320;">{{ $expiresAt?->toFormattedDayDateString() }}</strong>.</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer band — the honest "if you didn't expect this", the real public sign-in route, and
                         the "why you got this" line. Contrast here is the priority, not an afterthought. --}}
                    <tr>
                        <td bgcolor="#F4ECE4" style="background-color:#F4ECE4; border-top:1px solid #EADFD4; border-radius:0 0 20px 20px; padding:22px 32px;">
                            <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.6; color:#5F5853;">{{ $ignoreNote ?? 'If you didn’t expect this, you can safely ignore this email — no account is created until you set a password.' }}</p>
                            <p style="margin:8px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.6;"><a href="{{ url('/login') }}" style="color:#5F5853; font-weight:600; text-decoration:underline;">Sign in</a></p>
                            <p style="margin:12px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; line-height:1.6; color:#6C645F;">Vyrobel · hospitality, warmly run · You’re receiving this because {{ $operator }} {{ $roleReason }}</p>
                            <p style="margin:4px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; line-height:1.6; color:#6C645F;">{{ $footerNote ?? 'Sent automatically when an operator invites someone to their organization.' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
