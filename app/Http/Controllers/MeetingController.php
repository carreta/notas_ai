<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingRequest;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;

class MeetingController extends Controller
{
    public function store(StoreMeetingRequest $request): JsonResponse
    {
        $meeting = Meeting::create([
            'title' => $request->validated('title'),
            'raw_text' => $request->validated('raw_text'),
            'status' => 'DRAFT',
            'meeting_time' => $request->validated('meeting_time'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'meeting_id' => $meeting->id,
                'status' => $meeting->status,
                'created_at' => $meeting->created_at,
            ],
            'error' => null,
        ], 201);
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
