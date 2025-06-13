<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourAvailability;
use App\Models\Booking;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BookingAssistantService
{
    /**
     * Analyze booking intent from user message
     */
    public function analyzeBookingIntent($message, $tourData = [])
    {
        $message = strtolower($message);
        $bookingKeywords = ['đặt', 'book', 'booking', 'đặt tour', 'đặt chỗ', 'mua tour'];

        $hasBookingIntent = false;
        foreach ($bookingKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $hasBookingIntent = true;
                break;
            }
        }

        if (!$hasBookingIntent || empty($tourData)) {
            return null;
        }

        // Generate booking options for available tours
        $bookingOptions = [];
        foreach (array_slice($tourData, 0, 3) as $tour) {
            $bookingOptions[] = [
                'id' => "book_tour_{$tour['id']}",
                'label' => "Đặt tour {$tour['name']}",
                'step' => 'select_tour',
                'data' => [
                    'tour_id' => $tour['id'],
                    'tour_name' => $tour['name'],
                    'price' => $tour['price'] ?? 0
                ]
            ];
        }

        return $bookingOptions;
    }

    /**
     * Process booking step
     */
    public function processStep($step, $data, $conversationId)
    {
        try {
            switch ($step) {
                case 'select_tour':
                    return $this->handleTourSelection($data, $conversationId);

                case 'select_date':
                    return $this->handleDateSelection($data, $conversationId);

                case 'select_guests':
                    return $this->handleGuestSelection($data, $conversationId);

                case 'apply_voucher':
                    return $this->handleVoucherApplication($data, $conversationId);

                case 'confirm_booking':
                    return $this->handleBookingConfirmation($data, $conversationId);

                default:
                    throw new \Exception("Unknown booking step: {$step}");
            }
        } catch (\Exception $e) {
            Log::error('Booking step processing failed', [
                'error' => $e->getMessage(),
                'step' => $step,
                'data' => $data,
                'conversation_id' => $conversationId
            ]);

            return [
                'message' => 'Có lỗi xảy ra trong quá trình đặt tour. Vui lòng thử lại.',
                'suggestions' => ['Thử lại', 'Chọn tour khác', 'Liên hệ admin']
            ];
        }
    }

    /**
     * Handle tour selection
     */
    private function handleTourSelection($data, $conversationId)
    {
        $tourId = $data['tour_id'];
        $tour = Tour::with(['availabilities' => function ($q) {
            $q->where('date', '>=', now()->toDateString())
                ->where('is_active', true)
                ->where('available_slots', '>', 0)
                ->orderBy('date', 'asc')
                ->limit(10);
        }])->find($tourId);

        if (!$tour) {
            return [
                'message' => 'Không tìm thấy thông tin tour. Vui lòng chọn tour khác.',
                'suggestions' => ['Xem tour khác', 'Tìm tour mới', 'Liên hệ hỗ trợ']
            ];
        }

        // Save booking context
        $this->saveBookingContext($conversationId, [
            'step' => 'date_selection',
            'tour_id' => $tourId,
            'tour_name' => $tour->name,
            'tour_price' => $data['price'] ?? 0
        ]);

        $availableDates = $tour->availabilities->map(function ($avail) {
            return [
                'date' => $avail->date,
                'formatted_date' => \Carbon\Carbon::parse($avail->date)->format('d/m/Y'),
                'day_of_week' => \Carbon\Carbon::parse($avail->date)->locale('vi')->dayName,
                'available_slots' => $avail->available_slots
            ];
        });

        $message = "✅ **Đã chọn tour: {$tour->name}**

📅 **Bước 2: Chọn ngày khởi hành**

Các ngày khả dụng:";

        foreach ($availableDates->take(5) as $date) {
            $message .= "\n• {$date['formatted_date']} ({$date['day_of_week']}) - {$date['available_slots']} chỗ trống";
        }

        if ($availableDates->count() > 5) {
            $message .= "\n...và " . ($availableDates->count() - 5) . " ngày khác";
        }

        $suggestions = [];
        foreach ($availableDates->take(4) as $date) {
            $suggestions[] = $date['formatted_date'];
        }
        $suggestions[] = 'Xem tất cả ngày';

        return [
            'message' => $message,
            'suggestions' => $suggestions,
            'booking_flow' => [
                'step' => 'select_date',
                'tour_id' => $tourId,
                'available_dates' => $availableDates->toArray()
            ]
        ];
    }

    /**
     * Handle date selection
     */
    private function handleDateSelection($data, $conversationId)
    {
        $context = $this->getBookingContext($conversationId);
        if (!$context || !isset($context['tour_id'])) {
            return [
                'message' => 'Phiên đặt tour đã hết hạn. Vui lòng bắt đầu lại.',
                'suggestions' => ['Đặt tour mới', 'Xem tour khác']
            ];
        }

        $selectedDate = $data['date'] ?? null;
        if (!$selectedDate) {
            return [
                'message' => 'Vui lòng chọn ngày khởi hành hợp lệ.',
                'suggestions' => ['Chọn ngày khác', 'Xem lịch khởi hành']
            ];
        }

        // Validate date availability
        $availability = TourAvailability::where('tour_id', $context['tour_id'])
            ->where('date', $selectedDate)
            ->where('is_active', true)
            ->where('available_slots', '>', 0)
            ->first();

        if (!$availability) {
            return [
                'message' => 'Ngày đã chọn không còn khả dụng. Vui lòng chọn ngày khác.',
                'suggestions' => ['Chọn ngày khác', 'Xem lịch khởi hành']
            ];
        }

        // Update booking context
        $context['selected_date'] = $selectedDate;
        $context['step'] = 'guest_selection';
        $this->saveBookingContext($conversationId, $context);

        $formattedDate = \Carbon\Carbon::parse($selectedDate)->format('d/m/Y');
        $dayOfWeek = \Carbon\Carbon::parse($selectedDate)->locale('vi')->dayName;

        $message = "✅ **Đã chọn ngày: {$formattedDate} ({$dayOfWeek})**

👥 **Bước 3: Số lượng khách**

Còn {$availability->available_slots} chỗ trống cho ngày này.

Vui lòng cho biết:
• Số người lớn (từ 12 tuổi trở lên)
• Số trẻ em (2-11 tuổi) - giá 50%
• Trẻ dưới 2 tuổi miễn phí";

        return [
            'message' => $message,
            'suggestions' => [
                '2 người lớn',
                '2 người lớn + 1 trẻ em',
                '4 người lớn',
                'Nhập số lượng khác'
            ],
            'booking_flow' => [
                'step' => 'select_guests',
                'max_guests' => $availability->available_slots
            ]
        ];
    }

    /**
     * Handle guest selection
     */
    private function handleGuestSelection($data, $conversationId)
    {
        $context = $this->getBookingContext($conversationId);
        if (!$context) {
            return [
                'message' => 'Phiên đặt tour đã hết hạn. Vui lòng bắt đầu lại.',
                'suggestions' => ['Đặt tour mới']
            ];
        }

        $adults = $data['adults'] ?? 0;
        $children = $data['children'] ?? 0;
        $totalGuests = $adults + $children;

        if ($totalGuests <= 0) {
            return [
                'message' => 'Vui lòng nhập số lượng khách hợp lệ.',
                'suggestions' => ['2 người lớn', '1 người lớn + 1 trẻ em']
            ];
        }

        // Calculate total price
        $tourPrice = $context['tour_price'] ?? 0;
        $totalPrice = ($adults * $tourPrice) + ($children * $tourPrice * 0.5);

        // Update context
        $context['adults'] = $adults;
        $context['children'] = $children;
        $context['total_price'] = $totalPrice;
        $context['step'] = 'voucher_application';
        $this->saveBookingContext($conversationId, $context);

        $message = "✅ **Số lượng khách: {$adults} người lớn";
        if ($children > 0) {
            $message .= " + {$children} trẻ em";
        }
        $message .= "**

💰 **Tổng chi phí:**
• Người lớn: {$adults} × " . number_format($tourPrice) . " VNĐ = " . number_format($adults * $tourPrice) . " VNĐ";

        if ($children > 0) {
            $message .= "\n• Trẻ em: {$children} × " . number_format($tourPrice * 0.5) . " VNĐ = " . number_format($children * $tourPrice * 0.5) . " VNĐ";
        }

        $message .= "\n• **Tổng cộng: " . number_format($totalPrice) . " VNĐ**

🎫 **Bước 4: Mã giảm giá (tùy chọn)**

Bạn có mã voucher không?";

        return [
            'message' => $message,
            'suggestions' => [
                'Có mã voucher',
                'Không có mã',
                'Xem mã khuyến mãi',
                'Tiếp tục đặt tour'
            ],
            'booking_flow' => [
                'step' => 'apply_voucher',
                'total_price' => $totalPrice
            ]
        ];
    }

    /**
     * Handle voucher application
     */
    private function handleVoucherApplication($data, $conversationId)
    {
        $context = $this->getBookingContext($conversationId);
        if (!$context) {
            return [
                'message' => 'Phiên đặt tour đã hết hạn. Vui lòng bắt đầu lại.',
                'suggestions' => ['Đặt tour mới']
            ];
        }

        $voucherCode = $data['voucher_code'] ?? null;
        $discount = 0;
        $voucherMessage = '';

        if ($voucherCode) {
            $voucher = Voucher::where('code', $voucherCode)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if ($voucher) {
                // Check if voucher is applicable to this tour
                $applicableTourIds = is_array($voucher->applicable_tour_ids)
                    ? $voucher->applicable_tour_ids
                    : (json_decode($voucher->applicable_tour_ids, true) ?? []);

                if (empty($applicableTourIds) || in_array($context['tour_id'], $applicableTourIds)) {
                    if ($voucher->discount) {
                        $discount = $voucher->discount;
                    } elseif ($voucher->discount_percentage) {
                        $discount = $context['total_price'] * ($voucher->discount_percentage / 100);
                    }

                    $context['voucher_code'] = $voucherCode;
                    $context['discount'] = $discount;
                    $voucherMessage = "\n🎉 **Áp dụng voucher thành công!**\n• Giảm giá: " . number_format($discount) . " VNĐ";
                } else {
                    $voucherMessage = "\n❌ **Voucher không áp dụng cho tour này.**";
                }
            } else {
                $voucherMessage = "\n❌ **Mã voucher không hợp lệ hoặc đã hết hạn.**";
            }
        }

        $finalPrice = $context['total_price'] - $discount;
        $context['final_price'] = $finalPrice;
        $context['step'] = 'confirmation';
        $this->saveBookingContext($conversationId, $context);

        $message = "💳 **Tóm tắt đặt tour:**

🏖️ **Tour:** {$context['tour_name']}
📅 **Ngày:** " . \Carbon\Carbon::parse($context['selected_date'])->format('d/m/Y') . "
👥 **Khách:** {$context['adults']} người lớn";

        if ($context['children'] > 0) {
            $message .= " + {$context['children']} trẻ em";
        }

        $message .= "\n💰 **Chi phí:**
• Tổng tiền: " . number_format($context['total_price']) . " VNĐ";

        $message .= $voucherMessage;

        if ($discount > 0) {
            $message .= "\n• **Thành tiền: " . number_format($finalPrice) . " VNĐ**";
        }

        $message .= "\n\n✅ **Xác nhận đặt tour?**";

        return [
            'message' => $message,
            'suggestions' => [
                'Xác nhận đặt tour',
                'Sửa thông tin',
                'Hủy đặt tour',
                'Liên hệ tư vấn'
            ],
            'booking_flow' => [
                'step' => 'confirm_booking',
                'final_price' => $finalPrice
            ]
        ];
    }

    /**
     * Handle booking confirmation
     */
    private function handleBookingConfirmation($data, $conversationId)
    {
        $context = $this->getBookingContext($conversationId);
        if (!$context) {
            return [
                'message' => 'Phiên đặt tour đã hết hạn. Vui lòng bắt đầu lại.',
                'suggestions' => ['Đặt tour mới']
            ];
        }

        // Here you would integrate with your actual booking system
        // For now, we'll simulate the booking process

        $bookingReference = 'TB' . time() . rand(100, 999);

        // Clear booking context
        $this->clearBookingContext($conversationId);

        $message = "🎉 **Đặt tour thành công!**

📋 **Mã đặt tour:** {$bookingReference}
🏖️ **Tour:** {$context['tour_name']}
📅 **Ngày khởi hành:** " . \Carbon\Carbon::parse($context['selected_date'])->format('d/m/Y') . "
👥 **Số khách:** {$context['adults']} người lớn";

        if ($context['children'] > 0) {
            $message .= " + {$context['children']} trẻ em";
        }

        $message .= "\n💰 **Tổng tiền:** " . number_format($context['final_price']) . " VNĐ

📧 **Thông tin chi tiết đã được gửi qua email.**
📞 **Hotline hỗ trợ:** 1900-xxxx

**Cảm ơn bạn đã tin tưởng dịch vụ của chúng tôi!** 🙏";

        return [
            'message' => $message,
            'suggestions' => [
                'Xem chi tiết booking',
                'Đặt tour khác',
                'Chia sẻ trải nghiệm',
                'Liên hệ hỗ trợ'
            ],
            'booking_completed' => true,
            'booking_reference' => $bookingReference
        ];
    }

    /**
     * Save booking context to cache
     */
    private function saveBookingContext($conversationId, $context)
    {
        Cache::put("booking_context_{$conversationId}", $context, 1800); // 30 minutes
    }

    /**
     * Get booking context from cache
     */
    private function getBookingContext($conversationId)
    {
        return Cache::get("booking_context_{$conversationId}");
    }

    /**
     * Clear booking context
     */
    private function clearBookingContext($conversationId)
    {
        Cache::forget("booking_context_{$conversationId}");
    }
}
