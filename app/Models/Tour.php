<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'days',
        'nights',
        'travel_type_id',
        'location_id',
        'vendor_id',
    ];

    protected $casts = [];

    public function travelType()
    {
        return $this->belongsTo(TravelType::class, 'travel_type_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function images()
    {
        return $this->hasMany(TourImage::class);
    }

    public function availabilities()
    {
        return $this->hasMany(TourAvailability::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'feature_tour', 'tour_id', 'feature_id');
    }

    /**
     * Convert the model instance to an array.
     */
    public function toArray()
    {
        $attributes = parent::toArray();
        $attributes['features'] = $this->features->pluck('name')->toArray();
        return $attributes;
    }
}