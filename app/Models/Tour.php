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

    /**
     * Get the travel type associated with the tour.
     */
    public function travelType()
    {
        return $this->belongsTo(TravelType::class, 'travel_type_id');
    }

    /**
     * Get the location associated with the tour.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the vendor associated with the tour.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the images for the tour.
     */
    public function images()
    {
        return $this->hasMany(TourImage::class);
    }

    /**
     * Get the primary image for the tour.
     */
    public function primaryImage()
    {
        return $this->hasOne(TourImage::class)->where('is_primary', true);
    }

    /**
     * Get the availabilities for the tour.
     */
    public function availabilities()
    {
        return $this->hasMany(TourAvailability::class);
    }

    /**
     * Get the prices for the tour.
     */
    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    /**
     * Get the features for the tour.
     */
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'feature_tour', 'tour_id', 'feature_id');
    }

    /**
     * Get the reviews for the tour.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
    /**
     * Calculate the average rating of the tour.
     *
     * @return float|null
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->where('status', 'approved')->avg('rating');
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