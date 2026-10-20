<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\MlModel;
use App\Models\ModelActivationLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin Default
        $admin = User::firstOrCreate(
            ['email' => 'admin@phishguard.test'],
            [
                'name' => 'Administrator PhishGuard',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Regular User Default
        $user = User::firstOrCreate(
            ['email' => 'user@phishguard.test'],
            [
                'name' => 'User PhishGuard',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        // 3. Daftarkan Model Aktif Awal (XGBoost v1.2)
        $features = [
            "length_url", "length_hostname", "ip", "nb_dots", "nb_hyphens", "nb_at",
            "nb_qm", "nb_and", "nb_or", "nb_eq", "nb_underscore", "nb_tilde", "nb_percent",
            "nb_slash", "nb_star", "nb_colon", "nb_comma", "nb_semicolon", "nb_dollar",
            "nb_space", "nb_www", "nb_com", "nb_http", "nb_dslash", "is_https",
            "ratio_digits_url", "ratio_digits_host", "punycode", "has_port", "tld_in_path",
            "tld_in_subdomain", "abnormal_subdomain", "nb_subdomains", "prefix_suffix",
            "shortening_service", "path_extension", "length_words_raw", "char_repeat",
            "shortest_words_raw", "shortest_word_host", "shortest_word_path", "longest_words_raw",
            "longest_word_host", "longest_word_path", "avg_words_raw", "avg_word_host",
            "avg_word_path", "phish_hints", "entropy_host"
        ];

        $model = MlModel::firstOrCreate(
            ['bundle_name' => 'xgboost_v1_1'],
            [
                'name' => 'PhishGuard XGBoost',
                'version' => '1.2',
                'algorithm' => 'XGBoost',
                'file_path' => 'models/xgboost_v1_1/model.pkl',
                'file_hash' => 'bc535d3012468f54298d7d08fcb3708538e442c7d586d976476f66255985ba6b',
                'dataset_name' => 'Web page phishing detection (Hannousse & Yahiouche), dataset B',
                'feature_count' => count($features),
                'feature_names' => $features,
                'accuracy' => 0.875760,
                'precision' => 0.875130,
                'recall' => 0.873434,
                'f1_score' => 0.874100,
                'roc_auc' => 0.950254,
                'description' => 'Model deteksi URL phishing berbasis fitur leksikal URL (tanpa akses jaringan).',
                'status' => MlModel::STATUS_ACTIVE,
                'uploaded_by' => $admin->id,
                'activated_by' => $admin->id,
                'activated_at' => now(),
            ]
        );

        // 4. Log Aktivasi Awal
        ModelActivationLog::firstOrCreate(
            ['new_model_id' => $model->id, 'action' => ModelActivationLog::ACTION_ACTIVATE],
            [
                'previous_model_id' => null,
                'admin_id' => $admin->id,
                'reason' => 'Inisialisasi sistem dengan model baseline XGBoost v1.2',
            ]
        );

        // 5. Log Aktivitas Awal
        ActivityLog::firstOrCreate(
            ['activity' => ActivityLog::ACTIVATE_MODEL, 'user_id' => $admin->id],
            [
                'description' => 'Inisialisasi sistem: Model ' . $model->name . ' v' . $model->version . ' diaktifkan.',
                'ip_address' => '127.0.0.1',
            ]
        );
    }
}
