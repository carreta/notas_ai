<?php

namespace App\AI\DTO;

final readonly class AnalysisResult
{
    /**
     * @param  array<Decision>  $decisions
     * @param  array<ActionItem>  $actionItems
     * @param  array<OpenQuestion>  $openQuestions
     */
    public function __construct(
        public string $summary,
        public array $decisions,
        public array $actionItems,
        public array $openQuestions,
    ) {}
}
