<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AmadeusService;
use App\Models\FlightBooking;
use App\Services\PaymentService;
use App\Mail\FlightBookingConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FlightController extends Controller
{
    protected $amadeusService;
    protected $paymentService;

    public function __construct(AmadeusService $amadeusService, PaymentService $paymentService)
    {
        $this->amadeusService = $amadeusService;
        $this->paymentService = $paymentService;
    }

    /**
     * Search for flights
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'originLocationCode' => 'required|string|size:3',
            'destinationLocationCode' => 'required|string|size:3',
            'departureDate' => 'required|date|after_or_equal:today',
            'returnDate' => 'nullable|date|after:departureDate',
            'adults' => 'required|integer|min:1|max:9',
            'currencyCode' => 'string|size:3',
            'max' => 'integer|min:1|max:250'
        ]);

        try {
            $searchParams = [
                'originLocationCode' => $request->originLocationCode,
                'destinationLocationCode' => $request->destinationLocationCode,
                'departureDate' => $request->departureDate,
                'adults' => $request->adults,
                'currencyCode' => $request->get('currencyCode', 'VND'),
                'max' => $request->get('max', 10)
            ];

            if ($request->returnDate) {
                $searchParams['returnDate'] = $request->returnDate;
            }

            $flights = $this->amadeusService->searchFlights($searchParams);

            return response()->json([
                'success' => true,
                'data' => $flights,
                'message' => 'Flights retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Flight search error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to search flights. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Book a flight
     */
    public function book(Request $request): JsonResponse
    {
        $request->validate([
            'flight' => 'required|array',
            'passenger' => 'required|array',
            'passenger.firstName' => 'required|string|max:255',
            'passenger.lastName' => 'required|string|max:255',
            'passenger.email' => 'required|email|max:255',
            'passenger.phone' => 'required|string|max:20',
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
            $bookingReference = 'FL' . strtoupper(Str::random(8));

            // Create booking record with PENDING status
            $booking = FlightBooking::create([
                'booking_reference' => $bookingReference,
                'flight_data' => json_encode($request->flight),
                'passenger_data' => json_encode($request->passenger),
                'contact_data' => json_encode($request->contact),
                'search_params' => json_encode($request->searchParams),
                'preferences' => json_encode($request->preferences ?? []),
                'payment_method' => $request->payment['method'],
                'total_amount' => $request->flight['price']['total'] ?? 0,
                'currency' => $request->flight['price']['currency'] ?? 'VND',
                'status' => 'pending', // Always start with pending
                'payment_status' => 'pending' // Always start with pending payment
            ]);

            // Process payment based on method
            $paymentResult = $this->processPayment($booking, $request->payment);

            if ($paymentResult['success']) {
                // For credit card and bank transfer, mark as completed immediately
                if (in_array($request->payment['method'], ['credit_card', 'bank_transfer'])) {
                    $booking->update([
                        'status' => 'confirmed',
                        'payment_status' => 'paid',
                        'payment_transaction_id' => $paymentResult['transaction_id'] ?? null
                    ]);

                    // Send confirmation email for immediate payment methods
                    $this->sendConfirmationEmail($booking);
                }
                // For VNPay/MoMo, keep pending until callback confirms payment
                else {
                    $booking->update([
                        'payment_transaction_id' => $paymentResult['transaction_id'] ?? null
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $request->payment['method'] === 'vnpay' || $request->payment['method'] === 'momo'
                        ? 'Booking created. Please complete payment.'
                        : 'Flight booked successfully',
                    'bookingId' => $booking->id,
                    'bookingReference' => $bookingReference,
                    'paymentUrl' => $paymentResult['payment_url'] ?? null,
                    'status' => $booking->status,
                    'paymentStatus' => $booking->payment_status
                ]);
            } else {
                throw new \Exception($paymentResult['message'] ?? 'Payment processing failed');
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Flight booking error: ' . $e->getMessage());

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
                    return $this->createVNPayPayment($booking);

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

    /**
     * Create VNPay payment for flight booking
     */
    private function createVNPayPayment($booking): array
    {
        try {
            $vnp_TmnCode = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');

            if (empty($vnp_TmnCode) || empty($vnp_HashSecret)) {
                throw new \Exception('VNPay configuration missing');
            }

            $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
            $vnp_ReturnUrl = env('VNPAY_RETURN_FLIGHTS_URL', url('/api/flights/vnpay/callback'));
            $vnp_TxnRef = 'VNP_FL_' . $booking->booking_reference;
            $vnp_Amount = $booking->total_amount * 100; // VNPay expects amount in VND cents
            $vnp_CreateDate = Carbon::now()->format('YmdHis');
            $vnp_ExpireDate = Carbon::now()->addMinutes(15)->format('YmdHis');

            $inputData = [
                'vnp_Version' => '2.1.0',
                'vnp_TmnCode' => $vnp_TmnCode,
                'vnp_Amount' => $vnp_Amount,
                'vnp_Command' => 'pay',
                'vnp_CreateDate' => $vnp_CreateDate,
                'vnp_CurrCode' => 'VND',
                'vnp_IpAddr' => request()->ip(),
                'vnp_Locale' => 'vn',
                'vnp_OrderInfo' => 'Thanh toan ve may bay #' . $booking->booking_reference,
                'vnp_OrderType' => 'flight_booking',
                'vnp_ReturnUrl' => $vnp_ReturnUrl,
                'vnp_TxnRef' => $vnp_TxnRef,
                'vnp_ExpireDate' => $vnp_ExpireDate,
            ];

            ksort($inputData);
            $hashData = '';
            foreach ($inputData as $key => $value) {
                if ($hashData) $hashData .= '&';
                $hashData .= $key . '=' . urlencode($value);
            }

            $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
            $vnp_Url .= '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnpSecureHash;

            return [
                'success' => true,
                'payment_url' => $vnp_Url,
                'transaction_id' => $vnp_TxnRef,
                'message' => 'VNPay payment URL generated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('VNPay URL generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to generate VNPay payment URL'
            ];
        }
    }

    /**
     * VNPay callback for flight bookings
     */
    public function vnpayCallback(Request $request)
    {
        Log::info('Flight VNPay callback received', ['request' => $request->all()]);

        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = '';
        foreach ($inputData as $key => $value) {
            if ($hashData) $hashData .= '&';
            $hashData .= $key . '=' . urlencode($value);
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        try {
            if ($secureHash !== $vnp_SecureHash) {
                Log::error('Flight VNPay callback invalid signature');
                return redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/payment-result?status=failed&message=' . urlencode('Chữ ký không hợp lệ'));
            }

            // Extract booking reference from vnp_TxnRef (format: VNP_FL_FLXXXXXXXX)
            $txnRef = $inputData['vnp_TxnRef'];
            $bookingReference = str_replace('VNP_FL_', '', $txnRef);

            $booking = FlightBooking::where('booking_reference', $bookingReference)->first();
            if (!$booking) {
                Log::error('Flight VNPay callback booking not found', ['booking_reference' => $bookingReference]);
                return redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/payment-result?status=failed&message=' . urlencode('Không tìm thấy booking'));
            }

            if ($inputData['vnp_ResponseCode'] == '00') {
                // Payment successful
                Log::info('Flight VNPay payment successful', ['booking_reference' => $bookingReference]);
                Log::info('inputData', $inputData);
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_transaction_id' => $inputData['vnp_TransactionNo'] ?? $txnRef
                ]);

                // Send confirmation email
                $this->sendConfirmationEmail($booking);

                Log::info('Flight VNPay payment completed', [
                    'booking_reference' => $booking->booking_reference,
                    'vnp_transaction_no' => $inputData['vnp_TransactionNo'] ?? null,
                ]);

                return redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/payment-result?status=success&booking_reference=' . $booking->booking_reference);
            } else {
                // Payment failed
                $booking->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed'
                ]);

                Log::warning('Flight VNPay payment failed', [
                    'booking_reference' => $booking->booking_reference,
                    'response_code' => $inputData['vnp_ResponseCode'],
                ]);

                return redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/payment-result?status=failed&message=' . urlencode('Thanh toán thất bại'));
            }
        } catch (\Exception $e) {
            Log::error('Flight VNPay callback processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/payment-result?status=failed&message=' . urlencode('Lỗi xử lý thanh toán'));
        }
    }

    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail($booking)
    {
        try {
            $contactData = json_decode($booking->contact_data, true);
            LOG::info('$contactData', $contactData);

            Mail::to($contactData['email'])
                ->send(new FlightBookingConfirmation($booking));

            Log::info('Flight booking confirmation email sent', [
                'booking_reference' => $booking->booking_reference,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send flight booking confirmation email: ' . $e->getMessage());
        }
    }

    /**
     * Get booking details
     */
    public function getBooking(Request $request, $bookingReference): JsonResponse
    {
        try {
            $booking = FlightBooking::where('booking_reference', $bookingReference)->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Booking retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Get booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve booking',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
