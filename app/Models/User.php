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
        'status',
        'preferences',
        'contact_number',
        'delivery_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : asset('images/default-avatar.png');
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
