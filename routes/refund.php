<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// staff request refunds from the counter; branch manager / superadmin approve (checked in the controller)
Route::prefix('/refund')->as('refund.')->middleware(['auth', 'role:1,2,3,5'])->group(function() {
    Route::get('/index', 'RefundController@index')->name('index');
    Route::get('/create', 'RefundController@create')->name('create');
    Route::post('/store', 'RefundController@store')->name('store');
    Route::post('/approve/{refund}', 'RefundController@approve')->name('approve');
    Route::post('/reject/{refund}', 'RefundController@reject')->name('reject');
});
