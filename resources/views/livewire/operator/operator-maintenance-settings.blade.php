{{-- Operator MAINTENANCE SETTINGS (Phase 1 · Sub-gate 4; rebuilt to the wireframe in #89) — a REAL typed save
     (POST → service → PRG), not toast-only. SLA targets drive a REAL breach on the board; the auto-assignment
     master toggle + category map REALLY route a new order; the Technicians panel shows real open-order load.
     Honest where deferred: maintenance alerts aren't dispatched yet, require-photo isn't enforced yet — shown,
     never faked. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div x-data="{ autoAssign: @js((bool) $settings->auto_assign_enabled) }">
    <x-operator.shell active="maintenance" :title="__('livewire/operator/operator-maintenance-settings.page_title')" :subtitle="__('livewire/operator/operator-maintenance-settings.page_subtitle')" :viewer="$viewer">

        <div style="margin-bottom: 16px;">
            <a href="{{ route('app.maintenance') }}" style="font-size: 12.5px; color: var(--vy-accent); text-decoration: none;">{{ __('livewire/operator/operator-maintenance-settings.back_to_board') }}</a>
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

            {{-- Resolution targets (SLA) — REAL: MaintenanceSla computes a live breach from these, shown on the board --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-maintenance-settings.sla_heading') }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">{!! __('livewire/operator/operator-maintenance-settings.sla_intro') !!}</p>
                <div class="vyms-grid4">
                    @foreach ([
                        'urgent' => __('livewire/operator/operator-maintenance-settings.sla_urgent_hours'),
                        'high' => __('livewire/operator/operator-maintenance-settings.sla_high_hours'),
                        'normal' => __('livewire/operator/operator-maintenance-settings.sla_normal_hours'),
                        'low' => __('livewire/operator/operator-maintenance-settings.sla_low_hours'),
                    ] as $key => $label)
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="sla_{{ $key }}">{{ $label }}</label>
                            <input id="sla_{{ $key }}" type="number" name="sla_{{ $key }}_hours" class="vy-input" min="1" max="720"
                                value="{{ $settings->{'sla_'.$key.'_hours'} }}">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Auto-assignment: master toggle + category → technician map (REAL — open() routes when enabled) --}}
            <div class="vy-card">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                    <div>
                        <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-maintenance-settings.auto_heading') }}</h2>
                        <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0;">{{ __('livewire/operator/operator-maintenance-settings.auto_intro') }}</p>
                    </div>
                    <label class="vyms-switch" title="{{ __('livewire/operator/operator-maintenance-settings.auto_toggle_title') }}">
                        <input type="checkbox" name="auto_assign_enabled" value="1" x-model="autoAssign" @checked($settings->auto_assign_enabled)>
                        <span class="vyms-switch-track"><span class="vyms-switch-knob"></span></span>
                    </label>
                </div>
                <div class="vyms-grid2" style="margin-top: 14px;" :style="autoAssign ? '' : 'opacity:.5; pointer-events:none;'">
                    @foreach ($categories as $category)
                        @php $field = 'assignee_'.$category.'_id'; @endphp
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="{{ $field }}">{{ $categoryLabel($category) }}</label>
                            <select id="{{ $field }}" name="{{ $field }}" class="vy-input">
                                <option value="">{{ __('livewire/operator/operator-maintenance-settings.unassigned') }}</option>
                                @foreach ($technicians as $tech)
                                    <option value="{{ $tech['id'] }}" @selected($settings->{$field} === $tech['id'])>{{ $tech['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <p style="font-size: 11.5px; color: var(--vy-faint); margin: 12px 0 0;" x-show="!autoAssign" x-cloak>{{ __('livewire/operator/operator-maintenance-settings.auto_off_note') }}</p>
            </div>

            {{-- Technicians — REAL: category assignees + anyone holding an open order, with a real open-order load --}}
            <div class="vy-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <h2 class="vy-card-title" style="margin: 0;">{{ __('livewire/operator/operator-maintenance-settings.technicians_heading') }}</h2>
                    <a href="{{ route('app.roles') }}" class="vy-btn vy-btn--ghost" style="font-size: 12px; padding: 6px 12px;">{{ __('livewire/operator/operator-maintenance-settings.manage_team_link') }}</a>
                </div>
                <p style="font-size: 12px; color: var(--vy-faint); margin: 0 0 12px;">{{ __('livewire/operator/operator-maintenance-settings.technicians_intro') }}</p>
                @forelse ($technicianPanel as $tech)
                    <div class="vyms-tech">
                        <div class="vyms-tech-avatar">{{ \Illuminate\Support\Str::of($tech['name'])->explode(' ')->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->implode('') }}</div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 13px; color: var(--vy-ink); font-weight: 600;">{{ $tech['name'] }}</div>
                            <div style="font-size: 11.5px; color: var(--vy-faint);">
                                @if (count($tech['categories']) > 0){{ collect($tech['categories'])->map(fn ($c) => $categoryLabel($c))->implode(' · ') }}@else {{ __('livewire/operator/operator-maintenance-settings.assigned_work') }} @endif
                            </div>
                        </div>
                        <div class="vyms-tech-load" title="{{ __('livewire/operator/operator-maintenance-settings.open_orders_title') }}">{{ trans_choice('livewire/operator/operator-maintenance-settings.tech_load', $tech['load'], ['count' => $tech['load']]) }}</div>
                    </div>
                @empty
                    <div class="vy-empty" style="padding: 14px 0;">{{ __('livewire/operator/operator-maintenance-settings.no_technicians') }}</div>
                @endforelse
            </div>

            </div>{{-- /left stack --}}
            <div class="vy-stack">

            {{-- Out-of-order policy --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">{{ __('livewire/operator/operator-maintenance-settings.ooo_heading') }}</h2>
                <label class="vyms-toggle">
                    <input type="checkbox" name="block_room_from_sale" value="1" @checked($settings->block_room_from_sale)>
                    <span>{!! __('livewire/operator/operator-maintenance-settings.block_room') !!}<br><span class="vyms-sub">{{ __('livewire/operator/operator-maintenance-settings.block_room_sub') }}</span></span>
                </label>
                <label class="vyms-toggle">
                    <input type="checkbox" name="notify_front_desk" value="1" @checked($settings->notify_front_desk)>
                    <span>{!! __('livewire/operator/operator-maintenance-settings.notify_front_desk') !!} <span class="vyms-soon">{{ __('livewire/operator/operator-maintenance-settings.badge_not_dispatched') }}</span><br><span class="vyms-sub">{{ __('livewire/operator/operator-maintenance-settings.notify_front_desk_sub') }}</span></span>
                </label>
                <label class="vyms-toggle">
                    <input type="checkbox" name="require_photo_on_resolve" value="1" @checked($settings->require_photo_on_resolve)>
                    <span><strong>{{ __('livewire/operator/operator-maintenance-settings.require_photo') }}</strong> <span class="vyms-soon">{{ __('livewire/operator/operator-maintenance-settings.badge_not_enforced') }}</span><br><span class="vyms-sub">{{ __('livewire/operator/operator-maintenance-settings.require_photo_sub') }}</span></span>
                </label>
            </div>

            {{-- Notifications --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/operator-maintenance-settings.notify_heading') }} <span class="vyms-soon">{{ __('livewire/operator/operator-maintenance-settings.badge_not_dispatched') }}</span></h2>
                <p style="font-size: 12px; color: var(--vy-faint); margin: 0 0 12px;">{{ __('livewire/operator/operator-maintenance-settings.notify_intro') }}</p>
                @foreach ([
                    'notify_new_urgent' => __('livewire/operator/operator-maintenance-settings.notify_new_urgent'),
                    'notify_out_of_service' => __('livewire/operator/operator-maintenance-settings.notify_out_of_service'),
                    'notify_parts_arrive' => __('livewire/operator/operator-maintenance-settings.notify_parts_arrive'),
                    'notify_sla_breach' => __('livewire/operator/operator-maintenance-settings.notify_sla_breach'),
                    'notify_daily_digest' => __('livewire/operator/operator-maintenance-settings.notify_daily_digest'),
                ] as $field => $label)
                    <label class="vyms-toggle">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked($settings->{$field})>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            </div>{{-- /right stack --}}
            </div>{{-- /detail-cols --}}

            {{-- The design pairs Save with a Discard. Discard is a plain GET link back to this same route:
                 re-reading the page IS the discard, because every control above renders from the stored
                 settings. That keeps it honest — it makes no claim to "undo" anything already saved, and it
                 needs no backend. Ordered Discard-then-Save so the destructive-ish control is not the one
                 under the thumb. --}}
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 22px;">
                <a class="vy-btn vy-btn--ghost" style="padding: 12px 20px;" href="{{ route('app.maintenance.settings') }}">{{ __('livewire/operator/operator-maintenance-settings.discard') }}</a>
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 12px 22px;">{{ __('common.save_changes') }}</button>
            </div>
        </form>

    </x-operator.shell>

    <style>
        [x-cloak] { display: none !important; }
        .vyms-grid4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .vyms-grid2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .vyms-toggle { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; font-size: 13px; color: var(--vy-ink); cursor: pointer; }
        .vyms-toggle + .vyms-toggle { border-top: 1px solid var(--vy-hairline); }
        .vyms-toggle input { margin-top: 3px; }
        .vyms-sub { font-size: 11.5px; color: var(--vy-faint); }
        .vyms-soon { font-size: 9px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 1px 6px; margin-left: 4px; }
        .vyms-switch { position: relative; display: inline-flex; cursor: pointer; }
        .vyms-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .vyms-switch-track { width: 42px; height: 24px; border-radius: 999px; background: var(--vy-hairline); transition: background .15s; display: inline-flex; align-items: center; padding: 0 3px; }
        .vyms-switch-knob { width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .15s; }
        .vyms-switch input:checked + .vyms-switch-track { background: var(--vy-accent); }
        .vyms-switch input:checked + .vyms-switch-track .vyms-switch-knob { transform: translateX(18px); }
        .vyms-tech { display: flex; align-items: center; gap: 11px; padding: 10px 0; }
        .vyms-tech + .vyms-tech { border-top: 1px solid var(--vy-hairline); }
        .vyms-tech-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--vy-accent-wash, #F6E4D6); color: var(--vy-accent); font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vyms-tech-load { font-size: 11.5px; font-weight: 600; color: var(--vy-muted); background: var(--vy-hairline); border-radius: 999px; padding: 3px 10px; white-space: nowrap; }
        @media (max-width: 860px) { .vyms-grid4, .vyms-grid2 { grid-template-columns: 1fr 1fr; } }
    </style>
</div>
