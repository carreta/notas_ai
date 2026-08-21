<?php

namespace Tests\Unit\AI;

use App\AI\Metadata\AnalysisMetadata;
use PHPUnit\Framework\TestCase;

class AnalysisMetadataTest extends TestCase
{
    public function test_defaults_to_meeting_analysis_v1(): void
    {
        $meta = new AnalysisMetadata;

        $this->assertSame('meeting-analysis-v1', $meta->schemaVersion);
        $this->assertNull($meta->provider);
        $this->assertNull($meta->model);
        $this->assertNull($meta->startedAt);
        $this->assertNull($meta->completedAt);
        $this->assertNull($meta->durationMs);
        $this->assertNull($meta->failureCategory);
    }

    public function test_failure_category_is_a_safe_scalar_not_an_exception_message(): void
    {
        $meta = new AnalysisMetadata(failureCategory: 'AI_INVALID_RESPONSE');

        $this->assertIsString($meta->failureCategory);
        $this->assertSame('AI_INVALID_RESPONSE', $meta->failureCategory);
        $this->assertStringNotContainsString('Exception', $meta->failureCategory);
        $this->assertStringNotContainsString('stack', $meta->failureCategory);
        $this->assertStringNotContainsString('SQLSTATE', $meta->failureCategory);
    }

    public function test_has_no_secret_bearing_properties(): void
    {
        $reflection = new \ReflectionClass(AnalysisMetadata::class);
        $names = array_map(static fn (\ReflectionProperty $p): string => $p->getName(), $reflection->getProperties());

        foreach (['apiKey', 'api_key', 'authorization', 'secret', 'credential', 'token'] as $forbidden) {
            $this->assertNotContains($forbidden, $names, "Metadata must not expose {$forbidden}");
        }
    }
}
