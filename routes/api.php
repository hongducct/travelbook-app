<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PriceController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::apiResource('tours', TourController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('packages', PackageController::class);
Route::apiResource('locations', LocationController::class);
Route::apiResource('bookings', BookingController::class);
Route::apiResource('prices', PriceController::class);