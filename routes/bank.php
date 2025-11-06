<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/bank')->as('bank.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BankController@index')->name('index');
    Route::get('/create', 'BankController@create')->name('create');
    Route::post('/store', 'BankController@store')->name('store');
    Route::get('/edit/{bank}', 'BankController@edit')->name('edit');
    Route::post('/update/{bank}', 'BankController@update')->name('update');
    Route::get('/destroy/{bank}', 'BankController@destroy')->name('destroy');
});
