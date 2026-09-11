<?php

use App\Modules\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('search/items', [SearchController::class, 'search'])
    ->middleware(['web', 'auth'])
    ->name('search.items');
