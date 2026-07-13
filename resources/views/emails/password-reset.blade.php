{{-- Password-reset email (Auth Phase B · finishing sweep). EMAIL-SAFE, brand-consistent with the invite
     email: table layout + inline CSS + web-safe font fallbacks, a bulletproof terracotta button. The
     broker's tokened link is the only secret. --}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Reset your Vyrobel password</title>
</head>
<body style="margin:0; padding:0; background-color:#F6EBE2; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    {{-- Preheader (hidden preview snippet) --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#F6EBE2; font-size:1px; line-height:1px;">
        Reset your Vyrobel password — this link is single-use and expires in {{ $expiresMinutes }} minutes.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F6EBE2;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="width:480px; max-width:480px;">
                    {{-- Brand --}}
                    <tr>
                        <td align="left" style="padding:4px 8px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="width:38px; height:38px; background-color:#C2410C; border-radius:10px; text-align:center; vertical-align:middle; color:#ffffff; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:19px; font-weight:700;">V</td>
                                    <td style="padding-left:10px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:17px; font-weight:600; color:#2B2320;">Vyrobel</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color:#FFFDFB; border:1px solid #EFE3D9; border-radius:18px; padding:34px 34px 30px;">
                            <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Password reset</p>
                            <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Reset your Vyrobel password</h1>
                            <p style="margin:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $userName }},</p>
                            <p style="margin:0 0 24px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">We received a request to reset the password on your Vyrobel account. Choose a new password using the button below. If you didn’t ask for this, you can safely ignore this email — your password won’t change.</p>

                            {{-- Bulletproof button (solid terracotta; padded anchor works across clients incl. Outlook) --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Reset password</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:22px 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#8A8079;">This link is single-use and expires in <strong style="color:#4A4038;">{{ $expiresMinutes }} minutes</strong>.</p>
                            <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#8A8079;">If the button doesn’t work, paste this link into your browser:</p>
                            <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; word-break:break-all;"><a href="{{ $resetUrl }}" style="color:#C2410C; text-decoration:underline;">{{ $resetUrl }}</a></p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:20px 8px;">
                            <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#A69A92;">If you didn’t request a password reset, no action is needed.</p>
                            <p style="margin:6px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#A69A92;">Powered by Vyrobel</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
