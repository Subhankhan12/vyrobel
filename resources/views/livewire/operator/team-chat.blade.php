{{-- TEAM CHAT (2026-10-06 — his "messages must be owner and the employees as well … toggle for client and employees …
     attachment … picture … voice … notification … Ajax based runtime"; `docs/TEAM-CHAT-DESIGN.md`).

     The page draws the first view; the script below keeps it live: the open conversation is asked every 3 seconds while the
     tab is visible (20 when hidden; nothing after 30 minutes without a key or click), messages are sent without a reload
     (text, up to five pictures or documents, a voice note), and "Seen" follows the other person's read position. Every
     text is put on the page with x-text (never as HTML). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div @unless ($support) x-data="vyTeamChat" @endunless>
<x-operator.shell active="inbox" :title="__('livewire/operator/team-chat.title')" :subtitle="__('livewire/operator/team-chat.subtitle')">

    @include('partials.messages-toggle', ['current' => 'team', 'guestsLabel' => $guestsLabel, 'guestUnread' => $guestUnread, 'teamUnread' => $teamUnread])

    @if ($support)
        <section class="vy-card vy-tc-private" role="status">
            <span class="vy-tc-private-ico" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>
            <h2>{{ __('livewire/operator/team-chat.private_heading') }}</h2>
            <p>{{ __('livewire/operator/team-chat.private_body') }}</p>
        </section>
    @else
    <div id="vy-team-chat" class="vy-tc" :class="{ 'has-open': active }" x-cloak>

        {{-- ── The conversations ─────────────────────────────────────────────────────────────────────────────── --}}
        <aside class="vy-card vy-card--flush vy-tc-rail" aria-label="{{ __('livewire/operator/team-chat.rail_aria') }}">
            <header class="vy-tc-rail-head">
                <h2 class="vy-tc-rail-title">{{ __('livewire/operator/team-chat.rail_title') }}</h2>
                <button type="button" class="vy-tc-new" @click="openNew()" x-show="teammates.length && !readOnly" aria-label="{{ __('livewire/operator/team-chat.new_conversation') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    <span>{{ __('livewire/operator/team-chat.new_button') }}</span>
                </button>
            </header>
            <label class="vy-gl-search vy-tc-search" x-show="teammates.length">
                <svg class="vy-gl-search-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="vy-sr-only">{{ __('livewire/operator/team-chat.search_label') }}</span>
                <input type="search" class="vy-gl-search-in" x-model="find" placeholder="{{ __('livewire/operator/team-chat.search_placeholder') }}" autocomplete="off">
            </label>

            <div class="vy-tc-list">
                <template x-for="c in rail" :key="c.id">
                    <a class="vy-tc-row" :href="c.url" @click.prevent="open(c.id)"
                       :class="{ 'is-active': active === c.id, 'is-unread': c.unread > 0 }" :aria-current="active === c.id ? 'true' : null">
                        <span class="vy-tc-av" :class="{ 'is-group': c.kind === 'group' }" aria-hidden="true">
                            <template x-if="c.kind === 'direct'"><span x-text="c.initials"></span></template>
                            <template x-if="c.kind === 'group'"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></template>
                        </span>
                        <span class="vy-tc-row-body">
                            <span class="vy-tc-row-top">
                                <span class="vy-tc-row-name" x-text="c.name"></span>
                                <span class="vy-tc-dot" x-show="isOnline(c)" role="img" aria-label="{{ __('livewire/operator/team-chat.online_now') }}"></span>
                                <span class="vy-tc-row-time" x-text="c.last ? railTime(c.last.at) : ''"></span>
                            </span>
                            <span class="vy-tc-row-bottom">
                                <span class="vy-tc-row-preview" :class="{ 'is-waiting': waiting(c.id) }" x-text="waiting(c.id) ? @js(__('livewire/operator/team-chat.preview_waiting')) : (c.last ? (c.last.mine ? @js(__('livewire/operator/team-chat.preview_you', ['text' => ':text'])).replace(':text', () => c.last.text) : c.last.text) : @js(__('livewire/operator/team-chat.preview_none')))"></span>
                                <span class="vy-tc-unread" x-show="c.unread > 0" x-text="c.unread > 99 ? '99+' : c.unread" :aria-label="unreadLabel(c.unread)"></span>
                            </span>
                        </span>
                    </a>
                </template>

                {{-- people found by the search who have no conversation with you yet --}}
                <template x-if="people.length && !readOnly">
                    <div class="vy-tc-people">
                        <p class="vy-tc-people-label">{{ __('livewire/operator/team-chat.start_a_conversation') }}</p>
                        <template x-for="p in people" :key="'p' + p.id">
                            <button type="button" class="vy-tc-row vy-tc-row--person" @click="startWith([p.id])">
                                <span class="vy-tc-av" aria-hidden="true"><span x-text="p.initials"></span></span>
                                <span class="vy-tc-row-body">
                                    <span class="vy-tc-row-name" x-text="p.name"></span>
                                    <span class="vy-tc-row-preview" x-text="p.role"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </template>

                <div class="vy-tc-empty" x-show="!teammates.length">
                    <strong>{{ __('livewire/operator/team-chat.no_one_heading') }}</strong>
                    <span>{{ __('livewire/operator/team-chat.no_one_body') }}</span>
                    <template x-if="urls.roles"><a class="vy-btn vy-btn--ghost" :href="urls.roles">{{ __('livewire/operator/team-chat.invite_team') }}</a></template>
                </div>
                <div class="vy-tc-empty" x-show="teammates.length && !conversations.length && !find">
                    <strong>{{ __('livewire/operator/team-chat.no_conversations_heading') }}</strong>
                    <span>{{ __('livewire/operator/team-chat.no_conversations_body') }}</span>
                    <button type="button" class="vy-btn vy-btn--accent" @click="openNew()" x-show="!readOnly">{{ __('livewire/operator/team-chat.new_conversation') }}</button>
                </div>
                <p class="vy-tc-none" x-show="find && !rail.length && !people.length">{!! __('livewire/operator/team-chat.nobody_matches', ['term' => '<span x-text="find"></span>']) !!}</p>
            </div>
        </aside>

        {{-- ── The open conversation ──────────────────────────────────────────────────────────────────────────── --}}
        <section class="vy-card vy-card--flush vy-tc-pane" aria-label="{{ __('livewire/operator/team-chat.pane_aria') }}"
                 @dragenter.prevent="dragIn($event)" @dragover.prevent="dragIn($event)" @dragleave="dragOut($event)" @drop.prevent="dropFiles($event)">
            <div class="vy-tc-blank" x-show="!active">
                <span class="vy-tc-blank-ico" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/></svg></span>
                <strong>{{ __('livewire/operator/team-chat.blank_heading') }}</strong>
                <span>{{ __('livewire/operator/team-chat.blank_body') }}</span>
            </div>

            <template x-if="active && current">
                <div class="vy-tc-convo">
                    <header class="vy-tc-head">
                        <button type="button" class="vy-tc-icon vy-tc-back" @click="back()" aria-label="{{ __('livewire/operator/team-chat.back_aria') }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <span class="vy-tc-av vy-tc-av--lg" :class="{ 'is-group': current.kind === 'group' }" aria-hidden="true">
                            <template x-if="current.kind === 'direct'"><span x-text="current.initials"></span></template>
                            <template x-if="current.kind === 'group'"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></template>
                        </span>
                        <span class="vy-tc-head-text">
                            <strong class="vy-tc-head-name" x-text="current.name"></strong>
                            <span class="vy-tc-head-sub" :class="{ 'is-typing': typingLine }" x-text="typingLine || headSub"></span>
                        </span>
                        <button type="button" class="vy-tc-icon" :class="{ 'is-on': searchOpen }" @click="toggleSearch()" :aria-expanded="searchOpen ? 'true' : 'false'" aria-controls="vy-tc-search-panel" aria-label="{{ __('livewire/operator/team-chat.search_chat_open') }}" title="{{ __('livewire/operator/team-chat.search_chat_open') }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        </button>
                        <button type="button" class="vy-tc-icon" :class="{ 'is-off': !desktop }" @click="toggleDesktop()" :aria-pressed="desktop ? 'true' : 'false'" :aria-label="desktop ? @js(__('livewire/operator/team-chat.desktop_on_aria')) : @js(__('livewire/operator/team-chat.desktop_off_aria'))" :title="desktop ? @js(__('livewire/operator/team-chat.desktop_on')) : @js(__('livewire/operator/team-chat.desktop_off'))">
                            <svg x-show="desktop" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <svg x-show="!desktop" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.73 21a2 2 0 0 1-3.46 0"/><path d="M18.63 13A17.9 17.9 0 0 1 18 8"/><path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9h14"/><path d="M18 8a6 6 0 0 0-9.33-5"/><path d="m1 1 22 22"/></svg>
                        </button>
                        <button type="button" class="vy-tc-icon" :class="{ 'is-off': !sound }" @click="toggleSound()" :aria-pressed="sound ? 'true' : 'false'" :aria-label="sound ? @js(__('livewire/operator/team-chat.sound_on_aria')) : @js(__('livewire/operator/team-chat.sound_off_aria'))" :title="sound ? @js(__('livewire/operator/team-chat.sound_on')) : @js(__('livewire/operator/team-chat.sound_off'))">
                            <svg x-show="sound" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/></svg>
                            <svg x-show="!sound" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="m22 9-6 6M16 9l6 6"/></svg>
                        </button>
                    </header>

                    <div class="vy-tc-find" id="vy-tc-search-panel" x-show="searchOpen" x-cloak>
                        <div class="vy-tc-find-bar">
                            <label class="vy-sr-only" for="vy-tc-find-input">{{ __('livewire/operator/team-chat.search_chat_label') }}</label>
                            <input id="vy-tc-find-input" type="search" class="vy-tc-find-input" x-ref="findInput" x-model="chatQuery" @input.debounce.350ms="runSearch()" @keydown.escape.prevent="closeSearch()" placeholder="{{ __('livewire/operator/team-chat.search_chat_placeholder') }}" autocomplete="off" maxlength="100">
                            <button type="button" class="vy-tc-icon" @click="closeSearch()" aria-label="{{ __('livewire/operator/team-chat.search_chat_close') }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="vy-tc-find-count" role="status" x-text="searchLine"></p>
                        <ul class="vy-tc-find-list" x-show="found.length">
                            <template x-for="r in found" :key="r.id">
                                <li>
                                    <button type="button" class="vy-tc-find-row" @click="goTo(r.id)">
                                        <span class="vy-tc-find-top"><strong x-text="r.mine ? @js(__('livewire/operator/team-chat.search_you')) : r.sender"></strong><span x-text="railTime(r.at)"></span></span>
                                        <span class="vy-tc-find-snippet" x-text="r.snippet"></span>
                                        <span class="vy-tc-find-go">{{ __('livewire/operator/team-chat.search_go') }} <span class="vy-flip-rtl" aria-hidden="true">→</span></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="vy-tc-banner" x-show="offline" role="alert">
                        <span x-text="offline"></span>
                        <button type="button" class="vy-btn vy-btn--ghost" @click="location.reload()">{{ __('livewire/operator/team-chat.reload') }}</button>
                    </div>
                    <div class="vy-tc-banner vy-tc-banner--note" x-show="notice && !offline" role="status">
                        <span x-text="notice"></span>
                        <button type="button" class="vy-btn vy-btn--ghost" @click="notice = ''">{{ __('livewire/operator/team-chat.dismiss') }}</button>
                    </div>

                    <div class="vy-tc-stream" x-ref="stream" @scroll.passive="onScroll()" role="log" aria-live="polite" aria-label="{{ __('livewire/operator/team-chat.stream_aria') }}">
                        <div class="vy-tc-older" x-show="hasOlder">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="loadOlder()" :disabled="loadingOlder" x-text="loadingOlder ? @js(__('common.loading')) : @js(__('livewire/operator/team-chat.load_earlier'))"></button>
                        </div>
                        <div class="vy-tc-hello" x-show="!messages.length && !loading">
                            <span class="vy-tc-av vy-tc-av--xl" :class="{ 'is-group': current.kind === 'group' }" aria-hidden="true"><span x-text="current.kind === 'direct' ? current.initials : '#'"></span></span>
                            <strong x-text="current.kind === 'direct' ? @js(__('livewire/operator/team-chat.say_hello_to', ['name' => ':name'])).replace(':name', () => current.name) : @js(__('livewire/operator/team-chat.say_hello_group'))"></strong>
                            <span>{{ __('livewire/operator/team-chat.hello_body') }}</span>
                        </div>

                        <template x-for="item in timeline" :key="item.key">
                            <div class="vy-tc-item">
                                <template x-if="item.type === 'day'">
                                    <div class="vy-tc-day"><span x-text="item.label"></span></div>
                                </template>
                                <template x-if="item.type === 'run'">
                                    <div class="vy-tc-run" :class="{ 'is-mine': item.mine }">
                                        <span class="vy-tc-av vy-tc-av--sm" x-show="!item.mine" aria-hidden="true"><span x-text="item.sender.initials"></span></span>
                                        <div class="vy-tc-run-body">
                                            <span class="vy-tc-who" x-show="!item.mine && current.kind === 'group'" x-text="item.sender.name"></span>
                                            <template x-for="m in item.items" :key="m.id">
                                                <div class="vy-tc-msg" :class="{ 'is-pending': m.pending, 'is-failed': m.failed, 'is-deleted': m.deleted, 'is-tools': toolsFor === m.id, 'is-found': foundId === m.id }" :data-mid="m.id"
                                                     @click="toolsFor = (toolsFor === m.id ? null : m.id)" @mouseleave="if (toolsFor === m.id && menuFor !== m.id && pickerFor !== m.id) toolsFor = null"
                                                     {{-- ⚠️ THE KEYBOARD PATH (found by KeyboardReachabilityContractTest in the full run): the tools are `display: none` until the row is hovered or opened, so Tab could never reach
                                                          the buttons inside them and `:focus-within` could never fire. The ROW is the stop — Enter or Space opens its tools and moves focus to the first one; Escape closes them. --}}
                                                     tabindex="0" @keydown.enter.self.prevent="toolsFor = (toolsFor === m.id ? null : m.id); if (toolsFor === m.id) $nextTick(() => $el.querySelector('.vy-tc-tool')?.focus())" @keydown.space.self.prevent="toolsFor = (toolsFor === m.id ? null : m.id); if (toolsFor === m.id) $nextTick(() => $el.querySelector('.vy-tc-tool')?.focus())" @keydown.escape="if (toolsFor === m.id) { toolsFor = null; $el.focus() }"
                                                     @touchstart.passive="pressStart(m)" @touchend="pressEnd()" @touchmove.passive="pressEnd()" @touchcancel="pressEnd()">
                                                    <template x-if="m.deleted">
                                                        <p class="vy-tc-bubble vy-tc-bubble--deleted">{{ __('livewire/operator/team-chat.message_deleted') }}</p>
                                                    </template>
                                                    <template x-if="m.files.filter(f => f.kind === 'image').length">
                                                        <div class="vy-tc-photos" :class="'n' + Math.min(4, m.files.filter(f => f.kind === 'image').length)">
                                                            <template x-for="f in m.files.filter(f => f.kind === 'image')" :key="f.id">
                                                                <button type="button" class="vy-tc-photo" @click="view(f)" :aria-label="@js(__('livewire/operator/team-chat.open_file_aria', ['name' => ':name'])).replace(':name', () => f.name)"><img :src="f.url" :alt="f.name" loading="lazy"></button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="m.body && editing !== m.id">
                                                        <p class="vy-tc-bubble"><span x-text="m.body"></span><span class="vy-tc-edited" x-show="m.edited">{{ __('livewire/operator/team-chat.edited') }}</span></p>
                                                    </template>
                                                    <template x-if="editing === m.id">
                                                        <div class="vy-tc-edit" @click.stop>
                                                            <label class="vy-sr-only" :for="'vy-tc-edit-' + m.id">{{ __('livewire/operator/team-chat.edit_aria') }}</label>
                                                            <textarea class="vy-tc-edit-text" :id="'vy-tc-edit-' + m.id" rows="2" x-model="editText" :maxlength="limits.body" :disabled="editSaving"
                                                                      @keydown.enter="editEnter($event)" @keydown.escape.prevent.stop="cancelEdit()"></textarea>
                                                            <p class="vy-tc-error" x-show="editError" x-text="editError" role="alert"></p>
                                                            <span class="vy-tc-edit-bar">
                                                                <span class="vy-tc-edit-hint">{{ __('livewire/operator/team-chat.edit_hint') }}</span>
                                                                <button type="button" class="vy-btn vy-btn--ghost" @click="cancelEdit()">{{ __('common.cancel') }}</button>
                                                                <button type="button" class="vy-btn vy-btn--accent" @click="saveEdit()" :disabled="editSaving">{{ __('livewire/operator/team-chat.edit_save') }}</button>
                                                            </span>
                                                        </div>
                                                    </template>
                                                    <template x-for="f in m.files.filter(f => f.kind === 'file')" :key="f.id">
                                                        <a class="vy-tc-file" :href="m.pending ? null : f.url" :aria-label="@js(__('livewire/operator/team-chat.download_file_aria', ['name' => ':name'])).replace(':name', () => f.name)">
                                                            <span class="vy-tc-file-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg></span>
                                                            <span class="vy-tc-file-text"><span class="vy-tc-file-name" x-text="f.name"></span><span class="vy-tc-file-size" x-text="f.size"></span></span>
                                                            <svg class="vy-tc-file-dl" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12M6 11l6 6 6-6M5 20h14"/></svg>
                                                        </a>
                                                    </template>
                                                    <template x-for="f in m.files.filter(f => f.kind === 'voice')" :key="f.id">
                                                        <div class="vy-tc-voice" x-data="vyVoice(f)">
                                                            <button type="button" class="vy-tc-play" @click="toggle()" :aria-label="playing ? @js(__('livewire/operator/team-chat.pause_voice_aria')) : @js(__('livewire/operator/team-chat.play_voice_aria'))">
                                                                <svg x-show="!playing" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg>
                                                                <svg x-show="playing" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
                                                            </button>
                                                            <span class="vy-tc-track" @click="seek($event)" role="slider" tabindex="0" aria-label="{{ __('livewire/operator/team-chat.position_aria') }}" aria-valuemin="0" :aria-valuemax="Math.round(length)" :aria-valuenow="Math.round(at)" :aria-valuetext="spoken()" @keydown="key($event)"><span class="vy-tc-track-fill" :style="{ width: pct + '%' }"></span></span>
                                                            <span class="vy-tc-voice-time" x-text="clock()"></span>
                                                        </div>
                                                    </template>
                                                    <p class="vy-tc-failed" x-show="m.failed"><span x-text="m.failed"></span></p>
                                                    <span class="vy-tc-progress" x-show="m.pending && m.progress !== null"><span :style="{ width: (m.progress || 0) + '%' }"></span></span>
                                                    <p class="vy-tc-note" x-show="m.redacted">{{ __('livewire/operator/team-chat.redacted_note') }}</p>

                                                    {{-- reactions: one chip per emoji; tapping yours takes it off --}}
                                                    <div class="vy-tc-reacts" x-show="(m.reactions || []).length" @click.stop>
                                                        <template x-for="r in (m.reactions || [])" :key="r.emoji">
                                                            <button type="button" class="vy-tc-react" :class="{ 'is-mine': r.mine }" @click="react(m, r.emoji)" :disabled="readOnly" :aria-pressed="r.mine ? 'true' : 'false'"
                                                                    :title="r.names.join(', ')" :aria-label="@js(__('livewire/operator/team-chat.reaction_chip_aria', ['emoji' => ':emoji', 'names' => ':names'])).replace(':emoji', () => r.emoji).replace(':names', () => r.names.join(', '))">
                                                                <span aria-hidden="true" x-text="r.emoji"></span><span class="vy-tc-react-n" aria-hidden="true" x-text="r.count"></span>
                                                            </button>
                                                        </template>
                                                    </div>

                                                    {{-- what you can do with it: react to any message, edit or delete your own --}}
                                                    <template x-if="!m.pending && !m.deleted && !readOnly">
                                                        <div class="vy-tc-tools" role="toolbar" aria-label="{{ __('livewire/operator/team-chat.tools_aria') }}" @click.stop>
                                                            <button type="button" class="vy-tc-tool" @click.stop="togglePicker(m)" :aria-expanded="pickerFor === m.id ? 'true' : 'false'" aria-haspopup="true" aria-label="{{ __('livewire/operator/team-chat.react_aria') }}" title="{{ __('livewire/operator/team-chat.react_aria') }}">
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9.5h.01M15 9.5h.01"/></svg>
                                                            </button>
                                                            <template x-if="m.mine">
                                                                <button type="button" class="vy-tc-tool" @click.stop="toggleMenu(m)" :aria-expanded="menuFor === m.id ? 'true' : 'false'" aria-haspopup="menu" aria-label="{{ __('livewire/operator/team-chat.options_aria') }}" title="{{ __('livewire/operator/team-chat.options_aria') }}">
                                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                                                </button>
                                                            </template>
                                                            <div class="vy-tc-picker" x-show="pickerFor === m.id" x-cloak role="group" aria-label="{{ __('livewire/operator/team-chat.picker_aria') }}" @click.outside="if (pickerFor === m.id) pickerFor = null" @keydown.escape.stop="pickerFor = null">
                                                                <template x-for="e in reactionList" :key="e">
                                                                    <button type="button" class="vy-tc-pick-emoji" @click.stop="react(m, e)" :aria-label="e" x-text="e"></button>
                                                                </template>
                                                            </div>
                                                            <div class="vy-tc-menu" x-show="menuFor === m.id" x-cloak role="menu" @click.outside="if (menuFor === m.id) closeMenu()" @keydown.escape.stop="closeMenu()">
                                                                <template x-if="confirmDelete !== m.id">
                                                                    <div class="vy-tc-menu-list">
                                                                        <button type="button" role="menuitem" class="vy-tc-menu-item" x-show="canEdit(m)" @click.stop="startEdit(m)">{{ __('livewire/operator/team-chat.edit') }}</button>
                                                                        <button type="button" role="menuitem" class="vy-tc-menu-item is-danger" @click.stop="confirmDelete = m.id">{{ __('livewire/operator/team-chat.delete') }}</button>
                                                                    </div>
                                                                </template>
                                                                <template x-if="confirmDelete === m.id">
                                                                    <div class="vy-tc-menu-confirm">
                                                                        <p>{{ __('livewire/operator/team-chat.delete_confirm') }}</p>
                                                                        <span>
                                                                            <button type="button" class="vy-btn vy-btn--ghost" @click.stop="closeMenu()">{{ __('common.cancel') }}</button>
                                                                            <button type="button" class="vy-btn vy-btn--danger" @click.stop="doDelete(m)">{{ __('livewire/operator/team-chat.delete') }}</button>
                                                                        </span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <span class="vy-tc-meta">
                                                <span x-text="clockOf(item.items[item.items.length - 1])"></span>
                                                <span x-show="item.mine && item.items.some(m => m.id === lastMineId)" x-text="receipt"></span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <button type="button" class="vy-tc-jump" x-show="unseenBelow > 0" @click="toBottom(true)" x-text="jumpLabel(unseenBelow)"></button>

                    {{-- ── The composer ─────────────────────────────────────────────────────────────────────────── --}}
                    <footer class="vy-tc-composer vy-tc-paused" x-show="readOnly">
                        <p>{{ __('livewire/operator/team-chat.paused') }}</p>
                    </footer>
                    <footer class="vy-tc-composer" x-show="!readOnly">
                        <p class="vy-tc-error" x-show="error" x-text="error" role="alert"></p>

                        <div class="vy-tc-tray" x-show="pending.length">
                            <template x-for="(p, i) in pending" :key="p.key">
                                <div class="vy-tc-chip" :class="{ 'is-image': p.kind === 'image' }">
                                    <template x-if="p.kind === 'image'"><img :src="p.preview" :alt="p.name"></template>
                                    <template x-if="p.kind === 'file'"><span class="vy-tc-chip-name" x-text="p.name"></span></template>
                                    <button type="button" class="vy-tc-chip-x" @click="removePending(i)" :aria-label="@js(__('livewire/operator/team-chat.remove_file_aria', ['name' => ':name'])).replace(':name', () => p.name)">×</button>
                                </div>
                            </template>
                        </div>

                        <div class="vy-tc-rec" x-show="rec.state !== 'idle'">
                            <template x-if="rec.state === 'starting'">
                                <div class="vy-tc-rec-row">
                                    <span class="vy-tc-rec-time">{{ __('livewire/operator/team-chat.starting_mic') }}</span>
                                    <button type="button" class="vy-btn vy-btn--ghost" @click="cancelRec()">{{ __('common.cancel') }}</button>
                                </div>
                            </template>
                            <template x-if="rec.state === 'recording'">
                                <div class="vy-tc-rec-row">
                                    <button type="button" class="vy-tc-icon" @click="cancelRec()" aria-label="{{ __('livewire/operator/team-chat.cancel_recording_aria') }}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>
                                    </button>
                                    <span class="vy-tc-rec-dot" aria-hidden="true"></span>
                                    <span class="vy-tc-rec-time" x-text="@js(__('livewire/operator/team-chat.recording', ['time' => ':time'])).replace(':time', mmss(rec.seconds))"></span>
                                    <span class="vy-tc-rec-cap" x-text="@js(__('livewire/operator/team-chat.recording_cap', ['time' => ':time'])).replace(':time', mmss(limits.voice))"></span>
                                    <button type="button" class="vy-tc-send" @click="stopRec()" aria-label="{{ __('livewire/operator/team-chat.stop_recording_aria') }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                                    </button>
                                </div>
                            </template>
                            <template x-if="rec.state === 'ready'">
                                <div class="vy-tc-rec-row">
                                    <button type="button" class="vy-tc-icon" @click="cancelRec()" aria-label="{{ __('livewire/operator/team-chat.discard_voice_aria') }}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>
                                    </button>
                                    <div class="vy-tc-voice vy-tc-voice--preview" x-data="vyVoice({ url: rec.url, seconds: rec.seconds })">
                                        <button type="button" class="vy-tc-play" @click="toggle()" :aria-label="playing ? @js(__('livewire/operator/team-chat.pause')) : @js(__('livewire/operator/team-chat.listen_before_sending'))">
                                            <svg x-show="!playing" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg>
                                            <svg x-show="playing" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
                                        </button>
                                        <span class="vy-tc-track" @click="seek($event)" role="slider" tabindex="0" aria-label="{{ __('livewire/operator/team-chat.position_aria') }}" aria-valuemin="0" :aria-valuemax="Math.round(length)" :aria-valuenow="Math.round(at)" :aria-valuetext="spoken()" @keydown="key($event)"><span class="vy-tc-track-fill" :style="{ width: pct + '%' }"></span></span>
                                        <span class="vy-tc-voice-time" x-text="clock()"></span>
                                    </div>
                                    <button type="button" class="vy-tc-send" @click="send()" :disabled="sending" aria-label="{{ __('livewire/operator/team-chat.send_voice_aria') }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="vy-tc-input" x-show="rec.state === 'idle'">
                            <button type="button" class="vy-tc-icon" @click="$refs.files.click()" aria-label="{{ __('livewire/operator/team-chat.attach_documents') }}" title="{{ __('livewire/operator/team-chat.attach_documents') }}">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                            </button>
                            <button type="button" class="vy-tc-icon" @click="$refs.photos.click()" aria-label="{{ __('livewire/operator/team-chat.add_pictures') }}" title="{{ __('livewire/operator/team-chat.add_pictures') }}">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            </button>
                            <input type="file" x-ref="files" class="vy-sr-only" multiple :accept="limits.accept" @change="addFiles($event.target.files); $event.target.value = ''" tabindex="-1" aria-hidden="true">
                            <input type="file" x-ref="photos" class="vy-sr-only" multiple accept="image/jpeg,image/png,image/webp,image/gif" @change="addFiles($event.target.files); $event.target.value = ''" tabindex="-1" aria-hidden="true">
                            <label class="vy-sr-only" for="vy-tc-text">{{ __('livewire/operator/team-chat.message_label') }}</label>
                            <textarea id="vy-tc-text" class="vy-tc-text" x-ref="text" rows="1" x-model="draft" @input="grow(); typingPing()" @keydown.enter="enter($event)" @paste="paste($event)" :maxlength="limits.body" :placeholder="current.kind === 'direct' ? @js(__('livewire/operator/team-chat.message_placeholder_direct', ['name' => ':name'])).replace(':name', () => current.name.split(' ')[0]) : @js(__('livewire/operator/team-chat.message_placeholder_group'))"></textarea>
                            <button type="button" class="vy-tc-send" x-show="draft.trim() || pending.length" @click="send()" :disabled="sending" aria-label="{{ __('livewire/operator/team-chat.send_aria') }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                            </button>
                            <button type="button" class="vy-tc-mic" x-show="!draft.trim() && !pending.length" @click="startRec()" aria-label="{{ __('livewire/operator/team-chat.record_voice') }}" title="{{ __('livewire/operator/team-chat.record_voice') }}">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/></svg>
                            </button>
                        </div>
                        <p class="vy-tc-hint" x-show="rec.state === 'idle'">{!! trans_choice('livewire/operator/team-chat.composer_hint', $boot['limits']['files'], ['count' => '<span x-text="limits.files"></span>', 'mb' => '<span x-text="limits.mb"></span>']) !!}</p>
                    </footer>

                    <div class="vy-tc-drop" x-show="dragging" aria-hidden="true"><span>{{ __('livewire/operator/team-chat.drop_to_attach') }}</span></div>
                </div>
            </template>
        </section>
    </div>
    @endif

</x-operator.shell>

@unless ($support)
    {{-- ── New conversation ───────────────────────────────────────────────────────────────────────────────── --}}
    <div x-show="newOpen" x-cloak class="vy-modal-backdrop" @click.self="newOpen = false" @keydown.escape.window="newOpen = false">
        <div class="vy-modal vy-modal-card vy-tc-dlg" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="newOpen" aria-labelledby="vy-tc-new-title">
            <div class="vy-modal-head">
                <span class="vy-modal-ico" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v6M9 10h6"/></svg></span>
                <div class="vy-tc-dlg-head">
                    <h2 class="vy-modal-title" id="vy-tc-new-title">{{ __('livewire/operator/team-chat.new_conversation') }}</h2>
                    <p class="vy-modal-sub">{{ __('livewire/operator/team-chat.new_dialog_sub') }}</p>
                </div>
                <button type="button" class="vy-modal-x" @click="newOpen = false" aria-label="{{ __('common.close') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
            </div>
            <div class="vy-modal-body">
                <label class="vy-gl-search vy-tc-search">
                    <svg class="vy-gl-search-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <span class="vy-sr-only">{{ __('livewire/operator/team-chat.find_colleague') }}</span>
                    <input type="search" class="vy-gl-search-in" x-model="pickFind" placeholder="{{ __('livewire/operator/team-chat.find_colleague') }}" autocomplete="off">
                </label>
                <fieldset class="vy-tc-pick">
                    <legend class="vy-sr-only">{{ __('livewire/operator/team-chat.colleagues_legend') }}</legend>
                    <template x-for="p in pickList" :key="'k' + p.id">
                        <label class="vy-tc-pick-row" :class="{ 'is-on': pick.includes(p.id) }">
                            <input type="checkbox" :value="p.id" x-model="pick">
                            <span class="vy-tc-av" aria-hidden="true"><span x-text="p.initials"></span></span>
                            <span class="vy-tc-pick-text"><strong x-text="p.name"></strong><span x-text="p.role"></span></span>
                            <span class="vy-tc-tick" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        </label>
                    </template>
                    <p class="vy-tc-none" x-show="!pickList.length">{!! __('livewire/operator/team-chat.nobody_matches', ['term' => '<span x-text="pickFind"></span>']) !!}</p>
                </fieldset>
                <div class="vy-field vy-tc-group-name" x-show="pick.length > 1">
                    <label class="vy-field-label" for="vy-tc-group-title">{{ __('livewire/operator/team-chat.group_name_label') }}</label>
                    <input id="vy-tc-group-title" type="text" class="vy-input" x-model="groupTitle" maxlength="80" placeholder="{{ __('livewire/operator/team-chat.group_name_placeholder') }}">
                </div>
                <p class="vy-tc-error" x-show="newError" x-text="newError" role="alert"></p>
            </div>
            <div class="vy-modal-foot">
                <button type="button" class="vy-btn vy-btn--ghost" @click="newOpen = false">{{ __('common.cancel') }}</button>
                <button type="button" class="vy-btn vy-btn--accent" @click="startWith(pick, groupTitle)" :disabled="!pick.length || starting"
                        x-text="starting ? @js(__('livewire/operator/team-chat.starting')) : (pick.length > 1 ? @js(__('livewire/operator/team-chat.start_group')) : @js(__('livewire/operator/team-chat.start_conversation')))"></button>
            </div>
        </div>
    </div>

    {{-- ── A picture, full size ───────────────────────────────────────────────────────────────────────────── --}}
    <div x-show="viewer" x-cloak class="vy-modal-backdrop vy-tc-viewer" @click.self="viewer = null" @keydown.escape.window="viewer = null">
        <div class="vy-modal vy-tc-viewer-card" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="viewer" aria-labelledby="vy-tc-viewer-title">
            <div class="vy-tc-viewer-bar">
                <h2 class="vy-tc-viewer-name" id="vy-tc-viewer-title" x-text="viewer ? viewer.name : ''"></h2>
                <a class="vy-btn vy-btn--ghost" :href="viewer ? viewer.url : null" download>{{ __('common.download') }}</a>
                <button type="button" class="vy-modal-x" @click="viewer = null" aria-label="{{ __('common.close') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
            </div>
            <img class="vy-tc-viewer-img" :src="viewer ? viewer.url : ''" :alt="viewer ? viewer.name : ''">
        </div>
    </div>

    @include('partials.chat-media')
    {{-- A count's words are computed HERE, per count (a script cannot pluralise for every language): a group's members (up to
         everyone here), the unread and new-message counts (1–99), the files of one message. A count outside its list falls back
         to the many-form. --}}
    @php
        $__tcPl = [
            'members' => collect(range(1, max(2, count($boot['teammates']) + 1)))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/team-chat.members_line', $n, ['count' => $n, 'names' => ':names'])])->all(),
            'membersOther' => trans_choice('livewire/operator/team-chat.members_line', 2, ['count' => ':count', 'names' => ':names']),
            'unread' => collect(range(1, 99))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/team-chat.unread_aria', $n, ['count' => $n])])->all(),
            'unreadOther' => trans_choice('livewire/operator/team-chat.unread_aria', 2, ['count' => ':count']),
            'jump' => collect(range(1, 99))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/team-chat.new_messages_jump', $n, ['count' => $n])])->all(),
            'jumpOther' => trans_choice('livewire/operator/team-chat.new_messages_jump', 2, ['count' => ':count']),
            'online' => collect(range(1, max(1, count($boot['teammates']))))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/team-chat.online_count', $n, ['count' => $n])])->all(),
            'onlineOther' => trans_choice('livewire/operator/team-chat.online_count', 2, ['count' => ':count']),
            'files' => collect(range(1, max(1, (int) $boot['limits']['files'])))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/team-chat.files_count', $n, ['count' => $n])])->all(),
            'filesOther' => trans_choice('livewire/operator/team-chat.files_count', 2, ['count' => ':count']),
        ];
    @endphp
    <script type="application/json" id="vy-team-chat-data">@json($boot)</script>
    <script>
        document.addEventListener('alpine:init', () => {
            const boot = JSON.parse(document.getElementById('vy-team-chat-data').textContent);
            const POLL_VISIBLE = 3000, POLL_HIDDEN = 20000, IDLE_MS = 30 * 60 * 1000, PRESENT_MS = 2 * 60 * 1000;
            const RUN_GAP = 5 * 60 * 1000;
            const PICTURES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            const DOCS = boot.limits.accept.split(',').map((x) => x.replace('.', '').toLowerCase());
            // the words this script shows (translated on the server), and each count's words, per count
            const T = {
                formerTeammate: @js(__('livewire/operator/team-chat.former_teammate')),
                sent: @js(__('livewire/operator/team-chat.receipt_sent')),
                seen: @js(__('livewire/operator/team-chat.receipt_seen')),
                seenByEveryone: @js(__('livewire/operator/team-chat.receipt_seen_by_everyone')),
                seenBy: @js(__('livewire/operator/team-chat.receipt_seen_by', ['names' => ':names'])),
                today: @js(__('common.today')),
                yesterday: @js(__('livewire/operator/team-chat.yesterday')),
                notSent: @js(__('livewire/operator/team-chat.not_sent')),
                sending: @js(__('livewire/operator/team-chat.sending')),
                signedOut: @js(__('livewire/operator/team-chat.signed_out_banner')),
                startFailed: @js(__('livewire/operator/team-chat.start_failed')),
                tooManyFiles: @js(trans_choice('livewire/operator/team-chat.too_many_files', $boot['limits']['files'], ['count' => $boot['limits']['files']])),
                wrongFileType: @js(__('livewire/operator/team-chat.wrong_file_type')),
                fileTooBig: @js(__('livewire/operator/team-chat.file_too_big', ['name' => ':name', 'mb' => ':mb'])),
                messageTooLong: @js(__('livewire/operator/team-chat.message_too_long')),
                filesTooBigTogether: @js(__('livewire/operator/team-chat.files_too_big_together', ['mb' => ':mb'])),
                voiceMessage: @js(__('livewire/operator/team-chat.voice_message')),
                photo: @js(__('livewire/operator/team-chat.photo')),
                file: @js(__('livewire/operator/team-chat.file')),
                notSentNoticeTo: @js(__('livewire/operator/team-chat.not_sent_notice_to', ['name' => ':name'])),
                notSentNotice: @js(__('livewire/operator/team-chat.not_sent_notice')),
                reasonSignedOut: @js(__('livewire/operator/team-chat.reason_signed_out')),
                reasonNoConnection: @js(__('livewire/operator/team-chat.reason_no_connection')),
                reasonTooLarge: @js(__('livewire/operator/team-chat.reason_too_large')),
                reasonTooMany: @js(__('livewire/operator/team-chat.reason_too_many')),
                reasonFailed: @js(__('livewire/operator/team-chat.reason_failed')),
                nothingRecorded: @js(__('livewire/operator/team-chat.nothing_recorded')),
                editEmpty: @js(__('livewire/operator/team-chat.edit_empty')),
                editFailed: @js(__('livewire/operator/team-chat.edit_failed')),
                deleteFailed: @js(__('livewire/operator/team-chat.delete_failed')),
                reactionFailed: @js(__('livewire/operator/team-chat.reaction_failed')),
                searchFailed: @js(__('livewire/operator/team-chat.search_failed')),
                searchTooFar: @js(__('livewire/operator/team-chat.search_too_far')),
                searchHint: @js(__('livewire/operator/team-chat.search_hint')),
                typingOne: @js(__('livewire/operator/team-chat.typing_one', ['name' => ':name'])),
                typingTwo: @js(__('livewire/operator/team-chat.typing_two', ['a' => ':a', 'b' => ':b'])),
                typingMany: @js(__('livewire/operator/team-chat.typing_many')),
                onlineNow: @js(__('livewire/operator/team-chat.online_now')),
                desktopHelp: @js(__('livewire/operator/team-chat.desktop_help')),
            };
            const PL = @js($__tcPl);
            const plural = (map, other, n) => (map[n] !== undefined ? map[n] : other.replace(':count', String(n)));
            // I18N-01 — dates in the PAGE's language; English keeps the browser's, as it always has
            const DATE_LOCALE = @js(app()->getLocale() === 'en' ? null : \App\Support\I18n\Locales::dateTag()) ?? undefined;

            // the things that must never become reactive (the recorder, timers) live outside Alpine's state
            let recording = null, recToken = 0, pollTimer = null, readTimer = null;
            let lastActive = Date.now(), polling = false, tempSeq = 0, fails = 0;
            // D-TC-LATER (2026-11-11): the SERVER's clock at the last poll answer — handed back as `since`, so the poll also
            // returns what was edited, deleted, reacted to or scrubbed meanwhile (a message the page already holds)
            let since = boot.serverTime || null;
            // when "typing" was last sent (at most every 3 s), a touch-hold on a message, and the newest search
            let lastTypingPost = 0, pressTimer = null, searchToken = 0, foundTimer = null;
            const TYPING_EVERY = 3000;
            // ⚠️ THE CURSOR (the independent review, 2026-10-06): the newest message the SERVER has delivered to this page —
            // never one this page sent. The poll asks from it (the server looks 30 seconds further back) and every message is
            // kept once, by id, in id order; a colleague's message is never skipped because yours was newer.
            let cursor = null;
            // the conversation a "not sent" notice is about — opening it answers the notice
            let noticeFor = null;
            const IDLE_REC = () => ({ state: 'idle', seconds: 0, url: null, mime: '' });
            const byId = (a, b) => (a.id < b.id ? -1 : (a.id > b.id ? 1 : 0));
            // the person is at the page: visible, focused, and has touched it lately — only then is anything read for them
            const present = () => !document.hidden && document.hasFocus() && Date.now() - lastActive < PRESENT_MS;

            const pad = (n) => String(n).padStart(2, '0');
            const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

            Alpine.data('vyTeamChat', () => ({
                me: boot.me,
                teammates: boot.teammates,
                conversations: boot.conversations,
                active: boot.active,
                messages: boot.messages,
                reads: boot.reads,
                urls: boot.urls,
                limits: boot.limits,
                hasOlder: boot.messages.length >= boot.page,
                readOnly: boot.readOnly,
                loading: false,
                loadingOlder: false,
                find: '',
                draft: '',
                // what each conversation's composer held when you left it — the words, the files, a recording, a refusal
                stash: {},
                pending: [],
                error: '',
                notice: '',
                sending: false,
                offline: '',
                newOpen: false,
                newError: '',
                pick: [],
                pickFind: '',
                groupTitle: '',
                starting: false,
                viewer: null,
                dragging: false,
                unseenBelow: 0,
                sound: window.VyLive ? window.VyLive.soundOn() : true,
                desktop: window.VyLive ? window.VyLive.desktopOn() : false,
                rec: IDLE_REC(),
                // edit · delete · reactions (D-TC-LATER): which message's tools, options menu, reaction picker or delete
                // confirmation is open, and the edit in progress
                toolsFor: null,
                menuFor: null,
                pickerFor: null,
                confirmDelete: null,
                // the id of the message being edited (or null), its words, and why a save was refused — scalars, so a binding never reads into null
                editing: null,
                editText: '',
                editError: '',
                editSaving: false,
                reactionList: boot.reactions,
                // typing, and who of the people you talk to is online (an empty list when the server cannot tell)
                typing: [],
                online: boot.online || [],
                // search in this conversation
                searchOpen: false,
                chatQuery: '',
                found: [],
                searchLine: '',
                foundId: null,

                init() {
                    this.advance(this.messages);
                    this.$nextTick(() => { this.toBottom(); if (this.active) this.markReadSoon(); });
                    this.schedule();
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) { this.poll(); this.markReadSoon(); this.schedule(); }
                    });
                    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach((type) => window.addEventListener(type, () => {
                        const wasIdle = Date.now() - lastActive > IDLE_MS;
                        lastActive = Date.now();
                        if (wasIdle) { this.poll(); this.schedule(); }
                        if (this.current && this.current.unread > 0) this.markReadSoon();
                    }, { passive: true }));
                    window.addEventListener('focus', () => this.markReadSoon());
                    document.addEventListener('vy:sound', (e) => { this.sound = !!(e.detail && e.detail.on); });
                    document.addEventListener('vy:desktop', (e) => { this.desktop = !!(e.detail && e.detail.on); });
                    window.addEventListener('popstate', () => {
                        const c = new URLSearchParams(location.search).get('c');
                        if (c && c !== this.active) this.open(c, false);
                        if (!c) this.active = null;
                    });
                    if (window.VyLive) window.VyLive.observe({ team: boot.unread });
                },

                // ── what the page shows ──────────────────────────────────────────────────────────────────
                get current() { return this.conversations.find((c) => c.id === this.active) || null; },
                get rail() {
                    const q = this.find.trim().toLowerCase();
                    if (!q) return this.conversations;
                    return this.conversations.filter((c) => c.name.toLowerCase().includes(q) || c.members.some((m) => m.name.toLowerCase().includes(q)));
                },
                get people() {
                    const q = this.find.trim().toLowerCase();
                    if (!q) return [];
                    const withDirect = new Set(this.conversations.filter((c) => c.kind === 'direct').flatMap((c) => c.members.map((m) => m.id)));
                    return this.teammates.filter((p) => !withDirect.has(p.id) && (p.name.toLowerCase().includes(q) || (p.role || '').toLowerCase().includes(q)));
                },
                get pickList() {
                    const q = this.pickFind.trim().toLowerCase();
                    return q ? this.teammates.filter((p) => p.name.toLowerCase().includes(q) || (p.role || '').toLowerCase().includes(q)) : this.teammates;
                },
                get headSub() {
                    const c = this.current;
                    if (!c) return '';
                    if (c.kind === 'group') {
                        const line = plural(PL.members, PL.membersOther, c.members.length).replace(':names', () => c.members.filter((m) => m.id !== this.me.id).map((m) => m.name.split(' ')[0]).join(', '));
                        const here = c.members.filter((m) => m.id !== this.me.id && this.online.includes(m.id)).length;
                        return here ? line + ' · ' + plural(PL.online, PL.onlineOther, here) : line;
                    }
                    const other = c.members.find((m) => m.id !== this.me.id);
                    const mate = other && this.teammates.find((p) => p.id === other.id);
                    const line = mate ? mate.role : T.formerTeammate;
                    return this.isOnline(c) ? line + ' · ' + T.onlineNow : line;
                },
                /** Who is typing in the open conversation, in words (first names) — empty when nobody is. */
                get typingLine() {
                    const names = this.typing.map((p) => p.name.split(' ')[0]);
                    if (!names.length) return '';
                    if (names.length === 1) return T.typingOne.replace(':name', () => names[0]);
                    if (names.length === 2) return T.typingTwo.replace(':a', () => names[0]).replace(':b', () => names[1]);
                    return T.typingMany;
                },
                /** The other person in a direct conversation is online now. */
                isOnline(c) {
                    if (!c || c.kind !== 'direct') return false;
                    const other = c.members.find((m) => m.id !== this.me.id);
                    return !!other && this.online.includes(other.id);
                },
                get timeline() {
                    const out = [];
                    let day = null, run = null;
                    for (const m of this.messages) {
                        const at = new Date(m.at);
                        if (!day || !sameDay(day, at)) {
                            day = at;
                            out.push({ key: 'd' + m.id, type: 'day', label: this.dayLabel(at) });
                            run = null;
                        }
                        const sid = m.sender ? m.sender.id : null;
                        if (!run || run.sid !== sid || at - run.lastAt > RUN_GAP) {
                            run = { key: 'r' + m.id, type: 'run', sid, mine: m.mine, sender: m.sender || { name: T.formerTeammate, initials: '?' }, items: [], lastAt: at };
                            out.push(run);
                        }
                        run.items.push(m);
                        run.lastAt = at;
                    }
                    return out;
                },
                get lastMineId() {
                    for (let i = this.messages.length - 1; i >= 0; i--) {
                        if (this.messages[i].mine && !this.messages[i].pending) return this.messages[i].id;
                    }
                    return null;
                },
                get receipt() {
                    const id = this.lastMineId;
                    if (!id) return '';
                    const seen = this.reads.filter((r) => r.message_id && r.message_id >= id);
                    if (!this.current || !seen.length) return T.sent;
                    if (this.current.kind === 'direct') return T.seen;
                    return seen.length === this.reads.length ? T.seenByEveryone : T.seenBy.replace(':names', () => seen.map((r) => r.name.split(' ')[0]).join(', '));
                },

                // ── words and times ──────────────────────────────────────────────────────────────────────
                dayLabel(d) {
                    const today = new Date(), yesterday = new Date(Date.now() - 86400000);
                    if (sameDay(d, today)) return T.today;
                    if (sameDay(d, yesterday)) return T.yesterday;
                    if (today - d < 6 * 86400000) return d.toLocaleDateString(DATE_LOCALE, { weekday: 'long' });
                    return d.toLocaleDateString(DATE_LOCALE, { day: 'numeric', month: 'long', year: d.getFullYear() === today.getFullYear() ? undefined : 'numeric' });
                },
                railTime(iso) {
                    const d = new Date(iso), now = new Date();
                    if (sameDay(d, now)) return pad(d.getHours()) + ':' + pad(d.getMinutes());
                    if (now - d < 6 * 86400000) return d.toLocaleDateString(DATE_LOCALE, { weekday: 'short' });
                    return d.toLocaleDateString(DATE_LOCALE, { day: 'numeric', month: 'short' });
                },
                clockOf(m) {
                    if (!m) return '';
                    if (m.pending) return m.failed ? T.notSent : T.sending;
                    const d = new Date(m.at);
                    return pad(d.getHours()) + ':' + pad(d.getMinutes());
                },
                mmss(s) { s = Math.max(0, Math.round(s || 0)); return Math.floor(s / 60) + ':' + pad(s % 60); },
                unreadLabel(n) { return plural(PL.unread, PL.unreadOther, n); },
                jumpLabel(n) { return plural(PL.jump, PL.jumpOther, n); },

                // ── the network ──────────────────────────────────────────────────────────────────────────
                /**
                 * JSON from the server. Signed out (401, 419, or sent to another page — the sign-in, the paywall) stops the
                 * page and says so; busy or failing (429, 5xx, no connection) only slows the next ask (the review).
                 */
                async request(url, options = {}) {
                    const headers = Object.assign({ Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});
                    if (options.method && options.method !== 'GET') headers['X-CSRF-TOKEN'] = boot.csrf;
                    let res;
                    try {
                        res = await fetch(url, Object.assign({ credentials: 'same-origin', cache: 'no-store' }, options, { headers }));
                    } catch (e) {
                        fails++;
                        return { ok: false, status: 0, data: null };
                    }
                    if (res.redirected || res.status === 401 || res.status === 419) {
                        this.signedOut();
                        return { ok: false, status: res.status, data: null };
                    }
                    const json = (res.headers.get('content-type') || '').includes('application/json');
                    const data = json ? await res.json().catch(() => null) : null;
                    if (!json || res.status === 429 || res.status >= 500) fails++; else fails = 0;
                    return { ok: res.ok && json, status: res.status, data };
                },
                signedOut() {
                    this.offline = T.signedOut;
                    this.stopPolling();
                },
                pollUrl(params) {
                    const u = new URL(this.urls.poll, location.origin);
                    Object.entries(params).forEach(([k, v]) => { if (v) u.searchParams.set(k, v); });
                    return u.toString();
                },
                /**
                 * Messages the server sent, kept once each, in id order — the ones still sending stay last. Returns the new ones.
                 * ⚠️ An UPSERT (D-TC-LATER, 2026-11-11): a message the page already holds is REPLACED by the server's copy — that is
                 * how an edit, a delete, a reaction or a removed name reaches an open screen. One the page does not hold and
                 * that is older than everything it has loaded is left out (it comes with "Load earlier messages", in its place).
                 */
                merge(list) {
                    const real = this.messages.filter((m) => !m.pending);
                    const held = new Set(real.map((m) => m.id));
                    const oldest = real.reduce((a, m) => (a === null || m.id < a ? m.id : a), null);
                    const replaced = new Map(), fresh = [];
                    for (const m of list) {
                        if (held.has(m.id)) replaced.set(m.id, m);
                        else if (oldest === null || m.id > oldest) fresh.push(m);
                    }
                    if (replaced.size || fresh.length) {
                        this.messages = real.map((m) => replaced.get(m.id) || m).concat(fresh).sort(byId).concat(this.messages.filter((m) => m.pending));
                    }
                    return fresh;
                },
                advance(list) { for (const m of list) if (!m.pending && (!cursor || m.id > cursor)) cursor = m.id; },

                schedule() {
                    clearTimeout(pollTimer);
                    if (this.offline || Date.now() - lastActive > IDLE_MS) return;   // idle: the next key or click starts it again
                    const wait = (document.hidden ? POLL_HIDDEN : POLL_VISIBLE) * Math.pow(2, Math.min(fails, 5));
                    pollTimer = setTimeout(async () => { await this.poll(); this.schedule(); }, Math.min(wait, 2 * 60 * 1000));
                },
                stopPolling() { clearTimeout(pollTimer); pollTimer = null; },

                async poll() {
                    if (polling || this.offline) return;
                    polling = true;
                    const openId = this.active;
                    try {
                        const before = Object.fromEntries(this.conversations.map((c) => [c.id, c.unread]));
                        const { ok, data } = await this.request(this.pollUrl({ c: openId, after: openId ? cursor : null, since: openId ? since : null }));
                        if (!ok || !data) return;
                        this.conversations = data.conversations;
                        this.online = data.online || [];
                        let chime = data.conversations.some((c) => c.unread > (before[c.id] || 0) && (c.id !== this.active || document.hidden));
                        if (openId && openId === this.active && !this.loading) {
                            this.reads = data.reads;
                            this.typing = data.typing || [];
                            const near = this.nearBottom();
                            const fresh = this.merge(data.messages);
                            this.advance(data.messages);
                            if (data.server_time) since = data.server_time;
                            if (fresh.length) {
                                const incoming = fresh.filter((m) => !m.mine).length;
                                if (incoming && document.hidden) chime = true;
                                if (near) { this.$nextTick(() => this.toBottom(true)); } else { this.unseenBelow += incoming; }
                                if (incoming) this.markReadSoon();
                            }
                        }
                        // the shared counts first, so the desktop notification (if on, and you are elsewhere) says today's number
                        this.tellPulse(data);
                        if (chime && window.VyLive) { window.VyLive.chime(); window.VyLive.desktopNotify(); }
                    } finally {
                        polling = false;
                    }
                },

                markReadSoon() {
                    clearTimeout(readTimer);
                    readTimer = setTimeout(() => this.markRead(), 400);
                },
                async markRead() {
                    const c = this.current;
                    if (!c || !present() || !cursor) return;
                    const upto = cursor;   // what the page was shown — never past it
                    const { ok, data } = await this.request(this.urls.read.replace('__ID__', c.id), {
                        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ upto }),
                    });
                    if (!ok || !data) return;
                    const row = this.conversations.find((x) => x.id === c.id);
                    if (row) row.unread = 0;
                    this.tellPulse(data);
                },
                // what this page has seen goes to the shared pulse, so the bell, the badges and the title move with it — and it
                // does not chime again for the same message
                tellPulse(data) {
                    if (!window.VyLive) return;
                    const before = window.VyLive.counts() || {};
                    window.VyLive.observe({ team: data.unread_total, notifications: data.notifications });
                    if (before.notifications !== undefined && before.notifications !== data.notifications) window.VyLive.refreshBell();   // coalesced there
                },

                // ── opening a conversation ───────────────────────────────────────────────────────────────
                async open(id, push = true) {
                    this.saveComposer();
                    if (noticeFor === id) { this.notice = ''; noticeFor = null; }
                    this.active = id;
                    this.messages = [];
                    this.reads = [];
                    this.unseenBelow = 0;
                    cursor = null;
                    since = null;
                    lastTypingPost = 0;
                    this.typing = [];
                    this.resetTools();
                    this.restoreComposer(id);
                    this.loading = true;
                    if (push) history.pushState({ c: id }, '', this.urls.page + '?c=' + encodeURIComponent(id));
                    const { ok, data } = await this.request(this.pollUrl({ c: id }));
                    if (this.active !== id) return;
                    this.loading = false;
                    if (!ok || !data) return;
                    this.conversations = data.conversations;
                    this.messages = data.messages;
                    cursor = null;
                    this.advance(data.messages);
                    this.reads = data.reads;
                    this.online = data.online || [];
                    since = data.server_time || null;
                    this.hasOlder = data.messages.length >= boot.page;
                    this.$nextTick(() => { this.toBottom(); this.grow(); this.$refs.text && this.$refs.text.focus(); });
                    this.markReadSoon();
                },
                back() {
                    this.saveComposer();
                    this.resetTools();
                    this.active = null;
                    history.pushState({}, '', this.urls.page);
                },
                /**
                 * Leaving a conversation keeps what its composer held — the words, the files, a finished recording — for when
                 * you come back; nothing written for one person is ever sent to another. A recording still going is stopped.
                 */
                saveComposer() {
                    if (!this.active) return;
                    if (this.rec.state === 'recording' || this.rec.state === 'starting') this.cancelRec();
                    if (this.draft || this.pending.length || this.rec.state === 'ready' || this.error) {
                        this.stash[this.active] = { draft: this.draft, pending: this.pending, rec: this.rec, error: this.error };
                    }
                    this.draft = '';
                    this.pending = [];
                    this.rec = IDLE_REC();
                    this.error = '';
                },
                restoreComposer(id) {
                    const kept = this.stash[id];
                    delete this.stash[id];
                    this.draft = kept ? kept.draft : '';
                    this.pending = kept ? kept.pending : [];
                    this.rec = kept ? kept.rec : IDLE_REC();
                    this.error = kept ? kept.error : '';
                },
                /** A conversation holding a message that was not sent while you were elsewhere. */
                waiting(id) { return !!(this.stash[id] && this.stash[id].error); },
                async loadOlder() {
                    if (!this.messages.length || this.loadingOlder) return;
                    this.loadingOlder = true;
                    const el = this.$refs.stream, height = el ? el.scrollHeight : 0;
                    const { ok, data } = await this.request(this.pollUrl({ c: this.active, before: this.messages[0].id }));
                    this.loadingOlder = false;
                    if (!ok || !data) return;
                    this.messages = data.messages.concat(this.messages);
                    this.hasOlder = data.messages.length >= boot.page;
                    this.$nextTick(() => { if (el) el.scrollTop = el.scrollHeight - height; });
                },

                // ── new conversations ────────────────────────────────────────────────────────────────────
                openNew() { this.pick = []; this.pickFind = ''; this.groupTitle = ''; this.newError = ''; this.newOpen = true; },
                async startWith(ids, title = '') {
                    if (!ids.length || this.starting) return;
                    this.starting = true;
                    this.newError = '';
                    const { ok, data } = await this.request(this.urls.start, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ members: ids, title: ids.length > 1 ? title : '' }),
                    });
                    this.starting = false;
                    if (!ok || !data || !data.conversation) {
                        this.newError = (data && data.message) || T.startFailed;
                        return;
                    }
                    if (!this.conversations.some((c) => c.id === data.conversation.id)) this.conversations.unshift(data.conversation);
                    this.newOpen = false;
                    this.find = '';
                    this.open(data.conversation.id);
                },

                // ── writing ──────────────────────────────────────────────────────────────────────────────
                grow() {
                    const t = this.$refs.text;
                    if (!t) return;
                    t.style.height = 'auto';
                    t.style.height = Math.min(t.scrollHeight, 140) + 'px';
                },
                enter(e) {
                    if (e.shiftKey || e.isComposing) return;
                    e.preventDefault();
                    this.send();
                },
                addFiles(list) {
                    this.error = '';
                    for (const file of Array.from(list || [])) {
                        if (this.pending.length >= this.limits.files) { this.error = T.tooManyFiles; break; }
                        const ext = (file.name.split('.').pop() || '').toLowerCase();
                        const image = PICTURES.includes(file.type);
                        if (!image && !DOCS.includes(ext)) { this.error = T.wrongFileType; continue; }
                        if (file.size > this.limits.mb * 1048576) { this.error = T.fileTooBig.replace(':mb', () => String(this.limits.mb)).replace(':name', () => file.name); continue; }
                        this.pending.push({ key: 'f' + (++tempSeq), file, name: file.name, kind: image ? 'image' : 'file', preview: image ? URL.createObjectURL(file) : null });
                    }
                    this.$nextTick(() => this.$refs.text && this.$refs.text.focus());
                },
                removePending(i) {
                    const p = this.pending[i];
                    if (p && p.preview) URL.revokeObjectURL(p.preview);
                    this.pending.splice(i, 1);
                },
                paste(e) {
                    const files = Array.from((e.clipboardData && e.clipboardData.files) || []).filter((f) => PICTURES.includes(f.type));
                    if (files.length) { e.preventDefault(); this.addFiles(files); }
                },
                dragIn(e) { if (this.active && e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files')) this.dragging = true; },
                dragOut(e) { if (!e.currentTarget.contains(e.relatedTarget)) this.dragging = false; },
                dropFiles(e) { this.dragging = false; if (this.active && e.dataTransfer) this.addFiles(e.dataTransfer.files); },

                /** "I am typing" — at most every 3 seconds, while there are words in the box. Fire and forget: it must never slow the poll. */
                typingPing() {
                    if (this.readOnly || !this.active || !this.draft.trim()) return;
                    const now = Date.now();
                    if (now - lastTypingPost < TYPING_EVERY) return;
                    lastTypingPost = now;
                    fetch(this.urls.typing.replace('__ID__', this.active), {
                        method: 'POST', credentials: 'same-origin',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': boot.csrf },
                    }).catch(() => {});
                },

                // ── edit, delete, react (D-TC-LATER) ─────────────────────────────────────────────────────
                resetTools() {
                    this.toolsFor = this.menuFor = this.pickerFor = this.confirmDelete = this.editing = null;
                    this.searchOpen = false; this.chatQuery = ''; this.found = []; this.searchLine = ''; this.foundId = null;
                },
                replaceMessage(row) { this.messages = this.messages.map((x) => (x.id === row.id ? row : x)); },
                /** Your own message, still inside the edit window (the server decides — this only hides the button). */
                canEdit(m) { return !!(m.mine && !m.pending && !m.deleted && m.editable_until && Date.now() < Date.parse(m.editable_until)); },
                toggleMenu(m) { this.pickerFor = null; this.confirmDelete = null; this.menuFor = this.menuFor === m.id ? null : m.id; },
                closeMenu() { this.menuFor = null; this.confirmDelete = null; },
                togglePicker(m) { this.closeMenu(); this.pickerFor = this.pickerFor === m.id ? null : m.id; },
                /** A touch held on a message opens the reaction picker (there is no hover on a phone). */
                pressStart(m) {
                    clearTimeout(pressTimer);
                    pressTimer = setTimeout(() => { if (!m.pending && !m.deleted && !this.readOnly) { this.toolsFor = m.id; this.menuFor = null; this.pickerFor = m.id; } }, 450);
                },
                pressEnd() { clearTimeout(pressTimer); },
                startEdit(m) {
                    this.closeMenu();
                    this.toolsFor = null;
                    this.editing = m.id;
                    this.editText = m.body || '';
                    this.editError = '';
                    this.editSaving = false;
                    this.$nextTick(() => {
                        const box = document.getElementById('vy-tc-edit-' + m.id);
                        if (box) { box.focus(); box.setSelectionRange(box.value.length, box.value.length); }
                    });
                },
                cancelEdit() { this.editing = null; },
                editEnter(e) {
                    if (e.shiftKey || e.isComposing) return;
                    e.preventDefault();
                    this.saveEdit();
                },
                async saveEdit() {
                    const id = this.editing;
                    if (!id || this.editSaving) return;
                    const m = this.messages.find((x) => x.id === id);
                    const text = this.editText.trim();
                    if (m && text === (m.body || '').trim()) { this.editing = null; return; }   // nothing changed
                    if (!text && !(m && m.files.length)) { this.editError = T.editEmpty; return; }
                    if (text.length > this.limits.body) { this.editError = T.messageTooLong; return; }
                    this.editSaving = true;
                    this.editError = '';
                    const { ok, status, data } = await this.request(this.urls.message.replace('__ID__', id), {
                        method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ body: text }),
                    });
                    if (this.editing !== id) return;   // moved on meanwhile
                    this.editSaving = false;
                    if (ok && data && data.message) { this.replaceMessage(data.message); this.editing = null; return; }
                    this.editError = (data && data.message && (status === 422 || status === 404)) ? data.message : T.editFailed;
                },
                async doDelete(m) {
                    this.closeMenu();
                    this.toolsFor = null;
                    const { ok, status, data } = await this.request(this.urls.message.replace('__ID__', m.id), { method: 'DELETE' });
                    if (ok && data && data.message) { this.replaceMessage(data.message); return; }
                    this.notice = (data && data.message && (status === 422 || status === 404)) ? data.message : T.deleteFailed;
                    noticeFor = null;
                },
                async react(m, emoji) {
                    this.pickerFor = null;
                    this.toolsFor = null;
                    if (this.readOnly || m.pending) return;
                    const { ok, status, data } = await this.request(this.urls.react.replace('__ID__', m.id), {
                        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ emoji }),
                    });
                    if (ok && data && data.message) { this.replaceMessage(data.message); return; }
                    this.notice = (data && data.message && (status === 422 || status === 404)) ? data.message : T.reactionFailed;
                    noticeFor = null;
                },

                // ── search in this conversation ──────────────────────────────────────────────────────────
                toggleSearch() {
                    if (this.searchOpen) { this.closeSearch(); return; }
                    this.searchOpen = true;
                    this.$nextTick(() => this.$refs.findInput && this.$refs.findInput.focus());
                },
                closeSearch() { this.searchOpen = false; },
                async runSearch() {
                    const q = this.chatQuery.trim(), mine = ++searchToken, convId = this.active;
                    if (q.length < 2) { this.found = []; this.searchLine = q.length ? T.searchHint : ''; return; }
                    const url = new URL(this.urls.search.replace('__ID__', convId), location.origin);
                    url.searchParams.set('q', q);
                    const { ok, data } = await this.request(url.toString());
                    if (mine !== searchToken || convId !== this.active) return;   // a newer search, or another conversation
                    if (!ok || !data || !data.results) { this.found = []; this.searchLine = (data && data.message) || T.searchFailed; return; }
                    this.found = data.results;
                    this.searchLine = data.summary;
                },
                /**
                 * Take the stream to a found message. A message older than the page holds is reached by loading earlier pages one
                 * after another (at most ten — 500 messages back); beyond that it says so. ⚠️ A server "load around this message"
                 * would reach any age at once — a later step.
                 */
                async goTo(id) {
                    let pages = 0;
                    while (!this.messages.some((m) => m.id === id) && this.hasOlder && pages < 10) { await this.loadOlder(); pages++; }
                    await this.$nextTick();
                    const el = this.$refs.stream && this.$refs.stream.querySelector('[data-mid="' + id + '"]');
                    if (!el) { this.notice = T.searchTooFar; noticeFor = null; return; }
                    this.searchOpen = false;
                    await this.$nextTick();
                    el.scrollIntoView({ block: 'center', behavior: 'auto' });
                    this.foundId = id;
                    clearTimeout(foundTimer);
                    foundTimer = setTimeout(() => { if (this.foundId === id) this.foundId = null; }, 2400);
                },

                async send() {
                    const c = this.current;
                    if (!c || this.sending) return;
                    const body = this.draft.trim();
                    const voice = this.rec.state === 'ready' && this.rec.blob ? { blob: this.rec.blob, seconds: this.rec.seconds, mime: this.rec.mime } : null;
                    if (!body && !this.pending.length && !voice) return;
                    if (body.length > this.limits.body) { this.error = T.messageTooLong; return; }
                    const size = this.pending.reduce((n, f) => n + f.file.size, 0) + (voice ? voice.blob.size : 0);
                    if (size > this.limits.post) { this.error = T.filesTooBigTogether.replace(':mb', () => String(Math.floor(this.limits.post / 1048576))); return; }

                    const form = new FormData();
                    if (body) form.append('body', body);
                    const sent = this.pending.slice();
                    sent.forEach((f) => form.append('files[]', f.file, f.name));
                    if (voice) {
                        form.append('voice', voice.blob, window.VyRecorder.fileName(voice.mime));
                        form.append('voice_seconds', String(voice.seconds));
                    }

                    // the message appears at once; the server's copy replaces it
                    const convId = c.id;
                    const temp = {
                        id: 'tmp-' + (++tempSeq), pending: true, failed: '', progress: null, mine: true, body,
                        sender: { id: this.me.id, name: this.me.name, initials: '' }, at: new Date().toISOString(),
                        files: sent.map((f, i) => ({ id: 'tf' + tempSeq + '-' + i, kind: f.kind, name: f.name, size: '', url: f.preview, seconds: null }))
                            .concat(voice ? [{ id: 'tv' + tempSeq, kind: 'voice', name: T.voiceMessage, size: '', url: this.rec.url, seconds: voice.seconds }] : []),
                    };
                    this.messages.push(temp);
                    this.draft = '';
                    this.pending = [];
                    this.error = '';
                    this.sending = true;
                    const keepRec = voice ? Object.assign({}, this.rec) : null;
                    if (voice) this.rec = IDLE_REC();
                    this.$nextTick(() => { this.grow(); this.toBottom(true); });

                    const res = await this.upload(this.urls.send.replace('__ID__', convId), form, temp);
                    this.sending = false;
                    lastTypingPost = 0;   // sending ended "typing" on the server; the next keystroke says so again at once
                    const at = this.messages.findIndex((m) => m.id === temp.id);   // -1 when you have moved on meanwhile
                    if (res.status === 201 && res.data && res.data.message) {
                        const real = res.data.message;
                        if (at >= 0) { this.messages.splice(at, 1); this.merge([real]); }
                        sent.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
                        if (keepRec && keepRec.url) URL.revokeObjectURL(keepRec.url);
                        const row = this.conversations.find((x) => x.id === convId);
                        if (row) {
                            row.last = { text: body || (voice ? T.voiceMessage : (sent.length === 1 ? (sent[0].kind === 'image' ? T.photo : T.file) : plural(PL.files, PL.filesOther, sent.length))), at: real.at, mine: true };
                            this.conversations = [row].concat(this.conversations.filter((x) => x.id !== convId));
                        }
                        return;
                    }
                    // not sent: the words, the files and the recording go back to THEIR conversation, and the reason is said
                    if (at >= 0) this.messages.splice(at, 1);
                    const reason = this.sendReason(res);
                    if (this.active === convId) {
                        this.draft = body;
                        this.pending = sent;
                        if (keepRec) this.rec = keepRec;
                        this.error = reason;
                        this.$nextTick(() => this.grow());
                        return;
                    }
                    const kept = this.stash[convId] || { draft: '', pending: [], rec: IDLE_REC(), error: '' };
                    kept.draft = kept.draft ? body + '\n' + kept.draft : body;
                    kept.pending = sent.concat(kept.pending).slice(0, this.limits.files);
                    if (keepRec && kept.rec.state === 'idle') kept.rec = keepRec;
                    kept.error = reason;
                    this.stash[convId] = kept;
                    const row = this.conversations.find((x) => x.id === convId);
                    this.notice = row ? T.notSentNoticeTo.replace(':name', () => row.name) : T.notSentNotice;
                    noticeFor = convId;
                },
                /** Why a message was not sent, in words: our own refusal (422, 404) as the server said it, else what went wrong. */
                sendReason(res) {
                    if (res.data && res.data.message && (res.status === 422 || res.status === 404)) return res.data.message;
                    if (res.signedOut) return T.reasonSignedOut;
                    if (res.status === 0) return T.reasonNoConnection;
                    if (res.status === 413) return T.reasonTooLarge;
                    if (res.status === 429) return T.reasonTooMany;
                    return T.reasonFailed;
                },
                upload(url, form, temp) {
                    return new Promise((resolve) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', url);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.setRequestHeader('X-CSRF-TOKEN', boot.csrf);
                        xhr.upload.onprogress = (e) => {
                            if (!e.lengthComputable || e.total < 200000) return;
                            const m = this.messages.find((x) => x.id === temp.id);
                            if (m) m.progress = Math.round((e.loaded / e.total) * 100);
                        };
                        xhr.onload = () => {
                            // signed out: 401/419, or the request was sent on to another page (the sign-in, the paywall)
                            const moved = !!xhr.responseURL && xhr.responseURL !== new URL(url, location.origin).href;
                            const out = xhr.status === 401 || xhr.status === 419 || moved;
                            if (out) this.signedOut();
                            const json = (xhr.getResponseHeader('content-type') || '').includes('application/json');
                            let data = null;
                            try { data = json && !out ? JSON.parse(xhr.responseText) : null; } catch (e) { data = null; }
                            resolve({ status: xhr.status, data, signedOut: out });
                        };
                        xhr.onerror = () => resolve({ status: 0, data: null, signedOut: false });
                        xhr.send(form);
                    });
                },

                // ── voice notes (the shared recorder: partials/chat-media) ───────────────────────────────
                // ⚠️ One at a time, and only for the conversation it began in: "starting" covers the browser's microphone
                // question, and a recording that finishes after it was cancelled, or after a newer one began, is ignored.
                async startRec() {
                    if (this.rec.state !== 'idle') return;
                    this.error = '';
                    const mine = ++recToken;
                    this.rec = Object.assign(IDLE_REC(), { state: 'starting' });
                    let started;
                    try {
                        started = await window.VyRecorder.start({ limit: this.limits.voice, onTick: (n) => { if (recToken === mine) this.rec.seconds = n; } });
                    } catch (e) {
                        if (recToken === mine) { this.rec = IDLE_REC(); this.error = e.message; }
                        return;
                    }
                    if (recToken !== mine) { started.cancel(); return; }   // cancelled while the browser was asking
                    recording = started;
                    this.rec = Object.assign(IDLE_REC(), { state: 'recording' });
                    started.finished.then((r) => {
                        if (recToken !== mine || this.rec.state !== 'recording') return;
                        if (!r) { this.rec = IDLE_REC(); this.error = T.nothingRecorded; return; }
                        this.rec = { state: 'ready', seconds: r.seconds, url: URL.createObjectURL(r.blob), mime: r.mime, blob: r.blob };
                    });
                },
                stopRec() { if (recording && this.rec.state === 'recording') recording.stop(); },
                cancelRec() {
                    recToken++;
                    const was = this.rec.state;
                    if (this.rec.url) URL.revokeObjectURL(this.rec.url);
                    this.rec = IDLE_REC();
                    if (was === 'recording' && recording) recording.cancel();
                    recording = null;
                },

                // ── the rest ─────────────────────────────────────────────────────────────────────────────
                view(f) { this.viewer = f; },
                toggleSound() {
                    this.sound = !this.sound;
                    if (window.VyLive) window.VyLive.setSound(this.sound);
                },
                /** Desktop notifications: turning ON asks the browser for permission, here, on the click (it refuses to ask otherwise). */
                async toggleDesktop() {
                    if (!window.VyLive) return;
                    if (this.desktop) { window.VyLive.setDesktop(false); return; }
                    const state = await window.VyLive.setDesktop(true);
                    this.desktop = state === 'on';
                    this.notice = state === 'on' ? T.desktopHelp : window.VyLive.desktopMessage(state);
                    noticeFor = null;
                },
                nearBottom() {
                    const el = this.$refs.stream;
                    return !el || el.scrollHeight - el.scrollTop - el.clientHeight < 120;
                },
                onScroll() { if (this.nearBottom()) this.unseenBelow = 0; },
                toBottom(smooth) {
                    const el = this.$refs.stream;
                    if (!el) return;
                    el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                    this.unseenBelow = 0;
                },
            }));

        });
    </script>
