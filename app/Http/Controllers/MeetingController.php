<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MeetingController extends Controller
{
    /**
     * Detail view for a single meeting.
     *
     * - HTML (browser): renders meetings/show.blade.php with the meeting and
     *   its related Analysis (FR-007 navigation).
     * - JSON (Accept: application/json): preserves the original API shape used
     *   by MeetingRetrievalTest. Route-model binding returns 404 automatically
     *   for a missing/invalid meeting UUID.
     */
    public function show(Request $request, Meeting $meeting)
    {
        $meeting->load('analysis');

        if ($request->wantsJson()) {
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

        return response()->view('meetings.show', [
            'meeting' => $meeting,
        ]);
    }
}
