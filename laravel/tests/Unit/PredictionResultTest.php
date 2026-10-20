<?php

namespace Tests\Unit;

use App\Services\PredictionResult;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PredictionResultTest extends TestCase
{
    private const URL = 'https://www.example.com/path?a=1';

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        $data = [
            'url' => self::URL,
            'prediction' => 'Phishing',
            'probability' => 0.9123,
            'risk_score' => 91,
            'risk_level' => 'High',
            'model_id' => null,
            'model_name' => 'PhishGuard XGBoost',
            'model_version' => '1.1',
            'message' => 'URL terdeteksi sebagai Phishing berdasarkan model.',
            'indicators' => ['URL tidak memakai HTTPS.'],
        ];

        foreach ($override as $key => $value) {
            if ($value === '__hapus__') {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function test_jawaban_valid_menjadi_objek(): void
    {
        $result = PredictionResult::fromArray($this->payload(), self::URL);

        $this->assertSame('Phishing', $result->prediction);
        $this->assertTrue($result->isPhishing());
        $this->assertSame(0.9123, $result->probability);
        $this->assertSame(91, $result->riskScore);
        $this->assertSame('High', $result->riskLevel);
        $this->assertNull($result->modelId);
        $this->assertSame('PhishGuard XGBoost', $result->modelName);
        $this->assertSame('1.1', $result->modelVersion);
        $this->assertSame(['URL tidak memakai HTTPS.'], $result->indicators);
        $this->assertSame(self::URL, $result->toArray()['url']);
    }

    public function test_probabilitas_bilangan_bulat_diterima(): void
    {
        $result = PredictionResult::fromArray($this->payload(['probability' => 1, 'risk_score' => 100]), self::URL);

        $this->assertSame(1.0, $result->probability);
    }

    public function test_legitimate_dengan_model_id(): void
    {
        $result = PredictionResult::fromArray($this->payload([
            'prediction' => 'Legitimate', 'probability' => 0.97, 'risk_score' => 3, 'risk_level' => 'Low', 'model_id' => 4,
        ]), self::URL);

        $this->assertFalse($result->isPhishing());
        $this->assertSame(4, $result->modelId);
    }

    public function test_batas_konsistensi_risk_score_50_diterima(): void
    {
        $phishing = PredictionResult::fromArray($this->payload(['risk_score' => 50]), self::URL);
        $legitimate = PredictionResult::fromArray($this->payload([
            'prediction' => 'Legitimate', 'probability' => 0.5, 'risk_score' => 50, 'risk_level' => 'Medium',
        ]), self::URL);

        $this->assertSame(50, $phishing->riskScore);
        $this->assertSame(50, $legitimate->riskScore);
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function jawabanTidakValid(): array
    {
        return [
            'field url hilang' => [['url' => '__hapus__']],
            'field message hilang' => [['message' => '__hapus__']],
            'field indicators hilang' => [['indicators' => '__hapus__']],
            'url berbeda' => [['url' => 'https://lain.example.org/']],
            'prediksi tidak dikenal' => [['prediction' => 'Maybe']],
            'prediksi bukan teks' => [['prediction' => 1]],
            'probabilitas di atas 1' => [['probability' => 1.5]],
            'probabilitas di bawah 0,5' => [['probability' => 0.4]],
            'probabilitas teks' => [['probability' => '0.9']],
            'risk_score di atas 100' => [['risk_score' => 101]],
            'risk_score negatif' => [['risk_score' => -1]],
            'risk_score pecahan' => [['risk_score' => 91.5]],
            'phishing tetapi risk_score rendah' => [['risk_score' => 30]],
            'legitimate tetapi risk_score tinggi' => [['prediction' => 'Legitimate', 'risk_score' => 80]],
            'risk_level tidak dikenal' => [['risk_level' => 'Extreme']],
            'model_id nol' => [['model_id' => 0]],
            'model_id teks' => [['model_id' => '3']],
            'model_name kosong' => [['model_name' => '']],
            'model_name terlalu panjang' => [['model_name' => str_repeat('a', 101)]],
            'model_version kosong' => [['model_version' => '']],
            'message terlalu panjang' => [['message' => str_repeat('a', 501)]],
            'indicators bukan array' => [['indicators' => 'bukan array']],
            'indicators berisi angka' => [['indicators' => [1, 2]]],
            'indicators bukan daftar' => [['indicators' => ['a' => 'b']]],
            'indicators terlalu banyak' => [['indicators' => array_fill(0, 21, 'x')]],
            'indicator terlalu panjang' => [['indicators' => [str_repeat('a', 501)]]],
        ];
    }

    /** @param array<string, mixed> $override */
    #[DataProvider('jawabanTidakValid')]
    public function test_jawaban_yang_melanggar_kontrak_ditolak(array $override): void
    {
        $this->expectException(InvalidArgumentException::class);

        PredictionResult::fromArray($this->payload($override), self::URL);
    }
}