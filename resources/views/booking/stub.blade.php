{{-- UNSTYLED contract stub (Phase 2 Step 7) — documents the public booking-engine data contract.
     No CSS / design system; the design pass replaces this. All state changes go through the JSON
     endpoints below (which route booking through AssignmentService + payment through FolioService).
     Advisory hold only — a quote reserves NO inventory; inventory is occupied at BOOK time. --}}
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Booking engine — contract stub — {{ $property->name }}</title></head>
<body>
    <h1>Booking engine — contract stub</h1>
    <p>Property: <strong>{{ $property->name }}</strong> (slug: {{ $property->slug }})</p>

    <h2>1. SEARCH — <code>GET /book/{property}/search</code></h2>
    <p>Query: <code>check_in</code>, <code>check_out</code>, <code>adults</code>, <code>children</code>.</p>
    <p>Returns <code>data: [{ room_type_id, name, kind, available, nights, rate_plan_id, currency, total_minor }]</code>
       — availability via AvailabilityService (read-only), never another tenant's inventory.</p>

    <h2>2. QUOTE / HOLD — <code>POST /book/{property}/quote</code></h2>
    <p>Body: <code>room_type_id</code>, <code>rate_plan_id</code>, <code>check_in</code>, <code>check_out</code>,
       <code>adults</code>, <code>children</code>, <code>email?</code>, <code>session_token?</code>.</p>
    <p>Returns <code>data: { quote_id, reference, status, check_in, check_out, currency, total_minor, held_until, expires_at }</code>.
       <strong>Advisory hold only</strong> — NOT counted in sold, reserves no inventory; two holds on the last unit both succeed.</p>

    <h2>3. BOOK + PAY — <code>POST /book/{property}/book</code></h2>
    <p>Body: <code>quote_id</code>, <code>payment_token</code> (Stripe payment_method), <code>email</code>,
       <code>first_name?</code>, <code>last_name?</code>.</p>
    <p>Routes the booking THROUGH AssignmentService (source=direct — re-checks availability under lock; rejects
       when full, never overbooks) and takes payment via the configured gateway through FolioService.</p>
    <p>Returns <code>data: { status, reservation_reference, payment_status, client_secret }</code>
       — status ∈ {confirmed, unavailable, already_converted}; <code>client_secret</code> present when SCA/3DS is required.</p>

    <h2>4. CONFIRMATION — <code>GET /book/{property}/confirmation/{quote}</code></h2>
    <p>Returns <code>data: { quote_id, reference, status, ..., reservation_reference }</code>.</p>

    <p><em>Abandoned capture: a held/quoted quote past <code>expires_at</code> becomes <code>status=abandoned</code>
       (recovery_status=pending) via the expiry sweep — still touching no inventory.</em></p>
</body>
</html>
