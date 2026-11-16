<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/expiring-stock')->as('expiring_stock.')->middleware(['auth', 'role:1,2,3'])->group(function() {
    Route::get('/index', 'ExpiringStockController@index')->name('index');
});
