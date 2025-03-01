<?php

use App\Modules\Layout\Http\Controllers\LayoutController;
use Illuminate\Support\Facades\Route;

Route::get('test-home', [LayoutController::class,'testHome'])
    ->middleware(['web'])
    ->name('layout.test.home');

Route::get('test-frontend', [LayoutController::class,'testFrontend'])
    ->middleware(['web'])
    ->name('layout.test.frontend');

Route::get('test-admin', [LayoutController::class,'testAdmin'])
    ->middleware(['web'])
    ->name('layout.test.admin');
