<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * The marketing page.
     *
     * Everything it shows is static copy, so the values come straight from
     * config and no database is touched.
     */
    public function index(): View
    {
        return view('landing', [
            'appName' => (string) config('app.name'),
            'whatsappNumber' => (string) config('landing.whatsapp_number'),
            'whatsappUrl' => (string) config('landing.whatsapp_subscribe_url'),
            'demoSlug' => (string) config('landing.demo_slug'),
            'price' => (string) config('landing.price'),
            'currency' => (string) config('landing.currency'),
            'period' => (string) config('landing.period'),
        ]);
    }
}
