<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/company')->as('company.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'CompanyController@index')->name('index');
    Route::get('/create', 'CompanyController@create')->name('create');
    Route::post('/store', 'CompanyController@store')->name('store');
    Route::get('/edit/{company}', 'CompanyController@edit')->name('edit');
    Route::post('/update/{company}', 'CompanyController@update')->name('update');
    Route::get('/destroy/{company}', 'CompanyController@destroy')->name('destroy');
});
