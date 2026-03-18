<?php

use App\Http\Controllers\Frontend\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect'],
], function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/storefront-test', function () {
        return Inertia::render('Storefront/Home');
    })->name('storefront.test');

    /*
    |--------------------------------------------------------------------------
    | CMS pages
    |--------------------------------------------------------------------------
    |
    | Тримати в самому низу групи, щоб цей маршрут не перехоплював
    | інші сторінки магазину.
    |
    */
    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '^(?!admin|livewire|storage|api).+$')
        ->name('cms.page.show');
});