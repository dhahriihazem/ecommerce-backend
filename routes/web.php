<?php

use App\Http\Controllers\Api\PaymentController;
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

Route::get('/payment/callback/{order}', [PaymentController::class, 'callback'])
     ->name('payment.callback');
Route::get('/payment/error/{order}', [PaymentController::class, 'handleError'])
     ->name('payment.error');