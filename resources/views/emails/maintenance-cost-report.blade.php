{{-- Operator → self: the maintenance costs report PDF (#89). RAW-BLADE RULE: every `@` follows a space/newline/tag. --}}
@extends('emails.layout')
@section('footer_reason', 'account')

@section('title', __('emails/maintenance-cost-report.title'))
@section('preheader', __('emails/maintenance-cost-report.preheader'))

@section('content')
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ __('emails/maintenance-cost-report.heading') }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ __('emails/maintenance-cost-report.body') }}</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{{ __('emails/maintenance-cost-report.generated_from') }}</p>
@endsection
