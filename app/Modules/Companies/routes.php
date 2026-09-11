<?php

use App\Modules\Companies\Livewire\CompaniesEdit;
use App\Modules\Companies\Livewire\CompaniesTable;
use App\Modules\Companies\Livewire\CompaniesView;
use Illuminate\Support\Facades\Route;

Route::get('companies', CompaniesTable::class)
    ->middleware(['web', 'auth'])
    ->name('companies.table')
    ->crumbs(fn ($crumbs) => $crumbs->push('Companies', route('companies.table')));

Route::get('companies/view/{company}', CompaniesView::class)
    ->middleware(['web', 'auth'])
    ->name('companies.view')
    ->crumbs(function ($crumbs, $company) {
        $crumbs->parent('companies.table')->push($company->business_name, route('companies.view', $company));
    });

Route::get('companies/edit/{company?}', CompaniesEdit::class)
    ->middleware(['web', 'auth'])
    ->name('companies.edit')
    ->crumbs(function ($crumbs, $company = null) {
        if ($company?->exists) {
            $crumbs->parent('companies.view', $company)->push('Edit', route('companies.edit', $company));
        } else {
            $crumbs->parent('companies.table')->push('New Company', route('companies.edit'));
        }
    });
