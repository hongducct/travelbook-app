<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount',
        'discount_percentage',
        'start_date',
        'end_date',
        'usage_limit',
        'applicable_tour_ids',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applicable_tour_ids' => 'array',
        'discount' => 'decimal:2',
    ];

    protected $appends = ['usage_count', 'is_active', 'is_expired'];

    // Relationships
    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'voucher_tour_ids', 'voucher_id', 'tour_id');
    }

    // Accessors
    public function getUsageCountAttribute()
    {
        return $this->usages()->count();
    }

    public function getIsActiveAttribute()
    {
        $now = now();
        return $now->greaterThanOrEqualTo($this->start_date) &&
            $now->lessThanOrEqualTo($this->end_date);
    }

    public function getIsExpiredAttribute()
    {
        return now()->greaterThan($this->end_date);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereRaw('usage_limit > (SELECT COUNT(*) FROM voucher_usages WHERE voucher_id = vouchers.id)');
            });
    }

    // Methods
    public function canBeUsed()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isApplicableToTour($tourId)
    {
        if (!$this->applicable_tour_ids) {
            return true; // Apply to all tours if no specific tours set
        }

        return in_array($tourId, $this->applicable_tour_ids);
    }

    public function calculateDiscount($originalPrice)
    {
        if ($this->discount) {
            return min($this->discount, $originalPrice);
        }

        if ($this->discount_percentage) {
            return ($originalPrice * $this->discount_percentage) / 100;
        }

        return 0;
    }
}
