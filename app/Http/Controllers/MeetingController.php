<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MeetingController extends Controller
{
    /**
     * JSON API endpoint for meeting retrieval.
     * Used by tests and API consumers.
     */
    public function show(Request $request, Meeting $meeting)
    {
        $meeting->load('analysis');

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
