<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed the meeting-analysis-v1 prompt template (TD-010, TD-011, TD-017)
        // This implements the application-owned prompt per ADR-001 (database-stored prompts).
        $systemPrompt = <<<'PROMPT'
You are an expert meeting analyst. Analyze the provided meeting transcript and return ONLY a valid JSON object matching the schema below. Do not include any explanation, markdown, or additional text.

REQUIRED JSON SCHEMA:
{
  "type": "object",
  "properties": {
    "summary": {"type": "string", "minLength": 1},
    "decisions": {
      "type": "array",
      "items": {"type": "string", "minLength": 1}
    },
    "action_items": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "task": {"type": "string", "minLength": 1},
          "owner": {"type": "string"},
          "priority": {"type": "string", "enum": ["HIGH", "MEDIUM", "LOW"]},
          "priority_source": {"type": "string", "enum": ["EXPLICIT", "INFERRED"]},
          "due_date_text": {"type": "string"},
          "due_date": {"type": "string", "format": "date"},
          "due_date_source": {"type": "string", "enum": ["EXPLICIT", "RESOLVED", "INFERRED", "UNRESOLVED"]}
        },
        "required": ["task", "priority_source", "due_date_source"]
      }
    },
    "open_questions": {
      "type": "array",
      "items": {"type": "string", "minLength": 1}
    }
  },
  "required": ["summary", "decisions", "action_items", "open_questions"]
}

CRITICAL RULES (TD-010, TD-011, TD-017):
1. PRIORITY SOURCE (TD-010): For each action_item, set "priority_source" to "EXPLICIT" if the transcript explicitly states the priority (e.g., "high priority", "urgent", "P1"). Set to "INFERRED" if you deduce it from context. Never omit.
2. DUE DATE SOURCE (TD-011): For each action_item, always include "due_date_text" (the raw date expression from the transcript, e.g., "next Friday", "by EOD", "in two weeks"). Set "due_date" to the resolved YYYY-MM-DD date ONLY if a reference date is provided and the expression is resolvable. Set "due_date_source" to "EXPLICIT" if the transcript gives a concrete date, "RESOLVED" if you deterministically resolved a relative expression using a reliable reference date, "INFERRED" if the date is only guessed from context without a concrete anchor, or "UNRESOLVED" if not resolvable or no reference date.
3. MEETING TIME (TD-017): If no meeting_time is provided in the request, treat all due_date fields as UNRESOLVED and include the raw text in due_date_text.
4. EMPTY ARRAYS: If a section has no items, return an empty array ([]), never null.
5. NO PROVIDER-SPECIFIC FIELDS: Return only the fields defined in the schema above.

EXAMPLE ACTION ITEM:
{
  "task": "Send proposal to client",
  "owner": "Ana",
  "priority": "HIGH",
  "priority_source": "EXPLICIT",
  "due_date_text": "by Friday",
  "due_date": "2026-08-28",
  "due_date_source": "INFERRED"
}
  
Return ONLY valid JSON.
Do not include markdown fences.
Do not include explanations before or after the JSON.
PROMPT;

        $jsonSchema = json_encode([
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string', 'minLength' => 1],
                'decisions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'minLength' => 1],
                ],
                'action_items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'task' => ['type' => 'string', 'minLength' => 1],
                            'owner' => ['type' => 'string'],
                            'priority' => ['type' => 'string', 'enum' => ['HIGH', 'MEDIUM', 'LOW']],
                            'priority_source' => ['type' => 'string', 'enum' => ['EXPLICIT', 'INFERRED']],
                            'due_date_text' => ['type' => 'string'],
                            'due_date' => ['type' => 'string', 'format' => 'date'],
                            'due_date_source' => ['type' => 'string', 'enum' => ['EXPLICIT', 'RESOLVED', 'INFERRED', 'UNRESOLVED']],
                        ],
                        'required' => ['task', 'priority_source', 'due_date_source'],
                    ],
                ],
                'open_questions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'minLength' => 1],
                ],
            ],
            'required' => ['summary', 'decisions', 'action_items', 'open_questions'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        DB::table('prompt_templates')->upsert([
            [
                'id' => Str::uuid(),
                'version' => 'meeting-analysis-v1',
                'system_prompt' => $systemPrompt,
                'json_schema' => $jsonSchema,
                'is_active' => true,
                'created_at' => now(),
            ],
        ], ['version'], ['system_prompt', 'json_schema', 'is_active', 'created_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('prompt_templates')->where('version', 'meeting-analysis-v1')->delete();
    }
};
