{{--
    Owner Agreement (exact-match #82) — the owner's view of their commercial terms, matched to the decoded
    "Owner Agreement" wireframe: active agreement · amendment · what your fee covers · terms by villa ·
    key terms · documents · change terms. Shared chrome from <x-owner.shell> + --vy-* tokens.

    ⚠️ management_agreements / agreement_amendments / owners carry NO OwnerScope — every row here is scoped
    EXPLICITLY by App\Livewire\Owner\OwnerAgreement via OwnerContext::ownerIds(), fail-closed. See that class.

    HONESTY MAP (#39 — real-or-honest, nothing fabricated):
      • REAL      — commission (the ONE rate the statements apply, #17), unit count, started, per-villa
                    commission (the same resolveFor the attribution engine uses), payout schedule/currency,
                    term/notice/renew (when set), the amendment + its real acknowledgment record.
      • BUILT     — the amendment ACKNOWLEDGMENT (a real, audited record of consent → the new terms apply via
                    the certified effective-dating). Framed "acknowledged in your portal", NEVER a fake
                    "signed" badge; a legally-binding e-signature is honest-deferred.
      • DESCRIPTIVE — "what full-service management includes" is a generic honest service description, not a
                    per-owner term; the at-cost add-ons are the REAL owner-billed expense categories.
      • OMITTED   — per-villa cleaning ("At cost", no per-unit rate modelled), owner-stays / cancellations
                    (not modelled — never fabricated), term/notice/renew rows when null.
--}}
<x-owner.shell active="agreement" title="Agreement" :viewer="$viewer">
    @php $notice = session('owner_notice'); $error = session('owner_error'); @endphp
    @if ($notice)
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $notice }}</div></div>
    @endif
    @if ($error)
        <div class="vy-card vy-fade" style="border-left:3px solid #B4400F;margin-bottom:16px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $error }}</div></div>
    @endif

    {{-- ══ Header ══ --}}
    <div class="vy-fade" style="margin-bottom:20px">
        <h1 style="font-family:Sora,sans-serif;font-weight:600;font-size:26px;margin:0">Management agreement</h1>
        <p style="font-size:13.5px;color:#8A8079;margin:6px 0 0">Your terms with {{ $operatorName }} — commission, what’s included, and renewal.</p>
    </div>

    <div class="vy-cols">
        {{-- ══════════ LEFT ══════════ --}}
        <div class="vy-stack">
            {{-- 1 · ACTIVE AGREEMENT — the REAL agreement in effect today --}}
            @if ($active)
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.02s">
                    <div style="display:flex;flex-wrap:wrap;gap:22px;justify-content:space-between">
                        <div style="flex:1 1 260px;min-width:240px">
                            <span class="vy-eyebrow">Active agreement</span>
                            <div style="font-family:Sora,sans-serif;font-weight:600;font-size:19px;margin-top:4px">Full-service management</div>
                            <p style="font-size:12.5px;color:rgba(255,255,255,.68);margin:8px 0 0;line-height:1.5">
                                {{ $operatorName }} manages {{ $active['unit_count'] }} of your {{ \Illuminate\Support\Str::plural('villa', $active['unit_count']) }} end to end — bookings, guests, cleaning and payouts.
                            </p>

                            <div style="margin-top:18px;display:flex;flex-wrap:wrap;gap:22px">
                                <div>
                                    <div style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.5)">Started</div>
                                    <div style="font-size:13.5px;font-weight:600;color:#fff;margin-top:2px">{{ $active['from_display'] ?? 'Not set' }}</div>
                                </div>
                                @if ($active['term_display'])
                                    <div>
                                        <div style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.5)">Term</div>
                                        <div style="font-size:13.5px;font-weight:600;color:#fff;margin-top:2px">{{ $active['term_display'] }}</div>
                                    </div>
                                @endif
                                @if ($active['notice_display'])
                                    <div>
                                        <div style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.5)">Notice</div>
                                        <div style="font-size:13.5px;font-weight:600;color:#fff;margin-top:2px">{{ $active['notice_display'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div style="text-align:right;min-width:170px">
                            <div style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.5)">Management fee</div>
                            <div class="vy-dark-figure" style="margin-top:2px">{{ $active['commission_display'] ?? 'Not set' }}</div>
                            <div style="font-size:12px;color:rgba(255,255,255,.6)">{{ $active['commission_display'] ? 'of booking revenue' : 'no commission rate is set' }}</div>
                            @if (! is_null($active['auto_renews']))
                                <div style="margin-top:10px;font-size:11.5px;color:rgba(255,255,255,.6)">{{ $active['auto_renews'] ? 'Auto-renews' : 'Does not auto-renew' }}</div>
                            @endif
                            @if ($canExport)
                                <a href="{{ route('owner.agreement.pdf') }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff">Download agreement</a>
                            @endif
                        </div>
                    </div>

                    <span class="vy-status vy-status--{{ $active['status_meta']['tone'] }}" style="margin-top:16px">
                        <span class="vy-dot"></span>{{ $active['status_meta']['label'] }} · {{ $active['scope_label'] }}
                    </span>
                    @if ($active['owner_label'])
                        <div style="margin-top:8px;font-size:11.5px;color:rgba(255,255,255,.6)">{{ $active['owner_label'] }}</div>
                    @endif
                </section>
            @else
                <section class="vy-card vy-fade" style="animation-delay:.02s">
                    <h2 class="vy-card-title">Active agreement</h2>
                    <div class="vy-empty">No active agreement on file.</div>
                </section>
            @endif

            {{-- 2 · AMENDMENT — the REAL acknowledgment flow (never a fake "signed" badge) --}}
            @if ($amendment)
                <section class="vy-card vy-fade" style="animation-delay:.05s;{{ $amendment['needs'] ? 'border-left:3px solid var(--vy-accent)' : '' }}"
                         x-data="{ preview: false }">
                    @if ($amendment['needs'])
                        <span class="vy-eyebrow" style="color:var(--vy-accent)">Amendment ready to acknowledge</span>
                        <div style="font-family:Sora,sans-serif;font-weight:600;font-size:16px;margin-top:6px">{{ $amendment['title'] }}</div>
                        @if ($amendment['summary'])
                            <p style="font-size:12.5px;color:#8A8079;margin:6px 0 0;line-height:1.5">{{ $amendment['summary'] }} Effective {{ $amendment['effective_display'] }}.</p>
                        @else
                            <p style="font-size:12.5px;color:#8A8079;margin:6px 0 0">Effective {{ $amendment['effective_display'] }}.</p>
                        @endif

                        {{-- Preview — the REAL amendment content --}}
                        <div x-show="preview" x-collapse style="margin-top:12px;padding:12px 14px;background:rgba(194,65,12,.05);border-radius:12px">
                            <div class="vy-eyebrow" style="color:var(--vy-accent)">What changes</div>
                            @forelse ($amendment['changes'] as $c)
                                <div class="vy-list-row"><span style="font-size:12.5px;color:var(--vy-muted)">{{ $c['label'] }}</span><span style="font-size:12.5px;font-weight:600">{{ $c['value'] }}</span></div>
                            @empty
                                <div style="font-size:12.5px;color:#8A8079;margin-top:6px">This amendment updates the terms of your agreement.</div>
                            @endforelse
                        </div>

                        <div style="display:flex;gap:10px;margin-top:14px;align-items:center">
                            @if ($canAcknowledge)
                                <form method="POST" action="{{ route('owner.agreement.amendments.acknowledge', $amendment['id']) }}">
                                    @csrf
                                    <button type="submit" class="vy-btn vy-btn--primary">Review &amp; acknowledge</button>
                                </form>
                            @else
                                <button type="button" class="vy-btn vy-btn--ghost is-soon" style="opacity:.6;cursor:not-allowed" disabled title="Only a co-owner can acknowledge">Review &amp; acknowledge</button>
                            @endif
                            <button type="button" class="vy-btn vy-btn--ghost" x-on:click="preview = !preview" x-text="preview ? 'Hide preview' : 'Preview'">Preview</button>
                        </div>
                        <div class="vy-fx-note" style="margin-top:12px">
                            Acknowledging records your review and consent in the portal and applies the new terms on the
                            effective date. It’s a portal acknowledgment, not a legally-binding e-signature.
                        </div>
                    @else
                        <span class="vy-eyebrow" style="color:#1B6B48">{{ $amendment['version'] }} amendment acknowledged</span>
                        <div style="font-family:Sora,sans-serif;font-weight:600;font-size:16px;margin-top:6px">{{ $amendment['title'] }}</div>
                        <p style="font-size:12.5px;color:#8A8079;margin:6px 0 0">
                            Acknowledged{{ $amendment['acknowledged_by'] ? ' by '.$amendment['acknowledged_by'] : '' }}{{ $amendment['acknowledged_display'] ? ' on '.$amendment['acknowledged_display'] : '' }} · takes effect {{ $amendment['effective_display'] }}.
                        </p>
                    @endif
                </section>
            @endif

            {{-- 3 · WHAT YOUR FEE COVERS --}}
            @if ($feeCovers)
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <h2 class="vy-card-title">What your fee covers</h2>
                    <div class="vy-line-sub" style="margin-bottom:10px">Included in the {{ $active['commission_display'] ?? 'management' }} management fee — no extra charge.</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px 18px">
                        @foreach ($feeCovers['included'] as $item)
                            <div style="display:flex;align-items:center;gap:8px;font-size:13px">
                                <span style="width:16px;height:16px;border-radius:50%;background:rgba(27,107,72,.14);color:#1B6B48;display:inline-flex;align-items:center;justify-content:center;font-size:10px;flex:none">✓</span>
                                {{ $item }}
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top:16px;border-top:1px solid rgba(200,180,165,.22);padding-top:14px">
                        <div class="vy-eyebrow">Billed separately, at cost</div>
                        @if (! empty($feeCovers['addons']))
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
                                @foreach ($feeCovers['addons'] as $addon)
                                    <span style="font-size:12px;padding:4px 10px;border-radius:20px;background:rgba(194,65,12,.06);color:#8A5A08">{{ $addon }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">Anything beyond management — deep cleaning, repairs, consumables — is billed to you at cost as it’s incurred. Nothing has been charged yet.</div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- 4 · TERMS BY VILLA — REAL per-unit commission (the same rate the statements apply, #17) --}}
            @if (! empty($villas))
                <section class="vy-card vy-fade" style="animation-delay:.1s">
                    <h2 class="vy-card-title">Terms by villa</h2>
                    <div class="vy-line-sub" style="margin-bottom:6px">Commission and cleaning fees agreed per property.</div>
                    <div style="overflow-x:auto">
                        <table class="vy-table">
                            <thead><tr><th>Villa</th><th class="vy-num">Commission</th><th class="vy-num">Cleaning</th><th class="vy-num">Since</th></tr></thead>
                            <tbody>
                                @foreach ($villas as $v)
                                    <tr>
                                        <td><span class="vy-row-month">{{ $v['unit'] }}</span></td>
                                        <td class="vy-num vy-mono" style="{{ $v['is_low'] ? 'color:#3F6A38;font-weight:600' : '' }}">{{ $v['commission'] ?? 'Not set' }}</td>
                                        <td class="vy-num vy-muted">At cost</td>
                                        <td class="vy-num vy-muted">{{ $v['since'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- Agreement history (documented addition beyond the wireframe) — REAL earlier / ended /
                 upcoming terms, including the future-dated agreement created when an amendment is
                 acknowledged. Status = the real enum only; the period sub-line is factual dates. --}}
            @if (! empty($history))
                <section class="vy-card vy-fade" style="animation-delay:.13s">
                    <div style="margin-bottom:10px">
                        <h2 class="vy-card-title">Agreement history</h2>
                        <div class="vy-line-sub">Terms that are not in effect today.</div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="vy-table">
                            <thead>
                                <tr><th>Applies to</th><th>Period</th><th>Commission</th><th class="vy-num">Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $row)
                                    <tr>
                                        <td>
                                            <span class="vy-row-month">{{ $row['scope_label'] }}</span>
                                            @if ($row['owner_label'])<div class="vy-row-sub">{{ $row['owner_label'] }}</div>@endif
                                        </td>
                                        <td class="vy-muted">
                                            {{ $row['from_display'] ?? '—' }} – {{ $row['to_display'] ?? 'Open-ended' }}
                                            @if ($row['timing_note'])<div class="vy-row-sub">{{ $row['timing_note'] }}</div>@endif
                                        </td>
                                        <td class="vy-mono">{{ $row['commission_display'] ?? 'Not set' }}</td>
                                        <td class="vy-num">
                                            <span class="vy-status vy-status--{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        {{-- ══════════ RIGHT RAIL ══════════ --}}
        <div class="vy-stack">
            {{-- 5 · KEY TERMS — REAL fields; owner-stays / cancellations omitted (not modelled) --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s">
                <span class="vy-eyebrow">Key terms</span>
                @forelse ($keyTerms as $t)
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">{{ $t['label'] }}</span>
                        <span style="font-size:13px;font-weight:600">{{ $t['value'] }}</span>
                    </div>
                @empty
                    <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">Your payout schedule and terms appear here once set on your agreement.</div>
                @endforelse
                @if ($active)
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">Payout currency</span>
                        <span class="vy-mono" style="font-weight:600">{{ $active['payout_currency'] ?? 'Not set' }}</span>
                    </div>
                @endif
            </section>

            {{-- Attribution — the REAL overrides stored on the agreement, or the honest "no overrides" --}}
            @if ($active)
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <span class="vy-eyebrow">Attribution</span>
                    @if ($overrides)
                        <div class="vy-line-sub" style="margin-bottom:2px">Agreed exceptions to the standard rules.</div>
                        @foreach ($overrides as $rule)
                            <div class="vy-list-row">
                                <span style="font-size:13px;color:var(--vy-muted)">{{ $rule['label'] }}</span>
                                <span style="font-size:13px;font-weight:600">{{ $rule['value'] }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">
                            Standard attribution — your agreement carries no exceptions to the standard rules for
                            who bears OTA costs, cleaning fees and add-ons.
                        </div>
                    @endif
                </section>
            @endif

            {{-- 6 · DOCUMENTS — the REAL agreement PDF + one row per real amendment (CAP_EXPORTS-gated) --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <span class="vy-eyebrow">Documents</span>
                @if ($canExport && $active)
                    <a href="{{ route('owner.agreement.pdf') }}" class="vy-doc-row" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid rgba(200,180,165,.22);text-decoration:none;color:inherit">
                        <span>
                            <span style="font-size:13.5px;font-weight:500;display:block">Management agreement</span>
                            <span style="font-size:11.5px;color:var(--vy-muted)">Summary · PDF</span>
                        </span>
                        <span style="font-size:11px;color:var(--vy-accent);font-weight:600">Download</span>
                    </a>
                    @foreach ($documents['amendments'] as $doc)
                        <a href="{{ route('owner.agreement.amendments.pdf', $doc['id']) }}" class="vy-doc-row" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid rgba(200,180,165,.22);text-decoration:none;color:inherit">
                            <span>
                                <span style="font-size:13.5px;font-weight:500;display:block">{{ $doc['label'] }}</span>
                                <span style="font-size:11.5px;color:var(--vy-muted)">{{ $doc['meta'] }} · PDF</span>
                            </span>
                            <span style="font-size:11px;color:var(--vy-accent);font-weight:600">Download</span>
                        </a>
                    @endforeach
                @else
                    <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">Your agreement documents are available to co-owners and accountants on your team.</div>
                @endif
            </section>

            {{-- 7 · WANT TO CHANGE YOUR TERMS — the REAL owner→operator relay (never owner→guest) --}}
            <section class="vy-card vy-fade" style="animation-delay:.15s">
                <span class="vy-eyebrow">Want to change your terms?</span>
                <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">
                    Talk to your account manager about commission, add-ons or ending the agreement.
                </div>
                <a href="{{ route('owner.messages') }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;width:100%;justify-content:center">Message your manager</a>
            </section>
        </div>
    </div>
</x-owner.shell>
