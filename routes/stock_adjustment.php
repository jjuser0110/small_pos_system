<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// managers can see the history; only the superadmin can adjust (checked in the controller)
Route::prefix('/stock_adjustment')->as('stock_adjustment.')->middleware(['auth', 'role:1,2,3'])->group(function() {
    Route::get('/index', 'StockAdjustmentController@index')->name('index');
    Route::post('/store', 'StockAdjustmentController@store')->name('store');
});
