<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'title', 'content', 'image', 'published_at', 'blog_status',
    ];

    /**
     * Get the vendor that owns the news article.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    public function scopeStatus($query, $status)
    {
        return $query->where('blog_status', $status);
    }

    public function isDraft() { return $this->blog_status === self::STATUS_DRAFT; }
    public function isPending() { return $this->blog_status === self::STATUS_PENDING; }
    public function isRejected() { return $this->blog_status === self::STATUS_REJECTED; }
    public function isPublished() { return $this->blog_status === self::STATUS_PUBLISHED; }
    public function isArchived() { return $this->blog_status === self::STATUS_ARCHIVED; }
}