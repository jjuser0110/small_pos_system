<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/staff_attendance')->as('staff_attendance.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'StaffAttendanceController@index')->name('index');
});
