<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/receipt_setting')->as('receipt_setting.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'ReceiptSettingController@index')->name('index');
    Route::get('/create', 'ReceiptSettingController@create')->name('create');
    Route::post('/store', 'ReceiptSettingController@store')->name('store');
    Route::get('/edit/{receipt_setting}', 'ReceiptSettingController@edit')->name('edit');
    Route::post('/update/{receipt_setting}', 'ReceiptSettingController@update')->name('update');
    Route::get('/destroy/{receipt_setting}', 'ReceiptSettingController@destroy')->name('destroy');
});
