{{-- The INTERNAL contact-form notification (front-website Gate 7).

     ⚠️ THIS ONE GOES TO US, NOT TO A CUSTOMER — which is why it is deliberately plain. The branded
     `emails/layout` exists for mail a guest or owner receives; an internal notification does not need a
     gradient band, and a plain table renders identically in every client with nothing to drift.

     ⚠️ COLOURS ARE LITERALS, as in every email view here: custom properties are stripped by Outlook. The two
     used match the current tokens — #2B2320 = --vy-ink, #5F5853 = --vy-muted.

     ⚠️ EVERY VALUE IS ESCAPED. `{{ }}` not `{!! !!}`: the body is text a stranger typed, and it is reproduced
     here verbatim. `nl2br(e(...))` keeps the visitor's paragraphing without ever trusting their markup. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#F6EBE2;padding:28px 0;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600"
                   style="width:600px;max-width:600px;background:#FFFFFF;border-radius:14px;padding:28px 30px;">
                <tr>
                    <td style="font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#5F5853;padding-bottom:6px;">
                        {{ __('emails/marketing-contact.eyebrow') }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size:20px;font-weight:600;color:#2B2320;padding-bottom:18px;">
                        {{ $contact->name }}@if ($contact->company) — {{ $contact->company }}@endif
                    </td>
                </tr>

                <tr>
                    <td style="padding-bottom:18px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                               style="font-size:14px;color:#2B2320;">
                            <tr>
                                <td style="padding:5px 0;color:#5F5853;width:120px;">{{ __('emails/marketing-contact.reply_to') }}</td>
                                <td style="padding:5px 0;"><a href="mailto:{{ $contact->email }}" style="color:#B03808;">{{ $contact->email }}</a></td>
                            </tr>
                            @if ($contact->units_band)
                                <tr>
                                    <td style="padding:5px 0;color:#5F5853;">{{ __('emails/marketing-contact.units') }}</td>
                                    <td style="padding:5px 0;">{{ $contact->units_band }}</td>
                                </tr>
                            @endif
                            @if ($contact->topic)
                                <tr>
                                    <td style="padding:5px 0;color:#5F5853;">{{ __('emails/marketing-contact.topic') }}</td>
                                    <td style="padding:5px 0;">{{ $contact->topic }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding:5px 0;color:#5F5853;">{{ __('emails/marketing-contact.received') }}</td>
                                <td style="padding:5px 0;">{{ __('emails/marketing-contact.received_at', ['date' => (string) $contact->created_at?->translatedFormat(\App\Support\I18n\LocalDate::pattern('D, M j, Y g:i A'))]) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="border-top:1px solid #EDE4DC;padding-top:18px;font-size:15px;line-height:1.6;color:#2B2320;">
                        {!! nl2br(e($contact->message)) !!}
                    </td>
                </tr>

                {{-- ⚠️ THE ROW ID, so the record is findable even if this email is lost, filtered or
                     truncated. The database row is the system of record; this message is a notification. --}}
                <tr>
                    <td style="border-top:1px solid #EDE4DC;margin-top:18px;padding-top:16px;font-size:12px;color:#5F5853;">
                        {!! __('emails/marketing-contact.stored_as_html', ['id' => e($contact->id)]) !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
