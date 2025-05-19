<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'city',
        'description',
        'image',
        'latitude',
        'longitude',
    ];
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
    
}
