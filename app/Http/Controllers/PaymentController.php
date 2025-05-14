<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $payment = Payment::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['booking.tour'])
            ->firstOrFail();

        return response()->json(['payment' => $payment], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        $payment = Payment::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,completed,failed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 400);
        }

        $payment->status = $request->status;
        $payment->save();

        $booking = Booking::where('payment_id', $payment->id)->first();
        if ($booking) {
            if ($request->status === 'completed') {
                $booking->status = 'confirmed';
            } elseif ($request->status === 'failed') {
                $booking->status = 'cancelled';
            }
            $booking->save();
        }

        // Log::info('Payment status updated', [
        //     'payment_id' => $payment->id,
        //     'status' => $payment->status,
        //     'booking_id' => $booking?->id,
        // ]);

        return response()->json(['message' => 'Cập nhật trạng thái thành công', 'payment' => $payment], 200);
    }

    public function createVNPayPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:10000',
            'method' => 'required|in:credit_card,bank_transfer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->payment_id) {
            return response()->json(['message' => 'Booking đã có thanh toán.'], 422);
        }

        try {
            $payment = Payment::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'method' => $request->method,
                'status' => 'pending',
                'transaction_id' => 'TXN_' . uniqid(),
            ]);

            $booking->payment_id = $payment->id;
            $booking->save();

            $vnp_TmnCode = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            if (empty($vnp_TmnCode) || empty($vnp_HashSecret)) {
                Log::error('VNPay configuration missing', [
                    'vnp_TmnCode' => $vnp_TmnCode,
                    'vnp_HashSecret' => $vnp_HashSecret,
                ]);
                return response()->json(['message' => 'Cấu hình VNPay không đầy đủ.'], 500);
            }

            $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
            // $vnp_ReturnUrl = env('VNPAY_RETURN_URL', 'http://your-api-url.test/api/payments/vnpay/callback');
            $vnp_ReturnUrl = env('VNPAY_RETURN_URL', 'http://your-api-url.test/api/payments/vnpay/callback') . '?redirect=' . urlencode('https://hongducct.id.vn/payment-result');
            $vnp_TxnRef = $payment->transaction_id;
            $vnp_Amount = $request->amount * 100;
            $vnp_Locale = 'vn';
            $vnp_BankCode = $request->method === 'bank_transfer' ? 'NCB' : '';
            $vnp_IpAddr = $request->ip();
            $vnp_CreateDate = Carbon::now()->format('YmdHis');
            $vnp_ExpireDate = Carbon::now()->addMinutes(15)->format('YmdHis');

            $inputData = [
                'vnp_Version' => '2.1.0',
                'vnp_TmnCode' => $vnp_TmnCode,
                'vnp_Amount' => $vnp_Amount,
                'vnp_Command' => 'pay',
                'vnp_CreateDate' => $vnp_CreateDate,
                'vnp_CurrCode' => 'VND',
                'vnp_IpAddr' => $vnp_IpAddr,
                'vnp_Locale' => $vnp_Locale,
                'vnp_OrderInfo' => 'Thanh toan booking #' . $booking->id,
                'vnp_OrderType' => 'tour_booking',
                'vnp_ReturnUrl' => $vnp_ReturnUrl,
                'vnp_TxnRef' => $vnp_TxnRef,
                'vnp_ExpireDate' => $vnp_ExpireDate,
            ];

            if ($vnp_BankCode) {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }

            ksort($inputData);
            $query = http_build_query($inputData);
            $hashData = '';
            foreach ($inputData as $key => $value) {
                if ($hashData) $hashData .= '&';
                $hashData .= $key . '=' . urlencode($value);
            }

            $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
            $vnp_Url .= '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;

            // Rút ngắn URL bằng Bitly
            $bitlyToken = env('BITLY_ACCESS_TOKEN');
            $shortenedUrl = $vnp_Url;
            if ($bitlyToken) {
                $response = \Ixudra\Curl\Facades\Curl::to('https://api-ssl.bitly.com/v4/shorten')
                    ->withHeader('Authorization: Bearer ' . $bitlyToken)
                    ->withData(['long_url' => $vnp_Url])
                    ->asJson()
                    ->post();
                if (isset($response->link)) {
                    $shortenedUrl = $response->link;
                } else {
                    Log::warning('Bitly URL shortening failed', ['response' => $response]);
                }
            }

            // Log::info('VNPay payment URL created', [
            //     'payment_id' => $payment->id,
            //     'booking_id' => $booking->id,
            //     'vnp_url' => $vnp_Url,
            //     'shortened_url' => $shortenedUrl,
            // ]);

            return response()->json([
                'message' => 'Tạo URL thanh toán VNPay thành công',
                'payment' => $payment,
                'vnpay_url' => $shortenedUrl,
            ], 201);
        } catch (\Exception $e) {
            Log::error('VNPay payment creation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Lỗi khi tạo thanh toán VNPay: ' . $e->getMessage()], 500);
        }
    }
    public function vnpayCallback(Request $request)
    {
        // Log toàn bộ request để debug
        // Log::info('VNPay callback received', ['request' => $request->all()]);

        // Lấy secret key từ .env
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');

        // Lấy dữ liệu từ request và loại bỏ các tham số không cần thiết cho hash
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['redirect']); // Loại bỏ tham số redirect nếu có

        // Sắp xếp dữ liệu theo thứ tự alphabet để tạo hash
        ksort($inputData);
        $hashData = '';
        foreach ($inputData as $key => $value) {
            if ($hashData) $hashData .= '&';
            $hashData .= $key . '=' . urlencode($value);
        }

        // Tạo chữ ký để kiểm tra tính toàn vẹn của request
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        try {
            // Kiểm tra chữ ký
            if ($secureHash !== $vnp_SecureHash) {
                Log::error('VNPay callback invalid signature', [
                    'request' => $request->all(),
                    'calculated_hash' => $secureHash,
                ]);
                return redirect()->away('https://hongducct.id.vn/payment-result?status=failed&message=' . urlencode('Chữ ký không hợp lệ'));
            }

            // Tìm payment dựa trên vnp_TxnRef
            $payment = Payment::where('transaction_id', $inputData['vnp_TxnRef'])->first();
            if (!$payment) {
                Log::error('VNPay callback payment not found', [
                    'vnp_TxnRef' => $inputData['vnp_TxnRef'],
                ]);
                return redirect()->away('https://hongducct.id.vn/payment-result?status=failed&message=' . urlencode('Không tìm thấy thanh toán'));
            }

            // Tìm booking liên quan
            $booking = Booking::where('payment_id', $payment->id)->first();
            if (!$booking) {
                Log::warning('VNPay callback booking not found', [
                    'payment_id' => $payment->id,
                ]);
            }

            // Kiểm tra trạng thái thanh toán từ VNPay
            if ($inputData['vnp_ResponseCode'] == '00') {
                // Thanh toán thành công
                $payment->status = 'completed';
                $payment->save();

                if ($booking) {
                    $booking->status = 'confirmed';
                    $booking->save();
                }

                Log::info('VNPay payment completed', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking?->id,
                    'vnp_transaction_no' => $inputData['vnp_TransactionNo'],
                ]);

                // Redirect về frontend với trạng thái thành công
                return redirect()->away('https://hongducct.id.vn/payment-result?status=success&booking_id=' . ($booking?->id ?? ''));
            } else {
                // Thanh toán thất bại
                $payment->status = 'failed';
                $payment->save();

                if ($booking) {
                    $booking->status = 'cancelled';
                    $booking->save();
                }

                Log::warning('VNPay payment failed', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking?->id,
                    'response_code' => $inputData['vnp_ResponseCode'],
                ]);

                // Redirect về frontend với trạng thái thất bại
                return redirect()->away('https://hongducct.id.vn/payment-result?status=failed&message=' . urlencode('Thanh toán thất bại'));
            }
        } catch (\Exception $e) {
            Log::error('VNPay callback processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return redirect()->away('https://hongducct.id.vn/payment-result?status=failed&message=' . urlencode('Lỗi xử lý thanh toán: ' . $e->getMessage()));
        }
    }
}