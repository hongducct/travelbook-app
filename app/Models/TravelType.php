<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function tours()
    {
        return $this->hasMany(Tour::class, 'travel_type_id');
    }
}