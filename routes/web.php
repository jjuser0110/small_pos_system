<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PosController;

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
Route::post('/checkout/validate', [App\Http\Controllers\CartController::class, 'validateCart'])->name('validateCart');
Route::get('/checkout', [App\Http\Controllers\HomeController::class, 'checkout'])->name('checkout');
Route::get('/shift_closing', [App\Http\Controllers\HomeController::class, 'shift_closing'])->name('shift_closing');
Route::get('/receipt/{order_id}', [App\Http\Controllers\HomeController::class, 'receipt'])->name('receipt');
Route::get('/cart/load', [App\Http\Controllers\CartController::class, 'load']);
Route::get('empty_cart', [App\Http\Controllers\CartController::class, 'empty_cart'])->name('empty_cart');
Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
Route::post('/cart/add-special', [App\Http\Controllers\CartController::class, 'addSpecial']);
Route::post('/cart/update', [App\Http\Controllers\CartController::class, 'update']);
Route::post('/cart/remove', [App\Http\Controllers\CartController::class, 'remove']);
Route::post('/cart/convert-box', [App\Http\Controllers\CartController::class, 'convertBox']);
Route::post('/cart/convert-bottle-to-box', [App\Http\Controllers\CartController::class, 'convertBottleToBox']);
Route::post('/cart/placeorder', [App\Http\Controllers\CartController::class, 'place']);


Route::middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'counter']);

    // Tables
    Route::get('/pos/tables',          [PosController::class, 'getTables']);
    Route::put('/pos/tables/{id}/pay', [PosController::class, 'payTable'])->whereNumber('id');

    // Dabao
    Route::get('/pos/dabao',           [PosController::class, 'getDabao']);
    Route::post('/pos/dabao',          [PosController::class, 'createDabao']);
    Route::put('/pos/dabao/{id}',      [PosController::class, 'updateDabao'])->whereNumber('id');
    Route::put('/pos/dabao/{id}/pay',  [PosController::class, 'payDabao'])->whereNumber('id');

    // Menu
    Route::get('/pos/menu',            [PosController::class, 'getMenu']);

    // Cart: static routes FIRST
    Route::put('/pos/cart/mark-printed', [PosController::class, 'markPrinted']);

    // Cart: wildcard routes AFTER
    Route::get('/pos/cart/{tableId}',    [PosController::class, 'getCart'])->whereNumber('tableId');
    Route::post('/pos/cart',             [PosController::class, 'addToCart']);
    Route::put('/pos/cart/{cartId}',     [PosController::class, 'updateCart'])->whereNumber('cartId');
    Route::delete('/pos/cart/{cartId}',  [PosController::class, 'removeFromCart'])->whereNumber('cartId');

    // Settings / misc
    Route::get('/pos/receipt-settings',  [PosController::class, 'getReceiptSettings']);
    Route::get('/pos/payment-methods',   [PosController::class, 'getPaymentMethods']);
});
