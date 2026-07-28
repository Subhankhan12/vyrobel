{{-- Owner "Email to me" (completeness sweep · Wave A · GATE 4). EMAIL-SAFE: table layout + inline CSS + web-safe
     fonts (the frosted-glass brand is evoked with solid warm colours). Carries the owner's latest finalized
     STATEMENT as a PDF attachment; the body is a short cover note only — no figures duplicated (the PDF is the
     source of truth, so the two can never disagree). --}}
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your Vyrobel statement</title>
</head>
<body style="margin:0; padding:0; background-color:#F6EBE2; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#F6EBE2; font-size:1px; line-height:1px;">
        Your {{ $periodLabel }} statement is attached as a PDF.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F6EBE2;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="width:480px; max-width:480px;">
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

                    <tr>
                        <td style="background-color:#FFFDFB; border:1px solid #EFE3D9; border-radius:18px; padding:34px 34px 30px;">
                            <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Your statement</p>
                            <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $periodLabel }}</h1>
                            <p style="margin:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $ownerName }},</p>
                            <p style="margin:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">You asked us to email your latest statement — it’s attached to this message as a PDF, straight from your owner portal.</p>
                            <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#6B5F55;">You can always view every statement, booking summary and tax document under <strong style="color:#4A4038;">Documents</strong> in your portal.</p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:22px 8px 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; line-height:1.5; color:#9A8C7F;">
                            Sent to you because you requested it from your Vyrobel owner portal.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
