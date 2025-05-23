<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'google_id',
        'first_name',
        'last_name',
        'phone_number',
        'date_of_birth',
        'description',
        'avatar',
        'address',
        'gender',
        'is_vendor',
        'user_status',
    ];

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Favorite::class)
            ->where('favoritable_type', 'App\\Models\\Tour')
            ->with('favoritable');
    }

    // Scope: chỉ user active
    public function scopeActive($query)
    {
        return $query->where('user_status', 'active');
    }

    // Scope: chỉ user inactive
    public function scopeInactive($query)
    {
        return $query->where('user_status', 'inactive');
    }

    // Scope: chỉ user banned
    public function scopeBanned($query)
    {
        return $query->where('user_status', 'banned');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
