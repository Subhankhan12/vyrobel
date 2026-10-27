<?php

// resources/views/admin/customers/create.blade.php — creating an organisation from the admin console.

return [
    'title' => 'Create an organisation',
    'subtitle' => 'A new organisation, its first property, and a founder who sets their own password.',
    'back_label' => 'All organisations',
    'mail_off_warning' => '<strong>Mail is not being delivered</strong> — the founder could never receive the set-password link, so creating an organisation is refused until mail is switched on.',
    'organisation_heading' => 'Organisation',
    'organisation_name_label' => 'Organisation name',
    'currency_label' => 'Currency',
    'currency_fixed_hint' => 'Currency cannot be changed later from the console.',
    'country_label' => 'Country',
    'country_not_set' => '— Not set —',
    'property_heading' => 'First property',
    'property_name_label' => 'Property name',
    'property_type_label' => 'Property type',
    // the property type codes (OnboardingController::TYPES); an unknown code shows as the code with a capital
    'type_villa' => 'Villa',
    'type_hotel' => 'Hotel',
    'type_hostel' => 'Hostel',
    'location_label' => 'Location',
    'location_placeholder' => 'City, region',
    'founder_heading' => 'Founder',
    // the pre-conversion English printed "days" for every number, 1 included
    'founder_link_note' => '{1} They receive a single-use link to set their own password; using it also confirms their email. The link expires in :count days.|[0,*] They receive a single-use link to set their own password; using it also confirms their email. The link expires in :count days.',
    'founder_name_label' => 'Founder\'s name',
    'founder_email_label' => 'Founder\'s email',
    'internal_label' => 'Internal organisation',
    'internal_hint' => 'One of Vyrobel\'s own — excluded from platform statistics.',
    'reason_label' => 'Reason',
    'reason_placeholder' => 'Why is this organisation being created?',
    'create_button' => 'Create the organisation',
];
