@php
    $logStatus = [
        'COMPLETED' => ['label' => 'Completed', 'class' => 'border-outline bg-surface-container text-on-surface', 'dot' => 'bg-primary-container'],
        'FAILED' => ['label' => 'Failed', 'class' => 'border-error-container bg-error-container text-on-error-container', 'dot' => 'bg-error'],
    ];

    $formatDuration = static function (?int $milliseconds): string {
        if ($milliseconds === null) {
            return '—';
        }

        return $milliseconds >= 1000
            ? number_format($milliseconds / 1000, $milliseconds % 1000 === 0 ? 0 : 1).'s'
            : $milliseconds.'ms';
    };

    $durationForLog = static function ($log): ?int {
        if ($log->analysis?->metric?->duration_ms !== null) {
            return $log->analysis->metric->duration_ms;
        }

        if (! $log->started_at || ! $log->completed_at) {
            return null;
        }

        return (int) $log->completed_at->diffInMilliseconds($log->started_at);
    };
@endphp

<div class="w-full max-w-5xl">
    @if (! $selectedMeeting)
        <header class="mb-lg" id="page-header">
            <h1 class="font-sans text-headline-lg text-on-surface mb-xs">Debug Console</h1>
            <p class="font-sans text-body-lg text-secondary">System logs and prompt performance tracking.</p>
        </header>

        <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="debugMeetingsTable">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-secondary">
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Meeting Date</th>
                            <th class="p-4 font-mono text-label-md font-bold w-full">Title</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-right">Logs</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Status</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-on-surface">
                        @forelse ($meetings as $meeting)
                            <tr
                                class="hover:bg-surface-container-lowest transition-colors group cursor-pointer"
                                wire:click="selectMeeting('{{ $meeting->id }}')"
                            >
                                <td class="p-4 font-sans text-body-sm whitespace-nowrap text-secondary">{{ $meeting->meeting_time?->format('M d, Y') ?? '—' }}</td>
                                <td class="p-4 font-sans text-body-md font-semibold">{{ $meeting->title }}</td>
                                <td class="p-4 font-mono text-label-md text-right"><span class="bg-surface-container-high px-2 py-1 rounded-full text-on-surface-variant">{{ $meeting->analysis_logs_count }}</span></td>
                                <td class="p-4 whitespace-nowrap">@include('partials.status-badge', ['status' => $meeting->status])</td>
                                <td class="p-4 text-center text-secondary group-hover:text-primary transition-colors"><span class="material-symbols-outlined">chevron_right</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center font-sans text-body-md text-secondary">No meetings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mb-lg">
            <button
                type="button"
                class="text-primary hover:text-primary-container transition-colors font-mono text-label-md flex items-center gap-1 cursor-pointer"
                wire:click="backToMeetings"
            >
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                Back to Meetings
            </button>
        </div>

        <header class="mb-lg">
            <h1 class="font-sans text-headline-lg text-on-surface">Logs for {{ $selectedMeeting->title }}</h1>
        </header>

        <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="debugLogsTable">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-secondary">
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Starting Time</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Duration</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Model</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Status</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-right">Tokens</th>
                            <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-on-surface">
                        @forelse ($logs as $log)
                            @php
                                $metric = $log->analysis?->metric;
                                $duration = $durationForLog($log);
                            @endphp
                            <tr
                                class="hover:bg-surface-container-lowest transition-colors cursor-pointer group"
                                wire:click="selectLog('{{ $log->id }}')"
                            >
                                <td class="p-4 font-sans text-body-sm whitespace-nowrap text-secondary">{{ $log->started_at?->format('H:i:s') ?? '—' }}</td>
                                <td class="p-4 font-sans text-body-sm whitespace-nowrap">{{ $formatDuration($duration) }}</td>
                                <td class="p-4 font-sans text-body-sm whitespace-nowrap">{{ $log->model }}</td>
                                <td class="p-4 whitespace-nowrap">@include('partials.status-badge', ['status' => $log->status])</td>
                                <td class="p-4 font-mono text-label-md text-right text-secondary">{{ $metric ? number_format($metric->total_tokens) : '—' }}</td>
                                <td class="p-4 text-center text-secondary group-hover:text-primary transition-colors"><span class="material-symbols-outlined">chevron_right</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center font-sans text-body-md text-secondary">No logs found for this meeting.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($selectedLog)
        @php
            $metric = $selectedLog->analysis?->metric;
            $status = $logStatus[$selectedLog->status] ?? ['label' => $selectedLog->status, 'class' => 'border-outline bg-surface-container text-on-surface', 'dot' => 'bg-secondary'];
            $duration = $durationForLog($selectedLog);
        @endphp
        <div class="fixed inset-0 z-100 flex items-center justify-center p-lg" id="logDetailsModal" role="dialog" aria-modal="true" aria-labelledby="log-details-title">
            <button type="button" class="absolute inset-0 bg-inverse-surface/80 backdrop-blur-sm cursor-default" aria-label="Close log details" wire:click="closeModal"></button>
            <div class="w-full max-w-3xl max-h-[90vh] bg-surface-container-lowest rounded-xl border border-outline-variant shadow-2xl flex flex-col overflow-hidden relative">
                <header class="flex items-center justify-between px-lg py-md border-b border-outline-variant bg-surface-container-low">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-primary">description</span>
                        <h2 class="font-sans text-headline-md text-on-surface" id="log-details-title">Log Details - {{ $selectedLog->started_at?->format('M d, Y') ?? 'Unknown Date' }}</h2>
                    </div>
                    <button type="button" class="p-2 rounded-full hover:bg-surface-container-high transition-colors text-on-surface-variant" wire:click="closeModal" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
                </header>

                <div class="flex border-b border-outline-variant bg-surface-container-lowest">
                    <button type="button" class="flex-1 py-sm px-md text-center font-mono text-label-md font-medium border-b-2 transition-colors {{ $activeTab === 'metadata' ? 'text-primary border-primary bg-surface-container-low' : 'text-on-surface-variant border-transparent hover:text-on-surface hover:bg-surface-container-low' }}" wire:click="setTab('metadata')">Metadata</button>
                    <button type="button" class="flex-1 py-sm px-md text-center font-mono text-label-md font-medium border-b-2 transition-colors {{ $activeTab === 'prompt' ? 'text-primary border-primary bg-surface-container-low' : 'text-on-surface-variant border-transparent hover:text-on-surface hover:bg-surface-container-low' }}" wire:click="setTab('prompt')">Prompt View</button>
                </div>

                <div class="flex-1 overflow-y-auto p-lg bg-surface-container-lowest">
                    @if ($activeTab === 'metadata')
                        <section class="space-y-lg">
                            <div>
                                <h3 class="font-sans text-body-sm text-secondary mb-md uppercase tracking-wider font-semibold">Metadata</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-md">
                                    <x-debug.detail-card label="Meeting Title" :value="$selectedMeeting->title" />
                                    <x-debug.detail-card label="Meeting Date" :value="$selectedMeeting->meeting_time?->format('M d, Y') ?? '—'" />
                                    <x-debug.detail-card label="Log Status"><span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border font-mono text-label-sm {{ $status['class'] }}"><span class="w-1.5 h-1.5 rounded-full {{ $status['dot'] }}"></span>{{ $status['label'] }}</span></x-debug.detail-card>
                                    <x-debug.detail-card label="Started At" :value="$selectedLog->started_at?->format('H:i:s') ?? '—'" />
                                    <x-debug.detail-card label="Completed At" :value="$selectedLog->completed_at?->format('H:i:s') ?? '—'" />
                                    <x-debug.detail-card label="AI Provider" :value="$selectedLog->provider" />
                                    <x-debug.detail-card label="AI Model" :value="$selectedLog->model" />
                                    <x-debug.detail-card label="Prompt Version" :value="$selectedLog->prompt_version" />
                                </div>
                            </div>
                            <div>
                                <h3 class="font-sans text-body-sm text-secondary mb-md uppercase tracking-wider font-semibold">Performance Metrics</h3>
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-md">
                                    <x-debug.detail-card label="Error Category" :value="$selectedLog->error_category ?? 'None'" />
                                    <x-debug.detail-card label="Prompt Tokens" :value="$metric ? number_format($metric->prompt_tokens) : '—'" />
                                    <x-debug.detail-card label="Completion Tokens" :value="$metric ? number_format($metric->completion_tokens) : '—'" />
                                    <x-debug.detail-card label="Total Tokens" :value="$metric ? number_format($metric->total_tokens) : '—'" />
                                    <x-debug.detail-card label="API Duration" :value="$formatDuration($duration)" />
                                </div>
                            </div>
                            @if ($selectedLog->error_message)
                                <div class="bg-error-container border border-error-container rounded-lg p-md text-on-error-container"><span class="font-mono text-label-sm block mb-xs">Error Message</span>{{ $selectedLog->error_message }}</div>
                            @endif
                        </section>
                    @else
                        <section class="bg-surface-container-low border border-outline-variant rounded-lg p-lg">
                            <h3 class="font-sans text-body-sm text-secondary mb-md uppercase tracking-wider font-semibold">System Prompt</h3>
                            <pre class="font-mono text-label-sm text-on-surface whitespace-pre-wrap leading-relaxed overflow-x-auto">{{ $selectedLog->promptTemplate?->system_prompt ?? 'No prompt template was found for this log.' }}</pre>
                        </section>
                    @endif
                </div>

                <footer class="p-md border-t border-outline-variant bg-surface-container-lowest flex justify-end">
                    <button type="button" class="px-md py-sm rounded-lg bg-primary text-on-primary font-mono text-label-md hover:bg-primary-container transition-colors" wire:click="closeModal">Close</button>
                </footer>
            </div>
        </div>
    @endif
</div>
