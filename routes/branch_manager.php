<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/branch_manager')->as('branch_manager.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BranchManagerController@index')->name('index');
    Route::get('/create', 'BranchManagerController@create')->name('create');
    Route::post('/store', 'BranchManagerController@store')->name('store');
    Route::get('/edit/{branch_manager}', 'BranchManagerController@edit')->name('edit');
    Route::post('/update/{branch_manager}', 'BranchManagerController@update')->name('update');
    Route::get('/destroy/{branch_manager}', 'BranchManagerController@destroy')->name('destroy');
});
