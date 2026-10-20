<?php

namespace Tests\Feature;

use App\Enums\MlFailure;
use App\Exceptions\MlServiceException;
use App\Services\PhishGuardService;
use App\Services\PredictionResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhishGuardServiceTest extends TestCase
{
    private const URL = 'https://www.example.com/path?a=1';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('phishguard.ml.base_url', 'http://127.0.0.1:8000');
        config()->set('phishguard.ml.timeout', 10);
        config()->set('phishguard.ml.connect_timeout', 3);
    }

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        $data = array_merge([
            'url' => self::URL,
            'prediction' => 'Legitimate',
            'probability' => 0.9725,
            'risk_score' => 3,
            'risk_level' => 'Low',
            'model_id' => 2,
            'model_name' => 'PhishGuard XGBoost',
            'model_version' => '1.1',
            'message' => 'URL terdeteksi sebagai Legitimate berdasarkan model.',
            'indicators' => ['Tidak ditemukan indikator mencurigakan pada struktur URL.'],
        ], $override);

        return array_filter($data, fn ($nilai) => $nilai !== '__hapus__');
    }

    private function assertGagal(MlFailure $diharapkan, callable $aksi): void
    {
        try {
            $aksi();
        } catch (MlServiceException $e) {
            $this->assertSame($diharapkan, $e->failure);
            $this->assertSame($diharapkan->message(), $e->getMessage());
            $this->assertSame($diharapkan->message(), $e->userMessage());

            return;
        }

        $this->fail('Seharusnya melempar MlServiceException.');
    }

    public function test_prediksi_berhasil(): void
    {
        Http::fake(['*/predict' => Http::response($this->payload(), 200)]);

        $result = (new PhishGuardService)->predict(self::URL);

        $this->assertInstanceOf(PredictionResult::class, $result);
        $this->assertSame('Legitimate', $result->prediction);
        $this->assertSame(0.9725, $result->probability);
        $this->assertSame(3, $result->riskScore);
        $this->assertSame(2, $result->modelId);
        $this->assertSame('1.1', $result->modelVersion);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://127.0.0.1:8000/predict'
                && $request['url'] === self::URL;
        });
    }

    public function test_url_dipangkas_sebelum_dikirim(): void
    {
        Http::fake(['*/predict' => Http::response($this->payload(), 200)]);

        (new PhishGuardService)->predict('   '.self::URL."  \n");

        Http::assertSent(fn (Request $request) => $request['url'] === self::URL);
    }

    public function test_url_kosong_tidak_pernah_dikirim(): void
    {
        Http::fake();

        $this->assertGagal(MlFailure::InvalidUrl, fn () => (new PhishGuardService)->predict('   '));

        Http::assertNothingSent();
    }

    public function test_url_terlalu_panjang_tidak_pernah_dikirim(): void
    {
        Http::fake();

        $this->assertGagal(MlFailure::InvalidUrl, fn () => (new PhishGuardService)->predict('https://a.com/'.str_repeat('x', 2100)));

        Http::assertNothingSent();
    }

    public function test_fastapi_mati(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->assertGagal(MlFailure::Unavailable, fn () => (new PhishGuardService)->predict(self::URL));
    }

    public function test_model_tidak_tersedia_503(): void
    {
        Http::fake(['*/predict' => Http::response(['detail' => 'Model deteksi tidak dapat digunakan.'], 503)]);

        $this->assertGagal(MlFailure::ModelUnavailable, fn () => (new PhishGuardService)->predict(self::URL));
    }

    public function test_url_ditolak_fastapi_422(): void
    {
        Http::fake(['*/predict' => Http::response(['detail' => 'URL tidak valid.'], 422)]);

        $this->assertGagal(MlFailure::InvalidUrl, fn () => (new PhishGuardService)->predict(self::URL));
    }

    /** @return array<string, array{0: int}> */
    public static function statusTidakTerduga(): array
    {
        return ['500' => [500], '502' => [502], '401' => [401], '404' => [404]];
    }

    #[DataProvider('statusTidakTerduga')]
    public function test_status_lain_dianggap_tidak_tersedia(int $status): void
    {
        Http::fake(['*/predict' => Http::response(['detail' => 'rahasia internal'], $status)]);

        $this->assertGagal(MlFailure::Unavailable, fn () => (new PhishGuardService)->predict(self::URL));
    }

    public function test_jawaban_bukan_json(): void
    {
        Http::fake(['*/predict' => Http::response('<html>bukan json</html>', 200)]);

        $this->assertGagal(MlFailure::Unavailable, fn () => (new PhishGuardService)->predict(self::URL));
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function jawabanMelanggarKontrak(): array
    {
        return [
            'prediksi tidak dikenal' => [['prediction' => 'Maybe']],
            'probabilitas di atas 1' => [['probability' => 1.5]],
            'url berbeda' => [['url' => 'https://lain.example.org/']],
            'risk_score tidak sesuai prediksi' => [['risk_score' => 99]],
            'field indicators hilang' => [['indicators' => '__hapus__']],
        ];
    }

    /** @param array<string, mixed> $override */
    #[DataProvider('jawabanMelanggarKontrak')]
    public function test_jawaban_melanggar_kontrak(array $override): void
    {
        Http::fake(['*/predict' => Http::response($this->payload($override), 200)]);

        $this->assertGagal(MlFailure::Unavailable, fn () => (new PhishGuardService)->predict(self::URL));
    }

    public function test_pesan_pengguna_tidak_membocorkan_detail_teknis(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 7: Failed to connect to 127.0.0.1 port 8000');
        });

        try {
            (new PhishGuardService)->predict(self::URL);
            $this->fail('Seharusnya melempar MlServiceException.');
        } catch (MlServiceException $e) {
            $this->assertStringNotContainsString('127.0.0.1', $e->getMessage());
            $this->assertStringNotContainsString('cURL', $e->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
    }

    public function test_health_terjangkau_dengan_model(): void
    {
        Http::fake(['*/health' => Http::response([
            'status' => 'ok', 'model_loaded' => true, 'model_name' => 'PhishGuard XGBoost', 'model_version' => '1.1',
        ], 200)]);

        $this->assertSame([
            'reachable' => true, 'model_loaded' => true, 'model_name' => 'PhishGuard XGBoost', 'model_version' => '1.1',
        ], (new PhishGuardService)->health());
    }

    public function test_health_terjangkau_tanpa_model(): void
    {
        Http::fake(['*/health' => Http::response(['status' => 'degraded', 'model_loaded' => false], 200)]);

        $this->assertSame([
            'reachable' => true, 'model_loaded' => false, 'model_name' => null, 'model_version' => null,
        ], (new PhishGuardService)->health());
    }

    private function tidakTerjangkau(): array
    {
        return ['reachable' => false, 'model_loaded' => false, 'model_name' => null, 'model_version' => null];
    }

    public function test_health_fastapi_mati_tidak_melempar_error(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->assertSame($this->tidakTerjangkau(), (new PhishGuardService)->health());
    }

    public function test_health_jawaban_bukan_json(): void
    {
        Http::fake(['*/health' => Http::response('bukan json', 200)]);

        $this->assertSame($this->tidakTerjangkau(), (new PhishGuardService)->health());
    }

    public function test_health_tipe_model_loaded_salah(): void
    {
        Http::fake(['*/health' => Http::response(['model_loaded' => 'ya'], 200)]);

        $this->assertSame($this->tidakTerjangkau(), (new PhishGuardService)->health());
    }

    public function test_health_status_500(): void
    {
        Http::fake(['*/health' => Http::response(['model_loaded' => true], 500)]);

        $this->assertSame($this->tidakTerjangkau(), (new PhishGuardService)->health());
    }
}