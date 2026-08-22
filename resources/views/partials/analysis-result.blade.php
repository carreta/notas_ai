@php
    // Reusable FR-006 result presentation. Used by the analysis detail modal
    // (Livewire) and by the standalone meeting detail page. Safe against null
    // analysis / empty sections / nullable fields.
    $result = $analysis?->result ?? [];
    $summary = $result['summary'] ?? '';
    $decisions = $result['decisions'] ?? [];
    $actionItems = $result['action_items'] ?? [];
    $openQuestions = $result['open_questions'] ?? [];
    $meeting = $analysis?->meeting ?? $meeting ?? null;
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

                        @php
                            // Due date display: prefer resolved absolute date, otherwise
                            // fall back to the original relative expression so users still
                            // see a due-date signal when only due_date_text is available
                            // (e.g. due_date = null, due_date_text = "next Friday").
                            $dueDisplay = null;
                            if (! empty($item['due_date'])) {
                                $dueDisplay = \Carbon\Carbon::parse($item['due_date'])->format('M d, Y');
                            } elseif (! empty($item['due_date_text'])) {
                                $dueDisplay = $item['due_date_text'];
                            }

                            // Subtle provenance labels (TD-010 / TD-011). These are shown
                            // only when present and kept visually muted on purpose.
                            $prioritySourceLabel = [
                                'EXPLICIT' => 'Explicit',
                                'INFERRED' => 'Inferred',
                            ][$item['priority_source'] ?? ''] ?? null;

                            $dueSourceLabel = [
                                'EXPLICIT' => 'Explicit',
                                'RESOLVED' => 'Resolved',
                                'INFERRED' => 'Inferred',
                                'UNRESOLVED' => 'Unresolved',
                            ][$item['due_date_source'] ?? ''] ?? null;
                        @endphp

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
                                @if($prioritySourceLabel)
                                <span class="opacity-70">· {{ $prioritySourceLabel }}</span>
                                @endif
                            </span>
                            @endif

                            @if($dueDisplay)
                            <span class="flex items-center gap-xs {{ isset($item['priority']) && $item['priority'] === 'HIGH' ? 'text-error' : 'text-on-surface-variant' }}">
                                <span class="material-symbols-outlined text-[14px]">
                                    event
                                </span>
                                Due: {{ $dueDisplay }}
                                @if($dueSourceLabel)
                                <span class="opacity-70">· {{ $dueSourceLabel }}</span>
                                @endif
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

    <!-- Re-analyze Meeting Section -->
    <div
        class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg mb-md"
        wire:show="activeTab === 'analysis'"
        x-data="{
            // Use $wire directly for reactive access to Livewire properties (like AnalyzeForm)
            get reAnalyzeStage() { return $wire.reAnalyzeStage; },
            get reAnalyzeProgress() { return $wire.reAnalyzeProgress; },
            get reAnalyzeLabel() { return $wire.reAnalyzeLabel; },
            get reAnalyzeError() { return $wire.reAnalyzeError; },
            get reAnalyzeErrorCategory() { return $wire.reAnalyzeErrorCategory; },

            get progressPercent() {
                return this.reAnalyzeProgress ?? 0;
            },

            get progressLabel() {
                return this.reAnalyzeLabel ?? '';
            },

            async handleReAnalyze() {
                if (this.reAnalyzeStage !== 'idle') return;

                // Start the re-analysis
                await this.$wire.reAnalyze();

                // Poll for progress updates
                const pollInterval = setInterval(async () => {
                    await this.$wire.$refresh();
                    if (this.reAnalyzeStage === 'completed' || this.reAnalyzeStage === 'error' || this.reAnalyzeStage === 'idle') {
                        clearInterval(pollInterval);
                        // If completed, trigger a full refresh to update the analysis content
                        if (this.reAnalyzeStage === 'completed') {
                            await this.$wire.$refresh();
                        }
                    }
                }, 500);
            },

            async handleExecuteReAnalyze() {
                if (this.reAnalyzeStage !== 'analyzing') return;

                // Execute the actual analysis
                await this.$wire.executeReAnalyze();

                // Continue polling
                const pollInterval = setInterval(async () => {
                    await this.$wire.$refresh();
                    if (this.reAnalyzeStage === 'completed' || this.reAnalyzeStage === 'error' || this.reAnalyzeStage === 'idle') {
                        clearInterval(pollInterval);
                        if (this.reAnalyzeStage === 'completed') {
                            await this.$wire.$refresh();
                        }
                    }
                }, 500);
            },

            async handleRetryReAnalyze() {
                await this.$wire.retryReAnalyze();
                const pollInterval = setInterval(async () => {
                    await this.$wire.$refresh();
                    if (this.reAnalyzeStage === 'completed' || this.reAnalyzeStage === 'error' || this.reAnalyzeStage === 'idle') {
                        clearInterval(pollInterval);
                        if (this.reAnalyzeStage === 'completed') {
                            await this.$wire.$refresh();
                        }
                    }
                }, 500);
            }
        }"
    >
        <h3
            class="text-body-lg font-body-lg font-semibold text-on-surface flex items-center gap-sm mb-md pb-xs border-b border-surface-variant"
        >
            <span class="material-symbols-outlined text-primary">
                psychology
            </span>
            Re-analyze Meeting
        </h3>

        <div class="flex flex-col md:flex-row gap-md items-end">
            <!-- AI Model Selector -->
            <div class="grow w-full">
                <label
                    for="re-analyze-model-select"
                    class="block text-label-sm font-label-sm text-on-surface-variant mb-xs"
                >
                    Select AI Model
                </label>

                <select
                    wire:model.live="reAnalyzeModel"
                    id="re-analyze-model-select"
                    class="w-full bg-surface-container-low border border-outline-variant rounded px-md py-sm text-body-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                    @disabled($reAnalyzeStage !== 'idle')
                    x-ref="modelSelect"
                >
                    @foreach($models as $key => $modelConfig)
                        <option value="{{ $key }}">
                            {{ $modelConfig['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Re-analyze Button -->
            <button
                type="button"
                x-on:click="handleReAnalyze()"
                x-ref="reAnalyzeBtn"
                class="bg-primary text-on-primary px-lg py-sm rounded font-label-md flex items-center justify-center gap-xs hover:bg-primary-container transition-colors h-10.5 whitespace-nowrap"
                :class="{ 'opacity-50 cursor-not-allowed': reAnalyzeStage !== 'idle' }"
                :disabled="reAnalyzeStage !== 'idle'"
            >
                <span class="material-symbols-outlined text-[18px]">
                    refresh
                </span>
                <span x-show="reAnalyzeStage === 'idle'">Re-analyze</span>
                <span x-show="reAnalyzeStage !== 'idle'">Re-analyzing...</span>
            </button>
        </div>

        <!-- Progress Bar -->
        <template x-if="reAnalyzeStage !== 'idle' && reAnalyzeStage !== 'completed' && reAnalyzeStage !== 'error'">
            <div
                class="w-full bg-surface-container border border-surface-tint/20 rounded-lg p-md flex items-center gap-md mt-md"
                x-ref="progressBar"
            >
                <div class="relative w-8 h-8 flex items-center justify-center">
                    <svg
                        class="animate-spin text-primary w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>

                        <path
                            class="opacity-75"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l2-2.647z"
                            fill="currentColor"
                        ></path>
                    </svg>
                </div>

                <div class="flex-1">
                    <p class="font-mono text-label-md text-on-surface" x-text="progressLabel"></p>

                    <div class="w-full bg-outline-variant/30 h-1.5 rounded-full mt-sm overflow-hidden">
                        <div
                            class="bg-primary h-full rounded-full transition-all duration-500"
                            :style="'width: ' + progressPercent + '%'"
                        ></div>
                    </div>
                </div>

                <span class="font-mono text-label-sm text-outline" x-text="progressPercent + '%'"></span>
            </div>
        </template>

        <!-- Error Display -->
        <template x-if="reAnalyzeStage === 'error'">
            <div class="mt-md p-md bg-error-container border border-error/20 rounded-lg text-error">
                <p class="font-mono text-label-md flex items-center gap-sm">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">error</span>
                    <span x-text="reAnalyzeError"></span>
                </p>
                <p class="font-sans text-body-sm text-error/70 mt-xs" x-text="$wire.getReAnalyzeErrorDescription()"></p>
                <div class="mt-md flex justify-end">
                    <button
                        type="button"
                        x-on:click="handleRetryReAnalyze()"
                        class="bg-error text-on-error font-mono text-label-sm px-md py-xs rounded-lg hover:bg-error/90 transition-colors flex items-center gap-xs"
                    >
                        <span class="material-symbols-outlined text-[16px]">refresh</span>
                        <span>Retry Re-analysis</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Success Display -->
        <template x-if="reAnalyzeStage === 'completed'">
            <div class="mt-md p-md bg-success-container/20 border border-success/30 rounded-lg text-success flex items-center gap-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-label-md">Re-analysis completed successfully</span>
            </div>
        </template>

        <!-- Trigger executeReAnalyze when stage changes to analyzing -->
        <div x-init="
            $watch('reAnalyzeStage', (value) => {
                if (value === 'analyzing') {
                    handleExecuteReAnalyze();
                }
            });
        "></div>
    </div>
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
