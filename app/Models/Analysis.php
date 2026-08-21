<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    use HasUuids;

    protected $fillable = [
        'meeting_id',
        'result',
        'analysis_metadata',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function analysisMetadata(): BelongsTo
    {
        return $this->belongsTo(AnalysisLog::class, 'analysis_metadata', 'id');
    }
}
