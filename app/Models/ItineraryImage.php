<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_id',
        'image_path',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
