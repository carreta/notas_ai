<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

class HistoryController extends Controller
{
    public function index()
    {
        $meetings = [
            [
                'id' => 1,
                'title' => 'Q3 Planning Session',
                'date' => 'Oct 24, 2023',
                'processed' => 'Oct 25, 2023',
                'status' => 'Completed',
            ],
            [
                'id' => 2,
                'title' => 'Sprint Retrospective',
                'date' => 'Oct 20, 2023',
                'processed' => 'Oct 21, 2023',
                'status' => 'In Progress',
            ],
            [
                'id' => 3,
                'title' => 'Budget Review Meeting',
                'date' => 'Oct 18, 2023',
                'processed' => 'Oct 19, 2023',
                'status' => 'Failed',
            ],
        ];

        return view('history', ['meetings' => $meetings]);
    }
}
