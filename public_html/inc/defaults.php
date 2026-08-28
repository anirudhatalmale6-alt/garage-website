<?php
/* ------------------------------------------------------------------
   First-run content.

   These values are only used the very first time the site loads, to
   create /data/settings.json and /data/stock.json. After that every
   word on the site comes out of those files and is edited from the
   Stock Manager — nothing here is read again.
------------------------------------------------------------------ */

function default_settings(): array {
    return [
        'business_name'   => 'MUB Autos',
        'strapline'       => 'Sales & Service',
        'logo_mark'       => 'M',
        'logo_file'       => '',
        'established'     => '',
        'town'            => 'Tranent',

        'phone'           => '01875 710 203',
        'mobile'          => '0774 653 3000',
        'whatsapp'        => '447746533000',

        'address_line1'   => '128A High Street',
        'address_line2'   => 'Tranent',
        'postcode'        => 'EH33 1HJ',

        'hero_heading'    => 'Reliable car repairs & <em>quality used cars</em> in Tranent',
        'hero_text'       => 'Independent garage on the High Street in Tranent. Servicing, diagnostics, clutches, brakes and tyres for all makes and models — plus a hand-picked selection of used cars for sale.',
        'meta_desc'       => "Independent garage in Tranent, East Lothian. Servicing, repairs, diagnostics, clutches, brakes and tyres — plus quality used cars for sale.",

        'about_heading'   => 'A local garage that still answers the phone',
        'about_1'         => 'MUB Autos is an independent garage on the High Street in Tranent, looking after drivers across East Lothian. Servicing, repairs and diagnostics for all makes and models, with the price agreed before any work starts.',
        'about_2'         => 'Alongside the workshop we sell a small, carefully chosen selection of used cars — every one prepared and checked in our own workshop before it goes on the forecourt.',
        'footer_blurb'    => 'Independent garage and used car sales in Tranent. Servicing, repairs and diagnostics for all makes and models.',

        'serviced_a_year' => '',
        'warranty'        => '',

        'google_rating'   => '',
        'google_count'    => '',
        'google_url'      => '',

        'hours' => [
            ['day' => 'Monday',    'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Tuesday',   'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Wednesday', 'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Thursday',  'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Friday',    'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Saturday',  'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Sunday',    'open' => '',      'close' => '',      'closed' => true],
        ],

        'reviews' => [],

        'services' => [
            ['title' => 'General Car Repairs',            'text' => 'Suspension, exhausts, steering, cooling, electrics — diagnosed properly and fixed once.'],
            ['title' => 'Engine Service & Replacement',   'text' => 'From timing belts and head gaskets to full engine and gearbox replacement.'],
            ['title' => 'Clutch Replacement',             'text' => 'Slipping, juddering or a heavy pedal? Clutch and dual-mass flywheel work at a fair fixed price.'],
            ['title' => 'Brake Service & Replacement',    'text' => 'Discs, pads, calipers, handbrake cables and brake fluid changes. Free brake check any time.'],
            ['title' => 'Diagnostics',                    'text' => 'Dealer-level fault code reading and live data. Warning light on? We will tell you what it actually means.'],
            ['title' => 'Oil Change & Maintenance',       'text' => 'Interim, full and manufacturer-schedule servicing using the correct grade oil and OE-quality filters.'],
            ['title' => 'Battery Replacement',            'text' => 'Free battery and charging system test. Fitted and coded the same day, with a 3-year guarantee.'],
            ['title' => 'Tyre Replacement',               'text' => 'Budget to premium brands, supplied and fitted while you wait. Balancing and puncture repairs too.'],
        ],

        /* password is "changeme" — the Stock Manager nags until it is changed */
        'password_hash'   => '',
        'password_is_default' => true,
    ];
}

function default_stock(): array {
    /* The forecourt starts empty. Real cars go in through the Stock
       Manager — a live site should never show vehicles that do not exist. */
    return [];
}
