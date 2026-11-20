<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// superadmin only
Route::prefix('/activity-log')->as('activity_log.')->middleware(['auth', 'role:1'])->group(function() {
    Route::get('/index', 'ActivityLogController@index')->name('index');
});
