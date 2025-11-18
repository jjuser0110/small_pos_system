<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/payment_method')->as('payment_method.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'PaymentMethodController@index')->name('index');
    Route::get('/create', 'PaymentMethodController@create')->name('create');
    Route::post('/store', 'PaymentMethodController@store')->name('store');
    Route::get('/edit/{payment_method}', 'PaymentMethodController@edit')->name('edit');
    Route::post('/update/{payment_method}', 'PaymentMethodController@update')->name('update');
    Route::get('/destroy/{payment_method}', 'PaymentMethodController@destroy')->name('destroy');
});
