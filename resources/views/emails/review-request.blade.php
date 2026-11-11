{{-- Guest review request (transactional-emails exact-match). Matched to "Review Request Email.html" — a star row
     that deep-links into the REAL review page with the tapped rating pre-selected. Email-safe: inline styles,
     table layout, bulletproof button. "We only ask once" is real (one review per stay, DB unique). No fabricated
     named host (#39). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/review-request.page_title'))
@section('preheader', __('emails/review-request.preheader', ['unit' => $unitName]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/review-request.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $intro }}</p>

    @if ($reviewUrl && ($blocks['tap_a_star'] ?? true))
        {{-- Star row — each star opens the review with that rating pre-selected --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 18px;">
            <tr>
                <td align="center" style="padding:20px 16px 8px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#4A4038;">{{ __('emails/review-request.how_rate') }}</span>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:0 16px 6px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        {{-- DO10-4 capstone (2026-08-12). ⚠️ These are NOT a rating DISPLAY — they are five
                             rating INPUTS, each deep-linking the review page with that rating pre-selected
                             (`route('guest.review', [..., 'rating' => $n])`). So filled-★-vs-empty-☆ would be
                             WRONG here: it would show a rating the guest has not given yet. Two real defects
                             were fixed instead:
                               • #E8A317 measured 2.14 on this card — a meaningful non-text CONTROL needs 3:1
                                 (WCAG 1.4.11). Now #8F5A06 = 4.17, the same star gold the shared component
                                 uses. ⚠️ DO-EMAIL-4: `--vy-amber-text` is defined twice with different values,
                                 so an email cannot safely "use the token" even in spirit — this is the brand
                                 amber as an email-safe literal, and the drift stays logged, not fixed here.
                               • each link's accessible name was the bare glyph "★", so a screen reader heard
                                 "star, link" five times with nothing to choose between them. Each now names
                                 the rating it applies, mirroring the web review form's `aria-label` pattern
                                 (guest/review.blade.php), with `title` alongside for clients that drop ARIA. --}}
                        @foreach ($starUrls as $n => $url)
                            <td style="padding:0 4px;"><a href="{{ $url }}" target="_blank" aria-label="{{ __('emails/review-request.rate_star', ['rating' => $n]) }}" title="{{ __('emails/review-request.rate_star', ['rating' => $n]) }}" style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:32px; color:#8F5A06; text-decoration:none; line-height:1;">★</a></td>
                        @endforeach
                    </tr></table>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:0 16px 18px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#5F5853;">{{ __('emails/review-request.tap_star') }}</span>
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
    @elseif (! empty($brand->replyTo))
        {{-- ⚠️ D-REVIEW-REPLY (2026-11-11, his "do all"): "reply to this email" is printed ONLY when a reply reaches the property — the email
             carries the property's Reply-To header exactly when the property has set one ({@see \App\Mail\Concerns\BrandsGuestMail::brandedEnvelope}).
             Without one a reply goes to Vyrobel's own sender, where nobody reads it, so the line was an invitation to write into the void. --}}
        <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#4A4038;">{{ __('emails/review-request.reply_instead') }}</p>
    @endif

    {{-- Stay detail (toggleable block #86) --}}
    @if ($blocks['stay_recap'] ?? true)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
            <tr>
                <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/review-request.stay') }}</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $stay }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/review-request.unit') }}</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $unitName }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:12px 16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/review-request.reference') }}</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $reference }}</span>
                </td>
            </tr>
        </table>
    @endif

    {{-- Team signature (toggleable block #86) — the honest property team, never a fabricated named person (#39) --}}
    @if ($blocks['signature'] ?? true)
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{{ __('emails/review-request.signature') }}</p>
    @endif
@endsection
