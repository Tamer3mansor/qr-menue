<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Number
    |--------------------------------------------------------------------------
    |
    | The number the landing page points its subscribe buttons at, in
    | international format without the leading plus, for example 201001234567.
    | It carries a placeholder default so a misconfigured environment produces
    | an obviously dead link rather than a silent one.
    |
    */

    'whatsapp_number' => env('WHATSAPP_NUMBER', '201001234567'),

    /*
    |--------------------------------------------------------------------------
    | Subscribe Link
    |--------------------------------------------------------------------------
    |
    | Derived from the number above so the view never builds a URL by hand.
    | wa.me expects the number without the leading plus sign.
    |
    */

    'whatsapp_subscribe_url' => 'https://wa.me/'.ltrim((string) env('WHATSAPP_NUMBER', '201001234567'), '+'),

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | The yearly price the landing page advertises, in Egyptian pounds. Kept
    | here rather than in the view so the number has one home when real billing
    | eventually replaces the placeholder pricing.
    |
    */

    'price' => env('LANDING_PRICE', '1500'),

    'currency' => env('LANDING_CURRENCY', 'جنيه'),

    'period' => env('LANDING_PERIOD', 'سنة'),

    /*
    |--------------------------------------------------------------------------
    | Demo Restaurant
    |--------------------------------------------------------------------------
    |
    | The slug the "demo" button points at. The landing page links to it
    | unconditionally, so the seeded tenant must exist or the button 404s.
    |
    */

    'demo_slug' => env('LANDING_DEMO_SLUG', 'demo'),

];
