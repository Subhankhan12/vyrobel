{{-- SUPER ADMIN · create a plan of EITHER shape. This is the panel's reason for existing: a per-unit plan
     previously had no in-app path at all and could only be made in a seeder or the console.

     ⚠️ A NEW PLAN IS ALWAYS A DRAFT. Its Stripe Price does not exist yet, so offering it immediately would put
     a tier on the paywall whose checkout cannot complete — the broken checkout billing pass 2 made throw. --}}
<x-admin.shell active="plans" :title="__('admin/plans/create.title')"
    :subtitle="__('admin/plans/create.subtitle')"
    :back="route('admin.plans.index')" :back-label="__('admin/plans/create.back_label')">

    @if ($errors->any())
        <div class="ad-flash ad-flash--error">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.plans.store') }}" style="max-width: 640px;" data-guard-submit>
        @csrf

        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/plans/create.details_heading') }}</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label class="ad-label" for="name">{{ __('admin/plans/create.plan_name_label') }}</label>
                    <input class="ad-input" type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="ad-label" for="code">{{ __('admin/plans/create.code_label') }}</label>
                    <input class="ad-input" type="text" id="code" name="code" value="{{ old('code') }}"
                           placeholder="{{ __('admin/plans/create.code_placeholder') }}" required>
                    <div style="color: var(--ad-faint); font-size: 11.5px; margin-top: 5px;">{{ __('admin/plans/create.code_hint') }}</div>
                </div>
            </div>
        </div>

        @include('admin.plans._shape-fields', ['plan' => null])
        @include('admin.partials.reason', ['placeholder' => __('admin/plans/create.reason_placeholder')])

        <div style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
            <button class="ad-btn" type="submit">{{ __('admin/plans/create.create_draft_button') }}</button>
            <a class="ad-btn ad-btn--ghost" href="{{ route('admin.plans.index') }}">{{ __('common.cancel') }}</a>
        </div>
    </form>
</x-admin.shell>
