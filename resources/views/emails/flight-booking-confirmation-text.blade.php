TRAVEL BOOKING VIETNAM
======================

XÁC NHẬN ĐẶT VÉ MÁY BAY
Mã đặt vé: {{ $booking->booking_reference }}
Trạng thái: {{ ucfirst($booking->status) }}

THÔNG TIN CHUYẾN BAY
====================
Tuyến bay: {{ $searchParams['originLocationCode'] ?? 'N/A' }} → {{ $searchParams['destinationLocationCode'] ?? 'N/A' }}
Hãng bay: {{ $booking->airline_name }}
@if(isset($flight['itineraries'][0]['segments'][0]))
Số hiệu: {{ $flight['itineraries'][0]['segments'][0]['carrierCode'] ?? '' }}{{ $flight['itineraries'][0]['segments'][0]['number'] ?? '' }}
@endif
Ngày khởi hành: {{ $booking->departure_date ? $booking->departure_date->format('d/m/Y H:i') : 'N/A' }}

THÔNG TIN HÀNH KHÁCH
====================
Họ tên: {{ $booking->passenger_full_name }}
Email: {{ $passenger['email'] ?? 'N/A' }}
Số điện thoại: {{ $passenger['phone'] ?? 'N/A' }}
@if(isset($passenger['passportNumber']) && $passenger['passportNumber'])
Số hộ chiếu: {{ $passenger['passportNumber'] }}
@endif

THÔNG TIN LIÊN HỆ
=================
Người liên hệ: {{ $booking->contact_full_name ?: $booking->passenger_full_name }}
Email liên hệ: {{ $contact['email'] ?? 'N/A' }}
Số điện thoại liên hệ: {{ $contact['phone'] ?? 'N/A' }}

THÔNG TIN THANH TOÁN
====================
Phương thức: @switch($booking->payment_method)
@case('credit_card')Thẻ tín dụng/ghi nợ@break
@case('vnpay')VNPay@break
@case('momo')MoMo@break
@case('bank_transfer')Chuyển khoản ngân hàng@break
@default{{ $booking->payment_method }}@endswitch

Trạng thái thanh toán: @switch($booking->payment_status)
@case('paid')Đã thanh toán@break
@case('pending')Chờ thanh toán@break
@case('failed')Thanh toán thất bại@break
@default{{ $booking->payment_status }}@endswitch

@if($booking->payment_transaction_id)
Mã giao dịch: {{ $booking->payment_transaction_id }}
@endif

TỔNG TIỀN: {{ $booking->formatted_amount }}

LƯU Ý QUAN TRỌNG
================
- Vui lòng có mặt tại sân bay ít nhất 2 giờ trước giờ khởi hành đối với chuyến bay nội địa.
- Mang theo giấy tờ tùy thân hợp lệ (CMND/CCCD/Hộ chiếu).
- Kiểm tra kỹ thông tin trên vé trước khi đi.
- Liên hệ với chúng tôi nếu cần hỗ trợ hoặc thay đổi thông tin.

TRAVEL BOOKING VIETNAM
Email: support@travelbooking.vn | Hotline: 1900 1234
Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi!
