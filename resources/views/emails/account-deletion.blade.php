{{-- Guest account-deletion-scheduled email (Guest Portal · GATE 3, D2). Sent when a guest schedules the
     reversible 14-day erase. HONEST: it's a schedule, not an instant delete; the undo path is real (sign in →
     Undo). Email-safe: inline styles, table layout. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'account')

@section('title', __('emails/account-deletion.title'))
@section('preheader', trans_choice('emails/account-deletion.preheader', $graceDays, ['count' => $graceDays]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#8F3110;">{{ __('emails/account-deletion.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ __('emails/account-deletion.greeting', ['name' => $guestName, 'intro' => $intro]) }}</p>

    @if ($effective)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
            <tr>
                <td align="center" style="padding:20px 16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/account-deletion.scheduled_for') }}</span><br>
                    <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; color:#2B2320;">{{ $effective }}</span>
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0 0 8px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#4A4038;">{!! __('emails/account-deletion.undo_html') !!}</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">{{ __('emails/account-deletion.not_you') }}</p>
@endsection
