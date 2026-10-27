<?php

// resources/views/livewire/operator/portfolio.blade.php — the operator Portfolio (/app/portfolio): every property, the comparison, and the add / edit / remove dialogs.

return [
    'title' => 'Portfolio',
    // the page subtitle: ":properties · :units", each built from property_count / unit_count below
    'subtitle' => ':properties · :units',
    'property_count' => '{1} :count property|[0,*] :count properties',
    'unit_count' => '{1} :count unit|[0,*] :count units',
    // the toolbar
    'your_properties' => 'Your properties',
    'add_property' => 'Add property',
    'add_property_requires_permission' => 'Requires the Manage properties permission',
    // the tag on the disabled "Add property" pill: only a manager may add one
    'manager_tag' => 'Manager',
    // the KPI strip (all properties together)
    'kpi_revenue_month' => 'Revenue · month',
    'room_revenue_across' => '{1} room revenue · :count property|[0,*] room revenue · :count properties',
    'kpi_occupancy_tonight' => 'Occupancy tonight',
    'rooms_sold_of' => ':sold of :count rooms sold',
    'kpi_arrivals_today' => 'Arrivals today',
    'across_properties' => '{1} across :count property|[0,*] across :count properties',
    'kpi_needs_attention' => 'Needs attention',
    'attention_all_good' => 'all good',
    'attention_setup_sync' => 'setup / sync',
    // no property yet (the second form is for someone who may add one)
    'empty' => 'No properties yet.',
    'empty_can_add' => 'No properties yet. Add your first property to get started.',
    // a property card
    'working_here' => 'Working here',
    // :location is the city (or time zone), :type the property type, :units the unit_count text
    'card_meta' => ':location · :type · :units',
    // :date is the leaving date without its year, e.g. "12 Nov"
    'leaving_badge' => 'Leaving :date',
    'stat_occupancy' => 'Occupancy',
    'stat_adr_month' => 'ADR · mo',
    'stat_revenue_month' => 'Revenue · mo',
    'occupancy_rail_label' => 'Occupancy tonight :pct% — :sold of :count units sold',
    'fix_sync' => 'Fix sync →',
    'chip_dashboard' => 'Dashboard',
    'chip_calendar' => 'Calendar',
    'arrivals_today_chip' => '{1} :count arrival today|[0,*] :count arrivals today',
    // a property that is leaving: one whole message per case
    'leaving_after_checkout' => '<strong>Leaving after the last checkout on :date.</strong> It stays open for its guests until then. Bookings, invoices and payments are kept.',
    'leaving_on' => '<strong>Leaving on :date, at the nightly removal.</strong> Bookings, invoices and payments are kept.',
    // the action that switches into a property
    'open' => 'Open',
    'keep_property' => 'Keep this property',
    'read_only_title' => 'Your workspace is read-only right now',
    'remove_unavailable_label' => 'Remove :name (unavailable while the workspace is read-only)',
    'remove_name_label' => 'Remove :name',
    'remove_property' => 'Remove property',
    // the comparison table (this year)
    'comparison_heading' => 'This year, side by side',
    'full_report' => 'Full report ›',
    'comparison_note' => 'occupancy · ADR · RevPAR · room revenue',
    'col_property' => 'Property',
    'col_occupancy' => 'Occupancy',
    'col_adr' => 'ADR',
    'col_revpar' => 'RevPAR',
    'col_room_revenue' => 'Room revenue',
    // the total row: every property together
    'portfolio_total' => 'Portfolio',
    // the Add dialog
    'add_heading' => 'Add a property',
    // one whole message per plan shape: per-unit plans are billed per unit, flat plans include it
    'add_sub_per_unit' => 'It joins your organization — billed per unit. You\'ll set up a room type, a unit and a rate plan next; all your team roles carry over.',
    'add_sub_flat' => 'It joins your organization — included in your plan. You\'ll set up a room type, a unit and a rate plan next; all your team roles carry over.',
    'property_name' => 'Property name',
    'name_placeholder' => 'e.g. Seaside Villas',
    'location_label' => 'Location <span class="vy-fl-note">· optional</span>',
    'location_placeholder' => 'e.g. Faro, Portugal',
    'type_label' => 'Type',
    'timezone_label' => 'Time zone',
    'timezone_search_placeholder' => 'Search time zones',
    'timezone_no_match' => 'No time zone matches — try a city or “UTC+4”',
    'new_property_help' => 'A new property starts empty — add a room type and a unit, then a rate plan with prices, and it can take bookings.',
    // the Edit dialog
    'edit_heading' => 'Edit property',
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    // the Remove dialog when this is the only property left
    'only_property_heading' => ':name is your only property',
    'only_property_body' => 'Vyrobel needs at least one property to work in, and this is the only one that isn’t leaving. <strong>Add another property first, then you can remove this one.</strong>',
    // the button that closes this dialog and opens the Add dialog
    'add_a_property_button' => 'Add a property',
    // the Remove dialog
    'remove_heading' => 'Remove :name?',
    // :location is the city (or time zone), :units the unit_count text
    'location_units' => ':location · :units',
    'removed_on_confirm' => 'Removed as soon as you confirm',
    'clear_no_owners' => 'Nobody is staying and nothing is booked ahead.',
    'clear_with_owners' => 'Nobody is staying and nothing is booked ahead. Owners of its units are told.',
    // the "Today" step: stays still ahead (and guests in the house). The space before the full stop in the
    // second message is how the page has always printed it.
    // the space before the full stop is the old page's own (kept byte-identical; on the English-quirks list)
    'today_stays' => '{1} :count current or upcoming stay . It stays open for them, and a new booking moves the date later.|[0,*] :count current or upcoming stays . It stays open for them, and a new booking moves the date later.',
    'today_stays_in_house' => '{1} :count current or upcoming stay, :in_house checked in . It stays open for them, and a new booking moves the date later.|[0,*] :count current or upcoming stays, :in_house checked in . It stays open for them, and a new booking moves the date later.',
    'today_in_house' => '{1} :count guest still checked in, past the checkout date. It stays open for them, and a new booking moves the date later.|[0,*] :count guests still checked in, past the checkout date. It stays open for them, and a new booking moves the date later.',
    'open_until_last_checkout' => 'Stays open until the last checkout on :date',
    'open_until_checkout' => 'Stays open until they check out',
    'then_removed' => 'Then it’s removed at the nightly run. You can keep it any time before.',
    'then_removed_with_owners' => 'Then it’s removed at the nightly run, and owners of its units are told. You can keep it any time before.',
    'kept' => 'Kept',
    'kept_bookings' => 'Bookings and guest history',
    'kept_invoices' => 'Invoices, payments and statements',
    'goes' => 'Goes',
    'goes_storefront' => 'Its storefront page',
    'goes_channels' => 'Its channel connections',
    'goes_card' => 'This card — it can’t be restored',
    'type_to_confirm' => 'Type “:name” to confirm',
    'name_matches' => 'Name matches',
    'name_must_match' => 'It must match exactly',
    'remove_after_last_checkout' => 'Remove after last checkout',
];
