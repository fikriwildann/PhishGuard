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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('url');
            $table->string('prediction', 20); // 'Legitimate' or 'Phishing'
            $table->decimal('probability', 6, 4);
            $table->unsignedTinyInteger('risk_score'); // 0 - 100
            $table->string('risk_level', 10); // 'Low', 'Medium', 'High'
            $table->foreignId('model_id')->nullable()->constrained('models')->nullOnDelete();
            $table->string('model_name', 100);
            $table->string('model_version', 50);
            $table->string('message', 500)->nullable();
            $table->json('indicators')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('prediction');
            $table->index('model_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
