{{-- Owner portal · ONE CONVERSATION (Wave 3) — the transcript + the real reply composer.
     See App\Livewire\Owner\OwnerMessageThread for the authoritative map.

     ⚠️ THE TRANSCRIPT IS MIRRORED, and that is the model being right rather than a hack:
     `messages.direction` is ORG-RELATIVE (`inbound` = into the organization), so on an owner thread the
     OWNER's own messages are the `inbound` ones and the staff replies are `outbound`. The component decides
     `mine`; this view just paints it. The operator inbox reads the same rows the other way up — one column,
     two correct perspectives, nothing duplicated to drift.

     ⚠️ OWNER-SCOPED: the thread is resolved via OwnerScope in render() (never route-model-bound, which
     resolves before the scope middleware) — a cross-owner thread AND any operator↔guest thread 404s.

     OMITTED, and named so nobody re-adds them: "✓✓ Read" (delivery_status has NO `read` value — the design
     invents one and defaults every message to it), the presence dot, "usually replies within an hour" (a
     response-time promise nothing can measure), the typing indicator, the per-message pin/star/react row
     (localStorage or nothing at all), and attachments (blob: URLs that die on reload).

     RAW-BLADE RULE: never write `word@directive` — Blade's \B guard would ship the raw source to the page.
     Every `@` below follows a space, a newline or a tag. --}}
<div>
    <x-owner.shell active="messages" :title="$thread['name']" :subtitle="$thread['role']" :viewer="$viewer">

        @if (session('owner_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">{{ session('owner_notice') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <a class="vy-btn vy-btn--ghost vy-fade" href="{{ route('owner.messages') }}" style="margin-bottom: 14px;">‹ All messages</a>

        <div class="vy-card vy-fade">
            {{-- The correspondent. NO presence dot and NO response-time line: neither has any source. --}}
            <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid var(--vy-hairline);">
                <span class="vy-convo-av">{{ $thread['initials'] }}</span>
                <div>
                    <div class="vy-convo-name" style="font-size: 15px;">{{ $thread['name'] }}</div>
                    <div class="vy-convo-role">{{ $thread['role'] }}</div>
                </div>
            </div>

            @if ($thread['subject'])
                <div class="vy-eyebrow" style="margin-top: 14px;">{{ $thread['subject'] }}</div>
            @endif

            <div style="margin-top: 16px;">
                @forelse ($messages as $message)
                    @if ($message['day_label'])
                        <div class="vy-day-sep">{{ $message['day_label'] }}</div>
                    @endif

                    {{-- `mine` is the MIRROR: the owner's own message is `inbound` to the org. --}}
                    <div class="vy-bubble-row @if ($message['mine']) is-mine @endif">
                        <div style="max-width: 76%;">
                            <div class="vy-bubble @if ($message['mine']) is-mine @else is-theirs @endif">{{ $message['body'] }}</div>
                            <div class="vy-bubble-meta">
                                {{ $message['author'] }} · {{ $message['time'] }}
                                {{-- The HONEST delivery state. `failed` is the one real status the design omits
                                     entirely (invariant #24) — on `manual`, recording IS the delivery, so this
                                     should never fire; if it ever does, the owner sees it. There is no "Read":
                                     the enum has no such value and nothing tracks per-message receipts. --}}
                                @if ($message['failed'])
                                    <span class="vy-bubble-failed">· Not delivered</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty" style="padding: 30px 16px;">
                        <strong style="display: block; color: var(--vy-ink); margin-bottom: 5px;">Start of your conversation with {{ $thread['first_name'] }}</strong>
                        Send a message below to start the conversation.
                    </div>
                @endforelse
            </div>

            {{-- REAL: posts through InboxService::postOwnerMessage() (inbound = into the org), owner-scoped,
                 CSRF, PRG. Not a fake send — it lands in the operator's inbox and bumps their unread. --}}
            <form method="POST" action="{{ route('owner.messages.reply', $thread['id']) }}"
                  class="vy-input-group" style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--vy-hairline);">
                @csrf
                <input class="vy-input" type="text" name="body" maxlength="5000" required
                       placeholder="Message {{ $thread['first_name'] }}…" aria-label="Write a message">
                <button type="submit" class="vy-btn vy-btn--accent">Send</button>
            </form>
        </div>

    </x-owner.shell>
</div>
