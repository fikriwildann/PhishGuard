<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent untuk tabel `model_activation_logs`.
 * Mencatat riwayat pergantian model aktif, aksi rollback, atau arsip oleh admin.
 */
class ModelActivationLog extends Model
{
    use HasFactory;

    public const ACTION_ACTIVATE = 'activate';
    public const ACTION_ROLLBACK = 'rollback';
    public const ACTION_DEACTIVATE = 'deactivate';
    public const ACTION_ARCHIVE = 'archive';

    protected $fillable = [
        'previous_model_id',
        'new_model_id',
        'action',
        'admin_id',
        'reason',
    ];

    /**
     * Model yang sebelumnya aktif sebelum diganti/rollback.
     */
    public function previousModel(): BelongsTo
    {
        return $this->belongsTo(MlModel::class, 'previous_model_id');
    }

    /**
     * Model baru yang diaktifkan.
     */
    public function newModel(): BelongsTo
    {
        return $this->belongsTo(MlModel::class, 'new_model_id');
    }

    /**
     * Administrator yang melakukan aksi aktivasi/rollback.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
