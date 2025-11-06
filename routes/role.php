<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/role')->as('role.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'RoleController@index')->name('index');
    Route::get('/create', 'RoleController@create')->name('create');
    Route::post('/store', 'RoleController@store')->name('store');
    Route::get('/edit/{role}', 'RoleController@edit')->name('edit');
    Route::post('/update/{role}', 'RoleController@update')->name('update');
    Route::get('/destroy/{role}', 'RoleController@destroy')->name('destroy');
});
