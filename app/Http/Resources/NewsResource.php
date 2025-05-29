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
            'author_type' => $this->author_type,
            'admin_id' => $this->admin_id,
            'admin_name' => $this->admin ? $this->admin->name : null, // Keep existing admin_name field
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $vendorName, // Use computed vendor_name
            'title' => $this->title,
            'content' => $this->content,
            'image_url' => $this->image,
            'average_rating' => $this->average_rating ?? 0,
            'review_count' => $this->review_count ?? 0,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'published_at' => $this->when($this->published_at, fn() => $this->published_at instanceof \Carbon\Carbon
                ? $this->published_at->format('Y-m-d H:i:s')
                : $this->published_at),
            'blog_status' => $this->blog_status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
