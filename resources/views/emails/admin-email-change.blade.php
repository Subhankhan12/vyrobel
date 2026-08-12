{{-- SUPER ADMIN · ACCOUNT — the "confirm your new platform email" mail (sent to the NEW address). Opening the
     link is what switches the login email. Plain, email-safe HTML. --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0; padding:0; background:#0C1322; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0C1322; padding:32px 16px;">
        <tr><td align="center">
            <table role="presentation" width="440" cellpadding="0" cellspacing="0" style="max-width:440px; width:100%; background:#111B2E; border:1px solid rgba(148,163,184,.18); border-radius:16px; overflow:hidden;">
                <tr><td style="padding:28px 28px 8px;">
                    <div style="display:inline-block; width:36px; height:36px; border-radius:10px; background:#6366F1; color:#fff; font-weight:700; font-size:19px; line-height:36px; text-align:center;">V</div>
                    <div style="color:#818CF8; font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; margin-top:8px;">Vyrobel · Platform admin</div>
                </td></tr>
                <tr><td style="padding:8px 28px 28px; color:#E8EDF6;">
                    <h1 style="font-size:20px; margin:8px 0 12px;">Confirm your new email</h1>
                    <p style="color:#94A3B8; font-size:14px; line-height:1.6; margin:0 0 8px;">Hi {{ $adminName }},</p>
                    <p style="color:#94A3B8; font-size:14px; line-height:1.6; margin:0 0 20px;">
                        You asked to change your platform-admin login email to <strong style="color:#E8EDF6;">{{ $newEmail }}</strong>.
                        Confirm it by opening the link below — your email stays the same until you do. The link expires in one hour.
                    </p>
                    <a href="{{ $verifyUrl }}" style="display:inline-block; background:linear-gradient(135deg,#5B5FE3,#4338CA); color:#fff; text-decoration:none; font-weight:600; font-size:14px; padding:12px 22px; border-radius:10px;">Confirm new email</a>
                    <p style="color:#94A3B8; font-size:12px; line-height:1.6; margin:22px 0 0;">
                        If you didn't request this, you can ignore this email — nothing changes without opening the link.
                    </p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
