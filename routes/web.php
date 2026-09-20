<?php

use Illuminate\Support\Facades\Route;

/*
 * Vue SPA — every route serves the SPA shell.
 * Vue Router handles all client-side routing including login.
 *
 * `api/*` is excluded on purpose. This catch-all used to swallow it too, so a
 * request to an endpoint that does not exist came back as the SPA's HTML with a
 * 200 — which a caller cannot tell from a real answer, and which made six screens
 * calling endpoints that were never built look like they were working. Outside
 * that prefix the catch-all still answers everything, so deep links keep working.
 */
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api/).*')->name('spa');
