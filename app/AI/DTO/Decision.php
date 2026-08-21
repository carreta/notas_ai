<?php

namespace App\AI\DTO;

final readonly class Decision
{
    public function __construct(
        public string $text,
    ) {}
}
