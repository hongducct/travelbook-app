<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourAvailability;
use App\Models\Price;
use App\Models\Payment;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Http\Requests\StoreBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->getTable() === 'admins') {
            $bookings = Booking::with(['user', 'bookable', 'bookable.primaryImage',  'payment', 'voucher'])->get();
        } elseif ($user->getTable() === 'users') {
            $bookings = Booking::with(['user', 'bookable', 'bookable.primaryImage', 'payment', 'voucher'])
                ->where('user_id', $user->id)
                ->get();
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($bookings, 200);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::with(['user', 'bookable', 'bookable.primaryImage', 'payment', 'voucher', 'voucherUsage'])->findOrFail($id);

        if ($user->getTable() === 'admins') {
            return response()->json($booking, 200);
        } elseif ($user->getTable() === 'users' && $booking->user_id == $user->id) {
            return response()->json($booking, 200);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function store(StoreBookingRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Log request data
            Log::info('Booking request received', [
                'user_id' => auth()->id(),
                'tour_id' => $request->tour_id,
                'start_date' => $request->start_date,
                'number_of_guests_adults' => $request->number_of_guests_adults,
                'number_of_children' => $request->number_of_children,
                'voucher_code' => $request->voucher_code,
                'special_requests' => $request->special_requests,
                'contact_phone' => $request->contact_phone,
                'payment_method' => $request->payment_method,
            ]);
            try {
                $tour = Tour::findOrFail($request->tour_id);
                $startDate = $request->start_date;
                $adults = $request->number_of_guests_adults;
                $children = $request->number_of_children ?? 0;
                $voucherCode = $request->voucher_code;
                $specialRequests = $request->special_requests;
                $contactPhone = $request->contact_phone;
                $paymentMethod = $request->payment_method;

                // Kiểm tra tính khả dụng
                $availability = TourAvailability::where('tour_id', $tour->id)
                    ->where('date', $startDate)
                    ->where('is_active', true)
                    ->first();

                if (!$availability) {
                    Log::warning('Booking failed: Date not available', [
                        'tour_id' => $tour->id,
                        'start_date' => $startDate,
                    ]);
                    return response()->json(['message' => 'Ngày này không khả dụng.'], 422);
                }

                if ($availability->available_slots < ($adults + $children)) {
                    Log::warning('Booking failed: Insufficient availability', [
                        'tour_id' => $tour->id,
                        'start_date' => $startDate,
                        'available_slots' => $availability->available_slots,
                        'requested_slots' => $adults + $children,
                    ]);
                    return response()->json(['message' => 'Tour không còn đủ chỗ cho ngày đã chọn.'], 422);
                }

                // Lấy giá tour
                $price = Price::where('tour_id', $tour->id)
                    // ->where('date', '<=', $startDate)
                    ->orderBy('date', 'desc')
                    ->first()?->price ?? $tour->price;

                if (!$price) {
                    Log::warning('Booking failed: No price found', [
                        'tour_id' => $tour->id,
                        'start_date' => $startDate,
                    ]);
                    return response()->json(['message' => 'Không có giá cho ngày đã chọn.'], 422);
                }

                // Tính tổng giá
                $totalPrice = $price * ($adults + $children * 0.5);

                // Kiểm tra và áp dụng voucher
                $discount = 0;
                $voucher = null;
                if ($voucherCode) {
                    $voucher = Voucher::where('code', $voucherCode)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now())
                        ->lockForUpdate()
                        ->first();

                    if (!$voucher) {
                        Log::warning('Booking failed: Invalid voucher', [
                            'voucher_code' => $voucherCode,
                        ]);
                        return response()->json(['message' => 'Mã voucher không hợp lệ hoặc đã hết hạn.'], 422);
                    }

                    // $applicableTourIds = json_decode($voucher->applicable_tour_ids, true) ?? [];
                    $applicableTourIds = is_array($voucher->applicable_tour_ids)
                        ? $voucher->applicable_tour_ids
                        : (json_decode($voucher->applicable_tour_ids, true) ?? []);
                    if (!empty($applicableTourIds) && !in_array($tour->id, $applicableTourIds)) {
                        Log::warning('Booking failed: Voucher not applicable', [
                            'voucher_code' => $voucherCode,
                            'tour_id' => $tour->id,
                        ]);
                        return response()->json(['message' => 'Voucher không áp dụng cho tour này.'], 422);
                    }

                    $usageCount = VoucherUsage::where('voucher_id', $voucher->id)->count();
                    if ($voucher->usage_limit && $usageCount >= $voucher->usage_limit) {
                        Log::warning('Booking failed: Voucher usage limit reached', [
                            'voucher_id' => $voucher->id,
                            'usage_count' => $usageCount,
                        ]);
                        return response()->json(['message' => 'Voucher đã đạt giới hạn sử dụng.'], 422);
                    }

                    if ($voucher->discount) {
                        $discount = $voucher->discount;
                    } elseif ($voucher->discount_percentage) {
                        $discount = $totalPrice * ($voucher->discount_percentage / 100);
                    }

                    $totalPrice = max(0, $totalPrice - $discount);
                }

                // Tạo Booking trước
                $booking = Booking::create([
                    'user_id' => auth()->id(),
                    'bookable_id' => $tour->id,
                    'bookable_type' => Tour::class,
                    'start_date' => $startDate,
                    'end_date' => (new \DateTime($startDate))->modify("+{$tour->days} days")->format('Y-m-d'),
                    'number_of_guests_adults' => $adults,
                    'number_of_children' => $children,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'voucher_id' => $voucher?->id,
                    'special_requests' => $specialRequests,
                    'contact_phone' => $contactPhone,
                    'payment_id' => null, // Để trống payment_id ban đầu
                ]);

                // Lưu voucher usage
                if ($voucher) {
                    VoucherUsage::create([
                        'voucher_id' => $voucher->id,
                        'booking_id' => $booking->id,
                        'user_id' => auth()->id(),
                        'discount_applied' => $discount,
                    ]);
                }

                // Cập nhật tour availability
                $availability->available_slots -= ($adults + $children);
                $availability->save();

                // Tạo Payment nếu không phải VNPay
                $payment = null;
                if (!in_array($paymentMethod, ['vnpay'])) {
                    $payment = Payment::create([
                        'user_id' => auth()->id(),
                        'amount' => $totalPrice,
                        'method' => $paymentMethod,
                        'status' => 'pending',
                        'transaction_id' => 'TXN_' . uniqid(),
                    ]);

                    $booking->payment_id = $payment->id;
                    $booking->save();
                }

                // // Log thành công
                // Log::info('Booking created', [
                //     'booking_id' => $booking->id,
                //     'payment_id' => $payment?->id,
                //     'voucher' => $voucher ? $voucher->toArray() : null,
                //     'discount' => $discount,
                //     'total_price' => $totalPrice,
                //     'payment_method' => $paymentMethod,
                // ]);

                return response()->json([
                    'message' => 'Đặt tour thành công!',
                    'booking' => $booking->load(['payment', 'voucher']),
                    'payment' => $payment,
                ], 201);
            } catch (\Exception $e) {
                Log::error('Booking creation failed', [
                    'error' => $e->getMessage(),
                    'request' => $request->all(),
                ]);
                return response()->json(['message' => 'Lỗi khi tạo booking: ' . $e->getMessage()], 500);
            }
        });
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::findOrFail($id);

        if ($user->getTable() !== 'admins' && $booking->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'number_of_guests_adults' => 'sometimes|integer|min:1',
            'number_of_children' => 'sometimes|integer|min:0',
            'total_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'special_requests' => 'sometimes|nullable|string|max:1000',
            'contact_phone' => 'sometimes|string|max:20',
        ]);

        $booking->update($validated);

        return response()->json(['message' => 'Booking updated', 'data' => $booking->load(['payment', 'voucher'])], 200);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::findOrFail($id);

        if ($user->getTable() !== 'admins' && $booking->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted'], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::findOrFail($id);

        if ($user->getTable() === 'admins') {
        } elseif ($user->getTable() === 'users') {
            if ($booking->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $booking->status = $validated['status'];
        $booking->save();

        return response()->json(['message' => 'Booking status updated', 'data' => $booking->load(['payment', 'voucher'])], 200);
    }
}
