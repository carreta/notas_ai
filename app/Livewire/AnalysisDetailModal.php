<?php

namespace App\Livewire;

use App\Models\Analysis;
use Livewire\Component;

class AnalysisDetailModal extends Component
{
    public ?string $analysisId = null;

    public ?Analysis $analysis = null;

    public string $activeTab = 'analysis';

    protected $listeners = [
        'openAnalysisModal' => 'openModal',
    ];

    public function mount(?string $analysisId = null): void
    {
        $this->analysisId = $analysisId;
        $this->loadAnalysis();
    }

    public function openModal(string $analysisId): void
    {
        $this->analysisId = $analysisId;
        $this->loadAnalysis();
        $this->activeTab = 'analysis';
    }

    public function closeModal(): void
    {
        $this->analysisId = null;
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

    public function render()
    {
        return view('livewire.analysis-detail-modal', [
            'analysis' => $this->analysis,
            'activeTab' => $this->activeTab,
            'showModal' => $this->analysisId !== null,
        ]);
    }
}