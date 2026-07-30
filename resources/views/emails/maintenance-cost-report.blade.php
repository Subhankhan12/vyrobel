{{-- Operator → self: the maintenance costs report PDF (#89). RAW-BLADE RULE: every `@` follows a space/newline/tag. --}}
@extends('emails.layout')

@section('title', 'Your maintenance costs report')
@section('preheader', 'Your maintenance costs report is attached as a PDF.')

@section('content')
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Maintenance costs</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Your maintenance costs report is attached as a PDF — total spend, spend by category, and the billable work orders (labour + parts).</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">Generated from your real resolved work orders.</p>
@endsection
