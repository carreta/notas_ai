<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitMeetingRequest;
use App\Support\TokenCounter;

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
        return back()->with('status', 'Analysis queued (placeholder — persistence is FR-002).');
    }
}
