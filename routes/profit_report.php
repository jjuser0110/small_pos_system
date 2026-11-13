<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// what every branch / company / shift earned - for the owners
Route::prefix('/profit_report')->as('profit_report.')->middleware(['auth', 'role:1,2'])->group(function() {
    Route::get('/index', 'ProfitReportController@index')->name('index');
});
