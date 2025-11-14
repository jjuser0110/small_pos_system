<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/category')->as('category.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'CategoryController@index')->name('index');
    Route::get('/create', 'CategoryController@create')->name('create');
    Route::post('/store', 'CategoryController@store')->name('store');
    Route::get('/edit/{category}', 'CategoryController@edit')->name('edit');
    Route::post('/update/{category}', 'CategoryController@update')->name('update');
    Route::get('/destroy/{category}', 'CategoryController@destroy')->name('destroy');
});
