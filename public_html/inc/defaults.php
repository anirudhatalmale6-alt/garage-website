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
        'business_name'   => 'Northgate Autos',
        'strapline'       => 'Sales & Service',
        'logo_mark'       => 'N',
        'logo_file'       => '',
        'established'     => '2009',
        'town'            => 'Leeds',

        'phone'           => '01632 960 118',
        'mobile'          => '07700 900 118',
        'whatsapp'        => '447700900118',

        'address_line1'   => 'Unit 4, Northgate Works',
        'address_line2'   => 'Leeds',
        'postcode'        => 'LS9 0AA',

        'hero_heading'    => 'Reliable car repairs & <em>quality used cars</em> in Leeds',
        'hero_text'       => 'Family-run workshop on Northgate Works. Servicing, diagnostics, clutches, brakes and tyres by MOT-trained technicians — plus a small, hand-picked selection of used cars we would happily put our own family in.',
        'meta_desc'       => "Independent garage in Leeds. Servicing, repairs, diagnostics, clutches, brakes and tyres — plus a hand-picked selection of used cars for sale.",

        'about_heading'   => 'A local garage that still answers the phone',
        'about_1'         => 'Northgate Autos has looked after drivers around Leeds since 2009. We are a small, family-run team — two ramps, one MOT-trained diagnostics technician, and no sales targets pushing work you do not need.',
        'about_2'         => 'Alongside the workshop we sell a handful of carefully chosen used cars. We buy them the same way we would buy for ourselves: low owners, real service history, and nothing that has not been through our own ramp first.',
        'footer_blurb'    => 'Independent garage and used car sales. Servicing, repairs and diagnostics for all makes and models.',

        'serviced_a_year' => '1,400+',
        'warranty'        => '12 mth',

        'google_rating'   => '4.9',
        'google_count'    => '187',
        'google_url'      => '',

        'hours' => [
            ['day' => 'Monday',    'open' => '08:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'Tuesday',   'open' => '08:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'Wednesday', 'open' => '08:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'Thursday',  'open' => '08:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'Friday',    'open' => '08:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'Saturday',  'open' => '08:30', 'close' => '13:00', 'closed' => false],
            ['day' => 'Sunday',    'open' => '',      'close' => '',      'closed' => true],
        ],

        'reviews' => [
            [
                'name' => 'Dan H.', 'when' => '2 weeks ago',
                'text' => 'Clutch went on the way to work and they had it back to me the next afternoon. Quoted me a price on the phone and that was exactly what I paid. Cannot ask for more than that.',
            ],
            [
                'name' => 'Sarah K.', 'when' => '1 month ago',
                'text' => 'Bought a Golf from them in March. It had a fresh service, twelve months MOT and they sorted a small rattle a fortnight later without quibbling. Genuinely straight people.',
            ],
            [
                'name' => 'Michael P.', 'when' => '1 month ago',
                'text' => 'Took my Qashqai in for an engine light two other garages could not work out. They found the fault in an hour and only charged for the part. Will not go anywhere else now.',
            ],
        ],

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
    return [
        [
            'id' => 'golf', 'make' => 'Volkswagen', 'model' => 'Golf', 'trim' => '1.6 TDI Match 5dr',
            'year' => 2015, 'mileage' => 68400, 'engine' => '1598 cc', 'gearbox' => 'Manual',
            'fuel' => 'Diesel', 'doors' => 5, 'colour' => 'Pure White',
            'mot' => '14 March 2027', 'history' => 'Full service history — 7 stamps',
            'price' => 7495, 'owners' => 2, 'reg' => 'MA15 XXX',
            'photos' => ['assets/img/golf-1.jpg', 'assets/img/golf-2.jpg'],
            'sold' => false, 'featured' => true,
            'blurb' => 'One of the best all-rounders we sell. Cambelt and water pump done at 62k, new front discs and pads fitted last month, and it drives absolutely faultlessly. Two former keepers, both local.',
        ],
        [
            'id' => 'qashqai', 'make' => 'Nissan', 'model' => 'Qashqai', 'trim' => '1.5 dCi Acenta Premium',
            'year' => 2016, 'mileage' => 59100, 'engine' => '1461 cc', 'gearbox' => 'Manual',
            'fuel' => 'Diesel', 'doors' => 5, 'colour' => 'Gun Metallic',
            'mot' => '21 January 2027', 'history' => 'Full main dealer history',
            'price' => 8950, 'owners' => 1, 'reg' => 'MB16 XXX',
            'photos' => ['assets/img/qashqai-1.jpg', 'assets/img/qashqai-2.jpg'],
            'sold' => false, 'featured' => true,
            'blurb' => 'One owner from new with a complete Nissan history. Panoramic roof, reversing camera and cruise control. Serviced, MOT\'d and fully valeted in our own workshop before going on sale.',
        ],
        [
            'id' => 'bmw-3-series', 'make' => 'BMW', 'model' => '3 Series', 'trim' => '320d Sport Saloon',
            'year' => 2017, 'mileage' => 82300, 'engine' => '1995 cc', 'gearbox' => 'Automatic',
            'fuel' => 'Diesel', 'doors' => 4, 'colour' => 'Alpine White',
            'mot' => '30 November 2026', 'history' => 'Full BMW service history',
            'price' => 11750, 'owners' => 2, 'reg' => 'MC17 XXX',
            'photos' => ['assets/img/bmwx-2.jpg', 'assets/img/bmwx-1.jpg', 'assets/img/bmw-2.jpg'],
            'sold' => false, 'featured' => true,
            'blurb' => 'Sport spec with the 8-speed automatic box, heated leather and sat nav. Motorway miles rather than town miles, and it shows — the interior is as good as you will find at this money.',
        ],
        [
            'id' => 'astra', 'make' => 'Vauxhall', 'model' => 'Astra', 'trim' => '1.4T SRi 5dr',
            'year' => 2014, 'mileage' => 70200, 'engine' => '1364 cc', 'gearbox' => 'Manual',
            'fuel' => 'Petrol', 'doors' => 5, 'colour' => 'Mint Green',
            'mot' => '17 April 2027', 'history' => 'Full service history — 6 stamps',
            'price' => 5495, 'owners' => 3, 'reg' => 'MD64 XXX',
            'photos' => ['assets/img/astra-1.jpg'],
            'sold' => true, 'featured' => false,
            'blurb' => 'Sold within the week — the turbo petrol SRi always goes quickly at this money. We usually have something similar coming through, so give us a ring and we will keep an eye out for you.',
        ],
        [
            'id' => 'focus', 'make' => 'Ford', 'model' => 'Focus', 'trim' => '1.6 Zetec 5dr',
            'year' => 2013, 'mileage' => 74900, 'engine' => '1596 cc', 'gearbox' => 'Manual',
            'fuel' => 'Petrol', 'doors' => 5, 'colour' => 'Moondust Silver',
            'mot' => '2 September 2026', 'history' => 'Service history — 6 stamps',
            'price' => 4295, 'owners' => 3, 'reg' => 'ME13 XXX',
            'photos' => ['assets/img/focus-1.jpg', 'assets/img/focus-2.jpg'],
            'sold' => false, 'featured' => false,
            'blurb' => 'Honest, cheap-to-run family hatch. Air conditioning ice cold, four matching tyres with plenty of tread, and a clean bill of health on the ramp. Ideal first car or second car.',
        ],
        [
            'id' => 'corsa', 'make' => 'Vauxhall', 'model' => 'Corsa', 'trim' => '1.2 SXi 3dr',
            'year' => 2013, 'mileage' => 61750, 'engine' => '1229 cc', 'gearbox' => 'Manual',
            'fuel' => 'Petrol', 'doors' => 3, 'colour' => 'Bright Yellow',
            'mot' => '8 June 2026', 'history' => 'Service history — 5 stamps',
            'price' => 3650, 'owners' => 2, 'reg' => 'MF13 XXX',
            'photos' => ['assets/img/corsa-3.jpg', 'assets/img/corsa-2.jpg', 'assets/img/corsa-1.jpg'],
            'sold' => false, 'featured' => false,
            'blurb' => 'Low insurance group and cheap tax to run — the reason these make such good first cars. Fresh service, four new tyres and an MOT with no advisories. Drives without a rattle.',
        ],
    ];
}
