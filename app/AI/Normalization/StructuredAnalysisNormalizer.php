<?php

namespace App\AI\Normalization;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;

final class StructuredAnalysisNormalizer
{
    /**
     * Apply only documented deterministic normalization rules to a trusted
     * AnalysisResult. The current approved contract (TD-010 / TD-011) already
     * validates priority and date representations into canonical form, so this
     * step preserves them and returns new immutable DTO instances.
     *
     * It does NOT infer priority from task text, does NOT resolve relative
     * dates, and performs no persistence or provider calls.
     */
    public function normalize(AnalysisResult $result): AnalysisResult
    {
        $actionItems = [];

        foreach ($result->actionItems as $item) {
            $actionItems[] = new ActionItem(
                $item->task,
                $item->owner,
                $item->priority,
                $item->prioritySource,
                $item->dueDateText,
                $item->dueDate,
                $item->dueDateSource,
            );
        }

        return new AnalysisResult(
            $result->summary,
            $result->decisions,
            $actionItems,
            $result->openQuestions,
        );
    }
}
