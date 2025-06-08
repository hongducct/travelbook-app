<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AmadeusService;
use App\Models\HotelBooking;
use App\Services\PaymentService;
use App\Mail\HotelBookingConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HotelController extends Controller
{
    protected $amadeusService;
    protected $paymentService;

    public function __construct(AmadeusService $amadeusService, PaymentService $paymentService)
    {
        $this->amadeusService = $amadeusService;
        $this->paymentService = $paymentService;
    }

    /**
     * Search for hotels
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'cityCode' => 'required|string|size:3',
            'checkInDate' => 'required|date|after_or_equal:today',
            'checkOutDate' => 'required|date|after:checkInDate',
            'adults' => 'required|integer|min:1|max:8',
            'rooms' => 'required|integer|min:1|max:5',
            'currency' => 'string|size:3',
            'lang' => 'string|size:2'
        ]);

        try {
            $searchParams = [
                'cityCode' => $request->cityCode,
                'checkInDate' => $request->checkInDate,
                'checkOutDate' => $request->checkOutDate,
                'adults' => $request->adults,
                'rooms' => $request->rooms,
                'currency' => $request->get('currency', 'VND'),
                'lang' => $request->get('lang', 'vi')
            ];

            $hotels = $this->amadeusService->searchHotels($searchParams);

            return response()->json([
                'success' => true,
                'data' => $hotels,
                'message' => 'Hotels retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Hotel search error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to search hotels. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Book a hotel
     */
    public function book(Request $request): JsonResponse
    {
        $request->validate([
            'hotel' => 'required|array',
            'guest' => 'required|array',
            'guest.firstName' => 'required|string|max:255',
            'guest.lastName' => 'required|string|max:255',
            'guest.email' => 'required|email|max:255',
            'guest.phone' => 'required|string|max:20',
            'contact' => 'required|array',
            'contact.email' => 'required|email|max:255',
            'contact.phone' => 'required|string|max:20',
            'payment' => 'required|array',
            'payment.method' => 'required|in:credit_card,vnpay,momo,bank_transfer',
            'searchParams' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            // Generate booking reference
            $bookingReference = 'HT' . strtoupper(Str::random(8));

            // Calculate total nights
            $checkIn = new \DateTime($request->searchParams['checkInDate']);
            $checkOut = new \DateTime($request->searchParams['checkOutDate']);
            $nights = $checkIn->diff($checkOut)->days;

            // Create booking record
            $booking = HotelBooking::create([
                'booking_reference' => $bookingReference,
                'hotel_data' => json_encode($request->hotel),
                'guest_data' => json_encode($request->guest),
                'contact_data' => json_encode($request->contact),
                'search_params' => json_encode($request->searchParams),
                'preferences' => json_encode($request->preferences ?? []),
                'payment_method' => $request->payment['method'],
                'total_amount' => ($request->hotel['price']['total'] ?? 1500000) * $nights,
                'currency' => 'VND',
                'nights' => $nights,
                'status' => 'pending'
            ]);

            // Process payment based on method
            $paymentResult = $this->processPayment($booking, $request->payment);

            if ($paymentResult['success']) {
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_transaction_id' => $paymentResult['transaction_id'] ?? null
                ]);

                // Send confirmation email
                try {
                    Mail::to($request->contact['email'])
                        ->send(new HotelBookingConfirmation($booking));
                } catch (\Exception $e) {
                    Log::error('Failed to send booking confirmation email: ' . $e->getMessage());
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Hotel booked successfully',
                    'bookingId' => $booking->id,
                    'bookingReference' => $bookingReference,
                    'paymentUrl' => $paymentResult['payment_url'] ?? null
                ]);
            } else {
                throw new \Exception($paymentResult['message'] ?? 'Payment processing failed');
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Hotel booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to process booking. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Process payment based on method
     */
    private function processPayment($booking, $paymentData): array
    {
        try {
            switch ($paymentData['method']) {
                case 'credit_card':
                    return $this->paymentService->processCreditCard($booking, $paymentData);

                case 'vnpay':
                    return $this->paymentService->generateVNPayUrl($booking);

                case 'momo':
                    return $this->paymentService->generateMoMoUrl($booking);

                case 'bank_transfer':
                    return [
                        'success' => true,
                        'message' => 'Bank transfer details sent to email',
                        'transaction_id' => 'BT_' . time()
                    ];

                default:
                    throw new \Exception('Unsupported payment method');
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
