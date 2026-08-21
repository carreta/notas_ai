<?php

namespace App\Livewire;

use App\Models\AnalysisLog;
use App\Models\Meeting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class DebugConsole extends Component
{
    public ?string $selectedMeetingId = null;

    public ?string $selectedLogId = null;

    public string $activeTab = 'metadata';

    public function selectMeeting(string $meetingId): void
    {
        if (! Meeting::query()->whereKey($meetingId)->exists()) {
            return;
        }

        $this->selectedMeetingId = $meetingId;
        $this->selectedLogId = null;
        $this->activeTab = 'metadata';
    }

    public function backToMeetings(): void
    {
        $this->selectedMeetingId = null;
        $this->closeModal();
    }

    public function selectLog(string $logId): void
    {
        if (! $this->selectedMeetingId) {
            return;
        }

        $logExists = AnalysisLog::query()
            ->whereKey($logId)
            ->where('meeting_id', $this->selectedMeetingId)
            ->exists();

        if (! $logExists) {
            return;
        }

        $this->selectedLogId = $logId;
        $this->activeTab = 'metadata';
    }

    public function closeModal(): void
    {
        $this->selectedLogId = null;
        $this->activeTab = 'metadata';
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['metadata', 'prompt'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function render(): View
    {
        $meetings = Meeting::query()
            ->withCount('analysisLogs')
            ->orderByDesc('created_at')
            ->get();

        $selectedMeeting = $this->selectedMeetingId
            ? Meeting::query()->find($this->selectedMeetingId)
            : null;

        if ($this->selectedMeetingId && ! $selectedMeeting) {
            $this->backToMeetings();
        }

        /** @var Collection<int, AnalysisLog> $logs */
        $logs = $selectedMeeting
            ? $selectedMeeting->analysisLogs()
                ->with(['analysis.metric', 'promptTemplate'])
                ->orderByDesc('started_at')
                ->get()
            : new Collection;

        $selectedLog = $this->selectedLogId
            ? $logs->firstWhere('id', $this->selectedLogId)
            : null;

        if ($this->selectedLogId && ! $selectedLog) {
            $this->closeModal();
        }

        return view('livewire.debug-console', [
            'meetings' => $meetings,
            'selectedMeeting' => $selectedMeeting,
            'logs' => $logs,
            'selectedLog' => $selectedLog,
        ]);
    }
}
