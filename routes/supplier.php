<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/supplier')->as('supplier.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'SupplierController@index')->name('index');
    Route::get('/create', 'SupplierController@create')->name('create');
    Route::post('/store', 'SupplierController@store')->name('store');
    Route::get('/edit/{supplier}', 'SupplierController@edit')->name('edit');
    Route::post('/update/{supplier}', 'SupplierController@update')->name('update');
    Route::get('/destroy/{supplier}', 'SupplierController@destroy')->name('destroy');
});
