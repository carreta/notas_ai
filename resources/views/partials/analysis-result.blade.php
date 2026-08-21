@php
    // Reusable FR-006 result presentation. Used by the analysis detail modal
    // (Livewire) and by the standalone meeting detail page. Safe against null
    // analysis / empty sections / nullable fields.
    $result = $analysis?->result ?? [];
    $summary = $result['summary'] ?? '';
    $decisions = $result['decisions'] ?? [];
    $actionItems = $result['action_items'] ?? [];
    $openQuestions = $result['open_questions'] ?? [];
    $meeting = $analysis?->meeting;
    $meetingTitle = $meeting?->title ?? 'Unknown Meeting';
    $meetingDate = $meeting?->meeting_time?->format('M d, Y') ?? 'Unknown Date';
    $duration = 'N/A';
    $participants = 'N/A';
@endphp

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
                            <span class="flex items-center gap-xs {{ $item['priority'] === 'HIGH' ? 'text-error' : 'text-on-surface-variant' }}">
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
