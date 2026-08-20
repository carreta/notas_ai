<?php

namespace App\AI\DTO;

final readonly class ActionItem
{
    public function __construct(
        public string $task,
        public ?string $owner,
        public ?string $priority,
        public ?string $prioritySource,
        public ?string $dueDateText,
        public ?string $dueDate,
        public ?string $dueDateSource,
    ) {}
}
