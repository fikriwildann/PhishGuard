<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent untuk tabel `models`.
 * Mengelola metadata model Machine Learning, status aktif/inaktif/arsip, dan versinya.
 */
class MlModel extends Model
{
    use HasFactory;

    protected $table = 'models';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'version',
        'algorithm',
        'bundle_name',
        'file_path',
        'file_hash',
        'dataset_name',
        'feature_count',
        'feature_names',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'roc_auc',
        'description',
        'status',
        'uploaded_by',
        'activated_by',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'feature_names' => 'array',
            'feature_count' => 'integer',
            'accuracy' => 'float',
            'precision' => 'float',
            'recall' => 'float',
            'f1_score' => 'float',
            'roc_auc' => 'float',
            'activated_at' => 'datetime',
        ];
    }

    /**
     * Admin yang mengunggah berkas model.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Admin yang mengaktifkan model ini menjadi active model.
     */
    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Seluruh riwayat scan yang dieksekusi dengan model ini.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class, 'model_id');
    }

    /**
     * Log aktivasi saat model ini menjadi model baru.
     */
    public function activationLogs(): HasMany
    {
        return $this->hasMany(ModelActivationLog::class, 'new_model_id');
    }

    /**
     * Scope untuk model yang sedang aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope untuk model tidak aktif.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope untuk model yang diarsipkan.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Mengambil satu-satunya model yang sedang aktif untuk produksi.
     */
    public static function getActiveModel(): ?self
    {
        return static::active()->latest('activated_at')->first();
    }
}
