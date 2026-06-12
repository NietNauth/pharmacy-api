<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password_hash',
        'role',
        'is_verified',
        'is_active',
        'branch_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'role' => UserRole::class,
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function hasVerifiedEmail()
    {
        return (bool) $this->is_verified;
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'is_verified' => true,
        ])->save();
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailVi);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordVi($token));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function reviewedPrescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'reviewed_by');
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function qas(): HasMany
    {
        return $this->hasMany(ProductQa::class);
    }



    public function chatbotSessions(): HasMany
    {
        return $this->hasMany(ChatbotSession::class);
    }




    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function getStaff($branchId = null)
    {
        $query = self::whereIn('role', [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Pharmacist]);
        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('role', \App\Enums\UserRole::Admin);
            });
        }
        return $query->get();
    }

}
