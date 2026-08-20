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

            /*
            $table->foreignUuid('analysis_metadata_id')
                ->nullable()
                ->unique()
                ->constrained('analysis_logs')
                ->nullOnDelete();
            */

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
