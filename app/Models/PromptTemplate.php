<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromptTemplate extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'prompt_templates';

    protected $fillable = [
        'version',
        'system_prompt',
        'json_schema',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'json_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
