{{-- Guest review request (transactional-emails exact-match). Matched to "Review Request Email.html" — a star row
     that deep-links into the REAL review page with the tapped rating pre-selected. Email-safe: inline styles,
     table layout, bulletproof button. "We only ask once" is real (one review per stay, DB unique). No fabricated
     named host (#39). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Rate your stay')
@section('preheader', 'A quick rating of your stay at '.$unitName.' would mean the world.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Your feedback</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $intro }}</p>

    @if ($reviewUrl && ($blocks['tap_a_star'] ?? true))
        {{-- Star row — each star opens the review with that rating pre-selected --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 18px;">
            <tr>
                <td align="center" style="padding:20px 16px 8px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#4A4038;">How would you rate your stay?</span>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:0 16px 6px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        @foreach ($starUrls as $n => $url)
                            <td style="padding:0 4px;"><a href="{{ $url }}" target="_blank" style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:32px; color:#E8A317; text-decoration:none; line-height:1;">★</a></td>
                        @endforeach
                    </tr></table>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:0 16px 18px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#8A8079;">Tap a star — it opens your review, already halfway done</span>
                </td>
            </tr>
        </table>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                            <a href="{{ $reviewUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">{{ $button }}</a>
                        </td>
                    </tr></table>
                </td>
            </tr>
        </table>
    @else
        <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#4A4038;">Simply reply to this email and tell us about your stay — we read every message.</p>
    @endif

    {{-- Stay detail (toggleable block #86) --}}
    @if ($blocks['stay_recap'] ?? true)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
            <tr>
                <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Stay</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $stay }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Unit</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $unitName }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Reference</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $reference }}</span>
                </td>
            </tr>
        </table>
    @endif

    {{-- Team signature (toggleable block #86) — the honest property team, never a fabricated named person (#39) --}}
    @if ($blocks['signature'] ?? true)
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">You’re getting this because you stayed with us. We only ask once — thank you for choosing us.</p>
    @endif
@endsection
