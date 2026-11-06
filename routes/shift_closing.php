<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// every role can see shifts in its own scope (staff: their own); the controller scopes the data
Route::prefix('/shift_closing')->as('shift_closing.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'ShiftClosingController@index')->name('index');
    Route::get('/print', 'ShiftClosingController@print')->name('print');
    Route::get('/view/{shift_closing}', 'ShiftClosingController@view')->name('view');
});
