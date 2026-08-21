<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_run_from_zero_and_app_boots(): void
    {
        // RefreshDatabase runs migrations on the configured testing DB;
        // Schema::hasTable verifies each expected table was created
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('sessions'));

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Verifies the approved Option A schema is fully created by the migrations.
     * Covers only schema integrity for the five Option A tables; no feature logic.
     */
    public function test_option_a_schema_is_present(): void
    {
        $tables = [
            'prompt_templates' => ['id', 'version', 'system_prompt', 'json_schema', 'is_active', 'created_at'],
            'meetings' => ['id', 'title', 'raw_text', 'status', 'meeting_time', 'created_at', 'updated_at'],
            'analysis_logs' => [
                'id', 'meeting_id', 'status', 'provider', 'model', 'prompt_version',
                'error_category', 'error_message', 'started_at', 'completed_at',
            ],
            'analyses' => ['id', 'meeting_id', 'result', 'analysis_metadata', 'created_at', 'updated_at'],
            'ai_metrics' => [
                'id', 'analysis_id', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'duration_ms',
            ],
        ];

        foreach ($tables as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] should exist");
            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Column [{$table}.{$column}] should exist"
                );
            }
        }
    }
}
