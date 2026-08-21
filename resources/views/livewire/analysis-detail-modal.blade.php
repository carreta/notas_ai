@php
    $result = $analysis?->result ?? [];
    $summary = $result['summary'] ?? '';
    $decisions = $result['decisions'] ?? [];
    $actionItems = $result['action_items'] ?? [];
    $openQuestions = $result['open_questions'] ?? [];
    $meeting = $analysis?->meeting;
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
            class="modal-backdrop absolute inset-0 transition-opacity"
            wire:click="closeModal"
        ></div>

        <!-- Modal Content Container -->
        <div
            class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0_12px_24px_rgba(0,0,0,0.08)] w-full max-w-4xl max-h-[90vh] flex flex-col relative z-10 mx-md"
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

                <!-- Analysis Summary -->
                <div
                    class="flex flex-col gap-xl"
                    wire:show="activeTab === 'analysis'"
                >

                    <!-- Summary -->
                    @if($summary)
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg"
                    >

                        <h3
                            class="text-body-lg font-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant"
                        >
                            <span class="material-symbols-outlined text-primary">
                                summarize
                            </span>

                            Summary
                        </h3>

                        <p class="text-body-md font-body-md text-on-surface-variant whitespace-pre-wrap">
                            {{ $summary }}
                        </p>

                    </div>
                    @endif

                    <!-- Key Decisions -->
                    @if(!empty($decisions))
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg"
                    >

                        <h3
                            class="text-body-lg font-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant"
                        >
                            <span class="material-symbols-outlined text-primary">
                                gavel
                            </span>

                            Key Decisions
                        </h3>

                        <ul
                            class="list-disc list-inside text-body-md font-body-md text-on-surface-variant space-y-sm pl-sm"
                        >
                            @foreach($decisions as $decision)
                            <li>
                                {{ is_array($decision) ? ($decision['text'] ?? '') : $decision }}
                            </li>
                            @endforeach
                        </ul>

                    </div>
                    @endif


                    <!-- Action Items -->
                    @if(!empty($actionItems))
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg"
                    >

                        <h3
                            class="text-body-lg font-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant"
                        >
                            <span class="material-symbols-outlined text-tertiary">
                                check_circle
                            </span>

                            Action Items
                        </h3>

                        <div class="space-y-md">
                            @foreach($actionItems as $item)
                            <!-- Action Item -->
                            <div
                                class="flex items-start justify-between bg-surface-container-low p-sm rounded border border-surface-variant"
                            >

                                <div class="flex items-start gap-sm">

                                    <span class="material-symbols-outlined text-outline-variant mt-0.5">
                                        radio_button_unchecked
                                    </span>

                                    <div>

                                        <p class="text-body-md font-body-md text-on-surface">
                                            {{ is_array($item) ? ($item['task'] ?? '') : $item }}
                                        </p>

                                        <div class="flex items-center gap-md mt-xs text-label-sm font-label-sm">
                                            @if(isset($item['owner']) && $item['owner'])
                                            <span class="bg-primary-fixed text-on-primary-fixed px-xs py-0.5 rounded">
                                                Owner: {{ $item['owner'] }}
                                            </span>
                                            @endif

                                            @if(isset($item['priority']) && $item['priority'])
                                            <span class="flex items-center gap-xs {{ $item['priority'] === 'HIGH' ? 'text-error' : ($item['priority'] === 'MEDIUM' ? 'text-on-surface-variant' : 'text-on-surface-variant') }}">
                                                <span class="material-symbols-outlined text-[14px]">
                                                    flag
                                                </span>
                                                Priority: {{ $item['priority'] }}
                                            </span>
                                            @endif

                                            @if(isset($item['due_date']) && $item['due_date'])
                                            <span class="flex items-center gap-xs {{ isset($item['priority']) && $item['priority'] === 'HIGH' ? 'text-error' : 'text-on-surface-variant' }}">
                                                <span class="material-symbols-outlined text-[14px]">
                                                    event
                                                </span>
                                                Due: {{ \Carbon\Carbon::parse($item['due_date'])->format('M d, Y') }}
                                            </span>
                                            @endif
                                        </div>

                                    </div>

                                </div>

                            </div>
                            @endforeach
                        </div>

                    </div>
                    @endif


                    <!-- Open Questions -->
                    @if(!empty($openQuestions))
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg"
                    >

                        <h3
                            class="text-body-lg font-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant"
                        >
                            <span class="material-symbols-outlined text-tertiary">
                                help
                            </span>

                            Open Questions
                        </h3>

                        <ul
                            class="list-disc list-inside text-body-md font-body-md text-on-surface-variant space-y-sm pl-sm"
                        >
                            @foreach($openQuestions as $question)
                            <li>
                                {{ is_array($question) ? ($question['text'] ?? '') : $question }}
                            </li>
                            @endforeach
                        </ul>

                    </div>
                    @endif

                    @if(empty($summary) && empty($decisions) && empty($actionItems) && empty($openQuestions))
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg text-center text-on-surface-variant"
                    >
                        No analysis data available.
                    </div>
                    @endif

                </div>


                <!-- Original Transcript -->
                <div
                    class="flex flex-col gap-md max-w-2xl mx-auto w-full"
                    wire:show="activeTab === 'transcript'"
                >
                    @if($meeting && $meeting->raw_text)
                    <div
                        class="bg-surface-container-lowest p-md rounded border border-outline-variant whitespace-pre-wrap text-body-md font-mono text-on-surface leading-relaxed"
                    >
                        {{ $meeting->raw_text }}
                    </div>
                    @else
                    <div
                        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg text-center text-on-surface-variant"
                    >
                        No transcript available.
                    </div>
                    @endif
                </div>

            </div>

        </div>
    </div>
    @endif
</div>