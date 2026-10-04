{{-- THE WAY BACK INTO THE CALENDAR ({@see \App\Support\Operator\CalendarReturn}, 2026-10-04) — the hidden fields a write sent from
     the Calendar's New reservation dialog carries: where the Calendar was (its first night, 7/14/30, the room-type filter) and,
     with `__stay`, the stay to reopen the dialog on (dates, adults and source as the dialog's own boxes have them, the unit that
     was clicked). ⚠️⚠️ NEVER the guest's name (#28): it stays in the tab, and `kept=1` on the way back fetches it from there.
     The server checks every one of these or leaves it out. --}}
<input type="hidden" name="return" value="{{ \App\Support\Operator\CalendarReturn::TOKEN }}">
<input type="hidden" name="return_start" value="{{ $window['start'] }}">
<input type="hidden" name="return_days" value="{{ $window['selected_days'] }}">
@if ($window['rt'] !== '')
    <input type="hidden" name="return_rt" value="{{ $window['rt'] }}">
@endif
@if ($__stay)
    <input type="hidden" name="return_check_in" value="{{ $__rnr['form']['check_in'] }}">
    <input type="hidden" name="return_check_out" value="{{ $__rnr['form']['check_out'] }}">
    <input type="hidden" name="return_adults" value="{{ (int) $__rnr['form']['adults'] }}" x-bind:value="adults">
    <input type="hidden" name="return_source" value="{{ $__rnr['form']['source'] }}" x-bind:value="source">
    @if ($__rnr['clicked'] !== '')
        <input type="hidden" name="return_unit" value="{{ $__rnr['clicked'] }}">
    @endif
@endif
