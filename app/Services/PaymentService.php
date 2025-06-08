<?php

namespace App\Services;

use App\Models\FlightBooking;
use App\Models\HotelBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Process credit card payment
     */
    public function processCreditCard($booking, $paymentData): array
    {
        try {
            // In a real implementation, you would integrate with a payment gateway
            // like Stripe, PayPal, or a Vietnamese payment processor

            // For demo purposes, we'll simulate a successful payment
            // In production, you would:
            // 1. Validate card details
            // 2. Process payment through payment gateway
            // 3. Handle response and errors

            // Simulate payment processing delay
            sleep(1);

            // Simulate 95% success rate
            if (rand(1, 100) <= 95) {
                return [
                    'success' => true,
                    'transaction_id' => 'CC_' . time() . '_' . rand(1000, 9999),
                    'message' => 'Payment processed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Payment failed. Please check your card details and try again.'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Credit card payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ];
        }
    }

    /**
     * Generate VNPay payment URL
     */
    public function generateVNPayUrl($booking): array
    {
        try {
            // VNPay configuration
            $vnpTmnCode = config('services.vnpay.tmn_code', 'DEMO_TMN_CODE');
            $vnpHashSecret = config('services.vnpay.hash_secret', 'DEMO_HASH_SECRET');
            $vnpUrl = config('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
            $vnpReturnUrl = config('services.vnpay.return_url', url('/payment/vnpay/return'));

            // Build VNPay parameters
            $vnpParams = [
                'vnp_Version' => '2.1.0',
                'vnp_Command' => 'pay',
                'vnp_TmnCode' => $vnpTmnCode,
                'vnp_Amount' => $booking->total_amount * 100, // VNPay expects amount in VND cents
                'vnp_CurrCode' => 'VND',
                'vnp_TxnRef' => $booking->booking_reference,
                'vnp_OrderInfo' => $this->getOrderInfo($booking),
                'vnp_OrderType' => $this->getOrderType($booking),
                'vnp_Locale' => 'vn',
                'vnp_ReturnUrl' => $vnpReturnUrl,
                'vnp_IpAddr' => request()->ip(),
                'vnp_CreateDate' => now()->format('YmdHis'),
            ];

            // Sort parameters and create query string
            ksort($vnpParams);
            $query = http_build_query($vnpParams);

            // Create secure hash
            $vnpSecureHash = hash_hmac('sha512', $query, $vnpHashSecret);
            $paymentUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;

            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'transaction_id' => 'VNP_' . $booking->booking_reference,
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
     * Generate MoMo payment URL
     */
    public function generateMoMoUrl($booking): array
    {
        try {
            // MoMo configuration
            $partnerCode = config('services.momo.partner_code', 'DEMO_PARTNER_CODE');
            $accessKey = config('services.momo.access_key', 'DEMO_ACCESS_KEY');
            $secretKey = config('services.momo.secret_key', 'DEMO_SECRET_KEY');
            $endpoint = config('services.momo.endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create');
            $redirectUrl = config('services.momo.redirect_url', url('/payment/momo/return'));
            $ipnUrl = config('services.momo.ipn_url', url('/payment/momo/ipn'));

            // Build MoMo request
            $orderId = $booking->booking_reference;
            $requestId = $orderId . '_' . time();
            $amount = (string) $booking->total_amount;
            $orderInfo = $this->getOrderInfo($booking);
            $requestType = 'captureWallet';
            $extraData = '';

            // Create signature
            $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
            $signature = hash_hmac('sha256', $rawHash, $secretKey);

            $requestData = [
                'partnerCode' => $partnerCode,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'requestType' => $requestType,
                'signature' => $signature,
                'lang' => 'vi',
                'extraData' => $extraData
            ];

            // Send request to MoMo
            $response = Http::post($endpoint, $requestData);
            $result = $response->json();

            if (isset($result['payUrl'])) {
                return [
                    'success' => true,
                    'payment_url' => $result['payUrl'],
                    'transaction_id' => 'MOMO_' . $requestId,
                    'message' => 'MoMo payment URL generated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unable to generate MoMo payment URL: ' . ($result['message'] ?? 'Unknown error')
                ];
            }
        } catch (\Exception $e) {
            Log::error('MoMo URL generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to generate MoMo payment URL'
            ];
        }
    }

    /**
     * Verify VNPay payment response
     */
    public function verifyVNPayPayment($params): array
    {
        try {
            $vnpHashSecret = config('services.vnpay.hash_secret', 'DEMO_HASH_SECRET');
            $vnpSecureHash = $params['vnp_SecureHash'] ?? '';
            unset($params['vnp_SecureHash']);

            ksort($params);
            $query = http_build_query($params);
            $calculatedHash = hash_hmac('sha512', $query, $vnpHashSecret);

            if ($calculatedHash === $vnpSecureHash) {
                return [
                    'success' => $params['vnp_ResponseCode'] === '00',
                    'transaction_id' => $params['vnp_TransactionNo'] ?? null,
                    'booking_reference' => $params['vnp_TxnRef'] ?? null,
                    'amount' => ($params['vnp_Amount'] ?? 0) / 100,
                    'message' => $this->getVNPayResponseMessage($params['vnp_ResponseCode'] ?? '')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid payment signature'
                ];
            }
        } catch (\Exception $e) {
            Log::error('VNPay verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];
        }
    }

    /**
     * Get order info based on booking type
     */
    private function getOrderInfo($booking): string
    {
        if ($booking instanceof FlightBooking) {
            return "Thanh toan ve may bay - {$booking->booking_reference}";
        } elseif ($booking instanceof HotelBooking) {
            return "Thanh toan khach san - {$booking->booking_reference}";
        }
        return "Thanh toan booking - {$booking->booking_reference}";
    }

    /**
     * Get order type based on booking type
     */
    private function getOrderType($booking): string
    {
        if ($booking instanceof FlightBooking) {
            return 'flight';
        } elseif ($booking instanceof HotelBooking) {
            return 'hotel';
        }
        return 'other';
    }

    /**
     * Get VNPay response message
     */
    private function getVNPayResponseMessage($responseCode): string
    {
        $messages = [
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường).',
            '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.',
            '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch.',
            '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa.',
            '13' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP).',
            '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
            '51' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch.',
            '65' => 'Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
            '79' => 'Giao dịch không thành công do: KH nhập sai mật khẩu thanh toán quá số lần quy định.',
            '99' => 'Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê)'
        ];

        return $messages[$responseCode] ?? 'Lỗi không xác định';
    }
}
