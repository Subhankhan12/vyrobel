{{-- ⚠️ ADMIN 01 · PHASE 5 — Vyrobel's announcements to THIS person's organisation: live (started, not ended, not
     withdrawn), aimed at every tenant or at theirs, and not yet dismissed by them. Plain text, escaped. Read-only here;
     dismissing is its own self-only POST. --}}
@php
    $vyAnnUser = auth()->user();
    $vyAnnOrg = app(\App\Support\Tenancy\TenantContext::class)->id();
    $vyAnnouncements = ($vyAnnUser !== null && $vyAnnOrg !== null)
        ? \App\Models\PlatformAnnouncement::query()->live()
            ->whereNotIn('id', \Illuminate\Support\Facades\DB::table('announcement_dismissals')->where('user_id', $vyAnnUser->getAuthIdentifier())->select('announcement_id'))
            ->latest('starts_at')->limit(5)->get()
            ->filter(fn ($a) => $a->reaches((string) $vyAnnOrg))->take(3)
        : collect();
@endphp
@foreach ($vyAnnouncements as $vyAnn)
    <div role="status" data-platform-announcement style="display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; padding: 10px 18px; font: 500 13.5px/1.5 ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: {{ $vyAnn->level === 'warning' ? '#FEF3C7' : '#E0E7FF' }}; color: {{ $vyAnn->level === 'warning' ? '#78350F' : '#1E1B4B' }}; border-bottom: 1px solid rgba(0,0,0,.08);">
        <span><strong>From Vyrobel · {{ $vyAnn->title }}</strong> — {{ $vyAnn->body }}</span>
        <form method="POST" action="{{ route('announcements.dismiss', $vyAnn->id) }}" style="margin: 0;">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <button type="submit" aria-label="Dismiss this announcement" style="font: inherit; background: transparent; border: 1px solid currentColor; border-radius: 8px; padding: 2px 10px; color: inherit; cursor: pointer;">Dismiss</button>
        </form>
    </div>
@endforeach
