<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
