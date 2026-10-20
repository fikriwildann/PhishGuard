<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Hasil prediksi dari FastAPI yang sudah divalidasi. Jawaban yang melanggar kontrak ditolak,
 * sehingga data yang tidak masuk akal tidak pernah sampai ke database atau tampilan.
 */
final readonly class PredictionResult
{
    public const PREDICTIONS = ['Legitimate', 'Phishing'];

    public const RISK_LEVELS = ['Low', 'Medium', 'High'];

    /** @param list<string> $indicators */
    public function __construct(
        public string $url,
        public string $prediction,
        public float $probability,
        public int $riskScore,
        public string $riskLevel,
        public ?int $modelId,
        public string $modelName,
        public string $modelVersion,
        public string $message,
        public array $indicators,
    ) {
    }

    public function isPhishing(): bool
    {
        return $this->prediction === 'Phishing';
    }

    /**
     * @param  array<string, mixed>  $data  Isi JSON jawaban /predict
     * @param  string  $expectedUrl  URL yang dikirim; jawaban harus menyebut URL yang sama
     *
     * @throws InvalidArgumentException jika ada field yang hilang, bertipe salah, atau tidak konsisten
     */
    public static function fromArray(array $data, string $expectedUrl): self
    {
        foreach (['url', 'prediction', 'probability', 'risk_score', 'risk_level', 'model_name', 'model_version', 'message', 'indicators'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Field '{$key}' tidak ada.");
            }
        }

        if (! is_string($data['url']) || $data['url'] !== $expectedUrl) {
            throw new InvalidArgumentException('URL pada jawaban berbeda dari URL yang dikirim.');
        }
        if (! is_string($data['prediction']) || ! in_array($data['prediction'], self::PREDICTIONS, true)) {
            throw new InvalidArgumentException("Nilai 'prediction' tidak dikenal.");
        }
        if (! (is_int($data['probability']) || is_float($data['probability'])) || $data['probability'] < 0.5 || $data['probability'] > 1) {
            throw new InvalidArgumentException("Nilai 'probability' harus antara 0,5 dan 1.");
        }
        if (! is_int($data['risk_score']) || $data['risk_score'] < 0 || $data['risk_score'] > 100) {
            throw new InvalidArgumentException("Nilai 'risk_score' harus bilangan bulat 0 sampai 100.");
        }
        if (! is_string($data['risk_level']) || ! in_array($data['risk_level'], self::RISK_LEVELS, true)) {
            throw new InvalidArgumentException("Nilai 'risk_level' tidak dikenal.");
        }

        // Konsistensi: prediksi Phishing berarti probabilitas phishing >= 0,5, begitu pula sebaliknya.
        if ($data['prediction'] === 'Phishing' && $data['risk_score'] < 50) {
            throw new InvalidArgumentException("'risk_score' tidak sesuai dengan prediksi Phishing.");
        }
        if ($data['prediction'] === 'Legitimate' && $data['risk_score'] > 50) {
            throw new InvalidArgumentException("'risk_score' tidak sesuai dengan prediksi Legitimate.");
        }

        $modelId = $data['model_id'] ?? null;
        if ($modelId !== null && (! is_int($modelId) || $modelId < 1)) {
            throw new InvalidArgumentException("Nilai 'model_id' harus bilangan bulat positif atau null.");
        }
        if (! is_string($data['model_name']) || $data['model_name'] === '' || mb_strlen($data['model_name']) > 100) {
            throw new InvalidArgumentException("Nilai 'model_name' tidak valid.");
        }
        if (! is_string($data['model_version']) || $data['model_version'] === '' || mb_strlen($data['model_version']) > 50) {
            throw new InvalidArgumentException("Nilai 'model_version' tidak valid.");
        }
        if (! is_string($data['message']) || mb_strlen($data['message']) > 500) {
            throw new InvalidArgumentException("Nilai 'message' tidak valid.");
        }

        $indicators = $data['indicators'];
        if (! is_array($indicators) || ! array_is_list($indicators) || count($indicators) > 20) {
            throw new InvalidArgumentException("Nilai 'indicators' harus daftar maksimal 20 teks.");
        }
        foreach ($indicators as $indicator) {
            if (! is_string($indicator) || mb_strlen($indicator) > 500) {
                throw new InvalidArgumentException("Isi 'indicators' harus teks maksimal 500 karakter.");
            }
        }

        return new self(
            url: $data['url'],
            prediction: $data['prediction'],
            probability: (float) $data['probability'],
            riskScore: $data['risk_score'],
            riskLevel: $data['risk_level'],
            modelId: $modelId,
            modelName: $data['model_name'],
            modelVersion: $data['model_version'],
            message: $data['message'],
            indicators: $indicators,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'prediction' => $this->prediction,
            'probability' => $this->probability,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel,
            'model_id' => $this->modelId,
            'model_name' => $this->modelName,
            'model_version' => $this->modelVersion,
            'message' => $this->message,
            'indicators' => $this->indicators,
        ];
    }
}