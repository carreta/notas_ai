<?php

namespace App\AI\Validation;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;
use App\AI\Exceptions\AiInvalidResponseException;

final class StructuredAnalysisValidator
{
    private const PRIORITY_VALUES = ['LOW', 'MEDIUM', 'HIGH'];

    private const PRIORITY_SOURCES = ['EXPLICIT', 'INFERRED'];

    private const DUE_DATE_SOURCES = ['EXPLICIT', 'RESOLVED', 'INFERRED', 'UNRESOLVED'];

    /**
     * Validate an untrusted associative array and build a trusted AnalysisResult.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AiInvalidResponseException
     */
    public function validate(array $data): AnalysisResult
    {
        $summary = $this->requireString($data, 'summary', true);
        $decisionsRaw = $this->requireArray($data, 'decisions');
        $actionItemsRaw = $this->requireArray($data, 'action_items');
        $openQuestionsRaw = $this->requireArray($data, 'open_questions');

        $decisions = [];
        foreach ($decisionsRaw as $item) {
            $decisions[] = $this->validateDecision($item);
        }

        $openQuestions = [];
        foreach ($openQuestionsRaw as $item) {
            $openQuestions[] = $this->validateOpenQuestion($item);
        }

        $actionItems = [];
        foreach ($actionItemsRaw as $item) {
            $actionItems[] = $this->validateActionItem($item);
        }

        return new AnalysisResult($summary, $decisions, $actionItems, $openQuestions);
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
        if (! is_array($item)) {
            throw new AiInvalidResponseException;
        }

        return new Decision($this->requireString($item, 'text', true));
    }

    private function validateOpenQuestion(mixed $item): OpenQuestion
    {
        if (! is_array($item)) {
            throw new AiInvalidResponseException;
        }

        return new OpenQuestion($this->requireString($item, 'text', true));
    }

    private function validateActionItem(mixed $item): ActionItem
    {
        if (! is_array($item)) {
            throw new AiInvalidResponseException;
        }

        $task = $this->requireString($item, 'task', true);
        $owner = $this->optionalString($item, 'owner');
        $priority = $this->optionalPriority($item, 'priority');
        $prioritySource = $this->optionalPrioritySource($item, 'priority_source');
        $dueDateText = $this->optionalString($item, 'due_date_text');
        $dueDate = $this->optionalIsoDate($item, 'due_date');
        $dueDateSource = $this->optionalDueDateSource($item, 'due_date_source');

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
        if ($dueDateSource === 'UNRESOLVED') {
            if ($dueDateText === null || $dueDate !== null) {
                throw new AiInvalidResponseException;
            }
        }
    }
}
