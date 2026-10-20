<?php

namespace App\Services;

use App\Exceptions\MlServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Penghubung Laravel ke FastAPI. Hanya memakai HTTP (Http::), tidak pernah menjalankan Python
 * lewat shell. Semua kegagalan diubah menjadi MlServiceException dengan pesan yang aman untuk
 * pengguna; rincian teknis hanya masuk ke log server.
 */
class PhishGuardService
{
    /**
     * Minta prediksi untuk satu URL.
     *
     * @throws MlServiceException
     */
    public function predict(string $url): PredictionResult
    {
        $url = trim($url);
        if ($url === '' || mb_strlen($url, 'UTF-8') > (int) config('phishguard.scan.max_url_length', 2048)) {
            throw MlServiceException::invalidUrl();
        }

        try {
            $response = $this->client()->post('/predict', ['url' => $url]);
        } catch (ConnectionException $e) {
            Log::warning('FastAPI tidak dapat dihubungi saat prediksi.', ['error' => $e->getMessage()]);
            throw MlServiceException::unavailable($e);
        }

        return $this->parsePrediction($response, $url);
    }

    /**
     * Status singkat layanan ML. Tidak pernah melempar error.
     *
     * @return array{reachable: bool, model_loaded: bool, model_name: ?string, model_version: ?string}
     */
    public function health(): array
    {
        $down = ['reachable' => false, 'model_loaded' => false, 'model_name' => null, 'model_version' => null];

        try {
            $response = $this->client()->timeout(3)->get('/health');
        } catch (ConnectionException) {
            return $down;
        }

        $data = $response->json();
        if ($response->status() !== 200 || ! is_array($data) || ! is_bool($data['model_loaded'] ?? null)) {
            return $down;
        }

        return [
            'reachable' => true,
            'model_loaded' => $data['model_loaded'],
            'model_name' => is_string($data['model_name'] ?? null) ? $data['model_name'] : null,
            'model_version' => is_string($data['model_version'] ?? null) ? $data['model_version'] : null,
        ];
    }

    /**
     * Memvalidasi bundle model ke FastAPI tanpa mengubah active model.
     *
     * @return array{ok: bool, errors: array, warnings: array, model: ?array}
     */
    public function validateModel(string $bundleName, ?int $modelId = null): array
    {
        $token = config('phishguard.ml.internal_token');
        try {
            $response = $this->client()
                ->withHeader('X-Internal-Token', $token)
                ->timeout(15)
                ->post('/internal/validate-model', [
                    'bundle_name' => $bundleName,
                    'model_id' => $modelId,
                ]);

            return $response->json() ?? ['ok' => false, 'errors' => ['Respon server tidak valid']];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'errors' => ['Tidak dapat menghubungi layanan FastAPI: ' . $e->getMessage()]];
        }
    }

    /**
     * Meminta FastAPI memvalidasi dan mengaktifkan bundle model sebagai active model.
     *
     * @return array{ok: bool, message: string, errors: array}
     */
    public function reloadModel(string $bundleName, ?int $modelId = null): array
    {
        $token = config('phishguard.ml.internal_token');
        try {
            $response = $this->client()
                ->withHeader('X-Internal-Token', $token)
                ->timeout(20)
                ->post('/internal/reload-model', [
                    'bundle_name' => $bundleName,
                    'model_id' => $modelId,
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Model berhasil diaktifkan di AI Engine.', 'errors' => []];
            }

            $body = $response->json() ?? [];
            return [
                'ok' => false,
                'message' => 'Gagal mengaktifkan model di AI Engine.',
                'errors' => $body['errors'] ?? [$response->body()],
            ];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'Gagal menghubungi FastAPI.', 'errors' => [$e->getMessage()]];
        }
    }

    private function parsePrediction(Response $response, string $url): PredictionResult
    {
        $status = $response->status();

        if ($status === 422) {
            throw MlServiceException::invalidUrl();
        }
        if ($status === 503) {
            throw MlServiceException::modelUnavailable();
        }
        if ($status !== 200) {
            Log::error('Jawaban FastAPI tidak terduga.', ['status' => $status]);
            throw MlServiceException::unavailable();
        }

        $data = $response->json();
        if (! is_array($data)) {
            Log::error('Jawaban FastAPI bukan JSON objek.');
            throw MlServiceException::unavailable();
        }

        try {
            return PredictionResult::fromArray($data, $url);
        } catch (InvalidArgumentException $e) {
            Log::error('Jawaban FastAPI tidak sesuai kontrak.', ['alasan' => $e->getMessage()]);
            throw MlServiceException::unavailable($e);
        }
    }

    private function client(): PendingRequest
    {
        $config = config('phishguard.ml');

        return Http::baseUrl(rtrim((string) $config['base_url'], '/'))
            ->timeout((float) $config['timeout'])
            ->connectTimeout((float) $config['connect_timeout'])
            ->acceptJson()
            ->asJson()
            ->withUserAgent('PhishGuard-Laravel/1.0');
    }
}