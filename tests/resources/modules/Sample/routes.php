<?php

use App\Modules\Sample\Livewire\HelloTable;
use Illuminate\Support\Facades\Route;

Route::get('sample/hello', HelloTable::class)->middleware(['web'])->name('sample.hello');
