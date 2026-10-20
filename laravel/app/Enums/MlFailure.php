<?php

namespace App\Enums;

/**
 * Alasan kegagalan pemanggilan layanan ML beserta pesan yang aman ditampilkan ke pengguna.
 */
enum MlFailure: string
{
    case Unavailable = 'unavailable';
    case ModelUnavailable = 'model_unavailable';
    case InvalidUrl = 'invalid_url';

    public function message(): string
    {
        return match ($this) {
            self::Unavailable => 'Sistem deteksi sedang tidak tersedia.',
            self::ModelUnavailable => 'Model deteksi tidak dapat digunakan.',
            self::InvalidUrl => 'URL tidak valid.',
        };
    }
}