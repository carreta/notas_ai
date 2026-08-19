<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

class DebugController extends Controller
{
    public function index()
    {
        $meetings = [
            [
                'id' => 1,
                'title' => 'Q4 Strategy Planning Kickoff',
                'date' => 'Oct 24, 2023',
                'logs_count' => 3,
                'prompt_version' => 'v2.1',
                'status' => 'Completed',
                'duration' => '45s',
            ],
            [
                'id' => 2,
                'title' => 'Engineering All-Hands',
                'date' => 'Oct 20, 2023',
                'logs_count' => 1,
                'prompt_version' => 'v1.9',
                'status' => 'Failed',
                'duration' => '12s',
            ],
        ];

        return view('debug', ['meetings' => $meetings]);
    }
}
