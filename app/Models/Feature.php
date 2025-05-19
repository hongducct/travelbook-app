<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'feature_tour', 'feature_id', 'tour_id');
    }
}