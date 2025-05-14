<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class NewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor ? $this->vendor->company_name : null, // Include vendor's company_name
            'title' => $this->title,
            'content' => $this->content,
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'published_at' => $this->when($this->published_at, fn() => $this->published_at instanceof \Carbon\Carbon 
                ? $this->published_at->format('Y-m-d H:i:s') 
                : $this->published_at),
            'blog_status' => $this->blog_status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Nếu muốn kèm thông tin vendor (nếu đã định quan hệ)
            // 'vendor' => new VendorResource($this->whenLoaded('vendor')),
        ];
    }
}