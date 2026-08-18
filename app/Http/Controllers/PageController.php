<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('home', ['models' => config('models')]);
    }

    public function history()
    {
        return view('history', [
            'models' => config('models'),
            'meetings' => $this->dummyMeetings(),
        ]);
    }

    public function debug()
    {
        return view('debug', [
            'models' => config('models'),
            'meetings' => $this->dummyMeetings(),
        ]);
    }

    private function dummyMeetings(): array
    {
        return [
            [
                'id' => 1,
                'date' => 'Oct 24, 2023',
                'title' => 'Q4 Strategy Planning Kickoff',
                'processed' => 'Oct 24, 2023 14:30',
                'status' => 'Completed',
                'logs_count' => 3,
                'prompt_version' => 'v2.1',
            ],
            [
                'id' => 2,
                'date' => 'Oct 23, 2023',
                'title' => 'Product Roadmap Sync',
                'processed' => 'Oct 23, 2023 10:15',
                'status' => 'Failed',
                'logs_count' => 12,
                'prompt_version' => 'v2.1.4',
            ],
            [
                'id' => 3,
                'date' => 'Oct 23, 2023',
                'title' => 'Client Discovery Call - Acme Corp',
                'processed' => 'Oct 23, 2023 16:45',
                'status' => 'In Progress',
                'logs_count' => 1,
                'prompt_version' => 'v2.2-beta',
            ],
            [
                'id' => 4,
                'date' => 'Oct 20, 2023',
                'title' => 'Engineering Standup (Corrupted Audio)',
                'processed' => 'Oct 20, 2023 09:05',
                'status' => 'Failed',
                'logs_count' => 0,
                'prompt_version' => 'v2.0',
            ],
        ];
    }
}
