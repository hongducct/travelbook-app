<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt phòng khách sạn</title>
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
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        .booking-ref {
            background-color: #28a745;
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
            border-left: 4px solid #28a745;
        }
        .section h3 {
            margin-top: 0;
            color: #28a745;
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
        .hotel-name {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #28a745;
            margin: 15px 0;
        }
        .dates {
            text-align: center;
            font-size: 18px;
            color: #666;
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
        .nights-badge {
            background-color: #17a2b8;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
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
            <div class="logo">🏨 Travel Booking Vietnam</div>
            <h1>Xác nhận đặt phòng khách sạn</h1>
            <div class="booking-ref">{{ $booking->booking_reference }}</div>
            <div class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</div>
        </div>

        <!-- Hotel Information -->
        <div class="section">
            <h3>🏨 Thông tin khách sạn</h3>
            @php
                $hotelData = $booking->hotel_data;
                $searchParams = $booking->search_params;
                $cityCode = $searchParams['cityCode'] ?? '';
                
                $cities = [
                    'SGN' => 'TP. Hồ Chí Minh',
                    'HAN' => 'Hà Nội',
                    'DAD' => 'Đà Nẵng',
                    'NHA' => 'Nha Trang',
                    'PQC' => 'Phú Quốc',
                    'HUE' => 'Huế',
                    'HOI' => 'Hội An',
                    'VTE' => 'Vũng Tàu',
                    'DLT' => 'Đà Lạt',
                    'CTO' => 'Cần Thơ'
                ];
                
                $cityName = $cities[$cityCode] ?? $cityCode;
                $hotelName = $hotelData['name'] ?? 'Khách sạn cao cấp';
            @endphp
            
            <div class="hotel-name">{{ $hotelName }}</div>
            <div class="info-row">
                <span class="info-label">Thành phố:</span>
                <span class="info-value">{{ $cityName }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Địa chỉ:</span>
                <span class="info-value">{{ $hotelData['address'] ?? 'Trung tâm thành phố' }}</span>
            </div>
            @if(isset($hotelData['rating']))
            <div class="info-row">
                <span class="info-label">Hạng sao:</span>
                <span class="info-value">{{ $hotelData['rating'] }} ⭐</span>
            </div>
            @endif
        </div>

        <!-- Booking Details -->
        <div class="section">
            <h3>📅 Chi tiết đặt phòng</h3>
            @php
                $checkInDate = isset($searchParams['checkInDate']) ? \Carbon\Carbon::parse($searchParams['checkInDate']) : null;
                $checkOutDate = isset($searchParams['checkOutDate']) ? \Carbon\Carbon::parse($searchParams['checkOutDate']) : null;
            @endphp
            
            <div class="dates">
                {{ $checkInDate ? $checkInDate->format('d/m/Y') : 'N/A' }} 
                → 
                {{ $checkOutDate ? $checkOutDate->format('d/m/Y') : 'N/A' }}
            </div>
            <div style="text-align: center; margin: 15px 0;">
                <span class="nights-badge">{{ $booking->nights }} đêm</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ngày nhận phòng:</span>
                <span class="info-value">{{ $checkInDate ? $checkInDate->format('d/m/Y') : 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ngày trả phòng:</span>
                <span class="info-value">{{ $checkOutDate ? $checkOutDate->format('d/m/Y') : 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Số phòng:</span>
                <span class="info-value">{{ $searchParams['rooms'] ?? 1 }} phòng</span>
            </div>
            <div class="info-row">
                <span class="info-label">Số khách:</span>
                <span class="info-value">{{ $searchParams['adults'] ?? 2 }} người lớn</span>
            </div>
            @if(isset($booking->preferences['roomType']))
            <div class="info-row">
                <span class="info-label">Loại phòng:</span>
                <span class="info-value">
                    @switch($booking->preferences['roomType'])
                        @case('standard')
                            Phòng tiêu chuẩn
                            @break
                        @case('deluxe')
                            Phòng deluxe
                            @break
                        @case('suite')
                            Phòng suite
                            @break
                        @default
                            {{ $booking->preferences['roomType'] }}
                    @endswitch
                </span>
            </div>
            @endif
            @if(isset($booking->preferences['bedType']) && $booking->preferences['bedType'] !== 'any')
            <div class="info-row">
                <span class="info-label">Loại giường:</span>
                <span class="info-value">
                    @switch($booking->preferences['bedType'])
                        @case('single')
                            Giường đơn
                            @break
                        @case('double')
                            Giường đôi
                            @break
                        @case('twin')
                            Hai giường đơn
                            @break
                        @default
                            {{ $booking->preferences['bedType'] }}
                    @endswitch
                </span>
            </div>
            @endif
        </div>

        <!-- Guest Information -->
        <div class="section">
            <h3>👤 Thông tin khách hàng</h3>
            @php $guestData = $booking->guest_data; @endphp
            
            <div class="info-row">
                <span class="info-label">Họ tên:</span>
                <span class="info-value">{{ ($guestData['firstName'] ?? '') . ' ' . ($guestData['lastName'] ?? '') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $guestData['email'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Số điện thoại:</span>
                <span class="info-value">{{ $guestData['phone'] ?? 'N/A' }}</span>
            </div>
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
                        {{ ($guestData['firstName'] ?? '') . ' ' . ($guestData['lastName'] ?? '') }}
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

        <!-- Special Requests -->
        @if(isset($booking->preferences['specialRequests']) && $booking->preferences['specialRequests'])
        <div class="section">
            <h3>📝 Yêu cầu đặc biệt</h3>
            <p style="margin: 0; padding: 10px; background-color: white; border-radius: 5px;">
                {{ $booking->preferences['specialRequests'] }}
            </p>
        </div>
        @endif

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
                <li>Thời gian nhận phòng: 14:00 | Thời gian trả phòng: 12:00</li>
                <li>Mang theo giấy tờ tùy thân hợp lệ khi check-in.</li>
                <li>Liên hệ trực tiếp với khách sạn nếu check-in muộn hoặc sớm.</li>
                <li>Kiểm tra chính sách hủy phòng trước khi đặt.</li>
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
