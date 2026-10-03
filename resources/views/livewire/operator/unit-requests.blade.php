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
    <x-operator.shell active="unit-requests" title="Unit requests" :subtitle="$pending . ' waiting for review'" :viewer="$viewer">
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

            <p class="vyo-ur-intro vy-fade">Owners ask for new units here. Approve one to create it in your inventory, owned by them, or decline it.</p>

            <nav class="vyo-ur-chips vy-fade" aria-label="Show requests">
                @foreach ($chips as $chip)
                    <a class="vyo-ur-chip {{ $view === $chip['key'] ? 'is-on' : '' }}" href="{{ $chip['url'] }}" @if ($view === $chip['key']) aria-current="page" @endif>
                        {{ $chip['label'] }} <span class="vyo-ur-chipcount">{{ $chip['count'] }}</span>
                    </a>
                @endforeach
            </nav>

            <section class="vy-card vyo-ur-list vy-fade" aria-label="Requests">
                @forelse ($requests as $r)
                    <article class="vyo-ur-item" @if ($canManage && $r['status'] === 'pending') data-review="{{ json_encode($vyReview($r), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) }}" @endif>
                        <div class="vyo-ur-media">
                            @if ($r['photos'] !== [])
                                <img class="vyo-ur-cover" src="{{ $r['photos'][0] }}" alt="Photo of {{ $r['name'] }}" loading="lazy">
                            @else
                                <span class="vyo-ur-noimg" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12M9.5 21v-5h5v5"/></svg></span>
                            @endif
                        </div>
                        <div class="vyo-ur-body">
                            <div class="vyo-ur-top">
                                <h2 class="vyo-ur-name">{{ $r['name'] }}</h2>
                                <span class="vyo-ur-status is-{{ $r['status'] }}">{{ $r['status'] === 'pending' ? 'Pending' : ucfirst($r['status']) }}</span>
                            </div>
                            <div class="vyo-ur-meta">{{ $r['kind'] }} · from {{ $r['owner'] }} · sent {{ $r['when'] }}</div>
                            @if ($r['owner_removed'] && $r['status'] === 'pending')
                                <p class="vyo-ur-removed">Owner removed — this request can only be declined.</p>
                            @endif
                            <p class="vyo-ur-details">{{ $r['details'] }}</p>
                            @if (count($r['photos']) > 1)
                                <div class="vyo-ur-strip">
                                    @foreach (array_slice($r['photos'], 1) as $vyPhoto)
                                        <img class="vyo-ur-thumb" src="{{ $vyPhoto }}" alt="" loading="lazy">
                                    @endforeach
                                    @if ($r['more_photos'] > 0)
                                        <span class="vyo-ur-more">+{{ $r['more_photos'] }}</span>
                                    @endif
                                </div>
                            @endif
                            @if ($r['created_unit'])
                                <p class="vyo-ur-outcome">Created as unit {{ $r['created_unit'] }}{{ $r['reviewed'] ? ' · ' . $r['reviewed'] : '' }}</p>
                            @elseif ($r['status'] !== 'pending' && $r['reviewed'])
                                <p class="vyo-ur-outcome is-quiet">{{ ucfirst($r['status']) }} {{ $r['reviewed'] }}</p>
                            @endif
                            @if ($r['review_note'] !== '')
                                <p class="vyo-ur-note">“{{ $r['review_note'] }}”</p>
                            @endif
                        </div>
                        @if ($canManage && $r['status'] === 'pending')
                            <div class="vyo-ur-actions">
                                <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="review('decline', $el)">Decline</button>
                                @unless ($r['owner_removed'])
                                    <button type="button" class="vy-btn vy-btn--accent vyo-sm" x-on:click="review('approve', $el)">Approve</button>
                                @endunless
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="vy-empty vyo-ur-empty">
                        @if ($view === 'pending')
                            No unit requests are waiting.
                        @elseif ($view === 'all')
                            No unit requests yet.
                        @else
                            No {{ $view }} requests.
                        @endif
                        <p class="vyo-ur-empty-sub">When an owner asks for a unit from their portal, it appears here for you to review.</p>
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
                                    <h3 class="vy-modal-title" id="vyo-ur-approve-t" x-text="req ? 'Approve ' + req.name + '?' : 'Approve the request?'">Approve the request?</h3>
                                    <p class="vyo-dlg-sub" x-text="req ? req.kind + ' · from ' + req.owner : ''"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="approveOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($properties === [])
                                    <div class="vyo-errors" role="alert">You don’t work in any property yet, so there is nowhere to add the unit.</div>
                                @endif
                                <div class="vyo-pair">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyo-ur-property">Property</label>
                                        <select id="vyo-ur-property" class="vy-input" name="property_id" x-model="propertyId" x-on:change="roomTypeId = ''" required>
                                            <option value="">Choose…</option>
                                            @foreach ($properties as $p)
                                                <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                                            @endforeach
                                        </select>
                                        @if ($vyOpen === 'approve')
                                            @error('property_id')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyo-ur-roomtype">Room type</label>
                                        <select id="vyo-ur-roomtype" class="vy-input" name="room_type_id" x-model="roomTypeId" required>
                                            <option value="" x-text="propertyId ? 'Choose…' : 'Choose a property first'">Choose a property first</option>
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
                                    <label class="vy-field-label" for="vyo-ur-anote">Note to the owner <span class="vy-fl-note">optional</span></label>
                                    <input id="vyo-ur-anote" class="vy-input" type="text" name="note" maxlength="500" value="{{ $vyOpen === 'approve' ? old('note') : '' }}" placeholder="Anything to tell them">
                                    @if ($vyOpen === 'approve')
                                        @error('note')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <template x-if="req">
                                    <ul class="vyo-factlist">
                                        <li x-text="'“' + req.name + '” becomes a unit of the room type you choose, owned by ' + req.owner + ', and can take bookings.'"></li>
                                        <li x-show="req.rate" x-text="'The ' + req.rate + ' a night they proposed is not applied — the unit takes the room type’s rates.'"></li>
                                        <li x-text="req.notifiable ? req.owner + ' gets a notice in their portal, with your note — unless they’ve turned these notices off.' : req.owner + ' has no portal login, so nothing is sent.'"></li>
                                        <li>Recorded in the audit log.</li>
                                    </ul>
                                </template>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="approveOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent" @disabled($properties === [])>Create unit</button>
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
                                    <h3 class="vy-modal-title" id="vyo-ur-decline-t" x-text="req ? 'Decline ' + req.name + '?' : 'Decline the request?'">Decline the request?</h3>
                                    <p class="vyo-dlg-sub" x-text="req ? req.kind + ' · from ' + req.owner : ''"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="declineOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyo-ur-dnote">Reason <span class="vy-fl-note">optional</span></label>
                                    <input id="vyo-ur-dnote" class="vy-input" type="text" name="note" maxlength="500" value="{{ $vyOpen === 'decline' ? old('note') : '' }}" placeholder="Why it isn’t going ahead" autofocus>
                                    @if ($vyOpen === 'decline')
                                        @error('note')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <template x-if="req">
                                    <ul class="vyo-factlist">
                                        <li>No unit is created.</li>
                                        <li x-text="req.notifiable ? req.owner + ' gets a notice in their portal, with your reason — unless they’ve turned these notices off.' : (req.removed ? req.owner + ' has been removed, so nothing is sent.' : req.owner + ' has no portal login, so nothing is sent.')"></li>
                                        <li>Recorded in the audit log.</li>
                                    </ul>
                                </template>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="declineOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--danger">Decline request</button>
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
