<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitMeetingRequest;
use App\Models\Meeting;
use App\Support\TokenCounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

// TODO: This is a temporary file for the purpose of comparing to view json data for meeting tables. It is not intended to be used in production and should be deleted after history page is implemented.
class MeetingController extends Controller
{
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
