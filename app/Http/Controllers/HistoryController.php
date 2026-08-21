<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        // Real history: list previously submitted meetings in explicit,
        // newest-first order. Status comes straight from the persisted column
        // (never derived from the existence of an analysis row).
        $meetings = Meeting::orderByDesc('created_at')->get();

        // Keep the post-submit flash behavior: when AnalyzeForm::complete()
        // sets session analysis_id, surface that result in the detail modal.
        $selectedAnalysisId = $request->session()->pull('analysis_id', null);

        $selectedAnalysis = null;
        if ($selectedAnalysisId) {
            $selectedAnalysis = Analysis::with('meeting')->find($selectedAnalysisId);
        }

        return view('history', [
            'meetings' => $meetings,
            'selectedAnalysis' => $selectedAnalysis,
            'selectedAnalysisId' => $selectedAnalysisId,
        ]);
    }
}
