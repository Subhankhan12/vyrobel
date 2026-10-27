{{-- Owner Help & Support (exact-match #66) — matched to the decoded "Owner Help" wireframe, HONEST throughout.
     The ONE real contact channel is "Message your manager" → the owner↔operator inbox; the FAQ answers link to the
     real owner screens; typing also filters the real FAQ client-side (Alpine) — NEVER a fabricated AI answer.
     ⚠️ THIS BLOCK USED TO LIST "the AI Q&A" AS REFUSED. That went stale when Wave D shipped Ask Vyrobel: a REAL
     grounded assistant ({@see App\Services\AskVyrobelService}) that retrieves over the Help KB first, escalates
     deterministically with NO model call when retrieval is empty, and falls back to the same grounded help when
     the cost cap or circuit breaker trips. Still REFUSED (#39): the support-TICKET queue (messaging is the
     tracked channel), a LIVE system-status board, and invented owner-desk phone/email numbers.
     RAW-BLADE RULE: every `@` follows a space, newline or tag (unregistered @click passes through to Alpine). --}}
<x-owner.shell active="help" :title="__('livewire/owner/help.title')" :subtitle="__('livewire/owner/help.subtitle')" :viewer="$viewer">
    <style>
        .vy-hlp-hero { text-align:center; padding:8px 0 4px; }
        .vy-hlp-search { display:flex; align-items:center; gap:10px; padding:6px 6px 6px 16px; border-radius:var(--vy-r-pill); background:rgba(255,252,248,.92); border:1px solid var(--vy-glass-border); box-shadow:0 10px 30px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); max-width:560px; margin:22px auto 0; }
        .vy-hlp-search input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:14.5px; color:var(--vy-ink); padding:11px 0; }
        .vy-hlp-pills { display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-top:14px; }
        .vy-hlp-pill { padding:7px 14px; border-radius:var(--vy-r-pill); background:rgba(255,252,248,.7); border:1px solid var(--vy-hairline); color:var(--vy-muted); font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; }
        .vy-hlp-pill:hover { background:rgba(255,255,255,.95); }
        .vy-hlp-contact { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin:20px 0; }
        .vy-hlp-ccard { display:flex; flex-direction:column; padding:20px; border-radius:20px; background:var(--vy-glass); backdrop-filter:blur(22px) saturate(1.15); border:1px solid var(--vy-glass-border); box-shadow:var(--vy-shadow); text-decoration:none; color:var(--vy-ink); }
        .vy-hlp-ccard-ic { width:44px; height:44px; border-radius:13px; color:#fff; display:flex; align-items:center; justify-content:center; }
        .vy-hlp-ccard h3 { font-family:var(--vy-display); font-weight:600; font-size:16px; margin:14px 0 0; }
        .vy-hlp-status { display:flex; align-items:center; gap:9px; padding:12px 16px; border-radius:14px; background:rgba(31,122,90,.09); border:1px solid rgba(31,122,90,.2); }
        .vy-hlp-status .dot { width:9px; height:9px; border-radius:50%; background:var(--vy-green); box-shadow:0 0 0 4px rgba(31,122,90,.15); }
        .vy-hlp-faq { border-bottom:1px solid var(--vy-hairline); padding:10px 0; }
        .vy-hlp-faq summary { cursor:pointer; font-size:13.5px; font-weight:600; color:var(--vy-ink); list-style:none; }
        .vy-hlp-ask-btn { flex-shrink:0; padding:10px 18px; border-radius:var(--vy-r-pill); border:none; background:var(--vy-grad); color:#fff; font:inherit; font-size:13.5px; font-weight:600; cursor:pointer; box-shadow:var(--vy-shadow-accent); }
        .vy-hlp-note { display:flex; align-items:flex-start; gap:7px; font-size:11.5px; color:var(--vy-ink); margin-top:12px; line-height:1.5; }
        .vy-hlp-answer { border:1px solid var(--vy-accent-border); }
        .vy-hlp-answer .ans { font-size:14.5px; line-height:1.6; color:var(--vy-ink); margin:6px 0 0; }
        .vy-hlp-src { display:flex; flex-direction:column; gap:8px; margin-top:14px; padding-top:12px; border-top:1px solid var(--vy-hairline); }
        .vy-hlp-src a { color:var(--vy-accent); font-size:13px; font-weight:600; text-decoration:none; }
        .vy-hlp-src .body { color:var(--vy-muted); font-size:12.5px; line-height:1.5; margin-top:2px; }
    </style>

    @php
        // The ONE shared Help knowledge base (anti-drift: the FAQ AND the grounded assistant read the SAME source).
        $faqs = \App\Support\Help\HelpKnowledgeBase::translatedArticles();   // the reader's language; Ask Vyrobel retrieves from the English (articles())
    @endphp

    <div x-data="{ q: '', faqs: {{ \Illuminate\Support\Js::from(collect($faqs)->map(fn ($f) => strtolower($f['question'].' '.$f['answer'].' '.$f['keywords']))->all()) }}, get anyMatch() { return this.faqs.some(t => t.includes(this.q.toLowerCase())); } }">
        {{-- ── Hero: "Ask Vyrobel" — a REAL grounded assistant (Wave D). Typing also filters the FAQ below live
             (x-model="q"); pressing Ask sends the question to the grounded service. GROUNDED + guard-railed:
             answers only from real help, escalates to the real relay when unsure — never a fabricated answer. ── --}}
        <section class="vy-card vy-fade vy-hlp-hero">
            <h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:28px;letter-spacing:-.02em">{{ __('livewire/owner/help.ask_heading') }}</h2>
            <p style="margin:11px 0 0;font-size:14px;color:var(--vy-muted)">{{ __('livewire/owner/help.ask_intro') }}</p>
            <form method="POST" action="{{ route('owner.help.ask') }}" class="vy-hlp-search">
                @csrf
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="text" name="question" x-model="q" maxlength="500" required placeholder="{{ __('livewire/owner/help.ask_placeholder') }}" aria-label="{{ __('livewire/owner/help.ask_aria') }}">
                <button type="submit" class="vy-hlp-ask-btn">{{ __('livewire/owner/help.ask_button') }}</button>
            </form>
            <div class="vy-hlp-pills">
                @foreach ([__('livewire/owner/help.suggest_paid'), __('livewire/owner/help.suggest_statement'), __('livewire/owner/help.suggest_tax'), __('livewire/owner/help.suggest_block')] as $s)
                    <button type="button" class="vy-hlp-pill" @click="q = @js($s)">{{ $s }}</button>
                @endforeach
            </div>
            <div class="vy-hlp-note" style="justify-content:center">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <span>{{ __('livewire/owner/help.ai_note') }}</span>
            </div>
        </section>

        {{-- ── The grounded answer (only after an ask) — honest on every path, always with sources + escalate ── --}}
        @if (session('ask_result'))
            @php
                $ask = session('ask_result');            // primitive array (see OwnerHelpAskController)
                $askQ = session('ask_question');
                $answered = ($ask['outcome'] ?? '') === 'answered';
                $escalate = ($ask['outcome'] ?? '') === 'escalate';
                $citations = $ask['citations'] ?? [];
                $fallbackNote = ($ask['reason'] ?? '') === 'cost_capped'
                    ? __('livewire/owner/help.fallback_cost_capped')
                    : __('livewire/owner/help.fallback_unavailable');
            @endphp
            <section class="vy-card vy-fade vy-hlp-answer">
                <div class="vy-eyebrow">{{ __('livewire/owner/help.answer_eyebrow') }}</div>
                <p style="font-size:12.5px;color:var(--vy-faint);margin:8px 0 0">{{ __('livewire/owner/help.you_asked', ['question' => $askQ]) }}</p>

                @if ($answered)
                    <p class="ans">{{ $ask['text'] }}</p>
                @elseif ($escalate)
                    <p class="ans">{{ __('livewire/owner/help.escalate') }}</p>
                @else
                    <p class="ans">{{ $fallbackNote }}</p>
                @endif

                @if (! empty($citations))
                    <div class="vy-hlp-src">
                        <div class="vy-eyebrow">{{ __('livewire/owner/help.sources') }}</div>
                        @foreach ($citations as $c)
                            @php $c = \App\Support\Help\HelpKnowledgeBase::translatedArticle((string) ($c['id'] ?? '')) ?? $c; @endphp
                            <div>
                                <a href="{{ route($c['route']) }}">{{ $c['question'] }} → {{ $c['label'] }}</a>
                                @unless ($answered)
                                    <div class="body">{{ $c['answer'] }}</div>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('owner.messages.start') }}" style="margin-top:16px">
                    @csrf
                    <input type="hidden" name="subject" value="{{ __('livewire/owner/help.message_subject') }}">
                    <input type="hidden" name="body" value="{{ $askQ }}">
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding:9px 16px">{{ __('livewire/owner/help.message_team_button') }}</button>
                </form>
                <div class="vy-hlp-note">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    <span>{{ __('livewire/owner/help.ai_note_short') }}</span>
                </div>
            </section>
        @endif

        {{-- ── Contact channels — REAL relay + HONEST guidance (no fabricated numbers, #39) + honest status ── --}}
        <section class="vy-hlp-contact vy-fade" style="animation-delay:.04s">
            <a href="{{ route('owner.messages') }}" class="vy-hlp-ccard" style="border-color:var(--vy-accent-border)">
                <span class="vy-hlp-ccard-ic" style="background:var(--vy-grad);box-shadow:var(--vy-shadow-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2z"/></svg></span>
                <h3>{{ __('livewire/owner/help.message_manager') }}</h3>
                <span style="font-size:12.5px;color:var(--vy-faint);margin-top:3px">{{ __('livewire/owner/help.message_manager_sub') }}</span>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:500;color:var(--vy-accent-text);margin-top:14px">{{ __('livewire/owner/help.open_messages_arrow') }}</span>
            </a>
            <div class="vy-hlp-ccard">
                <span class="vy-hlp-ccard-ic" style="background:linear-gradient(140deg,#E8875A,#C2410C)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.4-1.2a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></span>
                <h3>{{ __('livewire/owner/help.phone_heading') }}</h3>
                <span style="font-size:12.5px;color:var(--vy-muted);margin-top:6px;line-height:1.5">{{ __('livewire/owner/help.phone_body') }}</span>
            </div>
            <div class="vy-hlp-ccard" style="justify-content:center">
                {{-- NOT "System status": nothing here measures a system. The heading + a green presence dot
                     together read as "operational right now", which is the live status board this file's own
                     docblock records as REFUSED. The dot is gone and the heading now describes what the line
                     actually says — a fact about the support model, not a signal. --}}
                <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('livewire/owner/help.support_eyebrow') }}</div>
                <div class="vy-hlp-status"><span style="font-size:13px;font-weight:500;color:var(--vy-ink)">{{ __('livewire/owner/help.support_line') }}</span></div>
                {{-- ⚠️ THE LEFTOVER SAT BESIDE THE THING THAT WAS CORRECTLY REMOVED (§N.13.1). The status board and
                     its presence dot went; this sentence stayed, and "are running normally" is a PRESENT-TENSE
                     OPERATIONAL CLAIM with nothing measuring it — the same assertion the board was refused for,
                     in prose. It now describes the support model, which is a fact this page can actually back. --}}
                <span style="font-size:11.5px;color:var(--vy-faint);margin-top:8px">{{ __('livewire/owner/help.support_sub') }}</span>
            </div>
        </section>

        {{-- ── FAQ (real content) with client-side search ── --}}
        <div class="vy-cols" style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;align-items:start">
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom:10px">{{ __('livewire/owner/help.faq_heading') }}</h2>
                @foreach ($faqs as $faq)
                    <details class="vy-hlp-faq" x-show="q === '' || @js(strtolower($faq['question'].' '.$faq['answer'].' '.$faq['keywords'])).includes(q.toLowerCase())">
                        <summary>{{ $faq['question'] }}</summary>
                        <div class="vy-line-sub" style="font-size:12.5px;margin-top:8px;line-height:1.5">
                            {{ $faq['answer'] }}
                            <div style="margin-top:8px"><a href="{{ route($faq['route']) }}" style="color:var(--vy-accent);font-weight:600;font-size:12.5px">{{ __('livewire/owner/help.faq_go_to', ['page' => $faq['label']]) }}</a></div>
                        </div>
                    </details>
                @endforeach
                <p class="vy-line-sub" x-show="q !== '' && ! anyMatch" x-cloak style="padding:16px 0">{!! __('livewire/owner/help.faq_no_match', ['url' => e(route('owner.messages'))]) !!}</p>
            </section>

            {{-- Right rail: still-stuck (the honest tracked channel — NO fake tickets) + jump-to --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <div class="vy-eyebrow">{{ __('livewire/owner/help.still_stuck') }}</div>
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:6px;line-height:1.5">{{ __('livewire/owner/help.still_stuck_body') }}</p>
                    <a href="{{ route('owner.messages') }}" class="vy-btn vy-btn--accent" style="margin-top:12px;padding:9px 16px">{{ __('livewire/owner/help.open_messages') }}</a>
                </section>
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <div class="vy-eyebrow">{{ __('livewire/owner/help.jump_to') }}</div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                        <a href="{{ route('owner.payouts') }}" style="color:var(--vy-accent);font-size:13px">{{ __('livewire/owner/help.jump_payouts') }}</a>
                        <a href="{{ route('owner.archive') }}" style="color:var(--vy-accent);font-size:13px">{{ __('livewire/owner/help.jump_statements') }}</a>
                        <a href="{{ route('owner.tax') }}" style="color:var(--vy-accent);font-size:13px">{{ __('livewire/owner/help.jump_tax') }}</a>
                        <a href="{{ route('owner.payout-method') }}" style="color:var(--vy-accent);font-size:13px">{{ __('livewire/owner/help.jump_payout_method') }}</a>
                        <a href="{{ route('owner.agreement') }}" style="color:var(--vy-accent);font-size:13px">{{ __('livewire/owner/help.jump_agreement') }}</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-owner.shell>
