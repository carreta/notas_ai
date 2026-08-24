<?php

namespace App\AI\Normalization;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use Illuminate\Support\Facades\Log;

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
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisNormalizer] normalize() started', [
            'summary_length' => mb_strlen($result->summary),
            'decisions_count' => count($result->decisions),
            'action_items_count' => count($result->actionItems),
            'open_questions_count' => count($result->openQuestions),
        ]);

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

        // Original: return new AnalysisResult(
        $normalized = new AnalysisResult(
            $result->summary,
            $result->decisions,
            $actionItems,
            $result->openQuestions,
        );

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisNormalizer] normalize() completed', [
            'summary_length' => mb_strlen($normalized->summary),
            'decisions_count' => count($normalized->decisions),
            'action_items_count' => count($normalized->actionItems),
            'open_questions_count' => count($normalized->openQuestions),
        ]);

        return $normalized;
    }

    /**
     * Safe logging that works in both web and test contexts.
     */
    private function log(string $message, array $context = []): void
    {
        try {
            if (class_exists(Log::class) && app()->bound('log')) {
                Log::info($message, $context);
            }
        } catch (\Throwable) {
            // Ignore logging failures in test contexts
        }
    }
}
