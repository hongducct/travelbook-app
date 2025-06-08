TRAVEL BOOKING VIETNAM
======================

XÁC NHẬN ĐẶT PHÒNG KHÁCH SẠN
Mã đặt phòng: {{ $booking->booking_reference }}
Trạng thái: {{ ucfirst($booking->status) }}

THÔNG TIN KHÁCH SẠN
===================
Tên khách sạn: {{ $booking->hotel_name }}
Thành phố: {{ $booking->city_name }}
Địa chỉ: {{ $hotel['address'] ?? 'Trung tâm thành phố' }}
@if(isset($hotel['rating']))
Hạng sao: {{ $hotel['rating'] }} ⭐
@endif

CHI TIẾT ĐẶT PHÒNG
==================
Ngày nhận phòng: {{ $booking->check_in_date ? $booking->check_in_date->format('d/m/Y') : 'N/A' }}
Ngày trả phòng: {{ $booking->check_out_date ? $booking->check_out_date->format('d/m/Y') : 'N/A' }}
Số đêm: {{ $booking->nights }}
Số phòng: {{ $searchParams['rooms'] ?? 1 }} phòng
Số khách: {{ $searchParams['adults'] ?? 2 }} người lớn

@if(isset($booking->preferences['roomType']))
Loại phòng: @switch($booking->preferences['roomType'])
@case('standard')Phòng tiêu chuẩn@break
@case('deluxe')Phòng deluxe@break
@case('suite')Phòng suite@break
@default{{ $booking->preferences['roomType'] }}@endswitch
@endif

THÔNG TIN KHÁCH HÀNG
====================
Họ tên: {{ $booking->guest_full_name }}
Email: {{ $guest['email'] ?? 'N/A' }}
Số điện thoại: {{ $guest['phone'] ?? 'N/A' }}

THÔNG TIN LIÊN HỆ
=================
Người liên hệ: {{ $booking->contact_full_name ?: $booking->guest_full_name }}
Email liên hệ: {{ $contact['email'] ?? 'N/A' }}
Số điện thoại liên hệ: {{ $contact['phone'] ?? 'N/A' }}

@if(isset($booking->preferences['specialRequests']) && $booking->preferences['specialRequests'])
YÊU CẦU ĐẶC BIỆT
================
{{ $booking->preferences['specialRequests'] }}
@endif

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
- Thời gian nhận phòng: 14:00 | Thời gian trả phòng: 12:00
- Mang theo giấy tờ tùy thân hợp lệ khi check-in.
- Liên hệ trực tiếp với khách sạn nếu check-in muộn hoặc sớm.
- Kiểm tra chính sách hủy phòng trước khi đặt.
- Liên hệ với chúng tôi nếu cần hỗ trợ hoặc thay đổi thông tin.

TRAVEL BOOKING VIETNAM
Email: support@travelbooking.vn | Hotline: 1900 1234
Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi!
