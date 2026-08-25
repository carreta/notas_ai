<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HistoryController extends Controller
{
    /**
     * Columns that may be used for sorting.
     *
     * User input is never passed directly to ORDER BY.
     */
    private const ALLOWED_SORTS = [
        'title',
        'meeting_time',
        'status',
        'created_at',
    ];

    private const STATUS_OPTIONS = [
        'DRAFT' => 'Draft',
        'VALIDATED' => 'Validated',
        'ANALYZING' => 'Analyzing',
        'COMPLETED' => 'Completed',
        'FAILED' => 'Failed',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');

        $sort = (string) $request->input('sort', '');
        $dir = strtolower((string) $request->input('dir', 'desc'));

        if (! in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }

        $query = Meeting::query();

        /*
         * Search by meeting title.
         *
         * PostgreSQL ILIKE provides case-insensitive matching.
         * Laravel binds the value as a query parameter.
         */
        if ($search !== '') {
            $query->where('title', 'ilike', '%'.$search.'%');
        }

        /*
         * Filter by processing status.
         *
         * Only application-defined statuses are accepted.
         */
        if (
            $status !== ''
            && array_key_exists($status, self::STATUS_OPTIONS)
        ) {
            $query->where('status', $status);
        }

        /*
         * Filter by meeting date.
         */
        if ($dateFrom !== '') {
            $query->whereDate('meeting_time', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('meeting_time', '<=', $dateTo);
        }

        /*
         * Sorting.
         *
         * Only explicitly whitelisted columns may reach orderBy().
         * Invalid input falls back to newest meetings first.
         */
        if (in_array($sort, self::ALLOWED_SORTS, true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderByDesc('created_at');
        }

        $meetings = $query->get();

        /*
         * Preserve the existing post-analysis modal behavior.
         */
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
            'meetings' => $meetings,
            'selectedAnalysis' => $selectedAnalysis,
            'selectedAnalysisId' => $selectedAnalysisId,

            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'dir' => $dir,
            ],

            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }
}
