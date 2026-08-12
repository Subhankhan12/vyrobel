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

     ⚠️ THE FOOTER IS NOW A DECLARED CATEGORY, NOT A ONE-OFF OVERRIDE (DO-EMAIL-9, 2026-08-12). This template
     used to carry its own hand-written footer sentence, because the layout's single default read "…it relates to
     your booking with us." and a platform admin has no booking. That override was the right patch at the time and
     is now redundant: the layout derives the line from `footer_reason`, so declaring `email_change` gets an accurate
     sentence from the shared catalogue and one fewer copy of a claim can drift (§N.8f).

     Claims verified against the engine ({@see App\Http\Controllers\Admin\AccountController}): the link is
     `URL::temporarySignedRoute(..., now()->addHour())` — the one hour is REAL; and `email` is only written on
     verify, the new address being parked in `pending_email` until then — so "your email stays the same until
     you do" is REAL.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'email_change')

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

