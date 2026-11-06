<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// staff (role 5) can see and accept transfers into their company; the controller checks each action
Route::prefix('/stock_transfer')->as('stock_transfer.')->middleware(['auth', 'role:1,2,3,5'])->group(function() {
    Route::get('/index', 'StockTransferController@index')->name('index');
    Route::post('/store', 'StockTransferController@store')->name('store');
    Route::post('/approve/{stock_transfer}', 'StockTransferController@approve')->name('approve');
    Route::post('/reject/{stock_transfer}', 'StockTransferController@reject')->name('reject');
});
