<?php

namespace App\Console\Commands;

use App\Exceptions\MlServiceException;
use App\Services\PhishGuardService;
use Illuminate\Console\Command;

class PhishGuardPing extends Command
{
    protected $signature = 'phishguard:ping {--url= : URL contoh untuk diprediksi (harus diawali http:// atau https://)}';

    protected $description = 'Memeriksa koneksi Laravel ke FastAPI dan, jika diberi --url, mencoba satu prediksi';

    public function handle(PhishGuardService $service): int
    {
        $health = $service->health();

        $this->table(['Item', 'Nilai'], [
            ['Alamat FastAPI', (string) config('phishguard.ml.base_url')],
            ['FastAPI terjangkau', $health['reachable'] ? 'ya' : 'tidak'],
            ['Model aktif dimuat', $health['model_loaded'] ? 'ya' : 'tidak'],
            ['Nama model', $health['model_name'] ?? '-'],
            ['Versi model', $health['model_version'] ?? '-'],
        ]);

        if (! $health['reachable']) {
            $this->error('Sistem deteksi sedang tidak tersedia.');

            return self::FAILURE;
        }
        if (! $health['model_loaded']) {
            $this->warn('FastAPI hidup, tetapi belum ada model aktif.');
        }

        $url = $this->option('url');
        if (! is_string($url) || $url === '') {
            return self::SUCCESS;
        }

        try {
            $result = $service->predict($url);
        } catch (MlServiceException $e) {
            $this->error($e->userMessage());

            return self::FAILURE;
        }

        $this->table(['Hasil', 'Nilai'], [
            ['Prediksi', $result->prediction],
            ['Probabilitas', (string) $result->probability],
            ['Risk score', (string) $result->riskScore],
            ['Risk level', $result->riskLevel],
            ['Model', $result->modelName.' v'.$result->modelVersion],
            ['Indikator', implode("\n", $result->indicators)],
        ]);

        return self::SUCCESS;
    }
}