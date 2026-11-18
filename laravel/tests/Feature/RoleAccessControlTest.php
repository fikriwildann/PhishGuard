<?php

namespace Tests\Feature;

use App\Models\MlModel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tamu_diarahkan_ke_login_saat_mengakses_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_biasa_ditolak_403_saat_mengakses_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_admin_dapat_mengakses_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Pusat Kendali Sistem PhishGuard');
    }

    public function test_admin_tidak_bisa_menghapus_akun_dirinya_sendiri(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_tidak_bisa_menghapus_model_yang_sedang_aktif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeModel = MlModel::where('status', MlModel::STATUS_ACTIVE)->first();

        $this->assertNotNull($activeModel);

        $response = $this->actingAs($admin)->delete("/admin/models/{$activeModel->id}");
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('models', [
            'id' => $activeModel->id,
        ]);
    }

    public function test_admin_dapat_menghapus_model_inactive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testModel = MlModel::create([
            'name' => 'Model Test Delete',
            'version' => '9.9',
            'algorithm' => 'XGBoost',
            'bundle_name' => 'test_delete_bundle',
            'file_path' => 'models/test_delete_bundle/model.pkl',
            'file_hash' => 'hash_test_123',
            'accuracy' => 0.85,
            'precision' => 0.85,
            'recall' => 0.85,
            'f1_score' => 0.85,
            'roc_auc' => 0.90,
            'feature_count' => 49,
            'feature_names' => ['f1'],
            'status' => MlModel::STATUS_INACTIVE,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/models/{$testModel->id}");
        $response->assertRedirect('/admin/models');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('models', [
            'id' => $testModel->id,
        ]);
    }
}
