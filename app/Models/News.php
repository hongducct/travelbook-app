<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_type',
        'admin_id',
        'vendor_id',
        'category_id',
        'title',
        'content',
        'excerpt',
        'tags',
        'image',
        'published_at',
        'blog_status',
        'is_featured',
        'view_count',
        'reading_time',
        'last_viewed_at',
        'meta_description',
        'meta_keywords',
        'slug',
        'destination',
        'latitude',
        'longitude',
        'travel_season',
        'travel_tips',
        'estimated_budget',
        'duration_days',
    ];

    protected $casts = [
        'tags' => 'array',
        'travel_tips' => 'array',
        'published_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'is_featured' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'estimated_budget' => 'decimal:2',
    ];

    protected $appends = ['image_url', 'author_name'];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    // Travel season constants
    const SEASON_SPRING = 'spring';
    const SEASON_SUMMER = 'summer';
    const SEASON_AUTUMN = 'autumn';
    const SEASON_WINTER = 'winter';
    const SEASON_ALL_YEAR = 'all_year';

    /**
     * Relationships
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    public function views()
    {
        return $this->hasMany(NewsView::class);
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Scopes
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('blog_status', $status);
    }

    public function scopePublished($query)
    {
        return $query->where('blog_status', self::STATUS_PUBLISHED);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByDestination($query, $destination)
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    public function scopeBySeason($query, $season)
    {
        return $query->where('travel_season', $season);
    }

    public function scopeByTags($query, $tags)
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', trim($tag));
        }

        return $query;
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")
                ->orWhere('destination', 'like', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            // If it's already a full URL, return as is
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            // Otherwise, construct the full URL
            return asset('storage/' . $this->image);
        }

        return 'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747734951/o3jn0zgirfrmxttw5wkd.jpg';
    }

    public function getAuthorNameAttribute()
    {
        if ($this->author_type === 'admin' && $this->admin) {
            return $this->admin->name ?? $this->admin->username ?? 'Admin';
        } elseif ($this->author_type === 'vendor' && $this->vendor) {
            return $this->vendor->user->name ?? $this->vendor->user->username ?? $this->vendor->name ?? 'Vendor';
        }

        return 'Unknown Author';
    }

    /**
     * Status check methods
     */
    public function isDraft()
    {
        return $this->blog_status === self::STATUS_DRAFT;
    }

    public function isPending()
    {
        return $this->blog_status === self::STATUS_PENDING;
    }

    public function isRejected()
    {
        return $this->blog_status === self::STATUS_REJECTED;
    }

    public function isPublished()
    {
        return $this->blog_status === self::STATUS_PUBLISHED;
    }

    public function isArchived()
    {
        return $this->blog_status === self::STATUS_ARCHIVED;
    }

    /**
     * Utility methods
     */
    public function incrementViewCount($ipAddress = null, $userAgent = null, $userId = null, $adminId = null)
    {
        // Create view record
        $this->views()->create([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'referer' => request()->header('referer'),
            'viewed_at' => now(),
        ]);

        // Update view count and last viewed
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    public function generateSlug()
    {
        $slug = Str::slug($this->title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function calculateReadingTime()
    {
        if (!$this->content) {
            return 1;
        }

        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / 200); // 200 words per minute

        return max(1, $readingTime);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = $news->generateSlug();
            }

            if (empty($news->reading_time)) {
                $news->reading_time = $news->calculateReadingTime();
            }

            if (empty($news->excerpt) && $news->content) {
                $news->excerpt = Str::limit(strip_tags($news->content), 200);
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('title') && empty($news->slug)) {
                $news->slug = $news->generateSlug();
            }

            if ($news->isDirty('content')) {
                $news->reading_time = $news->calculateReadingTime();

                if (empty($news->excerpt)) {
                    $news->excerpt = Str::limit(strip_tags($news->content), 200);
                }
            }
        });
    }
}
