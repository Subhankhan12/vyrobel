{{-- Operator MAINTENANCE SETTINGS (Phase 1 · Sub-gate 4) — a REAL typed save (POST → service → PRG), not
     toast-only. SLA targets + category→technician map are live on new orders; notification toggles + require-
     photo store the preference but their EFFECT waits on Phase 3 / ticket photos (shown honestly, never faked).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="maintenance" title="Maintenance settings" :subtitle="'Rules for new work orders'" :viewer="$viewer">

        <div style="margin-bottom: 16px;">
            <a href="{{ route('app.maintenance') }}" style="font-size: 12.5px; color: var(--vy-accent); text-decoration: none;">← Back to the board</a>
        </div>

        @if (session('maintenance_settings_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('maintenance_settings_notice') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('app.maintenance.settings.update') }}">
            @csrf

            {{-- Two-column arrangement matching the prototype: wide left (the rule editors) + a right rail
                 (the notify / policy toggles). One <form>, so Save still submits every field. --}}
            <div class="vy-detail-cols vy-fade">
            <div class="vy-stack">

            {{-- Resolution targets (SLA) --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Resolution targets</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">How quickly each priority should be resolved, in hours.</p>
                <div class="vyms-grid4">
                    @foreach (['urgent' => 'Urgent', 'high' => 'High', 'normal' => 'Normal', 'low' => 'Low'] as $key => $label)
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="sla_{{ $key }}">{{ $label }} (hours)</label>
                            <input id="sla_{{ $key }}" type="number" name="sla_{{ $key }}_hours" class="vy-input" min="1" max="720"
                                value="{{ $settings->{'sla_'.$key.'_hours'} }}">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Auto-assignment: category → technician --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Auto-assignment</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">Route a new order to a technician by its category.</p>
                <div class="vyms-grid2">
                    @foreach ($categories as $category)
                        @php $field = 'assignee_'.$category.'_id'; @endphp
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="{{ $field }}">{{ ucfirst($category) }}</label>
                            <select id="{{ $field }}" name="{{ $field }}" class="vy-input">
                                <option value="">Unassigned</option>
                                @foreach ($technicians as $tech)
                                    <option value="{{ $tech['id'] }}" @selected($settings->{$field} === $tech['id'])>{{ $tech['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            </div>{{-- /left stack --}}
            <div class="vy-stack">

            {{-- Out-of-order policy --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Out-of-order policy</h2>
                <label class="vyms-toggle">
                    <input type="checkbox" name="block_room_from_sale" value="1" @checked($settings->block_room_from_sale)>
                    <span><strong>Block the room from sale</strong> when a ticket takes a unit out of service<br><span class="vyms-sub">Already enforced — an out-of-service unit is excluded from availability.</span></span>
                </label>
                <label class="vyms-toggle">
                    <input type="checkbox" name="notify_front_desk" value="1" @checked($settings->notify_front_desk)>
                    <span><strong>Notify front desk</strong> when a unit goes out of service <span class="vyms-soon">dispatch soon</span><br><span class="vyms-sub">Preference stored; the alert fires once the notification system ships (Phase 3).</span></span>
                </label>
                <label class="vyms-toggle">
                    <input type="checkbox" name="require_photo_on_resolve" value="1" @checked($settings->require_photo_on_resolve)>
                    <span><strong>Require a photo on resolve</strong> <span class="vyms-soon">not yet live</span><br><span class="vyms-sub">Preference stored; takes effect once work-order photos ship.</span></span>
                </label>
            </div>

            {{-- Notifications --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Notify me about <span class="vyms-soon">dispatch soon</span></h2>
                <p style="font-size: 12px; color: var(--vy-faint); margin: 0 0 12px;">Your preferences are saved now; the alerts start firing when the notification system ships (Phase 3). Nothing is sent yet.</p>
                @foreach ([
                    'notify_new_urgent' => 'A new URGENT work order is opened',
                    'notify_out_of_service' => 'A unit is taken out of service',
                    'notify_parts_arrive' => 'Parts arrive for an order',
                    'notify_sla_breach' => 'A work order breaches its resolution target',
                    'notify_daily_digest' => 'A daily maintenance digest',
                ] as $field => $label)
                    <label class="vyms-toggle">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked($settings->{$field})>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            </div>{{-- /right stack --}}
            </div>{{-- /detail-cols --}}

            <div style="display: flex; justify-content: flex-end; margin-top: 22px;">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 12px 22px;">Save changes</button>
            </div>
        </form>

    </x-operator.shell>

    <style>
        .vyms-grid4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .vyms-grid2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .vyms-toggle { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; font-size: 13px; color: var(--vy-ink); cursor: pointer; }
        .vyms-toggle + .vyms-toggle { border-top: 1px solid var(--vy-hairline); }
        .vyms-toggle input { margin-top: 3px; }
        .vyms-sub { font-size: 11.5px; color: var(--vy-faint); }
        .vyms-soon { font-size: 9px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 1px 6px; margin-left: 4px; }
        @media (max-width: 860px) { .vyms-grid4, .vyms-grid2 { grid-template-columns: 1fr 1fr; } }
    </style>
</div>
