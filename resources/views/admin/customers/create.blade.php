{{-- ⚠️⚠️ ADMIN 04 (2026-09-26) — CREATE AN ORGANISATION from the console. It is created through the ONE door every
     organisation is (TenantProvisioningService: roles, founder as Property Owner, first property, trial — one
     transaction). ⚠️ No password is typed here: the founder receives a single-use set-password link, which also
     confirms their address — so creation is refused, before anything is written, when that link could not reach them. --}}
<x-admin.shell active="customers" :title="__('admin/customers/create.title')" :subtitle="__('admin/customers/create.subtitle')"
    :back="route('admin.customers.index')" :back-label="__('admin/customers/create.back_label')">

    @if ($mailDiscards)
        <div class="ad-flash ad-flash--error" role="alert" style="margin-bottom: 16px;">
            {!! __('admin/customers/create.mail_off_warning') !!}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.customers.store') }}" data-guard-submit class="ad-card" style="max-width: 720px;">
        @csrf
        <h2 class="ad-panel-title">{{ __('admin/customers/create.organisation_heading') }}</h2>
        <label class="ad-label" for="c_org_name">{{ __('admin/customers/create.organisation_name_label') }}</label>
        <input id="c_org_name" class="ad-input" name="org_name" required maxlength="255" value="{{ old('org_name') }}" autocomplete="off" @error('org_name') aria-invalid="true" @enderror>
        <fieldset class="ad-choices" style="margin-top: 16px;">
            <legend class="ad-label">{{ __('admin/customers/create.currency_label') }}</legend>
            @foreach ($currencies as $currency)
                <label class="ad-choice"><input type="radio" name="currency" value="{{ $currency }}" required @checked(old('currency', 'EUR') === $currency)><span>{{ $currency }} <small>{{ \App\Support\Money\MoneyFormatter::symbol($currency) }}</small></span></label>
            @endforeach
        </fieldset>
        <p class="ad-reason-hint" style="margin: 6px 0 0;">{{ __('admin/customers/create.currency_fixed_hint') }}</p>
        <label class="ad-label" for="c_country">{{ __('admin/customers/create.country_label') }}</label>
        <select id="c_country" class="ad-input" name="country" data-searchable data-flags>
            <option value="">{{ __('admin/customers/create.country_not_set') }}</option>
            @foreach (\App\Support\Countries::options() as $code => $countryName)
                <option value="{{ $code }}" @selected(old('country') === $code)>{{ $countryName }}</option>
            @endforeach
        </select>

        <h2 class="ad-panel-title" style="margin-top: 22px;">{{ __('admin/customers/create.property_heading') }}</h2>
        <label class="ad-label" for="c_property_name">{{ __('admin/customers/create.property_name_label') }}</label>
        <input id="c_property_name" class="ad-input" name="property_name" required maxlength="255" value="{{ old('property_name') }}" autocomplete="off" @error('property_name') aria-invalid="true" @enderror>
        <fieldset class="ad-choices" style="margin-top: 16px;">
            <legend class="ad-label">{{ __('admin/customers/create.property_type_label') }}</legend>
            @foreach ($types as $type)
                <label class="ad-choice"><input type="radio" name="property_type" value="{{ $type }}" required @checked(old('property_type', 'hotel') === $type)><span>{{ match ($type) { 'villa' => __('admin/customers/create.type_villa'), 'hotel' => __('admin/customers/create.type_hotel'), 'hostel' => __('admin/customers/create.type_hostel'), default => ucfirst($type) } }}</span></label>
            @endforeach
        </fieldset>
        <label class="ad-label" for="c_location">{{ __('admin/customers/create.location_label') }}</label>
        <input id="c_location" class="ad-input" name="location" maxlength="255" value="{{ old('location') }}" placeholder="{{ __('admin/customers/create.location_placeholder') }}" autocomplete="off">

        <h2 class="ad-panel-title" style="margin-top: 22px;">{{ __('admin/customers/create.founder_heading') }}</h2>
        <p class="ad-stat-sub" style="margin: -8px 0 8px;">{{ trans_choice('admin/customers/create.founder_link_note', \App\Services\OwnerInviteService::EXPIRY_DAYS, ['count' => \App\Services\OwnerInviteService::EXPIRY_DAYS]) }}</p>
        <label class="ad-label" for="c_founder_name">{{ __('admin/customers/create.founder_name_label') }}</label>
        <input id="c_founder_name" class="ad-input" name="founder_name" required maxlength="255" value="{{ old('founder_name') }}" autocomplete="off" @error('founder_name') aria-invalid="true" @enderror>
        <label class="ad-label" for="c_founder_email">{{ __('admin/customers/create.founder_email_label') }}</label>
        <input id="c_founder_email" type="email" class="ad-input" name="founder_email" required maxlength="255" value="{{ old('founder_email') }}" autocomplete="off" @error('founder_email') aria-invalid="true" @enderror>

        <label class="ad-switch-row" style="margin-top: 16px;">
            <input type="checkbox" class="ad-switch" name="internal" value="1" @checked(old('internal'))>
            <span>{{ __('admin/customers/create.internal_label') }}<small>{{ __('admin/customers/create.internal_hint') }}</small></span>
        </label>

        <label class="ad-label" for="c_reason" style="margin-top: 16px;">{{ __('admin/customers/create.reason_label') }}</label>
        <input id="c_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/customers/create.reason_placeholder') }}" value="{{ old('reason') }}" autocomplete="off" @error('reason') aria-invalid="true" @enderror>

        <button type="submit" class="ad-btn" style="margin-top: 14px;" @disabled($mailDiscards)>{{ __('admin/customers/create.create_button') }}</button>
    </form>
</x-admin.shell>
