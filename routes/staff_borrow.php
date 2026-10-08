<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/staff_borrow')->as('staff_borrow.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'StaffBorrowController@index')->name('index');
    Route::post('/store', 'StaffBorrowController@store')->name('store');
    Route::post('/approve/{staff_borrow}', 'StaffBorrowController@approve')->name('approve');
    Route::post('/reject/{staff_borrow}', 'StaffBorrowController@reject')->name('reject');
});
