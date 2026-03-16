<?php

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
});