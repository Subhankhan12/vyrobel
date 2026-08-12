{{-- SUPER ADMIN · ACCOUNT — the "confirm your new platform email" mail (sent to the NEW address). Opening the
     link is what switches the login email.

     DO-EMAIL-3 (2026-08-12) — BRANDING ONLY. This was the one email outside the warm brand system: a standalone
     dark slate/indigo document (#0C1322 / #111B2E / #6366F1 / #94A3B8) with its own <style>-less inline palette,
     no brand mark and no footer, while the other 19 render the warm Vyrobel card. It now extends the SHARED
     layout, exactly like its sibling `owner-email-change` — the identical job in the owner realm, which was
     already on-brand and is what this template was diffed against.

     ⚠️ THE CONTENT IS UNCHANGED AND MUST STAY SO. This is a SECURITY email: changing the login address is the
     classic account-takeover move, so every sentence the recipient relies on is re-flowed VERBATIM — the
     platform-admin framing, the new address, "your email stays the same until you do", the one-hour expiry, the
     CTA and its URL, and the "if you didn't request this" reassurance. Nothing was reworded, dropped, added or
     reordered, and the warning still sits directly under the CTA rather than being demoted into the footer.

     ⚠️ THE FOOTER IS OVERRIDDEN DELIBERATELY. The layout's default service line reads "You're receiving this
     because it relates to your booking with us." A platform admin has no booking, so inheriting it would ship a
     FALSE statement on a security email. The layout's own footer-override slot carries an accurate line
     instead. (That slot is named `unsubscribe`; it is really "the footer service line" — logged, not renamed.)

     Claims verified against the engine ({@see App\Http\Controllers\Admin\AccountController}): the link is
     `URL::temporarySignedRoute(..., now()->addHour())` — the one hour is REAL; and `email` is only written on
     verify, the new address being parked in `pending_email` until then — so "your email stays the same until
     you do" is REAL.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Confirm your new Vyrobel platform email')
@section('preheader', 'Confirm this address to finish changing your Vyrobel platform-admin login email.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Vyrobel · Platform admin</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Confirm your new email</h1>
    <p style="margin:0 0 12px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $adminName }},</p>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">You asked to change your platform-admin login email to <strong style="color:#2B2320;">{{ $newEmail }}</strong>. Confirm it by opening the link below — your email stays the same until you do. The link expires in one hour.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Confirm new email</a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">If you didn't request this, you can ignore this email — nothing changes without opening the link.</p>
@endsection

@section('unsubscribe')
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">You're receiving this because this address was requested as the login email for a Vyrobel platform-admin account.</p>
@endsection
