{{-- Operator Email EDITOR (#86) — customize one guest email; the draft drives a LIVE preview of the REAL
     `emails.{key}` blade, and Save is a guarded controller POST that the real #83 Mailables read at send.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="email-templates" :title="$catalog['label']" :subtitle="$catalog['trigger']" :viewer="$viewer">

        @if (session('email_template_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('email_template_notice') }}</div>
            </div>
        @endif

        {{-- Header — name · status · trigger · last edited --}}
        <div class="vy-card vy-fade" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h2 class="vy-card-title" style="margin: 0;">{{ $catalog['label'] }}</h2>
                    @if ($enabled)
                        <span style="font-size: 11px; font-weight: 700; color: #1B7F5C; background: #E7F5EE; border-radius: 999px; padding: 3px 10px;">● Active</span>
                    @else
                        <span style="font-size: 11px; font-weight: 700; color: var(--vy-muted); background: var(--vy-hairline); border-radius: 999px; padding: 3px 10px;">○ Off</span>
                    @endif
                </div>
                <div style="font-size: 12.5px; color: var(--vy-muted); margin-top: 4px;">{{ $catalog['trigger'] }} · via {{ $catalog['channel'] }}</div>
                @if ($lastEdited)
                    <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 3px;">{{ $lastEdited }}</div>
                @endif
            </div>
            <a href="{{ route('app.email-templates') }}" class="vy-btn vy-btn--ghost" style="padding: 8px 16px; flex: none;">← All templates</a>
        </div>

        <div class="vy-email-editor" x-data>
            {{-- ── LEFT: the editor form ─────────────────────────────────────────────────────────── --}}
            <div>
                <form method="POST" action="{{ route('app.email-templates.save', $templateKey) }}">
                    @csrf

                    {{-- Subject + insert chips --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <label class="vy-field-label" for="tpl-subject">Subject line</label>
                        <input id="tpl-subject" type="text" name="subject" x-ref="subject" wire:model.live="subject" class="vy-input" maxlength="255">
                        @error('subject') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror

                        <div style="margin-top: 10px;">
                            <span style="font-size: 11px; color: var(--vy-faint); text-transform: uppercase; letter-spacing: .5px;">Insert a variable</span>
                            <div style="margin-top: 6px; display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach ($vars as $var)
                                    <button type="button"
                                        @click="const el=$refs.subject; el.value += '{{ '{'.$var.'}' }}'; el.dispatchEvent(new Event('input')); el.focus();"
                                        style="font-size: 11.5px; font-weight: 600; color: var(--vy-accent); background: var(--vy-hairline); border: none; border-radius: 6px; padding: 4px 9px; cursor: pointer;">{{ '{'.$var.'}' }}</button>
                                @endforeach
                            </div>
                            <p style="font-size: 11px; color: var(--vy-faint); margin: 8px 0 0;">These fill in from the real booking — <span style="color: var(--vy-muted);">{first_name}</span> becomes the guest’s name, and so on.</p>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <div class="vy-field" style="margin: 0 0 14px;">
                            <label class="vy-field-label" for="tpl-heading">Heading</label>
                            <input id="tpl-heading" type="text" name="heading" wire:model.live="heading" class="vy-input" maxlength="255">
                            @error('heading') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field" style="margin: 0 0 14px;">
                            <label class="vy-field-label" for="tpl-message">Message</label>
                            <textarea id="tpl-message" name="message" wire:model.live="message" class="vy-input" rows="5" maxlength="2000" style="resize: vertical;"></textarea>
                            @error('message') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="tpl-button">Button label</label>
                            <input id="tpl-button" type="text" name="button_label" wire:model.live="button" class="vy-input" maxlength="120">
                            @error('button_label') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Block toggles --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <h3 style="font-size: 13.5px; font-weight: 700; color: var(--vy-ink); margin: 0 0 4px;">Sections</h3>
                        <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Turn a section off and it won’t appear in the sent email.</p>
                        @foreach ($blockDefs as $blockKey => $def)
                            <label style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--vy-hairline); cursor: pointer;">
                                <span style="font-size: 13.5px; color: var(--vy-ink);">{{ $def['label'] }}</span>
                                <input type="checkbox" name="blocks[{{ $blockKey }}]" value="1" wire:model.live="blocks.{{ $blockKey }}" style="width: 16px; height: 16px; accent-color: var(--vy-accent);">
                            </label>
                        @endforeach
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 22px;">Save template</button>
                    </div>
                </form>

                {{-- Test send — separate form; sends the SAVED template to your own inbox (Save first). --}}
                <form method="POST" action="{{ route('app.email-templates.test', $templateKey) }}" style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                    @csrf
                    <span style="font-size: 11.5px; color: var(--vy-faint);">Sends the saved version to your own email — save your changes first.</span>
                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding: 9px 16px; flex: none;">Send me a test</button>
                </form>
            </div>

            {{-- ── RIGHT: the live preview ──────────────────────────────────────────────────────── --}}
            <div>
                <div class="vy-card vy-fade" style="position: sticky; top: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--vy-ink); text-transform: uppercase; letter-spacing: .5px;">Live preview</span>
                        <span style="font-size: 11px; color: var(--vy-faint);">Real layout · sample data</span>
                    </div>
                    <div style="font-size: 12px; color: var(--vy-muted); border: 1px solid var(--vy-hairline); border-radius: 8px 8px 0 0; padding: 8px 12px; background: var(--vy-hairline);">
                        <span style="color: var(--vy-faint);">Subject:</span> <span style="color: var(--vy-ink);">{{ $previewSubject }}</span>
                    </div>
                    <iframe title="Email preview" srcdoc="{{ $previewHtml }}" style="width: 100%; height: 680px; border: 1px solid var(--vy-hairline); border-top: none; border-radius: 0 0 8px 8px; background: #fff;"></iframe>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>

@once
    <style>
        .vy-email-editor { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        @media (max-width: 960px) { .vy-email-editor { grid-template-columns: 1fr; } }
    </style>
@endonce
