<?php

use App\Modules\Log\Livewire\LogAppTable;
use Illuminate\Support\Facades\Route;

Route::get('log/app', LogAppTable::class)
    ->middleware(['web', 'auth'])
    ->name('log.app')
    ->crumbs(fn ($crumbs) => $crumbs->push('App Logs', route('log.app')));
