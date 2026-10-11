<?php

use Illuminate\Support\Facades\Route;
use Zofe\Rapyd\Modules\Remote\Http\LookupController;

// Feeds <x-remote::select>: the browser asks this app, this app asks the source with its token.
Route::get('remote/lookup/{source}/{resource}', LookupController::class)
    ->middleware(['web', 'auth'])
    ->name('remote.lookup');
