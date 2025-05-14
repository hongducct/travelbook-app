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
