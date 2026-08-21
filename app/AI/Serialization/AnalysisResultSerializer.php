<?php

namespace App\AI\Serialization;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;

/**
 * Application-owned mapping from a trusted AnalysisResult DTO
 * to the persisted JSON structure defined by the AI contract.
 *
 * The output uses the approved snake_case external keys and must not
 * contain provider-specific fields or undocumented properties.
 */
final class AnalysisResultSerializer
{
    public function toArray(AnalysisResult $result): array
    {
        return [
            'summary' => $result->summary,
            'decisions' => array_map(
                static fn (Decision $decision): array => ['text' => $decision->text],
                $result->decisions,
            ),
            'action_items' => array_map(
                static fn (ActionItem $item): array => [
                    'task' => $item->task,
                    'owner' => $item->owner,
                    'priority' => $item->priority,
                    'priority_source' => $item->prioritySource,
                    'due_date_text' => $item->dueDateText,
                    'due_date' => $item->dueDate,
                    'due_date_source' => $item->dueDateSource,
                ],
                $result->actionItems,
            ),
            'open_questions' => array_map(
                static fn (OpenQuestion $question): array => ['text' => $question->text],
                $result->openQuestions,
            ),
        ];
    }
}
