<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/order')->as('order.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'OrderController@index')->name('index');
    Route::get('/create', 'OrderController@create')->name('create');
    Route::post('/store', 'OrderController@store')->name('store');
    Route::get('/edit/{order}', 'OrderController@edit')->name('edit');
    Route::get('/view/{order}', 'OrderController@view')->name('view');
    Route::post('/update/{order}', 'OrderController@update')->name('update');
    Route::get('/destroy/{order}', 'OrderController@destroy')->name('destroy');
});
