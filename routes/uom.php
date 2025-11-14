<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/uom')->as('uom.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'UomController@index')->name('index');
    Route::get('/create', 'UomController@create')->name('create');
    Route::post('/store', 'UomController@store')->name('store');
    Route::get('/edit/{uom}', 'UomController@edit')->name('edit');
    Route::post('/update/{uom}', 'UomController@update')->name('update');
    Route::get('/destroy/{uom}', 'UomController@destroy')->name('destroy');
});
