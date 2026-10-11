<?php

use Illuminate\Support\Facades\Route;
use Zofe\Rapyd\Modules\Remote\Http\ExposeController;

Route::get('{resource}', ExposeController::class)->name('remote.expose');
