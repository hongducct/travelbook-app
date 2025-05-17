<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'date',
        'max_guests',
        'available_slots',
        'is_active',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
