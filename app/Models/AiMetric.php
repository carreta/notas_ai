<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Option A ai_metrics row: token usage and duration for one analysis.
 *
 * 1:1 with analyses via analysis_id.
 */
class AiMetric extends Model
{
    use HasUuids;

    protected $table = 'ai_metrics';

    public $timestamps = false;

    protected $fillable = [
        'analysis_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
