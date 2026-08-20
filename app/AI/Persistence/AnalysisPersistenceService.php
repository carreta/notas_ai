<?php

namespace App\AI\Persistence;

use App\AI\DTO\AnalysisResult;
use App\AI\Serialization\AnalysisResultSerializer;
use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Support\Facades\DB;

/**
 * Persists a trusted AnalysisResult for a given Meeting.
 *
 * Trust boundary:
 *  - Input is an already-validated application-owned AnalysisResult
 *    (produced by StructuredAnalysisProcessor / FR-004).
 *  - Raw provider JSON is never accepted here.
 *  - The meeting association comes from the application-owned Meeting
 *    object, never from provider output.
 *
 * Atomicity:
 *  - Analysis is persisted first;
 *  - only after a successful persist is the Meeting marked COMPLETED;
 *  - the whole operation runs inside a database transaction, so a
 *    persistence failure rolls back and never leaves a false COMPLETED.
 */
final class AnalysisPersistenceService
{
    public function __construct(
        private readonly AnalysisResultSerializer $serializer = new AnalysisResultSerializer,
    ) {}

    public function persist(Meeting $meeting, AnalysisResult $result): Analysis
    {
        return DB::transaction(function () use ($meeting, $result): Analysis {
            $analysis = Analysis::create([
                'meeting_id' => $meeting->id,
                'result' => $this->serializer->toArray($result),
            ]);

            $meeting->update(['status' => 'COMPLETED']);

            return $analysis;
        });
    }
}
