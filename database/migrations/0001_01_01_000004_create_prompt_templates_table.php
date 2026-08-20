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
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Unique so analysis_logs.prompt_version can reference it as a FK.
            $table->string('version')->unique();
            $table->text('system_prompt');
            // Required per the approved Option A schema.
            $table->jsonb('json_schema');
            $table->boolean('is_active')->default(false);
            // Per the approved schema, prompt_templates tracks only created_at.
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
