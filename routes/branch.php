<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/branch')->as('branch.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BranchController@index')->name('index');
    Route::get('/create', 'BranchController@create')->name('create');
    Route::post('/store', 'BranchController@store')->name('store');
    Route::get('/edit/{branch}', 'BranchController@edit')->name('edit');
    Route::post('/update/{branch}', 'BranchController@update')->name('update');
    Route::get('/destroy/{branch}', 'BranchController@destroy')->name('destroy');
});
