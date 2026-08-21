<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'raw_text',
        'status',
        'meeting_time',
    ];

    protected function casts(): array
    {
        return [
            'meeting_time' => 'datetime',
        ];
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class);
    }
}
