<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'day',
        'title',
        'description',
        'activities',
        'accommodation',
        'meals',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'activities' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the tour that owns the itinerary.
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get the images for the itinerary.
     */
    public function images()
    {
        return $this->hasMany(ItineraryImage::class);
    }
}