<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/batch')->as('batch.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BatchController@index')->name('index');
    Route::get('/create', 'BatchController@create')->name('create');
    Route::post('/store', 'BatchController@store')->name('store');
    Route::get('/edit/{batch}', 'BatchController@edit')->name('edit');
    Route::post('/update/{batch}', 'BatchController@update')->name('update');
    Route::get('/complete/{batch}', 'BatchController@complete')->name('complete');
    Route::get('/destroy/{batch}', 'BatchController@destroy')->name('destroy');
    Route::post('/addBatchItem/{batch}', 'BatchController@addBatchItem')->name('addBatchItem');
    Route::get('/destroyItem/{batch_item}', 'BatchController@destroyItem')->name('destroyItem');
});
