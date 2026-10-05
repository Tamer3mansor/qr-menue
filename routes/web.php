<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\MenuController;
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
