<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        // Check session for analysis_id set by AnalyzeForm::complete()
        $selectedAnalysisId = $request->session()->pull('analysis_id', null);

        $selectedAnalysis = null;
        if ($selectedAnalysisId) {
            $selectedAnalysis = Analysis::with('meeting')->find($selectedAnalysisId);
        }

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

        return view('history', [
            'meetings' => $meetings,
            'selectedAnalysis' => $selectedAnalysis,
            'selectedAnalysisId' => $selectedAnalysisId,
        ]);
    }
}
