<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TourAvailabilityController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\VoucherUsageController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\TravelTypeController;
use App\Http\Controllers\FeatureController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::apiResource('tours', TourController::class);
Route::get('/tours/{tourId}/prices', [TourController::class, 'getPrices']);
// Route::apiResource('users', UserController::class);
Route::apiResource('packages', PackageController::class);
Route::apiResource('locations', LocationController::class)->only(['index', 'store', 'tours']);;
// Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/{id}/tours', [LocationController::class, 'tours']);
Route::apiResource('travel-types', TravelTypeController::class);
Route::apiResource('features', FeatureController::class);
Route::apiResource('vendors', VendorController::class);
Route::apiResource('prices', PriceController::class);
Route::apiResource('news', NewsController::class);
Route::apiResource('reviews', ReviewsController::class);
Route::apiResource('admins', AdminController::class);
// api login for admin
Route::post('/admin/login', [AdminController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admins', [AdminController::class, 'index']);
    Route::post('/admins', [AdminController::class, 'store']);
    // Các route admin khác...
});
// // api login for user
Route::post('/user/login', [UserController::class, 'login']);
// // api register for user
Route::post('/user/register', [UserController::class, 'register']);
// // api logout for user
// Protected user routes
Route::apiResource('users', UserController::class); // Bảo vệ tất cả các route users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/logout', [UserController::class, 'logout']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    
    // Booking routes
    Route::apiResource('bookings', BookingController::class); // Di chuyển bookings vào nhóm auth
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    Route::post('/vouchers/apply', [VoucherController::class, 'apply']);

    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::patch('/payments/{id}/status', [PaymentController::class, 'updateStatus']);
    Route::post('/payments/vnpay', [PaymentController::class, 'createVNPayPayment']);
});

Route::get('/payments/vnpay/callback', [PaymentController::class, 'vnpayCallback']);

// Tour availability routes
Route::get('/tour-availability', [TourAvailabilityController::class, 'index']);

// Admin routes
Route::prefix('admin')->middleware('auth:admin-token')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
});