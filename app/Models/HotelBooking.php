<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class HotelBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'hotel_data',
        'guest_data',
        'contact_data',
        'search_params',
        'preferences',
        'payment_method',
        'total_amount',
        'currency',
        'nights',
        'status',
        'payment_status',
        'payment_transaction_id',
        'payment_details',
        'notes',
        'booking_date'
    ];

    protected $casts = [
        'hotel_data' => 'array',
        'guest_data' => 'array',
        'contact_data' => 'array',
        'search_params' => 'array',
        'preferences' => 'array',
        'total_amount' => 'decimal:2',
        'booking_date' => 'datetime'
    ];

    /**
     * Get the guest's full name
     */
    protected function guestFullName(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->guest_data['firstName'] ?? '') . ' ' . ($this->guest_data['lastName'] ?? '')
        );
    }

    /**
     * Get the contact's full name
     */
    protected function contactFullName(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->contact_data['firstName'] ?? '') . ' ' . ($this->contact_data['lastName'] ?? '')
        );
    }

    /**
     * Get formatted total amount
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => number_format($this->total_amount, 0, ',', '.') . ' ' . $this->currency
        );
    }

    /**
     * Get check-in date
     */
    protected function checkInDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $searchParams = $this->search_params;
                if (isset($searchParams['checkInDate'])) {
                    return \Carbon\Carbon::parse($searchParams['checkInDate']);
                }
                return null;
            }
        );
    }

    /**
     * Get check-out date
     */
    protected function checkOutDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $searchParams = $this->search_params;
                if (isset($searchParams['checkOutDate'])) {
                    return \Carbon\Carbon::parse($searchParams['checkOutDate']);
                }
                return null;
            }
        );
    }

    /**
     * Get hotel name
     */
    protected function hotelName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hotel_data['name'] ?? 'Unknown Hotel'
        );
    }

    /**
     * Get city name
     */
    protected function cityName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $searchParams = $this->search_params;
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

                return $cities[$cityCode] ?? $cityCode;
            }
        );
    }

    /**
     * Get room details
     */
    protected function roomDetails(): Attribute
    {
        return Attribute::make(
            get: function () {
                $searchParams = $this->search_params;
                $preferences = $this->preferences;

                $rooms = $searchParams['rooms'] ?? 1;
                $adults = $searchParams['adults'] ?? 2;
                $roomType = $preferences['roomType'] ?? 'standard';

                return "{$rooms} phòng, {$adults} khách, {$roomType}";
            }
        );
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by payment status
     */
    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope for recent bookings
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('booking_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for upcoming check-ins
     */
    public function scopeUpcomingCheckIns($query, $days = 7)
    {
        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(search_params, '$.checkInDate')) BETWEEN ? AND ?", [
            now()->format('Y-m-d'),
            now()->addDays($days)->format('Y-m-d')
        ]);
    }
}
