<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/stock_adjustment')->as('stock_adjustment.')->middleware(['auth', 'role:1,2,3,4'])->group(function() {
    Route::get('/index', 'StockAdjustmentController@index')->name('index');
    Route::post('/store', 'StockAdjustmentController@store')->name('store');
    Route::post('/approve/{stock_adjustment}', 'StockAdjustmentController@approve')->name('approve');
    Route::post('/reject/{stock_adjustment}', 'StockAdjustmentController@reject')->name('reject');
});
