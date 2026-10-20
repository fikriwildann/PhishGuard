<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScanHistoryIsolationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_hanya_dapat_melihat_riwayat_pemindaian_miliknya_sendiri(): void
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $scanA = Scan::create([
            'user_id' => $userA->id,
            'url' => 'https://milik-user-a.com',
            'prediction' => 'Legitimate',
            'probability' => 0.95,
            'risk_score' => 5,
            'risk_level' => 'Low',
            'model_name' => 'PhishGuard XGBoost',
            'model_version' => '1.2',
        ]);

        $scanB = Scan::create([
            'user_id' => $userB->id,
            'url' => 'https://milik-user-b.com',
            'prediction' => 'Phishing',
            'probability' => 0.98,
            'risk_score' => 88,
            'risk_level' => 'High',
            'model_name' => 'PhishGuard XGBoost',
            'model_version' => '1.2',
        ]);

        // User A membuka history: hanya melihat scan miliknya
        $response = $this->actingAs($userA)->get('/history');
        $response->assertStatus(200);
        $response->assertSee('https://milik-user-a.com');
        $response->assertDontSee('https://milik-user-b.com');

        // User A mencoba melihat detail scan B secara langsung: ditolak 403
        $detailResponse = $this->actingAs($userA)->get("/history/{$scanB->id}");
        $detailResponse->assertStatus(403);

        // User A mencoba menghapus scan B: ditolak 403
        $deleteResponse = $this->actingAs($userA)->delete("/history/{$scanB->id}");
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('scans', ['id' => $scanB->id]);
    }
}
