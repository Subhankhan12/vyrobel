{{-- ⚠️ ADMIN 01 · PHASE 3 — VISIBLE: put on EVERY html page of a break-glass session by EnterBreakGlassSession, whatever
     layout the page uses. Self-contained (inline styles — the CSP allows them) so no page's CSS can hide it. --}}
<div role="status" data-break-glass-banner style="position: sticky; top: 0; z-index: 2147483000; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 8px 16px; padding: 9px 16px; background: #7A1C1C; color: #FFF; font: 600 13px/1.4 ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; box-shadow: 0 2px 10px rgba(0,0,0,.25);">
    <span>{{ __('admin/break-glass/banner.session_line', ['time' => $session->expires_at->utc()->format('H:i')]) }}</span>
    <span style="font-weight: 400; opacity: .9;">{{ __('admin/break-glass/banner.viewing_as', ['organization' => $organization->name, 'name' => $viewing->name]) }}</span>
    <form method="POST" action="{{ route('admin.break-glass.end') }}" style="margin: 0;">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button type="submit" style="font: inherit; color: #7A1C1C; background: #FFF; border: 0; border-radius: 8px; padding: 4px 12px; cursor: pointer;">{{ __('admin/break-glass/banner.end_session') }}</button>
    </form>
</div>
