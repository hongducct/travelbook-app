<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\TourController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsCategoryController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TourAvailabilityController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\VoucherUsageController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\TravelTypeController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\FavoriteController;

use App\Http\Controllers\ChatBotController;

use App\Http\Controllers\FlightController;
use App\Http\Controllers\HotelController;

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
Route::apiResource('locations', LocationController::class);
Route::get('/locations/{id}/tours', [LocationController::class, 'tours']);
Route::post('/locations/tour-counts', [LocationController::class, 'tourCounts']);

Route::apiResource('travel-types', TravelTypeController::class);
Route::apiResource('features', FeatureController::class);
Route::apiResource('vendors', VendorController::class);
Route::apiResource('prices', PriceController::class);
Route::get('/news/tags', [NewsController::class, 'getTags']);
Route::get('/news/destinations', [NewsController::class, 'getDestinations']);
Route::apiResource('news', NewsController::class);
Route::apiResource('news-categories', NewsCategoryController::class);
Route::apiResource('reviews', ReviewsController::class);
Route::post('/subscribe', [SubscriberController::class, 'subscribe'])->middleware('throttle:10,1');
Route::get('/subscribers', [SubscriberController::class, 'index'])->middleware('auth:admin-token');
Route::delete('/subscribers/{id}', [SubscriberController::class, 'destroy'])->middleware('auth:admin-token');

Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/register', [UserController::class, 'register']);
Route::post('/auth/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [UserController::class, 'resetPassword']);
Route::post('/auth/verify-reset-otp', [UserController::class, 'verifyResetOtp']);
Route::get('/auth/google', [UserController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [UserController::class, 'handleGoogleCallback']);
// // api logout for user
// Protected user routes
Route::apiResource('users', UserController::class); // Bảo vệ tất cả các route users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/logout', [UserController::class, 'logout']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update'])->name('user.profile.update');
    Route::post('user/send-otp', [UserController::class, 'sendOTP']);
    Route::put('user/{user}/change-status', [UserController::class, 'changeStatus']);

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

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/forgot-password', [AdminController::class, 'forgotPassword']);
    Route::post('/reset-password', [AdminController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AdminController::class, 'profile']);
        Route::put('/profile', [AdminController::class, 'update']);
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::post('/request-email-change-otp', [AdminController::class, 'requestEmailChangeOtp']);
        Route::post('/change-email', [AdminController::class, 'changeEmail']);
    });
});
// api login for admin
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admins', [AdminController::class, 'index']);
    Route::post('/admins', [AdminController::class, 'store']);
    // Các route admin khác...
});

// Chatbot route
// Route::post('/chatbot', [ChatBotController::class, 'chat']);
Route::post('/chatbot/query', [ChatBotController::class, 'processQuery']);
Route::get('/chatbot/tour/{id}', [ChatBotController::class, 'getTourDetails']);

// Route::post('/chatbot', function (Request $request) {
//     $response = Http::withToken(env('OPENAI_API_KEY'))
//         ->post('https://api.openai.com/v1/chat/completions', [
//             'model' => 'gpt-3.5-turbo',
//             'messages' => $request->input('messages')
//         ]);

//     return $response->json();
// });

// Route::post('/chatbot', function (Request $request) {
//     $messages = $request->input('messages');

//     $response = Http::withHeaders([
//         'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
//         'Content-Type' => 'application/json',
//     ])->post('https://openrouter.ai/api/v1/chat/completions', [
//         'model' => 'mistralai/mistral-7b-instruct',
//         'messages' => $messages,
//     ]);

//     if ($response->successful()) {
//         return response()->json($response->json());
//     } else {
//         return response()->json([
//             'error' => 'Chat API error',
//             'details' => $response->body()
//         ], $response->status());
//     }
// });


// routes/api.php
Route::get('/booking/flight/{reference}', [FlightController::class, 'getBooking']);
Route::get('/booking/hotel/{reference}', [HotelController::class, 'getBooking']);


// Flight routes
Route::prefix('flights')->group(function () {
    Route::post('/search', [FlightController::class, 'search']);
    Route::get('/airports', [FlightController::class, 'getAirports']);
    Route::post('/book', [FlightController::class, 'book']);
    Route::get('/booking/{reference}', [FlightController::class, 'getBooking']);
    Route::get('/vnpay/callback', [FlightController::class, 'vnpayCallback']);
});

// Hotel routes
Route::prefix('hotels')->group(function () {
    Route::post('/search', [HotelController::class, 'search']);
    Route::get('/cities', [HotelController::class, 'getCities']);
    Route::post('/book', [HotelController::class, 'book']);
    Route::get('/booking/{reference}', [HotelController::class, 'getBooking']);
    Route::get('/vnpay/callback', [HotelController::class, 'vnpayCallback']);
});