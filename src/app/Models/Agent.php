<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'login_id',
    'agt_name',
    'id_number',
    'email',
    'phone_number',
    'password',
    'agt_status',
    'address',
    'city',
    'state',
    'discount_percentage',
    'profile_picture',
    'last_login_at',
    'last_login_ip',
    'total_sale',
])]
#[Hidden(['password', 'remember_token'])]
class Agent extends Authenticatable
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory, Notifiable;

    public const StatusActive = 'active';

    public const StatusInactive = 'inactive';

    public const StatusBlocked = 'blocked';

    public const StatusSuspended = 'suspended';

    protected $table = 'usr_agent';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'agt_status' => self::StatusActive,
        'discount_percentage' => 0,
        'total_sale' => 0,
    ];

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::StatusActive => 'Active',
            self::StatusInactive => 'Inactive',
            self::StatusBlocked => 'Blocked',
            self::StatusSuspended => 'Suspended',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function businessSites(): BelongsToMany
    {
        return $this->belongsToMany(BusinessSite::class)->withTimestamps();
    }

    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function recordedPosSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'recorded_by_agent_id');
    }

    public function attributedPosSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'sales_agent_id');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->where('login_id', 'like', "%{$search}%")
                ->orWhere('agt_name', 'like', "%{$search}%")
                ->orWhere('id_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->agt_name)) ?: [];

        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'AG';
    }

    public function loginInfoMessage(?string $plainPassword = null): string
    {
        return implode(PHP_EOL, [
            'Agent Login Info',
            'Name: '.$this->agt_name,
            'Login ID: '.$this->login_id,
            'Password: '.($plainPassword ?: '[set or reset password before sharing]'),
        ]);
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $phone = $this->whatsappPhone();

        if ($phone === null) {
            return null;
        }

        $url = 'https://wa.me/'.$phone;

        if ($message !== null && $message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    public function whatsappPhone(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->phone_number);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            return '60'.substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:1',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'total_sale' => 'decimal:2',
        ];
    }
}
