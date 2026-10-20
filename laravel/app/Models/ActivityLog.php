<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Model Eloquent untuk tabel `activity_logs`.
 * Mencatat seluruh jejak audit sistem: login, logout, pemindaian, dan manajemen model.
 */
class ActivityLog extends Model
{
    use HasFactory;

    public const LOGIN = 'LOGIN';
    public const LOGOUT = 'LOGOUT';
    public const SCAN_URL = 'SCAN_URL';
    public const UPLOAD_MODEL = 'UPLOAD_MODEL';
    public const ACTIVATE_MODEL = 'ACTIVATE_MODEL';
    public const ROLLBACK_MODEL = 'ROLLBACK_MODEL';
    public const ARCHIVE_MODEL = 'ARCHIVE_MODEL';
    public const UPDATE_PROFILE = 'UPDATE_PROFILE';
    public const CHANGE_PASSWORD = 'CHANGE_PASSWORD';
    public const DELETE_SCAN = 'DELETE_SCAN';

    protected $fillable = [
        'user_id',
        'activity',
        'description',
        'ip_address',
    ];

    /**
     * User yang bersangkutan (jika ada).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper method statis untuk mencatat log aktivitas dengan IP otomatis.
     */
    public static function record(string $activity, ?string $description = null, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? Auth::id(),
            'activity' => $activity,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }
}
