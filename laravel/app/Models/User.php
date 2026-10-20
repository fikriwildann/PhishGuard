<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
        ];
    }

    /**
     * Cek apakah user memiliki role admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user memiliki role user biasa.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Relasi ke riwayat scan URL yang dilakukan user ini.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Relasi ke model ML yang diunggah oleh user ini (jika admin).
     */
    public function uploadedModels(): HasMany
    {
        return $this->hasMany(MlModel::class, 'uploaded_by');
    }

    /**
     * Relasi ke model ML yang diaktifkan oleh user ini (jika admin).
     */
    public function activatedModels(): HasMany
    {
        return $this->hasMany(MlModel::class, 'activated_by');
    }

    /**
     * Relasi ke log aktivitas yang dicatat untuk user ini.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
