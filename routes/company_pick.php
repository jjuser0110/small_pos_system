<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// top bar picker: All companies, or one company
Route::post('/company-pick', 'CompanyPickController@update')->middleware(['auth', 'role:1,2,3'])->name('company_pick');
