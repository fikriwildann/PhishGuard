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
        Schema::create('model_activation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('previous_model_id')->nullable()->constrained('models')->nullOnDelete();
            $table->foreignId('new_model_id')->nullable()->constrained('models')->nullOnDelete();
            $table->string('action', 30); // 'activate', 'rollback', 'deactivate', 'archive'
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('admin_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_activation_logs');
    }
};
