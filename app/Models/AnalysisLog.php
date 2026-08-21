<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Option A analysis_logs row: one record per analysis attempt for a meeting.
 *
 * The selected successful log is linked 1:1 from analyses.analysis_metadata.
 */
class AnalysisLog extends Model
{
    use HasUuids;

    protected $table = 'analysis_logs';

    public $timestamps = false;

    protected $fillable = [
        'meeting_id',
        'status',
        'provider',
        'model',
        'prompt_version',
        'error_category',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class, 'analysis_metadata', 'id');
    }
}
