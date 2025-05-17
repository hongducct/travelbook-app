<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'company_name', 'business_license', 'package_id', 'package_expiry_date'];

    /**
     * Get the news articles for the vendor.
     */
    public function news()
    {
        return $this->hasMany(News::class);
    }
    /**
     * Get the tours for the vendor.
     */
    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
    /**
     * Get the bookings for the vendor.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    /**
     * Get the payments for the vendor.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    /**
     * Get the reviews for the vendor.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the user that owns the vendor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package that owns the vendor.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}