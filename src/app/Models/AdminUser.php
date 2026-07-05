<?php

namespace App\Models;

use Database\Factories\AdminUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'status', 'email_verified_at', 'last_login_at', 'last_login_ip'])]
#[Hidden(['password', 'remember_token'])]
class AdminUser extends Authenticatable
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory, Notifiable;

    public const StatusActive = 'active';

    public const StatusInactive = 'inactive';

    public const RoleAdmin = 'admin';

    public const RoleSuperAdmin = 'super_admin';

    protected $table = 'usr_admin';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::RoleAdmin,
        'status' => self::StatusActive,
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::StatusActive);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
