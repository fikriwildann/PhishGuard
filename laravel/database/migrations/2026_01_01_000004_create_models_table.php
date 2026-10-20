<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('version', 50);
            $table->string('algorithm', 50);
            $table->string('bundle_name', 100)->unique();
            $table->string('file_path', 500);
            $table->string('file_hash', 64);
            $table->string('dataset_name', 200)->nullable();
            $table->unsignedInteger('feature_count');
            $table->json('feature_names');
            $table->decimal('accuracy', 8, 6);
            $table->decimal('precision', 8, 6);
            $table->decimal('recall', 8, 6);
            $table->decimal('f1_score', 8, 6);
            $table->decimal('roc_auc', 8, 6);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('inactive')->index(); // 'active', 'inactive', 'archived'
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['algorithm', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};
