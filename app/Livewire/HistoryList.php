<?php

namespace App\Livewire;

use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HistoryList extends Component
{
    public string $search = '';

    public string $status = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $sort = '';

    public string $dir = 'desc';

    /**
     * Whitelisted sort columns. User input never reaches ORDER BY directly.
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

    /**
     * Seed initial state from the request so shared/bookmarked URLs
     * (e.g. /history?search=postgres&status=COMPLETED) still filter on load.
     */
    public function mount(): void
    {
        $this->search = $this->normalizeSearch(
            (string) request()->query('search', request()->query('q', ''))
        );
        $this->status = (string) request()->query('status', '');
        $this->dateFrom = (string) request()->query('date_from', '');
        $this->dateTo = (string) request()->query('date_to', '');
        $this->sort = (string) request()->query('sort', '');
        $dir = strtolower((string) request()->query('dir', 'desc'));
        $this->dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';
    }

    /**
     * Reset all filters at once (used by the "Clear" control).
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sort = '';
        $this->dir = 'desc';
    }

    public function render()
    {
        $search = $this->normalizeSearch($this->search);
        $status = $this->status;
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;
        $dir = in_array($this->dir, ['asc', 'desc'], true) ? $this->dir : 'desc';

        $query = Meeting::query();

        /*
         * Free-text search across title, raw transcript, and the validated
         * analysis JSON (summary / decisions / action items / open questions).
         * PostgreSQL ILIKE is case-insensitive; the user value is always bound
         * as a parameter. JSON path expressions are fixed literals.
         */
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', '%'.$search.'%')
                    ->orWhere('raw_text', 'ilike', '%'.$search.'%')
                    ->orWhereExists(function ($exists) use ($search) {
                        $exists->select(DB::raw('1'))
                            ->from('analyses')
                            ->whereColumn('analyses.meeting_id', 'meetings.id')
                            ->where(function ($jq) use ($search) {
                                $jq->whereRaw("analyses.result->>'summary' ILIKE ?", ['%'.$search.'%'])
                                    ->orWhereRaw("analyses.result->>'decisions' ILIKE ?", ['%'.$search.'%'])
                                    ->orWhereRaw("analyses.result->>'action_items' ILIKE ?", ['%'.$search.'%'])
                                    ->orWhereRaw("analyses.result->>'open_questions' ILIKE ?", ['%'.$search.'%']);
                            });
                    });
            });
        }

        if ($status !== '' && array_key_exists($status, self::STATUS_OPTIONS)) {
            $query->where('status', $status);
        }

        if ($dateFrom !== '') {
            $query->whereDate('meeting_time', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('meeting_time', '<=', $dateTo);
        }

        if (in_array($this->sort, self::ALLOWED_SORTS, true)) {
            $query->orderBy($this->sort, $dir);
        } else {
            $query->orderByDesc('created_at');
        }

        $meetings = $query->get();

        $filters = [
            'search' => $search,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort' => $this->sort,
            'dir' => $dir,
        ];

        return view('livewire.history-list', [
            'meetings' => $meetings,
            'filters' => $filters,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    private function normalizeSearch(string $value): string
    {
        return mb_substr(trim($value), 0, 100);
    }
}
