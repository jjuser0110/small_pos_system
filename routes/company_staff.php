<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/company_staff')->as('company_staff.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'CompanyStaffController@index')->name('index');
    Route::get('/create', 'CompanyStaffController@create')->name('create');
    Route::post('/store', 'CompanyStaffController@store')->name('store');
    Route::get('/edit/{company_staff}', 'CompanyStaffController@edit')->name('edit');
    Route::post('/update/{company_staff}', 'CompanyStaffController@update')->name('update');
    Route::get('/destroy/{company_staff}', 'CompanyStaffController@destroy')->name('destroy');
});
