<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/receipt_setting')->as('receipt_setting.')->middleware(['auth', 'role:1,2'])->group(function() {
    Route::get('/index', 'ReceiptSettingController@index')->name('index');
    Route::post('/store', 'ReceiptSettingController@store')->name('store');
});
