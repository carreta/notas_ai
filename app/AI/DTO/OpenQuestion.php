<?php

namespace App\AI\DTO;

final readonly class OpenQuestion
{
    public function __construct(
        public string $text,
    ) {}
}
