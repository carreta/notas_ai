<?php

namespace App\AI\Validation;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;
use App\AI\Exceptions\AiInvalidResponseException;
use Illuminate\Support\Facades\Log; // TODO: Remove after logging is no longer needed

final class StructuredAnalysisValidator
{
    private const PRIORITY_VALUES = ['LOW', 'MEDIUM', 'HIGH'];

    private const PRIORITY_SOURCES = ['EXPLICIT', 'INFERRED'];

    private const DUE_DATE_SOURCES = ['EXPLICIT', 'INFERRED', 'UNKNOWN'];

    /**
     * Validate an untrusted associative array and build a trusted AnalysisResult.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AiInvalidResponseException
     */
    public function validate(array $data): AnalysisResult
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] validate() started', [
            'data_keys' => array_keys($data),
        ]);

        $summary = $this->requireString($data, 'summary', true);
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] summary validated', ['length' => mb_strlen($summary)]);

        $decisionsRaw = $this->requireArray($data, 'decisions');
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] decisions array validated', ['count' => count($decisionsRaw)]);

        $actionItemsRaw = $this->requireArray($data, 'action_items');
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] action_items array validated', ['count' => count($actionItemsRaw)]);

        $openQuestionsRaw = $this->requireArray($data, 'open_questions');
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] open_questions array validated', ['count' => count($openQuestionsRaw)]);

        $decisions = [];
        // TODO: original:
        // foreach ($decisionsRaw as $index => $item) {
        //      $decisions[] = $this->validateDecision($item);
        // }
        foreach ($decisionsRaw as $index => $item) {
            try {
                $decisions[] = $this->validateDecision($item);
            } catch (AiInvalidResponseException $e) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                $this->log('[TEMP][StructuredAnalysisValidator] Decision validation failed', ['index' => $index, 'item' => $item]);
                throw $e;
            }
        }
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] All decisions validated', ['count' => count($decisions)]);

        $openQuestions = [];
        // TODO: original:
        // foreach ($openQuestionsRaw as $index => $item) {
        //      $openQuestions[] = $this->validateOpenQuestion($item);
        // }
        foreach ($openQuestionsRaw as $index => $item) {
            try {
                $openQuestions[] = $this->validateOpenQuestion($item);
            } catch (AiInvalidResponseException $e) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                $this->log('[TEMP][StructuredAnalysisValidator] Open question validation failed', ['index' => $index, 'item' => $item]);
                throw $e;
            }
        }
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] All open questions validated', ['count' => count($openQuestions)]);

        $actionItems = [];
        // TODO: original:
        // foreach ($actionItemsRaw as $index => $item) {
        //      $actionItems[] = $this->validateActionItem($item);
        // }
        foreach ($actionItemsRaw as $index => $item) {
            try {
                $actionItems[] = $this->validateActionItem($item);
            } catch (AiInvalidResponseException $e) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                $this->log('[TEMP][StructuredAnalysisValidator] Action item validation failed', ['index' => $index, 'item' => $item]);
                throw $e;
            }
        }
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] All action items validated', ['count' => count($actionItems)]);

        $result = new AnalysisResult($summary, $decisions, $actionItems, $openQuestions);
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] validate() completed successfully');

        // Original: return new AnalysisResult($summary, $decisions, $actionItems, $openQuestions);
        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requireString(array $data, string $key, bool $nonEmpty): string
    {
        if (! array_key_exists($key, $data)) {
            throw new AiInvalidResponseException;
        }

        $value = $data[$key];

        if (! is_string($value) || ($nonEmpty && trim($value) === '')) {
            throw new AiInvalidResponseException;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, mixed>
     */
    private function requireArray(array $data, string $key): array
    {
        if (! array_key_exists($key, $data) || ! is_array($data[$key])) {
            throw new AiInvalidResponseException;
        }

        return $data[$key];
    }

    private function validateDecision(mixed $item): Decision
    {
        $text = $this->extractTextFromDecision($item);
        if ($text === null || trim($text) === '') {
            throw new AiInvalidResponseException;
        }

        return new Decision($text);
    }

    private function validateOpenQuestion(mixed $item): OpenQuestion
    {
        $text = $this->extractTextFromOpenQuestion($item);
        if ($text === null || trim($text) === '') {
            throw new AiInvalidResponseException;
        }

        return new OpenQuestion($text);
    }

    private function extractTextFromDecision(mixed $item): ?string
    {
        if (is_string($item)) {
            return $item;
        }
        if (is_array($item) && array_key_exists('text', $item) && is_string($item['text'])) {
            return $item['text'];
        }

        return null;
    }

    private function extractTextFromOpenQuestion(mixed $item): ?string
    {
        if (is_string($item)) {
            return $item;
        }
        if (is_array($item) && array_key_exists('text', $item) && is_string($item['text'])) {
            return $item['text'];
        }

        return null;
    }

    private function validateActionItem(mixed $item): ActionItem
    {
        if (! is_array($item)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisValidator] validateActionItem: item is not an array', ['item' => $item]);
            throw new AiInvalidResponseException;
        }

        $task = $this->requireString($item, 'task', true);
        $owner = $this->optionalString($item, 'owner');
        $priority = $this->optionalPriority($item, 'priority');
        $prioritySource = $this->optionalPrioritySource($item, 'priority_source');
        $dueDateText = $this->optionalString($item, 'due_date_text');
        $dueDate = $this->optionalIsoDate($item, 'due_date');
        $dueDateSource = $this->optionalDueDateSource($item, 'due_date_source');

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisValidator] Action item fields parsed', [
            'task' => $task,
            'owner' => $owner,
            'priority' => $priority,
            'priority_source' => $prioritySource,
            'due_date_text' => $dueDateText,
            'due_date' => $dueDate,
            'due_date_source' => $dueDateSource,
        ]);

        $this->assertPriorityConsistency($priority, $prioritySource);
        $this->assertDueDateConsistency($dueDateSource, $dueDateText, $dueDate);

        return new ActionItem(
            $task,
            $owner,
            $priority,
            $prioritySource,
            $dueDateText,
            $dueDate,
            $dueDateSource
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalString(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisValidator] optionalString: value is not a string', ['key' => $key, 'value' => $value]);
            throw new AiInvalidResponseException;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalPriority(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || ! in_array($value, self::PRIORITY_VALUES, true)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisValidator] optionalPriority: invalid value', ['key' => $key, 'value' => $value, 'allowed' => self::PRIORITY_VALUES]);
            throw new AiInvalidResponseException;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalPrioritySource(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || ! in_array($value, self::PRIORITY_SOURCES, true)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisValidator] optionalPrioritySource: invalid value', ['key' => $key, 'value' => $value, 'allowed' => self::PRIORITY_SOURCES]);
            throw new AiInvalidResponseException;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalDueDateSource(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || ! in_array($value, self::DUE_DATE_SOURCES, true)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisValidator] optionalDueDateSource: invalid value', ['key' => $key, 'value' => $value, 'allowed' => self::DUE_DATE_SOURCES]);
            throw new AiInvalidResponseException;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalIsoDate(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || ! $this->isValidIsoDate($value)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisValidator] optionalIsoDate: invalid date format', ['key' => $key, 'value' => $value]);
            throw new AiInvalidResponseException;
        }

        return $value;
    }

    private function isValidIsoDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function assertPriorityConsistency(?string $priority, ?string $prioritySource): void
    {
        if ($priority === null && $prioritySource !== null) {
            throw new AiInvalidResponseException;
        }

        if ($priority !== null && ! in_array($prioritySource, self::PRIORITY_SOURCES, true)) {
            throw new AiInvalidResponseException;
        }
    }

    private function assertDueDateConsistency(?string $dueDateSource, ?string $dueDateText, ?string $dueDate): void
    {
        if ($dueDateSource === 'UNKNOWN') {
            if ($dueDateText === null || $dueDate !== null) {
                throw new AiInvalidResponseException;
            }
        }
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
