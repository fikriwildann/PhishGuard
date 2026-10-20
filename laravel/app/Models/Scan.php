<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent untuk tabel `scans`.
 * Menyimpan setiap riwayat analisis URL, skor risiko, dan metadata model yang digunakan saat scan.
 */
class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'prediction',
        'probability',
        'risk_score',
        'risk_level',
        'model_id',
        'model_name',
        'model_version',
        'message',
        'indicators',
    ];

    protected function casts(): array
    {
        return [
            'probability' => 'float',
            'risk_score' => 'integer',
            'indicators' => 'array',
        ];
    }

    /**
     * User pemilik riwayat scan ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Model ML yang digunakan saat scan dilakukan.
     */
    public function mlModel(): BelongsTo
    {
        return $this->belongsTo(MlModel::class, 'model_id');
    }

    /**
     * Scope untuk hasil deteksi phishing.
     */
    public function scopePhishing(Builder $query): Builder
    {
        return $query->where('prediction', 'Phishing');
    }

    /**
     * Scope untuk hasil deteksi legitimate (aman).
     */
    public function scopeLegitimate(Builder $query): Builder
    {
        return $query->where('prediction', 'Legitimate');
    }

    /**
     * Scope berdasarkan ID pengguna.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isPhishing(): bool
    {
        return $this->prediction === 'Phishing';
    }

    public function isLegitimate(): bool
    {
        return $this->prediction === 'Legitimate';
    }
}
