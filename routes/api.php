<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', fn(Request $request) => $request->user());
 
// Product CRUD Routes (Create, Read, Update, Delete)
Route::apiResource('products', ProductController::class);
// Add this route for placing bids
Route::post('/products/{product}/bids', [BidController::class, 'store'])
    ->name('products.bids.store');

// Order creation route
Route::post('/orders', [OrderController::class, 'store']);
});