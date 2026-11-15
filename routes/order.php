<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/order')->as('order.')->middleware(['auth', 'role:1,2,3'])->group(function() {
    Route::get('/index', 'OrderController@index')->name('index');
    Route::get('/view/{order}', 'OrderController@view')->name('view');
    Route::post('/void/{order}', 'OrderController@void')->name('void');
    Route::post('/discount/{order}', 'OrderController@discount')->name('discount');
});
