{{-- ⚠️⚠️ ADMIN 04 (2026-09-26) — CREATE AN ORGANISATION from the console. It is created through the ONE door every
     organisation is (TenantProvisioningService: roles, founder as Property Owner, first property, trial — one
     transaction). ⚠️ No password is typed here: the founder receives a single-use set-password link, which also
     confirms their address — so creation is refused, before anything is written, when that link could not reach them. --}}
<x-admin.shell active="customers" title="Create an organisation" subtitle="A new organisation, its first property, and a founder who sets their own password."
    :back="route('admin.customers.index')" back-label="All organisations">

    @if ($mailDiscards)
        <div class="ad-flash ad-flash--error" role="alert" style="margin-bottom: 16px;">
            <strong>Mail is not being delivered</strong> — the founder could never receive the set-password link, so creating
            an organisation is refused until mail is switched on.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.customers.store') }}" data-guard-submit class="ad-card" style="max-width: 720px;">
        @csrf
        <h2 class="ad-panel-title">Organisation</h2>
        <label class="ad-label" for="c_org_name">Organisation name</label>
        <input id="c_org_name" class="ad-input" name="org_name" required maxlength="255" value="{{ old('org_name') }}" autocomplete="off" @error('org_name') aria-invalid="true" @enderror>
        <fieldset class="ad-choices" style="margin-top: 16px;">
            <legend class="ad-label">Currency</legend>
            @foreach ($currencies as $currency)
                <label class="ad-choice"><input type="radio" name="currency" value="{{ $currency }}" required @checked(old('currency', 'EUR') === $currency)><span>{{ $currency }} <small>{{ \App\Support\Money\MoneyFormatter::symbol($currency) }}</small></span></label>
            @endforeach
        </fieldset>
        <p class="ad-reason-hint" style="margin: 6px 0 0;">Currency cannot be changed later from the console.</p>
        <label class="ad-label" for="c_country">Country</label>
        <select id="c_country" class="ad-input" name="country" data-searchable data-flags>
            <option value="">— Not set —</option>
            @foreach (\App\Support\Countries::options() as $code => $countryName)
                <option value="{{ $code }}" @selected(old('country') === $code)>{{ $countryName }}</option>
            @endforeach
        </select>

        <h2 class="ad-panel-title" style="margin-top: 22px;">First property</h2>
        <label class="ad-label" for="c_property_name">Property name</label>
        <input id="c_property_name" class="ad-input" name="property_name" required maxlength="255" value="{{ old('property_name') }}" autocomplete="off" @error('property_name') aria-invalid="true" @enderror>
        <fieldset class="ad-choices" style="margin-top: 16px;">
            <legend class="ad-label">Property type</legend>
            @foreach ($types as $type)
                <label class="ad-choice"><input type="radio" name="property_type" value="{{ $type }}" required @checked(old('property_type', 'hotel') === $type)><span>{{ ucfirst($type) }}</span></label>
            @endforeach
        </fieldset>
        <label class="ad-label" for="c_location">Location</label>
        <input id="c_location" class="ad-input" name="location" maxlength="255" value="{{ old('location') }}" placeholder="City, region" autocomplete="off">

        <h2 class="ad-panel-title" style="margin-top: 22px;">Founder</h2>
        <p class="ad-stat-sub" style="margin: -8px 0 8px;">They receive a single-use link to set their own password; using it also confirms their email. The link expires in {{ \App\Services\OwnerInviteService::EXPIRY_DAYS }} days.</p>
        <label class="ad-label" for="c_founder_name">Founder's name</label>
        <input id="c_founder_name" class="ad-input" name="founder_name" required maxlength="255" value="{{ old('founder_name') }}" autocomplete="off" @error('founder_name') aria-invalid="true" @enderror>
        <label class="ad-label" for="c_founder_email">Founder's email</label>
        <input id="c_founder_email" type="email" class="ad-input" name="founder_email" required maxlength="255" value="{{ old('founder_email') }}" autocomplete="off" @error('founder_email') aria-invalid="true" @enderror>

        <label class="ad-switch-row" style="margin-top: 16px;">
            <input type="checkbox" class="ad-switch" name="internal" value="1" @checked(old('internal'))>
            <span>Internal organisation<small>One of Vyrobel's own — excluded from platform statistics.</small></span>
        </label>

        <label class="ad-label" for="c_reason" style="margin-top: 16px;">Reason</label>
        <input id="c_reason" class="ad-input ad-reason" name="reason" required minlength="5" maxlength="500" placeholder="Why is this organisation being created?" value="{{ old('reason') }}" autocomplete="off" @error('reason') aria-invalid="true" @enderror>

        <button type="submit" class="ad-btn" style="margin-top: 14px;" @disabled($mailDiscards)>Create the organisation</button>
    </form>
</x-admin.shell>
