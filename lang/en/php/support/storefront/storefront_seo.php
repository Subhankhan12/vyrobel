<?php

// App\Support\Storefront\StorefrontSeo — the public booking pages' browser-tab title and the meta description search engines
// show. Property, room, organisation and town names are data (placeholders); each description has one whole sentence per shape.

return [
    // the kind of property, as it reads in a title or description
    'type_hotel' => 'Hotel',
    'type_aparthotel' => 'Aparthotel',
    'type_serviced_apartment' => 'Serviced apartments',
    'type_apartment_building' => 'Apartments',
    'type_apartment' => 'Apartment',
    'type_villa' => 'Villa',
    'type_hostel' => 'Hostel',
    'type_guest_house' => 'Guest house',
    'type_resort' => 'Resort',
    'type_mixed' => 'Hotel & apartments',
    'type_portfolio' => 'Stays',
    // page titles (also the organisation landing's, with :name its name)
    'title_town' => ':name, :town — Book Direct',
    'title' => ':name — Book Direct',
    'title_room' => ':room at :property — Book Direct',
    // the organisation landing's description; :towns is a list of town names
    'org_description_towns' => '{1} Browse :count property in :towns. Check availability and book directly with :org.|[0,*] Browse :count properties in :towns. Check availability and book directly with :org.',
    'org_description' => '{1} Browse :count property. Check availability and book directly with :org.|[0,*] Browse :count properties. Check availability and book directly with :org.',
    'org_description_empty' => 'Check availability and book directly with :org.',
    // where a property is: a town and its country
    'place_town_country' => ':town, :country',
    // a property page's description; :type is one of the type_ labels above, :count the number of room types
    'description_place_rooms' => '{1} Book :name direct. :type in :place. :count room type. Check live availability and reserve without going through a booking site.|[0,*] Book :name direct. :type in :place. :count room types. Check live availability and reserve without going through a booking site.',
    'description_place' => 'Book :name direct. :type in :place. Check live availability and reserve without going through a booking site.',
    'description_rooms' => '{1} Book :name direct. :type. :count room type. Check live availability and reserve without going through a booking site.|[0,*] Book :name direct. :type. :count room types. Check live availability and reserve without going through a booking site.',
    'description' => 'Book :name direct. :type. Check live availability and reserve without going through a booking site.',
    // a room page's details, listed in :details below
    'detail_sleeps' => 'sleeps up to :guests',
    'detail_bedrooms' => '{1} :count bedroom|[0,*] :count bedrooms',
    // a room page's description
    'room_description_town_details' => ':room at :property in :town — :details. Check live availability and book direct.',
    'room_description_town' => ':room at :property in :town. Check live availability and book direct.',
    'room_description_details' => ':room at :property — :details. Check live availability and book direct.',
    'room_description' => ':room at :property. Check live availability and book direct.',
    // the last two items of a list: "Lisbon, Porto and Faro"
    'list_and' => ':items and :last',
];
