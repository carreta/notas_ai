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
        Schema::create('ai_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Option A 1:1 link to analyses.
            $table->foreignUuid('analysis_id')
                ->constrained('analyses')
                ->cascadeOnDelete();
            $table->unique('analysis_id');
            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->integer('total_tokens');
            $table->integer('duration_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_metrics');
    }
};
