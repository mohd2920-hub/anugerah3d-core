<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'sale_number',
    'pos_session_id',
    'business_site_id',
    'recorded_by_agent_id',
    'sales_agent_id',
    'customer_name',
    'customer_phone',
    'customer_email',
    'remark',
    'payment_method',
    'payment_remark',
    'sale_picture_path',
    'sale_picture_paths',
    'payment_proof_path',
    'payment_proof_paths',
    'total_amount',
    'sold_at',
])]
class PosSale extends Model
{
    public const PaymentCash = 'cash';

    public const PaymentQr = 'qr';

    public static function paymentMethods(): array
    {
        return [
            self::PaymentCash => 'Cash',
            self::PaymentQr => 'QR',
        ];
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function businessSite(): BelongsTo
    {
        return $this->belongsTo(BusinessSite::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'recorded_by_agent_id');
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'sales_agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    /** @return array<int, string> */
    public function salePicturePaths(): array
    {
        $paths = is_array($this->sale_picture_paths) ? $this->sale_picture_paths : [];

        if ($paths === [] && is_string($this->sale_picture_path) && $this->sale_picture_path !== '') {
            $paths = [$this->sale_picture_path];
        }

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
    }

    /** @return array<int, string> */
    public function paymentProofPaths(): array
    {
        $paths = is_array($this->payment_proof_paths) ? $this->payment_proof_paths : [];

        if ($paths === [] && is_string($this->payment_proof_path) && $this->payment_proof_path !== '') {
            $paths = [$this->payment_proof_path];
        }

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
    }

    /** @return array<int, string> */
    public function salePictureUrls(): array
    {
        return array_map(fn (string $path): string => $this->pictureUrl($path), $this->salePicturePaths());
    }

    /** @return array<int, string> */
    public function paymentProofUrls(): array
    {
        return array_map(fn (string $path): string => $this->pictureUrl($path), $this->paymentProofPaths());
    }

    public function pictureUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (File::exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk($this->pictureDisk())->url($path);
    }

    private function pictureDisk(): string
    {
        $default = (string) config('filesystems.default', 'public');

        if ($default === 's3' && class_exists('League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter')) {
            return 's3';
        }

        return 'public';
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'sale_picture_paths' => 'array',
            'payment_proof_paths' => 'array',
            'sold_at' => 'datetime',
        ];
    }
}
