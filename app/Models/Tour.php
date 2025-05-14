<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'name',
        'description',
        'days',
        'nights',
        'category',
        'features',
    ];

    protected $casts = ['features' => 'array'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function images()
    {
        return $this->hasMany(TourImage::class);
    }
    public  function availabilities(){
        return $this->hasMany(TourAvailability::class);
    }
}