<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// staff and branch manager report damage; only the superadmin approves (checked in the controller)
Route::prefix('/damage')->as('damage.')->middleware(['auth', 'role:1,2,3,5'])->group(function() {
    Route::get('/index', 'DamageController@index')->name('index');
    Route::post('/store', 'DamageController@store')->name('store');
    Route::post('/approve/{damage}', 'DamageController@approve')->name('approve');
    Route::post('/reject/{damage}', 'DamageController@reject')->name('reject');
});
