<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class NewsResource extends JsonResource
{
    public function toArray($request)
    {
        $vendorName = null;

        // If the author is an admin, construct vendor_name from admin's first_name and last_name
        if ($this->author_type === 'admin' && $this->admin) {
            $vendorName = trim(($this->admin->first_name ?? '') . ' ' . ($this->admin->last_name ?? ''));
        } elseif ($this->author_type === 'vendor' && $this->vendor) {
            // For vendors, use the company_name
            $vendorName = $this->vendor->company_name;
        }
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'slug' => $this->slug,
            'tags' => $this->tags,
            'published_at' => $this->published_at?->toISOString(),
            // 'published_at' => $this->when($this->published_at, fn() => $this->published_at instanceof \Carbon\Carbon
            // ? $this->published_at->format('Y-m-d H:i:s')
            // : $this->published_at),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            // 'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            // 'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'blog_status' => $this->blog_status,
            'is_featured' => $this->is_featured,
            'view_count' => $this->view_count,
            'reading_time' => $this->reading_time,
            'last_viewed_at' => $this->last_viewed_at?->toISOString(),

            // SEO fields
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,

            // Travel specific fields
            'destination' => $this->destination,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'travel_season' => $this->travel_season,
            'travel_tips' => $this->travel_tips,
            'estimated_budget' => $this->estimated_budget,
            'duration_days' => $this->duration_days,

            // Author information
            'author_type' => $this->author_type,
            'author_name' => $this->author_name,
            'admin_id' => $this->admin_id,
            'vendor_id' => $this->vendor_id,
            'admin_name' => $this->admin?->name,
            'vendor_name' => $vendorName, // Use computed vendor_name
            // Category
            'category_id' => $this->category_id,
            'category' => $this->when($this->category, [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
                'color' => $this->category?->color,
                'icon' => $this->category?->icon,
            ]),

            // Computed fields
            'average_rating' => $this->when(isset($this->average_rating), $this->average_rating),
            // 'review_count' => $this->when(isset($this->review_count), $this->review_count),
            // 'reviews' => $this->when(isset($this->reviews), $this->reviews),


            // 'admin_name' => $this->admin ? $this->admin->name : null, // Keep existing admin_name field


            // 'average_rating' => $this->average_rating ?? 0,
            'review_count' => $this->review_count ?? 0,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
