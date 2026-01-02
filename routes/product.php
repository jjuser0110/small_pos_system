<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/product')->as('product.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'ProductController@index')->name('index');
    Route::get('/create', 'ProductController@create')->name('create');
    Route::post('/store', 'ProductController@store')->name('store');
    Route::get('/edit/{product}', 'ProductController@edit')->name('edit');
    Route::get('/viewlog/{product}', 'ProductController@viewlog')->name('viewlog');
    Route::post('/update/{product}', 'ProductController@update')->name('update');
    Route::get('/destroy/{product}', 'ProductController@destroy')->name('destroy');
    Route::get('/createBundle/{product}', 'ProductController@createBundle')->name('createBundle');
    Route::post('/storeBundle', 'ProductController@storeBundle')->name('storeBundle');
    Route::get('/editBundle/{product_bundle}', 'ProductController@editBundle')->name('editBundle');
    Route::post('/updateBundle/{product_bundle}', 'ProductController@updateBundle')->name('updateBundle');
    Route::get('/convert/{product}', 'ProductController@convert')->name('convert');
    Route::get('/download-template', 'ProductController@downloadTemplate')->name('downloadTemplate');
    Route::post('/import', 'ProductController@import')->name('import');
});
