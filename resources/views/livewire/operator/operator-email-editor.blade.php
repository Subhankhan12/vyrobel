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

        {{-- The design's action bar: back left, status + trigger, and BOTH actions top-right.
             ⚠️ The buttons live outside their forms and reach them with the HTML5 `form=` attribute — the same
             two real POSTs (guarded save · guarded self-only test-send), just repositioned. Nothing rewired. --}}
        <div class="vy-card vy-fade vy-ee-bar">
            <a href="{{ route('app.email-templates') }}" class="vy-btn vy-btn--ghost vy-ee-back">← Templates</a>

            <div class="vy-ee-ident">
                <div class="vy-ee-title">
                    <h2 class="vy-card-title" style="margin: 0;">{{ $catalog['label'] }}</h2>
                    {{-- Status carries its WORD, never colour alone (1.4.1). --}}
                    <span class="vy-ee-status @if ($enabled) is-on @endif">{{ $enabled ? 'Active' : 'Off' }}</span>
                </div>
                <div class="vy-ee-sub">{{ $catalog['trigger'] }} · via {{ $catalog['channel'] }}@if ($lastEdited) · {{ $lastEdited }}@endif</div>
            </div>

            {{-- ⚠️ OPERATOR FIX 1 (audit Finding 5) — both controls submit to routes requiring
                 `properties.manage`. This page carries NO route gate, so every back-office role reached it
                 and was offered two writes that 403. The sibling LIST page already gates its edit/toggle
                 controls this way and asserts it in OperatorEmailTemplatesTest — same pattern (#30, §N.8f). --}}
            @can('properties.manage')
                <div class="vy-ee-actions">
                    <button type="submit" form="vy-ee-test" class="vy-btn vy-btn--ghost vy-ee-btn">Send me a test</button>
                    <button type="submit" form="vy-ee-save" class="vy-btn vy-btn--accent vy-ee-btn">Save template</button>
                </div>
            @endcan
        </div>

        <div class="vy-email-editor" x-data>
            {{-- ── LEFT: the editor form ─────────────────────────────────────────────────────────── --}}
            <div>
                {{-- ⚠️ OPERATOR FIX 1 (audit Finding 5, extended) — the FORM is gated, not merely its submit
                     buttons: a single-line input submits its form on Enter, so leaving the form rendered kept
                     the 403 invitation alive with the buttons hidden. An unentitled role now gets the live
                     preview and no editable surface at all. --}}
                @can('properties.manage')
                <form id="vy-ee-save" method="POST" action="{{ route('app.email-templates.save', $templateKey) }}">
                    @csrf

                    {{-- Subject + insert chips --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <label class="vy-field-label" for="tpl-subject">Subject line</label>
                        <input id="tpl-subject" type="text" name="subject" x-ref="subject" wire:model.live="subject" class="vy-input" maxlength="255">
                        @error('subject') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror

                        <div style="margin-top: 10px;">
                            <span style="font-size: 11px; color: var(--vy-faint); text-transform: uppercase; letter-spacing: .5px;">Insert a variable</span>
                            <div style="margin-top: 6px; display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach ($vars as $var)
                                    <button type="button"
                                        @click="const el=$refs.subject; el.value += '{{ '{'.$var.'}' }}'; el.dispatchEvent(new Event('input')); el.focus();"
                                        class="vy-ee-chip">{{ '{'.$var.'}' }}</button>
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
                            @error('heading') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field" style="margin: 0 0 14px;">
                            <label class="vy-field-label" for="tpl-message">Message</label>
                            <textarea id="tpl-message" name="message" wire:model.live="message" class="vy-input" rows="5" maxlength="2000" style="resize: vertical;"></textarea>
                            @error('message') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="tpl-button">Button label</label>
                            <input id="tpl-button" type="text" name="button_label" wire:model.live="button" class="vy-input" maxlength="120">
                            @error('button_label') <span style="font-size: 11.5px; color: var(--vy-accent-text);">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Block toggles --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <h3 style="font-size: 13.5px; font-weight: 700; color: var(--vy-ink); margin: 0 0 4px;">Sections</h3>
                        <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Turn a section off and it won’t appear in the sent email.</p>
                        @foreach ($blockDefs as $blockKey => $def)
                            <label class="vy-ee-blockrow">
                                <span class="vy-ee-blocklabel">{{ $def['label'] }}</span>
                                <input type="checkbox" class="vy-ee-switch" name="blocks[{{ $blockKey }}]" value="1" wire:model.live="blocks.{{ $blockKey }}">
                            </label>
                        @endforeach
                    </div>

                    {{-- The foot Save stays as well as the new top one: this form is long, and removing it
                         would make a keyboard user tab back up the whole editor to submit. --}}
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 22px;">Save template</button>
                    </div>
                </form>
                @endcan

                @can('properties.manage')
                {{-- Test send — separate form; sends the SAVED template to your own inbox (Save first). --}}
                <form id="vy-ee-test" method="POST" action="{{ route('app.email-templates.test', $templateKey) }}" style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                    @csrf
                    <span style="font-size: 11.5px; color: var(--vy-faint);">“Send me a test” delivers the SAVED version to your own address — save your changes first.</span>
                </form>
                @endcan
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
        /* ⚠️ Was 960px, which left each column ~341px at a 1000px viewport — too narrow for the email body,
           so the preview iframe CLIPPED: the message was cut mid-sentence and the Stay/Unit/Reference values
           sat behind an inner scrollbar. An email needs real width; stack sooner. */
        @media (max-width: 1180px) { .vy-email-editor { grid-template-columns: 1fr; } }

        /* The design's action bar. */
        .vy-ee-bar { margin-bottom: 16px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .vy-ee-back { padding: 8px 15px; flex: none; font-size: 12.5px; }
        .vy-ee-ident { flex: 1 1 240px; min-width: 0; }
        .vy-ee-title { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vy-ee-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; }
        /* ⚠️ Was raw #1B7F5C on #E7F5EE — measured 4.41 at 11px. Same pill, same defect, as the templates
           LIST carried at P31: one visual concept written twice, so the P31 fix did not reach here. */
        .vy-ee-status { font-size: 10.5px; font-weight: 700; border-radius: 999px; padding: 3px 10px; white-space: nowrap; color: var(--vy-muted); background: var(--vy-hairline); }
        .vy-ee-status.is-on { color: #14663F; background: #E7F5EE; }
        .vy-ee-actions { display: flex; gap: 8px; flex: none; margin-left: auto; }
        .vy-ee-btn { padding: 9px 18px; font-size: 12.5px; }

        /* ⚠️ The insert chips measured 4.19 as var(--vy-accent) at 11.5px — the accent-as-text family again. */
        .vy-ee-chip { font-size: 11.5px; font-weight: 600; color: var(--vy-accent-text); background: var(--vy-hairline); border: 1px dashed var(--vy-accent-border); border-radius: 6px; padding: 4px 9px; cursor: pointer; font-family: inherit; }
        .vy-ee-chip:hover { background: rgba(232,112,58,.12); }

        /* Block toggles as the design's switches. ⚠️ The checkbox stays a real, FOCUSABLE input (appearance
           reset, never display:none — the WCAG 2.1.1 defect found on Notification Preferences at P30), and
           the wrapping <label> keeps giving it its accessible name. */
        .vy-ee-blockrow { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--vy-hairline); cursor: pointer; }
        .vy-ee-blocklabel { font-size: 13.5px; color: var(--vy-ink); }
        .vy-ee-switch { appearance: none; -webkit-appearance: none; margin: 0; flex: none; width: 42px; height: 24px; border-radius: 999px; background: var(--vy-hairline); position: relative; cursor: pointer; transition: background .15s; }
        .vy-ee-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .15s; }
        .vy-ee-switch:checked { background: var(--vy-accent); }
        .vy-ee-switch:checked::after { transform: translateX(18px); }
        .vy-ee-switch:focus-visible { outline: 2px solid var(--vy-accent-text); outline-offset: 2px; }
    </style>
@endonce
