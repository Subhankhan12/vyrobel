{{-- Guest pre-arrival (Phase 3 · Sub-gate 4). Real directions; access code only when a real one exists.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Getting ready for your stay')
@section('preheader', 'Directions and everything you need for your arrival at '.$propertyName.'.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">You’re nearly here</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Getting ready for your stay</h1>
    <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, we can’t wait to welcome you to <strong style="color:#2B2320;">{{ $propertyName }}</strong> on {{ $checkIn }}. Here’s what you need for a smooth arrival.</p>

    @if ($directions)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 14px;">
            <tr>
                <td style="padding:16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Directions</span><br>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320;">{{ $directions }}</span>
                </td>
            </tr>
        </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Access</span><br>
                @if ($accessCode)
                    <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:20px; font-weight:700; letter-spacing:2px; color:#2B2320;">{{ $accessCode }}</span>
                @else
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#4A4038;">Your access code will be sent closer to your arrival.</span>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">Travelling with questions? Just reply — we’re happy to help.</p>
@endsection
