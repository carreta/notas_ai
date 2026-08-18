@extends('layouts.app')

@section('title', 'Meeting History')

@section('content')
<!-- Header Section -->
<header class="w-full max-w-3xl mb-xl flex justify-between items-end border-b border-surface-variant pb-md">
    <div>
        <h1 class="font-sans text-headline-lg text-on-surface mb-sm">Meeting History</h1>
        <p class="font-sans text-body-lg text-on-surface-variant">Review past analysis, extracted action items, and original transcripts.</p>
    </div>
    <div class="flex gap-sm">
        <button class="border border-outline-variant rounded px-md py-sm text-label-md font-mono text-on-surface hover:bg-surface-container-low flex items-center gap-xs transition-colors">
            <span class="material-symbols-outlined text-[18px]">filter_list</span>
            Filter
        </button>
    </div>
</header>

<!-- Data Table -->
<div class="w-full max-w-3xl bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead class="bg-surface-container-low border-b border-outline-variant">
            <tr>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs cursor-pointer hover:text-primary transition-colors">
                        Meeting Date
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs cursor-pointer hover:text-primary transition-colors">
                        Title
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs cursor-pointer hover:text-primary transition-colors">
                        Date Processed
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs cursor-pointer hover:text-primary transition-colors">
                        Status
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant text-on-surface">
            @foreach($meetings as $meeting)
            <tr class="hover:bg-surface-container-lowest hover:shadow-sm cursor-pointer transition-all duration-200 group" onclick="openHistoryModal('{{ $meeting['id'] }}')">
                <td class="px-md py-md text-body-sm font-sans text-on-surface">{{ $meeting['date'] }}</td>
                <td class="px-md py-md text-body-md font-sans font-semibold text-on-surface group-hover:text-primary transition-colors">{{ $meeting['title'] }}</td>
                <td class="px-md py-md text-body-sm font-sans text-on-surface-variant">{{ $meeting['processed'] }}</td>
                <td class="px-md py-md">
                    @if($meeting['status'] === 'Completed')
                        <span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono bg-secondary-container text-on-secondary-container">Completed</span>
                    @elseif($meeting['status'] === 'In Progress')
                        <span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono bg-tertiary-container text-on-tertiary-container">
                            <span class="material-symbols-outlined text-[14px] mr-1 animate-spin">sync</span>
                            In Progress
                        </span>
                    @elseif($meeting['status'] === 'Failed')
                        <span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono bg-error-container text-on-error-container border border-error-container">Failed</span>
                    @endif
                </td>
                <td class="px-md py-md text-right">
                    <button class="text-on-surface-variant hover:text-primary transition-colors p-xs" aria-label="View details">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Detail Modal -->
<div class="fixed inset-0 z-100 hidden items-center justify-center" id="historyModal">
    <div class="absolute inset-0 bg-on-surface/40 backdrop-blur-sm" onclick="closeHistoryModal()"></div>
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0_12px_24px_rgba(0,0,0,0.08)] w-full max-w-4xl max-h-[90vh] flex flex-col relative z-10 mx-md transform transition-transform scale-95 opacity-0" id="historyModalContent">
        <header class="px-xl py-lg border-b border-surface-variant bg-surface-bright rounded-t-xl flex justify-between items-start">
            <div>
                <h2 class="font-sans text-headline-md text-on-surface mb-xs" id="modalTitle">Meeting Title</h2>
                <div class="flex items-center gap-md text-label-sm font-mono text-on-surface-variant">
                    <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[16px]">calendar_today</span> <span id="modalDate">Date</span></span>
                    <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[16px]">schedule</span> <span id="modalDuration">Duration</span></span>
                    <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[16px]">group</span> <span id="modalParticipants">Participants</span></span>
                </div>
            </div>
            <button class="text-on-surface-variant hover:text-primary p-sm rounded hover:bg-surface-container-low transition-colors" onclick="closeHistoryModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="px-xl pt-sm border-b border-surface-variant bg-surface-bright flex gap-lg">
            <button class="text-label-md font-mono text-primary border-b-2 border-primary pb-sm px-sm font-semibold transition-colors" id="tab-analysis" onclick="switchHistoryTab('analysis')">Analysis Summary</button>
            <button class="text-label-md font-mono text-on-surface-variant border-b-2 border-transparent hover:text-on-surface pb-sm px-sm transition-colors" id="tab-transcript" onclick="switchHistoryTab('transcript')">Original Transcript</button>
        </div>
        <div class="p-xl overflow-y-auto grow bg-background rounded-b-xl" id="historyModalScrollArea">
            <div class="flex flex-col gap-xl" id="content-analysis">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <h3 class="font-sans text-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant">
                        <span class="material-symbols-outlined text-primary">gavel</span> Key Decisions
                    </h3>
                    <ul class="list-disc list-inside text-body-md font-sans text-on-surface-variant space-y-sm pl-sm" id="modalDecisions">
                        <li>Loading...</li>
                    </ul>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <h3 class="font-sans text-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant">
                        <span class="material-symbols-outlined text-tertiary">check_circle</span> Action Items
                    </h3>
                    <div class="space-y-md" id="modalActions">
                        <div class="flex items-start justify-between bg-surface-container-low p-sm rounded border border-surface-variant">
                            <div class="flex items-start gap-sm">
                                <span class="material-symbols-outlined text-outline-variant mt-0.5">radio_button_unchecked</span>
                                <div>
                                    <p class="font-sans text-body-md text-on-surface">Loading...</p>
                                    <div class="flex items-center gap-md mt-xs text-label-sm font-mono">
                                        <span class="bg-primary-fixed text-on-primary-fixed px-xs py-0.5 rounded">Owner: TBD</span>
                                        <span class="text-on-surface-variant flex items-center gap-xs"><span class="material-symbols-outlined text-[14px]">event</span> Due: TBD</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <h3 class="font-sans text-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant">
                        <span class="material-symbols-outlined text-secondary">help</span> Open Questions
                    </h3>
                    <ul class="list-disc list-inside text-body-md font-sans text-on-surface-variant space-y-sm pl-sm" id="modalQuestions">
                        <li>Loading...</li>
                    </ul>
                </div>
            </div>
            <div class="hidden flex-col gap-md max-w-200 mx-auto w-full " id="content-transcript">
                <div class="flex gap-md bg-surface-container-lowest p-md rounded border border-transparent hover:border-surface-variant transition-colors">
                    <div class="w-24 shrink-0 text-right">
                        <span class="text-label-sm font-mono font-bold text-primary block">Speaker</span>
                        <span class="text-label-sm font-mono text-outline-variant block">00:00</span>
                    </div>
                    <div class="text-body-md font-sans text-on-surface leading-relaxed" id="modalTranscript">
                        Loading transcript...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection