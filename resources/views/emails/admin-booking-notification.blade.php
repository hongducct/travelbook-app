<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo Booking mới</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #dc3545;
            margin: 0;
            font-size: 28px;
        }

        .alert-badge {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin: 10px 0;
        }

        .booking-summary {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .booking-summary h2 {
            color: #856404;
            margin-top: 0;
            font-size: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .info-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2dd4bf;
        }

        .info-section h3 {
            color: #2dd4bf;
            margin-top: 0;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;
            color: #495057;
            flex: 1;
        }

        .value {
            color: #212529;
            flex: 1;
            text-align: right;
        }

        .price-highlight {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 20px;
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

        .urgent-actions {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .urgent-actions h3 {
            color: #721c24;
            margin-top: 0;
        }

        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                flex-direction: column;
            }

            .value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🚨 BOOKING MỚI</h1>
            <div class="alert-badge">Cần xử lý ngay</div>
            <p>Có booking mới cần được xác nhận và xử lý</p>
        </div>

        <div class="booking-summary">
            <h2>📋 Tóm tắt Booking</h2>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Booking ID:</strong> #{{ $booking->id }}<br>
                    <strong>Thời gian đặt:</strong> {{ $booking->created_at->format('d/m/Y H:i:s') }}
                </div>
                <div>
                    <span
                        class="status-badge {{ $booking->status === 'confirmed' ? 'status-confirmed' : 'status-pending' }}">
                        {{ $booking->status === 'confirmed' ? 'Đã xác nhận' : 'Chờ xác nhận' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>👤 Thông tin khách hàng</h3>
                <div class="info-row">
                    <span class="label">Tên:</span>
                    <span class="value">{{ $user->name ?? $user->username }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="info-row">
                    <span class="label">SĐT:</span>
                    <span class="value">{{ $booking->contact_phone }}</span>
                </div>
                <div class="info-row">
                    <span class="label">ID khách hàng:</span>
                    <span class="value">#{{ $user->id }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>🏖️ Thông tin tour</h3>
                <div class="info-row">
                    <span class="label">Tên tour:</span>
                    <span class="value">{{ $tour->name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Ngày đi:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($booking->start_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Ngày về:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($booking->end_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Thời gian:</span>
                    <span class="value">{{ $tour->days }}N{{ $tour->nights }}Đ</span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3>👥 Chi tiết đặt chỗ</h3>
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
            <div class="info-row">
                <span class="label">Tổng số khách:</span>
                <span class="value">{{ $booking->number_of_guests_adults + $booking->number_of_children }} người</span>
            </div>
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
            <div class="info-section">
                <h3>💳 Thông tin thanh toán</h3>
                <div class="info-row">
                    <span class="label">Phương thức:</span>
                    <span class="value">
                        @if($payment->method === 'cash')
                            💵 Thanh toán sau (Tiền mặt)
                        @elseif($payment->method === 'vnpay')
                            🏦 VNPay
                        @else
                            {{ $payment->method }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Trạng thái:</span>
                    <span class="value">
                        <span
                            class="status-badge {{ $payment->status === 'completed' ? 'status-confirmed' : 'status-pending' }}">
                            @if($payment->status === 'completed')
                                ✅ Đã thanh toán
                            @elseif($payment->status === 'pending')
                                ⏳ Chờ thanh toán
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

        @if($payment && $payment->method === 'cash')
            <div class="urgent-actions">
                <h3>⚠️ Cần xử lý ngay</h3>
                <p><strong>Khách hàng chọn thanh toán sau (tiền mặt)</strong></p>
                <ul>
                    <li>Liên hệ khách hàng qua SĐT: <strong>{{ $booking->contact_phone }}</strong></li>
                    <li>Xác nhận thông tin tour và lịch trình</li>
                    <li>Thỏa thuận địa điểm và thời gian thanh toán</li>
                    <li>Cập nhật trạng thái booking sau khi xác nhận</li>
                </ul>
            </div>
        @endif

        <div class="action-buttons">
            <a href="https://travel-booking.hongducct.id.vn/admin/bookings" class="btn btn-primary">
                📋 Xem chi tiết Booking
            </a>
            <a href="tel:{{ $booking->contact_phone }}" class="btn btn-success">
                📞 Gọi khách hàng
            </a>
        </div>

        <div style="background-color: #e8f5f3; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3 style="color: #2dd4bf; margin-top: 0;">📞 Thông tin liên hệ khách hàng</h3>
            <p><strong>Tên:</strong> {{ $user->name ?? $user->username }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Số điện thoại:</strong> {{ $booking->contact_phone }}</p>
            @if($user->created_at)
                <p><strong>Khách hàng từ:</strong> {{ $user->created_at->format('d/m/Y') }}</p>
            @endif
        </div>

        <div class="footer">
            <p><strong>Email thông báo tự động từ hệ thống Travel Booking</strong></p>
            <p>Thời gian: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Vui lòng xử lý booking này trong thời gian sớm nhất!</p>
        </div>
    </div>
</body>

</html>