<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitMeetingRequest;
use App\Models\Meeting;
use App\Support\TokenCounter;
use Illuminate\Http\JsonResponse;

class MeetingController extends Controller
{
    public function store(SubmitMeetingRequest $request, TokenCounter $tokens)
    {
        $modelKey = $request->validated('model');
        $model = config("models.{$modelKey}");

        if (! $model) {
            return back()->withErrors(['model' => 'Invalid model selected.'])->withInput();
        }

        $transcript = $request->validated('transcript');

        if ($tokens->exceedsLimit($transcript, $model['max_tokens'])) {
            return back()->withErrors([
                'transcript' => "Token limit exceeded. Maximum {$model['max_tokens']} tokens allowed for {$model['label']}.",
            ])->withInput();
        }

        // Validation passed - no AI call yet (FR-001 scope)

        Meeting::create([
            'title' => $request->validated('meeting-title'),
            'raw_text' => $transcript,
            'status' => 'DRAFT',
            'meeting_time' => $request->validated('meeting-date'),
        ]);

        return back()->with('status', 'Analysis queued (placeholder — persistence is FR-002).');
    }

    public function show(Meeting $meeting): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'meeting_id' => $meeting->id,
                'title' => $meeting->title,
                'raw_text' => $meeting->raw_text,
                'status' => $meeting->status,
                'meeting_time' => $meeting->meeting_time,
                'created_at' => $meeting->created_at,
                'updated_at' => $meeting->updated_at,
            ],
            'error' => null,
        ]);
    }
}
