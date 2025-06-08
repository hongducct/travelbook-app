<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt vé máy bay</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .booking-ref {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 20px 0;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .section h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .flight-route {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin: 15px 0;
        }
        .price {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status.confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        @media (max-width: 600px) {
            .info-row {
                flex-direction: column;
            }
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">✈️ Travel Booking Vietnam</div>
            <h1>Xác nhận đặt vé máy bay</h1>
            <div class="booking-ref">{{ $booking->booking_reference }}</div>
            <div class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</div>
        </div>

        <!-- Flight Information -->
        <div class="section">
            <h3>🛫 Thông tin chuyến bay</h3>
            @php
                $searchParams = $booking->search_params;
                $flightData = $booking->flight_data;
            @endphp
            
            <div class="flight-route">
                {{ $searchParams['originLocationCode'] ?? 'N/A' }} → {{ $searchParams['destinationLocationCode'] ?? 'N/A' }}
            </div>
            
            @if(isset($flightData['itineraries'][0]['segments'][0]))
                @php 
                    $segment = $flightData['itineraries'][0]['segments'][0];
                    $carrierCode = $segment['carrierCode'] ?? '';
                    $airlines = [
                        'VN' => 'Vietnam Airlines',
                        'VJ' => 'VietJet Air',
                        'BL' => 'Jetstar Pacific',
                        'QH' => 'Bamboo Airways',
                        'VU' => 'Vietravel Airlines'
                    ];
                    $airlineName = $airlines[$carrierCode] ?? $carrierCode;
                @endphp
                
                <div class="info-row">
                    <span class="info-label">Hãng bay:</span>
                    <span class="info-value">{{ $airlineName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số hiệu chuyến bay:</span>
                    <span class="info-value">{{ $carrierCode }}{{ $segment['number'] ?? '' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày khởi hành:</span>
                    <span class="info-value">
                        @if(isset($segment['departure']['at']))
                            {{ \Carbon\Carbon::parse($segment['departure']['at'])->format('d/m/Y H:i') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thời gian bay:</span>
                    <span class="info-value">{{ $flightData['itineraries'][0]['duration'] ?? 'N/A' }}</span>
                </div>
            @endif
        </div>

        <!-- Passenger Information -->
        <div class="section">
            <h3>👤 Thông tin hành khách</h3>
            @php $passengerData = $booking->passenger_data; @endphp
            
            <div class="info-row">
                <span class="info-label">Họ tên:</span>
                <span class="info-value">{{ ($passengerData['firstName'] ?? '') . ' ' . ($passengerData['lastName'] ?? '') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $passengerData['email'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Số điện thoại:</span>
                <span class="info-value">{{ $passengerData['phone'] ?? 'N/A' }}</span>
            </div>
            @if(isset($passengerData['dateOfBirth']) && $passengerData['dateOfBirth'])
            <div class="info-row">
                <span class="info-label">Ngày sinh:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($passengerData['dateOfBirth'])->format('d/m/Y') }}</span>
            </div>
            @endif
            @if(isset($passengerData['passportNumber']) && $passengerData['passportNumber'])
            <div class="info-row">
                <span class="info-label">Số hộ chiếu:</span>
                <span class="info-value">{{ $passengerData['passportNumber'] }}</span>
            </div>
            @endif
        </div>

        <!-- Contact Information -->
        <div class="section">
            <h3>📞 Thông tin liên hệ</h3>
            @php $contactData = $booking->contact_data; @endphp
            
            <div class="info-row">
                <span class="info-label">Người liên hệ:</span>
                <span class="info-value">
                    @if(isset($contactData['firstName']) && $contactData['firstName'])
                        {{ $contactData['firstName'] . ' ' . ($contactData['lastName'] ?? '') }}
                    @else
                        {{ ($passengerData['firstName'] ?? '') . ' ' . ($passengerData['lastName'] ?? '') }}
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Email liên hệ:</span>
                <span class="info-value">{{ $contactData['email'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Số điện thoại liên hệ:</span>
                <span class="info-value">{{ $contactData['phone'] ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="section">
            <h3>💳 Thông tin thanh toán</h3>
            <div class="info-row">
                <span class="info-label">Phương thức thanh toán:</span>
                <span class="info-value">
                    @switch($booking->payment_method)
                        @case('credit_card')
                            Thẻ tín dụng/ghi nợ
                            @break
                        @case('vnpay')
                            VNPay
                            @break
                        @case('momo')
                            MoMo
                            @break
                        @case('bank_transfer')
                            Chuyển khoản ngân hàng
                            @break
                        @default
                            {{ $booking->payment_method }}
                    @endswitch
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Trạng thái thanh toán:</span>
                <span class="info-value">
                    @switch($booking->payment_status)
                        @case('paid')
                            Đã thanh toán
                            @break
                        @case('pending')
                            Chờ thanh toán
                            @break
                        @case('failed')
                            Thanh toán thất bại
                            @break
                        @default
                            {{ $booking->payment_status }}
                    @endswitch
                </span>
            </div>
            @if($booking->payment_transaction_id)
            <div class="info-row">
                <span class="info-label">Mã giao dịch:</span>
                <span class="info-value">{{ $booking->payment_transaction_id }}</span>
            </div>
            @endif
        </div>

        <!-- Total Amount -->
        <div class="price">
            Tổng tiền: {{ number_format($booking->total_amount, 0, ',', '.') }} {{ $booking->currency }}
        </div>

        <!-- Important Notes -->
        <div class="section">
            <h3>⚠️ Lưu ý quan trọng</h3>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Vui lòng có mặt tại sân bay ít nhất 2 giờ trước giờ khởi hành đối với chuyến bay nội địa.</li>
                <li>Mang theo giấy tờ tùy thân hợp lệ (CMND/CCCD/Hộ chiếu).</li>
                <li>Kiểm tra kỹ thông tin trên vé trước khi đi.</li>
                <li>Liên hệ với chúng tôi nếu cần hỗ trợ hoặc thay đổi thông tin.</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>Travel Booking Vietnam</strong></p>
            <p>Email: support@travelbooking.vn | Hotline: 1900 1234</p>
            <p>Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi!</p>
        </div>
    </div>
</body>
</html>
