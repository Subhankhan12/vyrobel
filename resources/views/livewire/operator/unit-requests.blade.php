{{-- Operator UNIT REQUESTS — rebuilt 2026-10-03 (the founder: "all the sort of work you have done in the previous pages … in this
     module … make sure everything works very well").

     An owner asks for a unit through the Add-Unit wizard; the operator approves it — creating the REAL unit in one of their
     properties, owned by that owner (OwnerUnitRequestService) — or declines it. Chips that count what clicking them shows;
     each request with its photos and everything the owner filed; Approve and Decline in the shared centred dialog. Both
     writes are `can:units.manage` controller POSTs (UnitRequestActionController), refused while the workspace is paused.

     ⚠️ DEC-k: the approve dialog offers only the properties the person works in — the same set the write accepts.
     ⚠️ The owner's free text is shown ESCAPED, never raw (DO9-1): `{{ $r['details'] }}` is the display path.
     ⚠️ Every dialog is the shared centred one and every display is a CLASS — x-show erases an inline display (A8).
     ⚠️ RAW-BLADE RULE: never `word@directive`. Alpine handlers use `x-on:` / `x-bind:`. --}}
<div>
    <x-operator.shell active="unit-requests" :title="__('livewire/operator/unit-requests.title')" :subtitle="trans_choice('livewire/operator/unit-requests.subtitle', $pending, ['count' => $pending])" :viewer="$viewer">
        @php
            // Which dialog a refused write reopens, and for which request: the form says both (`_form`, `_request`).
            $vyForm = (string) old('_form');
            $vyRequestId = (string) old('_request');
            $vyReopen = null;
            foreach ($requests as $vyR) {
                if ($vyR['id'] === $vyRequestId && $vyR['status'] === 'pending') {
                    $vyReopen = $vyR;
                }
            }
            $vyOpen = $canManage && $errors->any() && $vyReopen !== null && in_array($vyForm, ['approve', 'decline'], true) ? $vyForm : '';
            $vyStray = $vyOpen === '' ? $errors->all() : [];
            $vyReview = fn (array $r): array => ['id' => $r['id'], 'name' => $r['name'], 'owner' => $r['owner'], 'kind' => $r['kind'],
                'notifiable' => $r['notifiable'], 'rate' => $r['rate'], 'removed' => $r['owner_removed'],
                'approve' => route('app.unit-requests.approve', $r['id']), 'decline' => route('app.unit-requests.decline', $r['id'])];
        @endphp

        @if (session('unit_request_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('unit_request_notice') }}
            </div>
        @endif
        @if (session('unit_request_error') || $vyStray !== [])
            <div class="vy-flash is-err vyo-flash vy-fade" role="alert">
                @if (session('unit_request_error'))
                    <div>{{ session('unit_request_error') }}</div>
                @endif
                @foreach ($vyStray as $vyError)
                    <div>{{ $vyError }}</div>
                @endforeach
            </div>
        @endif

        <div class="vyo-root vyo-ur" x-data="vyUnitRequests({
                approveOpen: @js($vyOpen === 'approve'), declineOpen: @js($vyOpen === 'decline'),
                req: @js($vyReopen !== null ? $vyReview($vyReopen) : null),
                properties: @js($properties),
                propertyId: @js((string) old('property_id', count($properties) === 1 ? $properties[0]['id'] : '')),
                roomTypeId: @js((string) old('room_type_id', '')),
            })">

            <p class="vyo-ur-intro vy-fade">{{ __('livewire/operator/unit-requests.intro') }}</p>

            <nav class="vyo-ur-chips vy-fade" aria-label="{{ __('livewire/operator/unit-requests.chips_label') }}">
                @foreach ($chips as $chip)
                    <a class="vyo-ur-chip {{ $view === $chip['key'] ? 'is-on' : '' }}" href="{{ $chip['url'] }}" @if ($view === $chip['key']) aria-current="page" @endif>
                        {{ $chip['label'] }} <span class="vyo-ur-chipcount">{{ $chip['count'] }}</span>
                    </a>
                @endforeach
            </nav>

            <section class="vy-card vyo-ur-list vy-fade" aria-label="{{ __('livewire/operator/unit-requests.list_label') }}">
                @forelse ($requests as $r)
                    <article class="vyo-ur-item" @if ($canManage && $r['status'] === 'pending') data-review="{{ json_encode($vyReview($r), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) }}" @endif>
                        <div class="vyo-ur-media">
                            @if ($r['photos'] !== [])
                                <img class="vyo-ur-cover" src="{{ $r['photos'][0] }}" alt="{{ __('livewire/operator/unit-requests.photo_alt', ['name' => $r['name']]) }}" loading="lazy">
                            @else
                                <span class="vyo-ur-noimg" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12M9.5 21v-5h5v5"/></svg></span>
                            @endif
                        </div>
                        <div class="vyo-ur-body">
                            <div class="vyo-ur-top">
                                <h2 class="vyo-ur-name">{{ $r['name'] }}</h2>
                                <span class="vyo-ur-status is-{{ $r['status'] }}">{{ match ($r['status']) {
                                    'pending' => __('livewire/operator/unit-requests.status_pending'),
                                    'approved' => __('livewire/operator/unit-requests.status_approved'),
                                    'declined' => __('livewire/operator/unit-requests.status_declined'),
                                    'withdrawn' => __('livewire/operator/unit-requests.status_withdrawn'),
                                    default => ucfirst($r['status']),
                                } }}</span>
                            </div>
                            <div class="vyo-ur-meta">{{ __('livewire/operator/unit-requests.meta', ['kind' => $r['kind'], 'owner' => $r['owner'], 'date' => $r['when']]) }}</div>
                            @if ($r['owner_removed'] && $r['status'] === 'pending')
                                <p class="vyo-ur-removed">{{ __('livewire/operator/unit-requests.owner_removed') }}</p>
                            @endif
                            <p class="vyo-ur-details">{{ $r['details'] }}</p>
                            @if (count($r['photos']) > 1)
                                <div class="vyo-ur-strip">
                                    @foreach (array_slice($r['photos'], 1) as $vyPhoto)
                                        <img class="vyo-ur-thumb" src="{{ $vyPhoto }}" alt="" loading="lazy">
                                    @endforeach
                                    @if ($r['more_photos'] > 0)
                                        <span class="vyo-ur-more">{{ __('livewire/operator/unit-requests.more_photos', ['number' => $r['more_photos']]) }}</span>
                                    @endif
                                </div>
                            @endif
                            @if ($r['created_unit'])
                                <p class="vyo-ur-outcome">{{ $r['reviewed'] ? __('livewire/operator/unit-requests.created_as_unit_on', ['unit' => $r['created_unit'], 'date' => $r['reviewed']]) : __('livewire/operator/unit-requests.created_as_unit', ['unit' => $r['created_unit']]) }}</p>
                            @elseif ($r['status'] !== 'pending' && $r['reviewed'])
                                <p class="vyo-ur-outcome is-quiet">{{ match ($r['status']) {
                                    'approved' => __('livewire/operator/unit-requests.outcome_approved', ['date' => $r['reviewed']]),
                                    'declined' => __('livewire/operator/unit-requests.outcome_declined', ['date' => $r['reviewed']]),
                                    'withdrawn' => __('livewire/operator/unit-requests.outcome_withdrawn', ['date' => $r['reviewed']]),
                                    default => ucfirst($r['status']) . ' ' . $r['reviewed'],
                                } }}</p>
                            @endif
                            @if ($r['review_note'] !== '')
                                <p class="vyo-ur-note">{{ __('livewire/operator/unit-requests.review_note', ['note' => $r['review_note']]) }}</p>
                            @endif
                        </div>
                        @if ($canManage && $r['status'] === 'pending')
                            <div class="vyo-ur-actions">
                                <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="review('decline', $el)">{{ __('livewire/operator/unit-requests.decline') }}</button>
                                @unless ($r['owner_removed'])
                                    <button type="button" class="vy-btn vy-btn--accent vyo-sm" x-on:click="review('approve', $el)">{{ __('livewire/operator/unit-requests.approve') }}</button>
                                @endunless
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="vy-empty vyo-ur-empty">
                        {{ match ($view) {
                            'pending' => __('livewire/operator/unit-requests.empty_pending'),
                            'all' => __('livewire/operator/unit-requests.empty_all'),
                            'approved' => __('livewire/operator/unit-requests.empty_approved'),
                            'declined' => __('livewire/operator/unit-requests.empty_declined'),
                            default => __('livewire/operator/unit-requests.empty_withdrawn'),
                        } }}
                        <p class="vyo-ur-empty-sub">{{ __('livewire/operator/unit-requests.empty_sub') }}</p>
                    </div>
                @endforelse
            </section>

            @if ($canManage)
                {{-- ══ APPROVE — creates the REAL unit, owned by the owner, in a property the person works in (DEC-k) ══════════ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="approveOpen" x-transition.opacity x-on:click.self="approveOpen = false" x-on:keydown.escape.window="approveOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="approveOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyo-ur-approve-t" tabindex="-1" x-trap.noscroll="approveOpen">
                        <form method="POST" x-bind:action="req ? req.approve : ''" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="approve">
                            <input type="hidden" name="_request" x-bind:value="req ? req.id : ''">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9.5 14l2 2 3.5-4"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyo-ur-approve-t" x-text="req ? @js(__('livewire/operator/unit-requests.approve_title_named', ['name' => ':name'])).replace(':name', () => req.name) : @js(__('livewire/operator/unit-requests.approve_title'))">{{ __('livewire/operator/unit-requests.approve_title') }}</h3>
                                    <p class="vyo-dlg-sub" x-text="req ? @js(__('livewire/operator/unit-requests.dialog_sub', ['kind' => ':kind', 'owner' => ':owner'])).replace(/:kind|:owner/g, (m) => m === ':kind' ? req.kind : req.owner) : ''"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="approveOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($properties === [])
                                    <div class="vyo-errors" role="alert">{{ __('livewire/operator/unit-requests.no_properties') }}</div>
                                @endif
                                <div class="vyo-pair">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyo-ur-property">{{ __('livewire/operator/unit-requests.property') }}</label>
                                        <select id="vyo-ur-property" class="vy-input" name="property_id" x-model="propertyId" x-on:change="roomTypeId = ''" required>
                                            <option value="">{{ __('livewire/operator/unit-requests.choose') }}</option>
                                            @foreach ($properties as $p)
                                                <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                                            @endforeach
                                        </select>
                                        @if ($vyOpen === 'approve')
                                            @error('property_id')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyo-ur-roomtype">{{ __('livewire/operator/unit-requests.room_type') }}</label>
                                        <select id="vyo-ur-roomtype" class="vy-input" name="room_type_id" x-model="roomTypeId" required>
                                            <option value="" x-text="propertyId ? @js(__('livewire/operator/unit-requests.choose')) : @js(__('livewire/operator/unit-requests.choose_property_first'))">{{ __('livewire/operator/unit-requests.choose_property_first') }}</option>
                                            <template x-for="rt in roomTypes()" x-bind:key="rt.id">
                                                <option x-bind:value="rt.id" x-text="rt.name" x-bind:selected="rt.id === roomTypeId"></option>
                                            </template>
                                        </select>
                                        @if ($vyOpen === 'approve')
                                            @error('room_type_id')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyo-ur-anote">{!! __('livewire/operator/unit-requests.note_label') !!}</label>
                                    <input id="vyo-ur-anote" class="vy-input" type="text" name="note" maxlength="500" value="{{ $vyOpen === 'approve' ? old('note') : '' }}" placeholder="{{ __('livewire/operator/unit-requests.note_placeholder') }}">
                                    @if ($vyOpen === 'approve')
                                        @error('note')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <template x-if="req">
                                    <ul class="vyo-factlist">
                                        <li x-text="@js(__('livewire/operator/unit-requests.fact_becomes_unit', ['name' => ':name', 'owner' => ':owner'])).replace(/:name|:owner/g, (m) => m === ':name' ? req.name : req.owner)"></li>
                                        <li x-show="req.rate" x-text="@js(__('livewire/operator/unit-requests.fact_rate_not_applied', ['rate' => ':rate'])).replace(':rate', () => req.rate)"></li>
                                        <li x-text="(req.notifiable ? @js(__('livewire/operator/unit-requests.fact_notified_note', ['owner' => ':owner'])) : @js(__('livewire/operator/unit-requests.fact_no_login', ['owner' => ':owner']))).replace(':owner', () => req.owner)"></li>
                                        <li>{{ __('livewire/operator/unit-requests.fact_audit') }}</li>
                                    </ul>
                                </template>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="approveOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent" @disabled($properties === [])>{{ __('livewire/operator/unit-requests.create_unit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ DECLINE — no inventory is created; the owner is told, with the reason ════════════════════════════════════ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="declineOpen" x-transition.opacity x-on:click.self="declineOpen = false" x-on:keydown.escape.window="declineOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="declineOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyo-ur-decline-t" tabindex="-1" x-trap.noscroll="declineOpen">
                        <form method="POST" x-bind:action="req ? req.decline : ''" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="decline">
                            <input type="hidden" name="_request" x-bind:value="req ? req.id : ''">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyo-ur-decline-t" x-text="req ? @js(__('livewire/operator/unit-requests.decline_title_named', ['name' => ':name'])).replace(':name', () => req.name) : @js(__('livewire/operator/unit-requests.decline_title'))">{{ __('livewire/operator/unit-requests.decline_title') }}</h3>
                                    <p class="vyo-dlg-sub" x-text="req ? @js(__('livewire/operator/unit-requests.dialog_sub', ['kind' => ':kind', 'owner' => ':owner'])).replace(/:kind|:owner/g, (m) => m === ':kind' ? req.kind : req.owner) : ''"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="declineOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyo-ur-dnote">{!! __('livewire/operator/unit-requests.reason_label') !!}</label>
                                    <input id="vyo-ur-dnote" class="vy-input" type="text" name="note" maxlength="500" value="{{ $vyOpen === 'decline' ? old('note') : '' }}" placeholder="{{ __('livewire/operator/unit-requests.reason_placeholder') }}" autofocus>
                                    @if ($vyOpen === 'decline')
                                        @error('note')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <template x-if="req">
                                    <ul class="vyo-factlist">
                                        <li>{{ __('livewire/operator/unit-requests.fact_no_unit') }}</li>
                                        <li x-text="(req.notifiable ? @js(__('livewire/operator/unit-requests.fact_notified_reason', ['owner' => ':owner'])) : (req.removed ? @js(__('livewire/operator/unit-requests.fact_owner_removed', ['owner' => ':owner'])) : @js(__('livewire/operator/unit-requests.fact_no_login', ['owner' => ':owner'])))).replace(':owner', () => req.owner)"></li>
                                        <li>{{ __('livewire/operator/unit-requests.fact_audit') }}</li>
                                    </ul>
                                </template>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="declineOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--danger">{{ __('livewire/operator/unit-requests.decline_request') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </x-operator.shell>

    {{-- The review dialogs: a request card carries its own addresses (`data-review`); the room types follow the property. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyUnitRequests', (state) => Object.assign({
                review(which, el) {
                    const card = el.closest('[data-review]');
                    if (! card) return;
                    try { this.req = JSON.parse(card.dataset.review); } catch (e) { return; }
                    this.approveOpen = which === 'approve';
                    this.declineOpen = which === 'decline';
                },
                roomTypes() {
                    const p = this.properties.find((x) => x.id === this.propertyId);
                    return p ? p.room_types : [];
                },
            }, state));
        });
    </script>
</div>
