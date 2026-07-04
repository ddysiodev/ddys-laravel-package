<?php

use Ddys\Laravel\Http\Controllers\DiagnosticsController;
use Ddys\Laravel\Http\Controllers\PageController;
use Ddys\Laravel\Http\Controllers\ProxyController;
use Ddys\Laravel\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

if (config('ddys.routes.enabled', true)) {
    Route::middleware(config('ddys.routes.middleware', ['web']))
        ->prefix(config('ddys.routes.prefix', 'ddys'))
        ->name(config('ddys.routes.name', 'ddys.'))
        ->group(function () {
            Route::get('/', [PageController::class, 'front'])->name('front');
            Route::get('/movies', [PageController::class, 'view'])->defaults('ddysView', 'movies')->name('movies');
            Route::get('/latest', [PageController::class, 'view'])->defaults('ddysView', 'latest')->name('latest');
            Route::get('/hot', [PageController::class, 'view'])->defaults('ddysView', 'hot')->name('hot');
            Route::get('/search', [PageController::class, 'view'])->defaults('ddysView', 'search')->name('search');
            Route::get('/suggest', [PageController::class, 'view'])->defaults('ddysView', 'suggest')->name('suggest');
            Route::get('/calendar', [PageController::class, 'view'])->defaults('ddysView', 'calendar')->name('calendar');
            Route::get('/movie/{slug}', [PageController::class, 'view'])->defaults('ddysView', 'movie')->where('slug', '[^/]+')->name('movie');
            Route::get('/movie/{slug}/sources', [PageController::class, 'view'])->defaults('ddysView', 'sources')->where('slug', '[^/]+')->name('sources');
            Route::get('/movie/{slug}/related', [PageController::class, 'view'])->defaults('ddysView', 'related')->where('slug', '[^/]+')->name('related');
            Route::get('/movie/{slug}/comments', [PageController::class, 'view'])->defaults('ddysView', 'comments')->where('slug', '[^/]+')->name('comments');
            Route::get('/collections', [PageController::class, 'view'])->defaults('ddysView', 'collections')->name('collections');
            Route::get('/collection/{slug}', [PageController::class, 'view'])->defaults('ddysView', 'collection')->where('slug', '[^/]+')->name('collection');
            Route::get('/shares', [PageController::class, 'view'])->defaults('ddysView', 'shares')->name('shares');
            Route::get('/share/{id}', [PageController::class, 'view'])->defaults('ddysView', 'share')->whereNumber('id')->name('share');
            Route::get('/requests', [PageController::class, 'view'])->defaults('ddysView', 'requests')->name('requests');
            Route::get('/activities', [PageController::class, 'view'])->defaults('ddysView', 'activities')->name('activities');
            Route::get('/user/{username}', [PageController::class, 'view'])->defaults('ddysView', 'user')->where('username', '[^/]+')->name('user');
            Route::get('/types', [PageController::class, 'view'])->defaults('ddysView', 'types')->name('types');
            Route::get('/genres', [PageController::class, 'view'])->defaults('ddysView', 'genres')->name('genres');
            Route::get('/regions', [PageController::class, 'view'])->defaults('ddysView', 'regions')->name('regions');
            Route::post('/request-submit', [RequestController::class, 'submit'])->name('request-submit');

            if (config('ddys.diagnostics.enabled', false)) {
                Route::get('/diagnostics', [DiagnosticsController::class, 'index'])->name('diagnostics');
                Route::post('/diagnostics/test', [DiagnosticsController::class, 'test'])->name('diagnostics.test');
                Route::post('/diagnostics/cache', [DiagnosticsController::class, 'clear'])->name('diagnostics.cache');
            }
        });
}

if (config('ddys.proxy.enabled', true)) {
    Route::middleware(config('ddys.proxy.middleware', ['web']))
        ->prefix(config('ddys.proxy.prefix', 'ddys-api'))
        ->name(config('ddys.proxy.name', 'ddys.proxy.'))
        ->group(function () {
            Route::get('/{route}', [ProxyController::class, 'show'])
                ->where('route', 'movies|latest|hot|search|suggest|calendar|movie|sources|related|comments|collections|collection|shares|share|requests|activities|user|types|genres|regions')
                ->name('show');
        });
}
