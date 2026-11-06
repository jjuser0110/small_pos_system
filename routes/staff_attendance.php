<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/staff_attendance')->as('staff_attendance.')->middleware(['auth', 'role:1,2,3'])->group(function() {
    Route::get('/index', 'StaffAttendanceController@index')->name('index');
});
