<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layanan Machine Learning (FastAPI)
    |--------------------------------------------------------------------------
    | Semua nilai dibaca dari .env. Jangan memanggil env() di luar berkas config,
    | karena nilainya tidak tersedia setelah `php artisan config:cache`.
    */
    'ml' => [
        'base_url' => env('ML_API_URL', 'http://127.0.0.1:8000'),
        'timeout' => (float) env('ML_API_TIMEOUT', 10),
        'connect_timeout' => (float) env('ML_API_CONNECT_TIMEOUT', 3),
        'internal_token' => env('ML_INTERNAL_TOKEN'),
    ],

    'scan' => [
        'max_url_length' => 2048,
    ],

];