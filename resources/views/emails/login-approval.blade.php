{{-- Login-approval email (Wave B · GATE 4). EMAIL-SAFE: table layout + inline CSS + a bulletproof button. The link
     is single-use + expiring. If the recipient didn't just sign in, they should NOT click it + should change their
     password. --}}
<!DOCTYPE html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!} xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ __('emails/login-approval.title') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F6EBE2;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#F6EBE2; font-size:1px; line-height:1px;">
        {{ __('emails/login-approval.preheader', ['device' => $device]) }}
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F6EBE2;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="width:480px; max-width:480px;">
                    <tr>
                        <td align="left" style="padding:4px 8px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="width:38px; height:38px; background-color:#C2410C; border-radius:10px; text-align:center; vertical-align:middle; color:#ffffff; font-family:'Sora',Arial,sans-serif; font-size:19px; font-weight:700;">V</td>
                                    <td style="padding-left:10px; font-family:'Sora',Arial,sans-serif; font-size:17px; font-weight:600; color:#2B2320;">Vyrobel</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#FFFDFB; border:1px solid #EFE3D9; border-radius:18px; padding:34px 34px 30px;">
                            <p style="margin:0 0 6px; font-family:'Inter',Arial,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/login-approval.eyebrow') }}</p>
                            <h1 style="margin:0 0 14px; font-family:'Sora',Arial,sans-serif; font-size:22px; font-weight:700; line-height:1.25; color:#2B2320;">{{ __('emails/login-approval.heading') }}</h1>
                            <p style="margin:0 0 10px; font-family:'Inter',Arial,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ __('emails/login-approval.body', ['name' => $userName]) }}</p>
                            <p style="margin:0 0 16px; font-family:'Inter',Arial,sans-serif; font-size:13.5px; line-height:1.7; color:#6B5F55;">
                                <strong style="color:#4A4038;">{{ $device }}</strong><br>
                                @if ($ip){{ $ip }} · @endif{{ $when }}
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 14px;">
                                <tr><td style="background-color:#C2410C; border-radius:10px;">
                                    <a href="{{ $approveUrl }}" style="display:inline-block; padding:12px 26px; font-family:'Inter',Arial,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">{{ __('emails/login-approval.button') }}</a>
                                </td></tr>
                            </table>
                            <p style="margin:0; font-family:'Inter',Arial,sans-serif; font-size:12.5px; line-height:1.55; color:#5F5853;">{!! __('emails/login-approval.expiry_html') !!}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
