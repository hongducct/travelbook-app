<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // protected $fillable = [
    //     'user_id', 'bookable_id', 'bookable_type', 'start_date', 'end_date',
    //     'number_of_guests_adults', 'number_of_children', 'total_price', 'status'
    // ];

    protected $fillable = [
        'user_id',
        'bookable_id',
        'bookable_type',
        'start_date',
        'end_date',
        'number_of_guests_adults',
        'number_of_children',
        'total_price',
        'status',
        'voucher_id',
        'special_requests',
        'contact_phone',
        'payment_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookable()
    {
        return $this->morphTo();
    }
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    // voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
