{{-- What erasing a guest does — shared by the list's bulk Erase and a row's Erase (2026-09-29), so the two can never
     describe it differently. It says what Guest::anonymize() actually does: the person's data goes, the books stay.
     The kept/goes columns reuse the Portfolio removal's styles (one definition). --}}
<div class="vy-rm-ledger">
    <div class="vy-rm-col is-goes">
        <h4>{{ __('livewire/operator/partials/guest-erase-ledger.erased_heading') }}</h4>
        <ul><li>{{ __('livewire/operator/partials/guest-erase-ledger.erased_name_contact') }}</li><li>{{ __('livewire/operator/partials/guest-erase-ledger.erased_city_preferences') }}</li><li>{{ __('livewire/operator/partials/guest-erase-ledger.erased_documents_messages') }}</li><li>{{ __('livewire/operator/partials/guest-erase-ledger.erased_team_chat') }}</li></ul>
    </div>
    <div class="vy-rm-col is-kept">
        <h4>{{ __('livewire/operator/partials/guest-erase-ledger.kept_heading') }}</h4>
        <ul><li>{{ __('livewire/operator/partials/guest-erase-ledger.kept_bookings') }}</li><li>{{ __('livewire/operator/partials/guest-erase-ledger.kept_invoices') }}</li></ul>
    </div>
</div>
