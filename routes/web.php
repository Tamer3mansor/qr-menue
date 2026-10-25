<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

/*
| Both public pages are addressed by the same slug: the tenant domain when they
| have one, and their customer id otherwise.
*/
Route::get('/menu/{slug}', [MenuController::class, 'show'])->name('menu.show');

Route::get('/site/{slug}', [WebsiteController::class, 'show'])->name('site.show');

/*
| The visitor chatbot is public, so it is throttled to keep a single session
| from running up the restaurant's AI bill.
*/
Route::post('/menu/{slug}/chat', [ChatController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('menu.chat');
Route::post('/menu/{slug}/chat/clear', [ChatController::class, 'clearChat'])->name('menu.chat.clear');

Route::post('/site/{slug}/chat', [ChatController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('site.chat');
Route::post('/site/{slug}/chat/clear', [ChatController::class, 'clearChat'])->name('site.chat.clear');

/*
| Guests order without an account, so the cart lives in the session and every
| action resolves the tenant from the same slug the menu uses.
*/
foreach (['menu', 'site'] as $prefix) {
    Route::prefix($prefix)->name("{$prefix}.")->group(function (): void {
        Route::post('/{slug}/cart/add', [OrderController::class, 'addToCart'])->name('cart.add');
        Route::post('/{slug}/cart/update', [OrderController::class, 'updateCart'])->name('cart.update');
        Route::get('/{slug}/cart', [OrderController::class, 'getCart'])->name('cart');
        Route::post('/{slug}/order/submit', [OrderController::class, 'submitOrder'])->name('order.submit');
        Route::get('/{slug}/order/track', [OrderController::class, 'trackOrder'])->name('order.track');
    });
}

/*
| Tenant websites used to be served from a subdomain, so demo.example.com
| resolved the "demo" tenant. The slug routes above replace it until a real base
| domain is configured, at which point this group can be switched back on: it is
| only registered when a base domain exists, otherwise the {subdomain} placeholder
| would match every host on the install.
|
| if (filled(config('app.base_domain'))) {
|     Route::domain('{subdomain}.'.config('app.base_domain'))
|         ->middleware('identify.tenant.subdomain')
|         ->group(function (): void {
|             Route::get('/', [WebsiteController::class, 'show'])->name('tenant.home');
|             Route::get('/menu', fn () => abort(501))->name('tenant.menu');
|         });
| }
*/