@endunless

<style>
    /* Team chat (2026-10-06) — the shared kit's warm glass, one quiet accent: your own messages. */
    .vy-tc { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 16px; height: calc(100vh - 220px); min-height: 520px; }
    .vy-tc-rail, .vy-tc-pane { display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
    .vy-tc-pane { position: relative; }

    /* the conversations */
    .vy-tc-rail-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 16px 10px; }
    .vy-tc-rail-title { margin: 0; font-family: var(--vy-display); font-size: 17px; font-weight: 600; color: var(--vy-ink); }
    .vy-tc-new { display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px 0 11px; border: 0; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: var(--vy-shadow-accent); }
    .vy-tc-new:focus-visible, .vy-tc-icon:focus-visible, .vy-tc-send:focus-visible, .vy-tc-mic:focus-visible, .vy-tc-row:focus-visible, .vy-tc-play:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
    /* the shared search box grows along a toolbar (flex: 1 1 300px); in a column that would be 300 px of height */
    .vy-tc-search { flex: none; margin: 0 12px 10px; }
    .vy-tc-dlg .vy-tc-search { margin: 0; }
    .vy-tc-list { flex: 1; min-height: 0; overflow-y: auto; padding: 0 8px 12px; }
    .vy-tc-row { display: grid; grid-template-columns: 42px minmax(0, 1fr); gap: 11px; align-items: center; width: 100%; padding: 10px 10px; border: 0; border-radius: var(--vy-r-md); background: transparent; color: var(--vy-ink); text-decoration: none; text-align: left; font: inherit; cursor: pointer; transition: background .14s ease; }
    .vy-tc-row:hover { background: rgba(255, 255, 255, 0.55); }
    .vy-tc-row.is-active { background: #fff; box-shadow: var(--vy-shadow-sm); }
    .vy-tc-row-body { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
    .vy-tc-row-top, .vy-tc-row-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; min-width: 0; }
    .vy-tc-row-name { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-row-time { flex: none; font-size: 11.5px; color: var(--vy-faint); }
    .vy-tc-row-preview { font-size: 12.5px; color: var(--vy-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-row.is-unread .vy-tc-row-name { font-weight: 700; }
    .vy-tc-row.is-unread .vy-tc-row-preview { color: var(--vy-ink); font-weight: 600; }
    .vy-tc-row.is-unread .vy-tc-row-time { color: var(--vy-accent-text); font-weight: 600; }
    .vy-tc-unread { flex: none; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #fff; background: var(--vy-accent); font-variant-numeric: tabular-nums; }
    .vy-tc-people { margin-top: 6px; padding-top: 8px; border-top: 1px solid var(--vy-hairline); }
    .vy-tc-people-label { margin: 2px 10px 6px; font-size: 11.5px; font-weight: 600; color: var(--vy-muted); }
    .vy-tc-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 28px 18px; text-align: center; color: var(--vy-muted); font-size: 13px; line-height: 1.5; }
    .vy-tc-empty strong { color: var(--vy-ink); font-size: 14px; }
    .vy-tc-empty .vy-btn { margin-top: 8px; }
    .vy-tc-none { padding: 12px; margin: 0; text-align: center; font-size: 13px; color: var(--vy-muted); }

    /* avatars */
    .vy-tc-av { width: 42px; height: 42px; border-radius: 50%; flex: none; display: inline-flex; align-items: center; justify-content: center; background: var(--vy-avatar-bg, #F0A57A); color: var(--vy-on-peach, #5A2A12); font-size: 13.5px; font-weight: 700; letter-spacing: .02em; }
    .vy-tc-av.is-group { background: rgba(194, 65, 12, 0.10); color: var(--vy-accent-text); }
    .vy-tc-av--sm { width: 28px; height: 28px; font-size: 10.5px; align-self: flex-end; margin-bottom: 20px; }
    .vy-tc-av--lg { width: 40px; height: 40px; }
    .vy-tc-av--xl { width: 64px; height: 64px; font-size: 20px; }

    /* the open conversation */
    .vy-tc-convo { display: flex; flex-direction: column; flex: 1; min-height: 0; }
    .vy-tc-head { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--vy-hairline); }
    .vy-tc-head-text { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .vy-tc-head-name { font-size: 15px; font-weight: 700; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-head-sub { font-size: 12.5px; color: var(--vy-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-icon { flex: none; width: 38px; height: 38px; border: 0; border-radius: 50%; display: inline-grid; place-items: center; background: transparent; color: var(--vy-muted); cursor: pointer; transition: background .14s ease, color .14s ease; }
    .vy-tc-icon:hover { background: rgba(120, 70, 40, 0.07); color: var(--vy-ink); }
    .vy-tc-icon.is-off { color: var(--vy-faint); }
    .vy-tc-icon.vy-tc-back { display: none; }   /* a phone's way back to the list */
    .vy-tc-banner { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 10px 16px 0; padding: 10px 14px; border-radius: var(--vy-r-md); background: rgba(192, 57, 43, 0.08); color: #8E2A1F; font-size: 13px; }
    .vy-tc-banner--note { background: rgba(120, 70, 40, 0.07); color: var(--vy-ink); }
    .vy-tc-row-preview.is-waiting { color: #b23a2b; }

    .vy-tc-stream { flex: 1; min-height: 0; overflow-y: auto; padding: 16px 20px 8px; display: flex; flex-direction: column; gap: 4px; scroll-behavior: auto; }
    .vy-tc-older { display: flex; justify-content: center; padding: 4px 0 10px; }
    .vy-tc-hello { margin: auto; display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; color: var(--vy-muted); font-size: 13px; max-width: 320px; }
    .vy-tc-hello strong { color: var(--vy-ink); font-size: 15px; }
    .vy-tc-day { display: flex; justify-content: center; margin: 14px 0 8px; }
    .vy-tc-day span { padding: 4px 12px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, 0.7); border: 1px solid var(--vy-hairline); font-size: 11.5px; font-weight: 600; color: var(--vy-muted); }
    .vy-tc-run { display: flex; gap: 8px; align-items: flex-end; margin: 4px 0; max-width: 100%; }
    .vy-tc-run.is-mine { justify-content: flex-end; }
    .vy-tc-run-body { display: flex; flex-direction: column; gap: 3px; min-width: 0; max-width: min(76%, 560px); align-items: flex-start; }
    .vy-tc-run.is-mine .vy-tc-run-body { align-items: flex-end; }
    .vy-tc-who { margin: 0 0 1px 12px; font-size: 11.5px; font-weight: 600; color: var(--vy-muted); }
    .vy-tc-msg { display: flex; flex-direction: column; gap: 4px; max-width: 100%; align-items: inherit; }
    .vy-tc-bubble { margin: 0; padding: 9px 14px; border-radius: 18px; font-size: 14px; line-height: 1.45; white-space: pre-wrap; overflow-wrap: anywhere; background: #fff; color: var(--vy-ink); border: 1px solid var(--vy-hairline); box-shadow: 0 1px 2px rgba(120, 70, 40, 0.05); }
    .vy-tc-run:not(.is-mine) .vy-tc-msg:last-child .vy-tc-bubble { border-bottom-left-radius: 6px; }
    .vy-tc-run.is-mine .vy-tc-bubble { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: 0 2px 8px rgba(194, 65, 12, 0.18); }
    .vy-tc-run.is-mine .vy-tc-msg:last-child .vy-tc-bubble { border-bottom-right-radius: 6px; }
    .vy-tc-msg.is-pending { opacity: .7; }
    .vy-tc-msg.is-failed .vy-tc-bubble { background: rgba(192, 57, 43, 0.12); color: #8E2A1F; }
    .vy-tc-failed { margin: 0; font-size: 12px; color: #8E2A1F; }
    .vy-tc-meta { display: inline-flex; gap: 4px; margin: 1px 6px 0; font-size: 11px; color: var(--vy-faint); font-variant-numeric: tabular-nums; }
    .vy-tc-progress { display: block; width: 140px; height: 3px; border-radius: 3px; background: rgba(120, 70, 40, 0.12); overflow: hidden; }
    .vy-tc-progress span { display: block; height: 100%; background: var(--vy-accent); transition: width .2s ease; }

    /* pictures, documents, voice notes */
    .vy-tc-photos { display: grid; gap: 4px; border-radius: 16px; overflow: hidden; max-width: 320px; }
    .vy-tc-photos.n1 { grid-template-columns: minmax(0, 1fr); }
    .vy-tc-photos.n2, .vy-tc-photos.n3, .vy-tc-photos.n4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vy-tc-photo { padding: 0; border: 0; background: rgba(120, 70, 40, 0.06); cursor: zoom-in; display: block; }
    .vy-tc-photo:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
    /* fixed frames: a picture that finishes loading never moves the conversation (the stream stays at its bottom) */
    .vy-tc-photo img { display: block; width: 100%; height: auto; object-fit: cover; aspect-ratio: 1 / 1; }
    .vy-tc-photos.n1 { width: 280px; }
    .vy-tc-photos.n1 .vy-tc-photo img { aspect-ratio: 4 / 3; }
    .vy-tc-photos:not(.n1) { width: 260px; }
    .vy-tc-file { display: flex; align-items: center; gap: 10px; width: 270px; max-width: 100%; padding: 10px 12px; border-radius: 14px; background: #fff; border: 1px solid var(--vy-hairline); color: var(--vy-ink); text-decoration: none; box-shadow: 0 1px 2px rgba(120, 70, 40, 0.05); }
    .vy-tc-file:hover { border-color: var(--vy-accent-border, rgba(217, 102, 46, .3)); }
    .vy-tc-file-ico { width: 36px; height: 36px; flex: none; border-radius: 10px; display: grid; place-items: center; background: rgba(194, 65, 12, 0.10); color: var(--vy-accent-text); }
    .vy-tc-file-text { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .vy-tc-file-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-file-size { font-size: 11.5px; color: var(--vy-muted); }
    .vy-tc-file-dl { flex: none; color: var(--vy-muted); }
    .vy-tc-voice { display: flex; align-items: center; gap: 10px; min-width: 220px; padding: 8px 12px 8px 8px; border-radius: 18px; background: #fff; border: 1px solid var(--vy-hairline); color: var(--vy-ink); }
    .vy-tc-run.is-mine .vy-tc-voice { background: var(--vy-grad); border-color: transparent; color: #fff; }
    .vy-tc-play { width: 34px; height: 34px; flex: none; border: 0; border-radius: 50%; display: grid; place-items: center; background: var(--vy-accent); color: #fff; cursor: pointer; }
    .vy-tc-run.is-mine .vy-tc-play { background: rgba(255, 255, 255, 0.95); color: var(--vy-accent); }
    .vy-tc-track { position: relative; flex: 1; height: 6px; border-radius: 6px; background: rgba(120, 70, 40, 0.14); cursor: pointer; overflow: hidden; }
    .vy-tc-run.is-mine .vy-tc-track { background: rgba(255, 255, 255, 0.35); }
    .vy-tc-track:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 3px; }
    .vy-tc-run.is-mine .vy-tc-track:focus-visible { outline-color: #fff; }
    .vy-tc-track-fill { position: absolute; inset: 0 auto 0 0; border-radius: 6px; background: var(--vy-accent); }
    .vy-tc-run.is-mine .vy-tc-track-fill { background: #fff; }
    .vy-tc-voice-time { flex: none; font-size: 12px; font-variant-numeric: tabular-nums; opacity: .85; min-width: 34px; text-align: right; }

    .vy-tc-jump { position: absolute; left: 50%; bottom: 128px; transform: translateX(-50%); padding: 7px 14px; border: 0; border-radius: var(--vy-r-pill); background: var(--vy-ink); color: #fff; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; box-shadow: var(--vy-shadow); z-index: 2; }

    /* the composer */
    .vy-tc-composer { padding: 10px 14px 12px; border-top: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.35); }
    .vy-tc-error { margin: 0 4px 8px; font-size: 12.5px; color: #8E2A1F; }
    .vy-tc-paused p { margin: 4px; font-size: 13px; color: var(--vy-muted); text-align: center; }
    .vy-tc-tray { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 4px 10px; }
    .vy-tc-chip { position: relative; display: inline-flex; align-items: center; height: 56px; max-width: 220px; padding: 0 30px 0 12px; border-radius: 12px; background: #fff; border: 1px solid var(--vy-hairline); font-size: 12.5px; font-weight: 600; }
    .vy-tc-chip.is-image { width: 56px; padding: 0; overflow: hidden; }
    .vy-tc-chip.is-image img { width: 100%; height: 100%; object-fit: cover; }
    .vy-tc-chip-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-chip-x { position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; border: 0; border-radius: 50%; background: rgba(43, 35, 32, 0.72); color: #fff; font-size: 13px; line-height: 1; cursor: pointer; display: grid; place-items: center; }
    .vy-tc-input { display: flex; align-items: flex-end; gap: 4px; }
    .vy-tc-text { flex: 1; min-height: 42px; max-height: 140px; resize: none; padding: 10px 16px; border-radius: 22px; border: 1px solid var(--vy-hairline); background: #fff; font: inherit; font-size: 14px; line-height: 1.45; color: var(--vy-ink); outline: none; transition: border-color .14s ease, box-shadow .14s ease; }
    .vy-tc-text:focus { border-color: var(--vy-accent-border, rgba(217, 102, 46, .45)); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.15); }
    .vy-tc-send, .vy-tc-mic { flex: none; width: 42px; height: 42px; border: 0; border-radius: 50%; display: inline-grid; place-items: center; cursor: pointer; }
    .vy-tc-send { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
    .vy-tc-send:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }
    .vy-tc-mic { background: rgba(194, 65, 12, 0.10); color: var(--vy-accent-text); }
    .vy-tc-mic:hover { background: rgba(194, 65, 12, 0.16); }
    .vy-tc-hint { margin: 6px 6px 0; font-size: 11.5px; color: var(--vy-faint); }
    .vy-tc-rec-row { display: flex; align-items: center; gap: 10px; min-height: 46px; }
    .vy-tc-rec-dot { width: 10px; height: 10px; border-radius: 50%; background: #D93A2B; box-shadow: 0 0 0 0 rgba(217, 58, 43, .5); animation: vy-tc-pulse 1.4s ease-out infinite; }
    .vy-tc-rec-time { font-size: 14px; font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
    .vy-tc-rec-cap { flex: 1; font-size: 12px; color: var(--vy-faint); }
    .vy-tc-voice--preview { flex: 1; }
    @keyframes vy-tc-pulse { 0% { box-shadow: 0 0 0 0 rgba(217, 58, 43, .5); } 100% { box-shadow: 0 0 0 10px rgba(217, 58, 43, 0); } }
    @media (prefers-reduced-motion: reduce) { .vy-tc-rec-dot { animation: none; } }
    .vy-tc-drop { position: absolute; inset: 8px; border: 2px dashed var(--vy-accent); border-radius: var(--vy-r-lg); background: rgba(255, 252, 248, 0.88); display: grid; place-items: center; z-index: 3; pointer-events: none; }
    .vy-tc-drop span { font-size: 15px; font-weight: 700; color: var(--vy-accent-text); }

    /* D-TC-LATER (2026-11-11): edit, delete, reactions, search, typing, online. Warm glass, the one accent. */
    .vy-tc-icon.is-on { background: rgba(194, 65, 12, 0.10); color: var(--vy-accent-text); }
    .vy-tc-head-sub.is-typing { color: var(--vy-accent-text); font-weight: 600; }
    .vy-tc-dot { flex: none; width: 9px; height: 9px; border-radius: 50%; background: #2FA66A; box-shadow: 0 0 0 2px #fff; }
    .vy-tc-row-top { justify-content: flex-start; }
    .vy-tc-row-top .vy-tc-row-name { flex: 0 1 auto; }
    .vy-tc-row-top .vy-tc-row-time { margin-inline-start: auto; }

    /* a message: its tools float at the top corner of the bubble, on hover, on keyboard focus, or after a tap */
    .vy-tc-msg { position: relative; }
    .vy-tc-tools { position: absolute; top: -17px; display: none; align-items: center; gap: 2px; padding: 2px; border-radius: var(--vy-r-pill); background: #fff; border: 1px solid var(--vy-hairline); box-shadow: var(--vy-shadow-sm); z-index: 3; }
    .vy-tc-run.is-mine .vy-tc-tools { inset-inline-end: 6px; }
    .vy-tc-run:not(.is-mine) .vy-tc-tools { inset-inline-start: 6px; }
    .vy-tc-msg:hover > .vy-tc-tools, .vy-tc-msg:focus-within > .vy-tc-tools, .vy-tc-msg.is-tools > .vy-tc-tools { display: inline-flex; }
    .vy-tc-tool { width: 28px; height: 28px; border: 0; border-radius: 50%; display: inline-grid; place-items: center; background: transparent; color: var(--vy-muted); cursor: pointer; }
    .vy-tc-tool:hover { background: rgba(120, 70, 40, 0.08); color: var(--vy-ink); }
    .vy-tc-tool:focus-visible, .vy-tc-react:focus-visible, .vy-tc-pick-emoji:focus-visible, .vy-tc-menu-item:focus-visible, .vy-tc-find-row:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
    .vy-tc-picker { position: absolute; top: 34px; display: flex; gap: 2px; padding: 4px; border-radius: var(--vy-r-pill); background: #fff; border: 1px solid var(--vy-hairline); box-shadow: var(--vy-shadow); }
    .vy-tc-run.is-mine .vy-tc-picker { inset-inline-end: 0; }
    .vy-tc-run:not(.is-mine) .vy-tc-picker { inset-inline-start: 0; }
    .vy-tc-pick-emoji { width: 34px; height: 34px; border: 0; border-radius: 50%; background: transparent; font-size: 19px; line-height: 1; cursor: pointer; }
    .vy-tc-pick-emoji:hover { background: rgba(194, 65, 12, 0.10); }
    .vy-tc-menu { position: absolute; top: 34px; min-width: 168px; padding: 6px; border-radius: var(--vy-r-md); background: #fff; border: 1px solid var(--vy-hairline); box-shadow: var(--vy-shadow); }
    .vy-tc-run.is-mine .vy-tc-menu { inset-inline-end: 0; }
    .vy-tc-run:not(.is-mine) .vy-tc-menu { inset-inline-start: 0; }
    .vy-tc-menu-list { display: flex; flex-direction: column; }
    .vy-tc-menu-item { padding: 9px 12px; border: 0; border-radius: 8px; background: transparent; text-align: start; font: inherit; font-size: 13.5px; font-weight: 600; color: var(--vy-ink); cursor: pointer; }
    .vy-tc-menu-item:hover { background: rgba(120, 70, 40, 0.07); }
    .vy-tc-menu-item.is-danger { color: #8E2A1F; }
    .vy-tc-menu-confirm { display: flex; flex-direction: column; gap: 8px; padding: 6px; font-size: 13px; color: var(--vy-ink); }
    .vy-tc-menu-confirm p { margin: 0; line-height: 1.4; }
    .vy-tc-menu-confirm span { display: flex; gap: 6px; justify-content: flex-end; }

    .vy-tc-bubble--deleted { background: transparent; border: 1px dashed var(--vy-hairline); color: var(--vy-faint); font-style: italic; box-shadow: none; }
    .vy-tc-run.is-mine .vy-tc-bubble--deleted { background: transparent; color: var(--vy-faint); border-color: var(--vy-hairline); box-shadow: none; }
    .vy-tc-edited { margin-inline-start: 6px; font-size: 11px; opacity: .7; white-space: nowrap; }
    .vy-tc-note { margin: 0; max-width: 280px; font-size: 11.5px; line-height: 1.4; color: var(--vy-faint); }
    .vy-tc-msg.is-found .vy-tc-bubble { box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.35); }
    .vy-tc-reacts { display: flex; flex-wrap: wrap; gap: 4px; }
    .vy-tc-run.is-mine .vy-tc-reacts { justify-content: flex-end; }
    .vy-tc-react { display: inline-flex; align-items: center; gap: 4px; height: 24px; padding: 0 8px; border-radius: 999px; border: 1px solid var(--vy-hairline); background: #fff; font: inherit; font-size: 13px; line-height: 1; cursor: pointer; color: var(--vy-ink); }
    .vy-tc-react.is-mine { background: rgba(194, 65, 12, 0.10); border-color: rgba(194, 65, 12, 0.28); }
    .vy-tc-react:disabled { cursor: default; }
    .vy-tc-react-n { font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--vy-muted); }
    .vy-tc-edit { display: flex; flex-direction: column; gap: 6px; width: min(420px, 100%); }
    .vy-tc-edit-text { width: 100%; min-height: 56px; max-height: 180px; resize: vertical; padding: 9px 12px; border-radius: 14px; border: 1px solid var(--vy-accent-border, rgba(217, 102, 46, .45)); background: #fff; font: inherit; font-size: 14px; line-height: 1.45; color: var(--vy-ink); outline: none; box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.15); }
    .vy-tc-edit-bar { display: flex; align-items: center; gap: 8px; }
    .vy-tc-edit-hint { flex: 1; font-size: 11.5px; color: var(--vy-faint); text-align: start; }

    /* search in the conversation */
    .vy-tc-find { padding: 10px 16px 8px; border-bottom: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.45); }
    .vy-tc-find-bar { display: flex; align-items: center; gap: 6px; }
    .vy-tc-find-input { flex: 1; min-width: 0; height: 38px; padding: 0 14px; border-radius: var(--vy-r-pill); border: 1px solid var(--vy-hairline); background: #fff; font: inherit; font-size: 14px; color: var(--vy-ink); outline: none; }
    .vy-tc-find-input:focus { border-color: var(--vy-accent-border, rgba(217, 102, 46, .45)); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.15); }
    .vy-tc-find-count { margin: 8px 4px 0; font-size: 12.5px; color: var(--vy-muted); min-height: 1em; }
    .vy-tc-find-list { margin: 6px 0 0; padding: 0; list-style: none; max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
    .vy-tc-find-row { display: flex; flex-direction: column; gap: 2px; width: 100%; padding: 8px 10px; border: 0; border-radius: var(--vy-r-md); background: transparent; text-align: start; font: inherit; color: var(--vy-ink); cursor: pointer; }
    .vy-tc-find-row:hover { background: #fff; }
    .vy-tc-find-top { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; color: var(--vy-muted); }
    .vy-tc-find-top strong { color: var(--vy-ink); }
    .vy-tc-find-snippet { font-size: 13.5px; line-height: 1.4; overflow-wrap: anywhere; }
    .vy-tc-find-go { font-size: 12px; font-weight: 600; color: var(--vy-accent-text); }

    /* nothing open, and a support session */
    .vy-tc-blank { margin: auto; display: flex; flex-direction: column; align-items: center; gap: 8px; max-width: 340px; padding: 24px; text-align: center; color: var(--vy-muted); font-size: 13.5px; line-height: 1.5; }
    .vy-tc-blank strong { color: var(--vy-ink); font-size: 16px; }
    .vy-tc-blank-ico, .vy-tc-private-ico { width: 56px; height: 56px; border-radius: 50%; display: grid; place-items: center; background: rgba(194, 65, 12, 0.10); color: var(--vy-accent); margin-bottom: 6px; }
    .vy-tc-private { display: flex; flex-direction: column; align-items: center; gap: 8px; max-width: 520px; margin: 24px auto; padding: 32px; text-align: center; }
    .vy-tc-private h2 { margin: 0; font-size: 17px; color: var(--vy-ink); }
    .vy-tc-private p { margin: 0; color: var(--vy-muted); font-size: 13.5px; line-height: 1.5; }

    /* the dialogs */
    .vy-tc-dlg-head { flex: 1; min-width: 0; }
    .vy-tc-pick { margin: 12px 0 0; padding: 0; border: 0; display: flex; flex-direction: column; gap: 4px; max-height: 320px; overflow-y: auto; }
    .vy-tc-pick-row { display: grid; grid-template-columns: 42px minmax(0, 1fr) 24px; gap: 11px; align-items: center; padding: 8px 10px; border-radius: var(--vy-r-md); cursor: pointer; border: 1px solid transparent; }
    .vy-tc-pick-row:hover { background: rgba(120, 70, 40, 0.05); }
    .vy-tc-pick-row.is-on { background: rgba(194, 65, 12, 0.07); border-color: rgba(194, 65, 12, 0.18); }
    .vy-tc-pick-row input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .vy-tc-pick-row:focus-within { outline: 2px solid var(--vy-accent); outline-offset: 1px; }
    .vy-tc-pick-text { display: flex; flex-direction: column; min-width: 0; }
    .vy-tc-pick-text strong { font-size: 14px; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-pick-text span { font-size: 12px; color: var(--vy-muted); }
    .vy-tc-tick { width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid rgba(120, 70, 40, 0.25); display: grid; place-items: center; color: transparent; }
    .vy-tc-pick-row.is-on .vy-tc-tick { background: var(--vy-accent); border-color: var(--vy-accent); color: #fff; }
    .vy-tc-group-name { margin: 14px 0 0; }
    .vy-tc-viewer { background: rgba(20, 14, 10, 0.78); }
    .vy-tc-viewer-card { width: auto; max-width: min(1100px, 94vw); padding: 0; background: transparent; box-shadow: none; display: flex; flex-direction: column; gap: 10px; align-items: center; }
    .vy-tc-viewer-bar { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border-radius: var(--vy-r-md); background: rgba(255, 252, 248, 0.96); }
    .vy-tc-viewer-name { flex: 1; margin: 0; font-size: 14px; font-weight: 600; color: var(--vy-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vy-tc-viewer-img { display: block; max-width: 100%; max-height: 80vh; border-radius: var(--vy-r-md); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4); background: #fff; }

    /* one pane at a time on a narrow screen */
    @media (max-width: 900px) {
        .vy-tc { grid-template-columns: minmax(0, 1fr); height: auto; min-height: 0; }
        .vy-tc-rail { min-height: 420px; max-height: 70vh; }
        .vy-tc-pane { display: none; height: calc(100vh - 140px); min-height: 480px; }
        .vy-tc.has-open .vy-tc-rail { display: none; }
        .vy-tc.has-open .vy-tc-pane { display: flex; }
        .vy-tc-icon.vy-tc-back { display: inline-grid; }
        .vy-tc-run-body { max-width: 86%; }
        .vy-tc-stream { padding: 12px 12px 6px; }
        .vy-tc-hint { display: none; }
    }
</style>
</div>
