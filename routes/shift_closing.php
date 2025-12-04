<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/shift_closing')->as('shift_closing.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'ShiftClosingController@index')->name('index');
    Route::get('/create', 'ShiftClosingController@create')->name('create');
    Route::post('/store', 'ShiftClosingController@store')->name('store');
    Route::get('/edit/{shift_closing}', 'ShiftClosingController@edit')->name('edit');
    Route::get('/view/{shift_closing}', 'ShiftClosingController@view')->name('view');
    Route::post('/update/{shift_closing}', 'ShiftClosingController@update')->name('update');
    Route::get('/destroy/{shift_closing}', 'ShiftClosingController@destroy')->name('destroy');
});
