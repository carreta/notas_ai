<?php

namespace App\Livewire;

use App\Models\Analysis;
use App\Models\Meeting;
use Livewire\Component;
use Livewire\Attributes\On;

class AnalysisDetailModal extends Component
{
    public ?string $analysisId = null;

    public ?string $meetingId = null;

    public ?Analysis $analysis = null;

    public string $activeTab = 'analysis';

    protected $listeners = [
        'openAnalysisModal' => 'openModal',
        'openMeetingModal' => 'openModalByMeeting',
    ];

    public function mount(?string $analysisId = null): void
    {
        $this->analysisId = $analysisId;
        $this->loadAnalysis();
    }

    public function openModal(string $analysisId): void
    {
        $this->analysisId = $analysisId;
        $this->meetingId = null;
        $this->loadAnalysis();
        $this->activeTab = 'analysis';
    }

    #[On('openMeetingModal')]
    public function openModalByMeeting(string $meetingId): void
    {
        logger()->info('[AnalysisDetailModal] openModalByMeeting called', ['meetingId' => $meetingId]);
        $this->meetingId = $meetingId;
        $this->analysisId = null;
        $this->loadAnalysisFromMeeting();
        $this->activeTab = 'analysis';
    }

    public function closeModal(): void
    {
        $this->analysisId = null;
        $this->meetingId = null;
        $this->analysis = null;
        $this->activeTab = 'analysis';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    private function loadAnalysis(): void
    {
        if ($this->analysisId) {
            $this->analysis = Analysis::with('meeting')->find($this->analysisId);
        }
    }

    private function loadAnalysisFromMeeting(): void
    {
        if ($this->meetingId) {
            $meeting = Meeting::with('analysis')->find($this->meetingId);
            $this->analysis = $meeting?->analysis;
            if ($this->analysis) {
                $this->analysis->load('meeting');
            }
        }
    }

    public function render()
    {
        return view('livewire.analysis-detail-modal', [
            'analysis' => $this->analysis,
            'activeTab' => $this->activeTab,
            'showModal' => $this->analysisId !== null || $this->meetingId !== null,
        ]);
    }
}