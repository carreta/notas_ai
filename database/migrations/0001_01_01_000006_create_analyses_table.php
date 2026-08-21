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
        Schema::create('analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('meeting_id')
                ->constrained('meetings')
                ->cascadeOnDelete();

            $table->unique('meeting_id');

            $table->jsonb('result');

            // Option A 1:1 link to analysis_logs. Nullable because the
            // FR-005 persist() path creates an Analysis before a log exists.
            $table->uuid('analysis_metadata')->nullable();
            $table->foreign('analysis_metadata')
                ->references('id')
                ->on('analysis_logs')
                ->nullOnDelete();
            $table->unique('analysis_metadata');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
