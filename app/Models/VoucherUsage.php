<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'booking_id',
        'user_id',
        'discount_applied',
    ];
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
