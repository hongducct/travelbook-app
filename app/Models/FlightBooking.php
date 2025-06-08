<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FlightBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'flight_data',
        'passenger_data',
        'contact_data',
        'search_params',
        'preferences',
        'payment_method',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_transaction_id',
        'payment_details',
        'notes',
        'booking_date'
    ];

    protected $casts = [
        'flight_data' => 'array',
        'passenger_data' => 'array',
        'contact_data' => 'array',
        'search_params' => 'array',
        'preferences' => 'array',
        'total_amount' => 'decimal:2',
        'booking_date' => 'datetime'
    ];

    /**
     * Get the passenger's full name
     */
    protected function passengerFullName(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->passenger_data['firstName'] ?? '') . ' ' . ($this->passenger_data['lastName'] ?? '')
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
     * Get departure date from flight data
     */
    protected function departureDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $flightData = $this->flight_data;
                if (isset($flightData['itineraries'][0]['segments'][0]['departure']['at'])) {
                    return \Carbon\Carbon::parse($flightData['itineraries'][0]['segments'][0]['departure']['at']);
                }
                return null;
            }
        );
    }

    /**
     * Get airline name from flight data
     */
    protected function airlineName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $flightData = $this->flight_data;
                if (isset($flightData['itineraries'][0]['segments'][0]['carrierCode'])) {
                    $carrierCode = $flightData['itineraries'][0]['segments'][0]['carrierCode'];
                    $airlines = [
                        'VN' => 'Vietnam Airlines',
                        'VJ' => 'VietJet Air',
                        'BL' => 'Jetstar Pacific',
                        'QH' => 'Bamboo Airways',
                        'VU' => 'Vietravel Airlines'
                    ];
                    return $airlines[$carrierCode] ?? $carrierCode;
                }
                return 'Unknown Airline';
            }
        );
    }

    /**
     * Get route information
     */
    protected function route(): Attribute
    {
        return Attribute::make(
            get: function () {
                $searchParams = $this->search_params;
                return ($searchParams['originLocationCode'] ?? '') . ' → ' . ($searchParams['destinationLocationCode'] ?? '');
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
}
