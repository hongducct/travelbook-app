<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt tour</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #2dd4bf;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2dd4bf;
            margin: 0;
            font-size: 28px;
        }

        .booking-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;
            color: #495057;
        }

        .value {
            color: #212529;
        }

        .tour-details {
            background-color: #e8f5f3;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .price-highlight {
            background-color: #2dd4bf;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-confirmed {
            background-color: #28a745;
            color: white;
        }

        .contact-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .info-row {
                flex-direction: column;
            }

            .label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Xác nhận đặt tour thành công!</h1>
            <p>Cảm ơn bạn đã tin tưởng và lựa chọn dịch vụ của chúng tôi</p>
        </div>

        <div class="booking-info">
            <h2 style="color: #2dd4bf; margin-top: 0;">📋 Thông tin đặt tour</h2>

            <div class="info-row">
                <span class="label">Mã đặt tour:</span>
                <span class="value">#{{ $booking->id }}</span>
            </div>

            <div class="info-row">
                <span class="label">Khách hàng:</span>
                <span class="value">{{ $user->name ?? $user->username }}</span>
            </div>

            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value">{{ $user->email }}</span>
            </div>

            <div class="info-row">
                <span class="label">Số điện thoại:</span>
                <span class="value">{{ $booking->contact_phone }}</span>
            </div>

            <div class="info-row">
                <span class="label">Trạng thái:</span>
                <span class="value">
                    <span
                        class="status-badge {{ $booking->status === 'confirmed' ? 'status-confirmed' : 'status-pending' }}">
                        {{ $booking->status === 'confirmed' ? 'Đã xác nhận' : 'Chờ xác nhận' }}
                    </span>
                </span>
            </div>
        </div>

        <div class="tour-details">
            <h2 style="color: #2dd4bf; margin-top: 0;">🏖️ Chi tiết tour</h2>

            <div class="info-row">
                <span class="label">Tên tour:</span>
                <span class="value">{{ $tour->name }}</span>
            </div>

            <div class="info-row">
                <span class="label">Ngày khởi hành:</span>
                <span class="value">{{ \Carbon\Carbon::parse($booking->start_date)->format('d/m/Y') }}</span>
            </div>

            <div class="info-row">
                <span class="label">Ngày kết thúc:</span>
                <span class="value">{{ \Carbon\Carbon::parse($booking->end_date)->format('d/m/Y') }}</span>
            </div>

            <div class="info-row">
                <span class="label">Thời gian:</span>
                <span class="value">{{ $tour->days }} ngày {{ $tour->nights }} đêm</span>
            </div>

            <div class="info-row">
                <span class="label">Số người lớn:</span>
                <span class="value">{{ $booking->number_of_guests_adults }} người</span>
            </div>

            @if($booking->number_of_children > 0)
                <div class="info-row">
                    <span class="label">Số trẻ em:</span>
                    <span class="value">{{ $booking->number_of_children }} trẻ</span>
                </div>
            @endif

            @if($booking->special_requests)
                <div class="info-row">
                    <span class="label">Yêu cầu đặc biệt:</span>
                    <span class="value">{{ $booking->special_requests }}</span>
                </div>
            @endif
        </div>

        <div class="price-highlight">
            💰 Tổng tiền: {{ number_format($booking->total_price, 0, ',', '.') }}₫
        </div>

        @if($payment)
            <div class="booking-info">
                <h2 style="color: #2dd4bf; margin-top: 0;">💳 Thông tin thanh toán</h2>

                <div class="info-row">
                    <span class="label">Phương thức:</span>
                    <span class="value">
                        @if($payment->method === 'cash')
                            Thanh toán sau (Tiền mặt)
                        @elseif($payment->method === 'vnpay')
                            VNPay
                        @else
                            {{ $payment->method }}
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="label">Trạng thái thanh toán:</span>
                    <span class="value">
                        <span
                            class="status-badge {{ $payment->status === 'completed' ? 'status-confirmed' : 'status-pending' }}">
                            @if($payment->status === 'completed')
                                Đã thanh toán
                            @elseif($payment->status === 'pending')
                                Chờ thanh toán
                            @else
                                {{ $payment->status }}
                            @endif
                        </span>
                    </span>
                </div>

                @if($payment->transaction_id)
                    <div class="info-row">
                        <span class="label">Mã giao dịch:</span>
                        <span class="value">{{ $payment->transaction_id }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="contact-info">
            <h3 style="margin-top: 0; color: #856404;">📞 Thông tin liên hệ</h3>
            <p><strong>Hotline:</strong> 079.9076.901</p>
            <p><strong>Zalo:</strong> 079.9076.901</p>
            <p><strong>Email:</strong> travelbooking@hongducct.id.vn</p>
            <p><strong>Website:</strong> https://travel-booking.hongducct.id.vn</p>
        </div>

        @if($payment && $payment->method === 'cash')
            <div
                style="background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #0c5460;">💡 Lưu ý quan trọng</h3>
                <p>• Vui lòng liên hệ số điện thoại <strong>079.9076.901</strong> để xác nhận và thanh toán tour.</p>
                <p>• Chúng tôi sẽ liên hệ với bạn trong vòng 24h để xác nhận thông tin.</p>
                <p>• Vui lòng chuẩn bị đầy đủ giấy tờ tùy thân khi tham gia tour.</p>
            </div>
        @endif

        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống. Vui lòng không trả lời email này.</p>
            <p>Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ hotline: <strong>079.9076.901</strong></p>
            <p style="margin-top: 20px;">
                <strong>Travel Booking</strong><br>
                Cảm ơn bạn đã tin tưởng dịch vụ của chúng tôi! 🙏
            </p>
        </div>
    </div>
</body>

</html>