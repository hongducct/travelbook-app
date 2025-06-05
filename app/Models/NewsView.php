<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsView extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_id',
        'user_id',
        'admin_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
        'device_type',
        'browser',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function news()
    {
        return $this->belongsTo(News::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
