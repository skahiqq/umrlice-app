<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json(['test' => $_SERVER['REMOTE_HOST']]);
    dd(\App\Models\Cart::first());
    return response()->json(['success' => true]);
});

Route::group([
    'prefix' => 'api'
], function () {
    Route::get('init-payment', [PaymentController::class, 'initPayment']);
    // cart
    Route::group([
        'prefix' => 'cart'
    ], function () {
        Route::get('create', [CartController::class, 'insert']);
        Route::get('get', [CartController::class, 'get']);
        Route::get('update', [CartController::class, 'update']);
        Route::get('delete', [CartController::class, 'destroy']);
        Route::get('empty-fields', [CartController::class, 'setNull']);
    });
});

