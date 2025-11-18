<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/change_password', [App\Http\Controllers\HomeController::class, 'change_password'])->name('change_password');
Route::get('/counter', [App\Http\Controllers\HomeController::class, 'counter'])->name('counter');
Route::get('/checkout', [App\Http\Controllers\HomeController::class, 'checkout'])->name('checkout');
Route::get('/receipt', [App\Http\Controllers\HomeController::class, 'receipt'])->name('receipt');
Route::get('/cart/load', [App\Http\Controllers\CartController::class, 'load']);
Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
Route::post('/cart/update', [App\Http\Controllers\CartController::class, 'update']);
Route::post('/cart/remove', [App\Http\Controllers\CartController::class, 'remove']);
Route::post('/cart/placeorder', [App\Http\Controllers\CartController::class, 'place']);


