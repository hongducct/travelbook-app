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