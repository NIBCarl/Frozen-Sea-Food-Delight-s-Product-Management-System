<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Enums\UserStatus;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'avatar',
        'avatar_url',
        'status',
        'preferences',
        'contact_number',
        'delivery_address',
        'otp_code',
        'otp_expires_at',
        'google_id',
        'profile_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expires_at',
        'google_id',
    ];

    protected $appends = [
        'avatar_display_url',
        'is_google_user',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'preferences' => 'array',
            'otp_expires_at' => 'datetime',
            'profile_completed' => 'boolean',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function getAvatarDisplayUrlAttribute(): string
    {
        // Priority: uploaded avatar > Google avatar > default
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        if ($this->avatar_url) {
            return $this->avatar_url;
        }
        return asset('images/default-avatar.png');
    }

    public function getIsGoogleUserAttribute(): bool
    {
        return !empty($this->google_id);
    }

    /**
     * Check if user signed up via Google OAuth
     */
    public function isGoogleUser(): bool
    {
        return !empty($this->google_id);
    }

    /**
     * Check if user needs to complete onboarding
     */
    public function needsOnboarding(): bool
    {
        return !$this->profile_completed || !$this->username || !$this->contact_number;
    }

    /**
     * Get all orders placed by this user (customer)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all deliveries assigned to this user (delivery personnel)
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'delivery_personnel_id');
    }

    /**
     * Get all shipments created by this user (supplier)
     */
    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'supplier_id');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is supplier
     */
    public function isSupplier(): bool
    {
        return $this->hasRole('supplier');
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    /**
     * Check if user is delivery personnel
     */
    public function isDeliveryPersonnel(): bool
    {
        return $this->hasRole('delivery_personnel');
    }
}
