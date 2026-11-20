<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// language and light / dark mode; open before login (already in the web group) so the login page can switch too
Route::post('/preferences', 'PreferenceController@update')->name('preferences.update');
