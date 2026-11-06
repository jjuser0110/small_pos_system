<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/report')->as('report.')->middleware(['auth', 'role:1,2,3'])->group(function() {
    Route::get('/index', 'ReportController@index')->name('index');
    Route::get('/print', 'ReportController@print')->name('print');
});
