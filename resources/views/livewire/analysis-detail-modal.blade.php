@php
    $result = $analysis?->result ?? [];
    $summary = $result['summary'] ?? '';
    $decisions = $result['decisions'] ?? [];
    $actionItems = $result['action_items'] ?? [];
    $openQuestions = $result['open_questions'] ?? [];
    $meeting = $analysis?->meeting ?? $meeting ?? null;
    $meetingTitle = $meeting?->title ?? 'Unknown Meeting';
    $meetingDate = $meeting?->meeting_time?->format('M d, Y') ?? 'Unknown Date';
    // TODO: Implement duration from analysis_metadata when available
    $duration = 'N/A';
    // TODO: Implement participants count when available
    $participants = 'N/A';
@endphp

<!-- Detail Modal Wrapper -->
<div>
    @if($showModal)
    <div
        class="fixed inset-0 z-100 flex items-center justify-center"
        id="detailModal"
    >

        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-inverse-surface/80 backdrop-blur-sm cursor-default transition-opacity"
            wire:click="closeModal"
        ></div>

        <!-- Modal Content Container -->
        <div
            class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0_12px_24px_rgba(0,0,0,0.08)] w-full max-w-4xl max-h-[85vh] flex flex-col relative z-10 mx-md"
            id="modalContent"
        >

            <!-- Modal Header -->
            <div
                class="px-xl py-lg border-b border-surface-variant flex justify-between items-start bg-surface-bright rounded-t-xl"
            >
                <div>
                    <h2 class="text-headline-md font-headline-md text-on-surface mb-xs">
                        {{ $meetingTitle }}
                    </h2>

                    <div class="flex items-center gap-md text-label-sm font-label-sm text-on-surface-variant">

                        <span class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">
                                calendar_today
                            </span>
                            {{ $meetingDate }}
                        </span>

                        <span class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">
                                schedule
                            </span>
                            {{ $duration }}
                        </span>

                        <span class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">
                                group
                            </span>
                            {{ $participants }} Participants
                        </span>

                    </div>
                </div>

                <div class="flex items-center gap-sm">
                    @if($analysis)
                        @if($editing)
                            <button
                                type="button"
                                wire:click="saveEdits"
                                class="bg-primary text-on-primary font-label-md px-md py-xs rounded hover:bg-primary-container transition-colors flex items-center gap-xs"
                            >
                                <span class="material-symbols-outlined text-[16px]">save</span>
                                Save Changes
                            </button>
                            <button
                                type="button"
                                wire:click="cancelEditing"
                                class="bg-surface-container-low text-on-surface font-label-md px-md py-xs rounded hover:bg-surface-container-lowest transition-colors"
                            >
                                Cancel
                            </button>
                        @else
                            <button
                                type="button"
                                wire:click="startEditing"
                                class="bg-surface-container-low text-on-surface font-label-md px-md py-xs rounded hover:bg-surface-container-lowest transition-colors flex items-center gap-xs"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                Edit
                            </button>
                        @endif
                    @endif

                    <button
                        class="text-on-surface-variant hover:text-primary p-sm rounded hover:bg-surface-container-low transition-colors"
                        wire:click="closeModal"
                        aria-label="Close modal"
                    >
                        <span class="material-symbols-outlined">
                            close
                        </span>
                    </button>
                </div>
            </div>


            <!-- Tab Navigation -->
            <div
                class="px-xl pt-sm border-b border-surface-variant bg-surface-bright flex gap-lg"
            >

                <button
                    class="text-label-md font-label-md {{ $activeTab === 'analysis' ? 'text-primary border-b-2 border-primary pb-sm px-sm font-semibold' : 'text-on-surface-variant border-b-2 border-transparent hover:text-on-surface pb-sm px-sm' }} transition-colors"
                    wire:click="setTab('analysis')"
                >
                    Analysis Summary
                </button>

                <button
                    class="text-label-md font-label-md {{ $activeTab === 'transcript' ? 'text-primary border-b-2 border-primary pb-sm px-sm font-semibold' : 'text-on-surface-variant border-b-2 border-transparent hover:text-on-surface pb-sm px-sm' }} transition-colors"
                    wire:click="setTab('transcript')"
                >
                    Original Transcript
                </button>

            </div>


            <!-- Modal Body -->
            <div
                class="p-xl overflow-y-auto grow bg-background rounded-b-xl"
                id="modalScrollArea"
            >
              <!--   partials/analysis-result -->
                @include('partials.analysis-result', ['editing' => $editing, 'editableResult' => $editableResult])

            </div>

        </div>
    </div>
    @endif
</div>
