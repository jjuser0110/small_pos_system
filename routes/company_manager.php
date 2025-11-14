<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/company_manager')->as('company_manager.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'CompanyManagerController@index')->name('index');
    Route::get('/create', 'CompanyManagerController@create')->name('create');
    Route::post('/store', 'CompanyManagerController@store')->name('store');
    Route::get('/edit/{company_manager}', 'CompanyManagerController@edit')->name('edit');
    Route::post('/update/{company_manager}', 'CompanyManagerController@update')->name('update');
    Route::get('/destroy/{company_manager}', 'CompanyManagerController@destroy')->name('destroy');
});
