{{-- Owner invite email (minimal — Step 3 ports the email-safe styled design). --}}
<p>Hi {{ $ownerName }},</p>
<p>You’ve been invited to the Vyrobel owner portal. Set your password to activate your account:</p>
<p><a href="{{ $acceptUrl }}">Set your password</a></p>
<p>Or paste this link into your browser:<br>{{ $acceptUrl }}</p>
<p>This invitation expires on {{ $expiresAt?->toDayDateTimeString() }}.</p>
<p>— Vyrobel</p>
