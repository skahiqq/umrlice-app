<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    return response()->json(['success' => true]);
});

Route::group([
    'prefix' => 'api/{api_token}',
    'middleware' => 'api_token'
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

    Route::group([
        'prefix' => 'payment'
    ], function () {
        Route::get('set-post', [PaymentController::class, 'setPostId']);
        Route::get('capture', [PaymentController::class, 'capture']);
        Route::get('void', [PaymentController::class, 'void']);
        Route::get('last', [PaymentController::class, 'getLastPayment']);
    });
});

Route::post('api/callback', [PaymentController::class, 'callBackPreAuthorize']);
Route::post('callback', [PaymentController::class, 'callBackPreAuthorize']);

