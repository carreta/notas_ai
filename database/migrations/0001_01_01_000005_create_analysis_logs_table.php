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
        Schema::create('analysis_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')
                ->constrained('meetings')
                ->cascadeOnDelete();
            // Allowed values COMPLETED / FAILED (per the approved Option A schema).
            $table->enum('status', ['COMPLETED', 'FAILED']);
            $table->string('provider');
            $table->string('model');
            // FK to prompt_templates.version (version is UNIQUE in that table).
            $table->string('prompt_version');
            $table->foreign('prompt_version')
                ->references('version')
                ->on('prompt_templates')
                ->restrictOnDelete();
            // ADR-002 error categories; nullable on success (per approved schema).
            $table->enum('error_category', [
                'VALIDATION_ERROR',
                'AI_CONFIGURATION_ERROR',
                'AI_DEPENDENCY_ERROR',
                'AI_TIMEOUT',
                'AI_RATE_LIMIT',
                'AI_INVALID_RESPONSE',
                'PERSISTENCE_ERROR',
                'INTERNAL_ERROR',
            ])->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_logs');
    }
};



/**

Doing it manually starts working.

INSERT INTO prompt_templates (
    id,
    version,
    system_prompt,
    json_schema,
    is_active,
    created_at
)
VALUES (
    gen_random_uuid(),
    'meeting-analysis-v1',
    'system',
    '{"version":"meeting-analysis-v1"}',
    true,
    NOW()
);
*/
