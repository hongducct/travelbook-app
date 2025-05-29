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
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\FavoriteController;

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
Route::get('tours/{tourId}/available-dates', [TourController::class, 'getAvailableDates']);
Route::get('tours/{tourId}/availabilities', [TourController::class, 'getAvailableDates']);

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
Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:10,1');
// api login for admin
Route::post('/admin/login', [AdminController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admins', [AdminController::class, 'index']);
    Route::post('/admins', [AdminController::class, 'store']);
    Route::get('/admin/profile', [AdminController::class, 'profile']);
    // Các route admin khác...
});
// // api login for user
Route::post('/user/login', [UserController::class, 'login']);
// // api register for user
Route::post('/user/register', [UserController::class, 'register']);
Route::get('/auth/google', [UserController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [UserController::class, 'handleGoogleCallback']);
// // api logout for user
// Protected user routes
Route::apiResource('users', UserController::class); // Bảo vệ tất cả các route users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/logout', [UserController::class, 'logout']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update'])->name('user.profile.update');

    // Booking routes
    Route::apiResource('bookings', BookingController::class); // Di chuyển bookings vào nhóm auth
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    Route::get('/vouchers/statistics', [VoucherController::class, 'statistics']);
    Route::post('/vouchers/{id}/toggle', [VoucherController::class, 'toggle']);
    Route::post('/vouchers/apply', [VoucherController::class, 'apply']);
    Route::apiResource('vouchers', VoucherController::class);

    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::patch('/payments/{id}/status', [PaymentController::class, 'updateStatus']);
    Route::post('/payments/vnpay', [PaymentController::class, 'createVNPayPayment']);

    // Favorite routes
    Route::get('/user/wishlist', [FavoriteController::class, 'index']);
    Route::post('/user/wishlist', [FavoriteController::class, 'store']);
    Route::delete('/user/wishlist/{tourId}', [FavoriteController::class, 'destroy']);
    Route::get('/user/wishlist/check/{tourId}', [FavoriteController::class, 'check']);
});

Route::get('/payments/vnpay/callback', [PaymentController::class, 'vnpayCallback']);

// Tour availability routes
Route::get('/tour-availability', [TourAvailabilityController::class, 'index']);

// Admin routes
Route::prefix('admin')->middleware('auth:admin-token')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
});
