<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HistoryController extends Controller
{
    /**
     * Render the History page.
     *
     * Search / filter / sort logic now lives in the `HistoryList` Livewire
     * component (which reads any incoming request filters in its mount()), so
     * this controller only handles the post-analysis modal deep-link via the
     * session, preserving the existing "open analysis from another page" flow.
     */
    public function index(Request $request)
    {
        $selectedAnalysisId = $request->session()->pull(
            'analysis_id',
            null
        );

        $selectedAnalysis = null;

        if ($selectedAnalysisId) {
            $selectedAnalysis = Analysis::with('meeting')
                ->find($selectedAnalysisId);
        }

        return view('history', [
            'selectedAnalysis' => $selectedAnalysis,
            'selectedAnalysisId' => $selectedAnalysisId,
        ]);
    }
}
