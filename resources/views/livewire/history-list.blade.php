{{-- ========================================================= --}}
{{-- HISTORY SEARCH / FILTER TOOLBAR + TABLE (Livewire)      --}}
{{-- ========================================================= --}}

<div>
    <form
        id="historyFilters"
        wire:submit.prevent
        class="w-full max-w-3xl mb-lg flex flex-wrap items-end gap-md"
    >
        {{-- Search --}}
        <div class="flex flex-col gap-xs">
            <label
                for="search"
                class="text-label-sm font-mono text-on-surface-variant uppercase tracking-wider"
            >
                Search meetings
            </label>

            <input
                id="search"
                name="search"
                type="search"
                wire:model.live.debounce.500ms="search"
                placeholder="Search meetings..."
                autocomplete="off"
                class="border border-outline-variant rounded px-md py-sm text-body-md font-sans text-on-surface bg-surface-container-lowest min-w-50"
            />
        </div>

        {{-- Status --}}
        <div class="flex flex-col gap-xs">
            <label
                for="status"
                class="text-label-sm font-mono text-on-surface-variant uppercase tracking-wider"
            >
                Status
            </label>

            <select
                id="status"
                name="status"
                wire:model.live="status"
                class="border border-outline-variant rounded px-md py-sm text-body-md font-sans text-on-surface bg-surface-container-lowest"
            >
                <option value="">
                    All statuses
                </option>

                @foreach($statusOptions as $code => $label)
                    <option value="{{ $code }}">
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Date From --}}
        <div class="flex flex-col gap-xs">
            <label
                for="date_from"
                class="text-label-sm font-mono text-on-surface-variant uppercase tracking-wider"
            >
                From
            </label>

            <input
                id="date_from"
                name="date_from"
                type="date"
                wire:model.live="dateFrom"
                class="border border-outline-variant rounded px-md py-sm text-body-md font-sans text-on-surface bg-surface-container-lowest"
            />
        </div>

        {{-- Date To --}}
        <div class="flex flex-col gap-xs">
            <label
                for="date_to"
                class="text-label-sm font-mono text-on-surface-variant uppercase tracking-wider"
            >
                To
            </label>

            <input
                id="date_to"
                name="date_to"
                type="date"
                wire:model.live="dateTo"
                class="border border-outline-variant rounded px-md py-sm text-body-md font-sans text-on-surface bg-surface-container-lowest"
            />
        </div>

        {{-- Clear --}}
        @if(
            $filters['search'] !== ''
            || $filters['status'] !== ''
            || $filters['date_from'] !== ''
            || $filters['date_to'] !== ''
        )
            <button
                type="button"
                wire:click="clearFilters"
                class="px-md py-sm text-label-md font-mono text-on-surface-variant hover:text-primary transition-colors"
            >
                Clear
            </button>
        @endif
    </form>

    @php
        $curSort = $filters['sort'];
        $curDir = $filters['dir'];

        /*
         * Sortable links that preserve the active search/status/date filters.
         * Rendered as normal GET links so sorting works on a fresh request and
         * remains testable, while the text/status/date controls above use
         * Livewire for live (debounced) updates.
         */
        $sortLink = function (string $col) use ($curSort, $curDir, $filters) {
            $isActive = $curSort === $col;

            $nextDir = ($isActive && $curDir === 'asc')
                ? 'desc'
                : 'asc';

            $params = array_merge(
                array_filter([
                    'search' => $filters['search'] !== '' ? $filters['search'] : null,
                    'status' => $filters['status'] !== '' ? $filters['status'] : null,
                    'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
                    'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null,
                ]),
                [
                    'sort' => $col,
                    'dir' => $nextDir,
                ]
            );

            return (object) [
                'url' => route('history', $params),
                'active' => $isActive,
                'dir' => $curDir,
            ];
        };

        $hasFilters = $filters['search'] !== ''
            || $filters['status'] !== ''
            || $filters['date_from'] !== ''
            || $filters['date_to'] !== '';
    @endphp

    @php
        // Collect status styling only for statuses present in the result set so
        // the Alpine status component has the classes it needs (avoids leaking
        // every possible status string into the markup).
        $allStatusConfig = [
            'COMPLETED' => ['label' => 'Completed', 'class' => 'bg-secondary-container text-on-secondary-container'],
            'ANALYZING' => ['label' => 'Analyzing', 'class' => 'bg-tertiary-container text-on-tertiary-container'],
            'FAILED' => ['label' => 'Failed', 'class' => 'bg-error-container text-on-error-container border border-error-container'],
            'VALIDATED' => ['label' => 'Validated', 'class' => 'bg-surface-container text-on-surface-variant'],
            'DRAFT' => ['label' => 'Draft', 'class' => 'bg-surface-container text-on-surface-variant'],
        ];

        $presentStatuses = $meetings->pluck('status')->unique()->values()->all();
        $statusClasses = [];
        $statusLabels = [];
        foreach ($presentStatuses as $status) {
            if (isset($allStatusConfig[$status])) {
                $statusClasses[$status] = $allStatusConfig[$status]['class'];
                $statusLabels[$status] = $allStatusConfig[$status]['label'];
            } else {
                $statusClasses[$status] = 'bg-surface-container text-on-surface-variant';
                $statusLabels[$status] = $status;
            }
        }
        $statusClassesJson = json_encode($statusClasses);
        $statusLabelsJson = json_encode($statusLabels);
    @endphp

    <div
        class="w-full max-w-3xl bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm"
    >
        <table
            class="w-full text-left border-collapse"
            id="meetingsTable"
            data-status-classes='{{ $statusClassesJson ?? '{}' }}'
            data-status-labels='{{ $statusLabelsJson ?? '{}' }}'
        >

            <thead class="bg-surface-container-low border-b border-outline-variant">

                <tr>

                    {{-- Meeting Date --}}
                    @php
                        $s = $sortLink('meeting_time');
                    @endphp

                    <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">

                        <a
                            href="{{ $s->url }}"
                            class="flex items-center gap-xs hover:text-primary transition-colors"
                        >
                            Meeting Date

                            <span class="material-symbols-outlined text-[16px]">
                                @if($s->active)
                                    {{ $s->dir === 'asc'
                                        ? 'arrow_upward'
                                        : 'arrow_downward'
                                    }}
                                @else
                                    unfold_more
                                @endif
                            </span>
                        </a>

                    </th>


                    {{-- Title --}}
                    @php
                        $s = $sortLink('title');
                    @endphp

                    <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">

                        <a
                            href="{{ $s->url }}"
                            class="flex items-center gap-xs hover:text-primary transition-colors"
                        >
                            Title

                            <span class="material-symbols-outlined text-[16px]">
                                @if($s->active)
                                    {{ $s->dir === 'asc'
                                        ? 'arrow_upward'
                                        : 'arrow_downward'
                                    }}
                                @else
                                    unfold_more
                                @endif
                            </span>
                        </a>

                    </th>


                    {{-- Date Processed --}}
                    @php
                        $s = $sortLink('created_at');
                    @endphp

                    <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">

                        <a
                            href="{{ $s->url }}"
                            class="flex items-center gap-xs hover:text-primary transition-colors"
                        >
                            Date Processed

                            <span class="material-symbols-outlined text-[16px]">
                                @if($s->active)
                                    {{ $s->dir === 'asc'
                                        ? 'arrow_upward'
                                        : 'arrow_downward'
                                    }}
                                @else
                                    unfold_more
                                @endif
                            </span>
                        </a>

                    </th>


                    {{-- Status --}}
                    @php
                        $s = $sortLink('status');
                    @endphp

                    <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">

                        <a
                            href="{{ $s->url }}"
                            class="flex items-center gap-xs hover:text-primary transition-colors"
                        >
                            Status

                            <span class="material-symbols-outlined text-[16px]">
                                @if($s->active)
                                    {{ $s->dir === 'asc'
                                        ? 'arrow_upward'
                                        : 'arrow_downward'
                                    }}
                                @else
                                    unfold_more
                                @endif
                            </span>
                        </a>

                    </th>


                    {{-- Actions --}}
                    <th
                        class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold text-right"
                    >
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody
                x-data="{
                    meetingStatuses: {},
                    statusClasses: JSON.parse(document.getElementById('meetingsTable')?.dataset.statusClasses || '{}'),
                    statusLabels: JSON.parse(document.getElementById('meetingsTable')?.dataset.statusLabels || '{}'),
                    getStatusLabel(status) { return this.statusLabels[status] ?? status; },
                    getStatusClass(status) { return this.statusClasses[status] ?? 'bg-surface-container text-on-surface-variant'; },
                    init() {
                        window.addEventListener('meetingStatusUpdated', (e) => this.updateMeetingStatus(e.detail));
                    },
                    updateMeetingStatus(detail) {
                        const { meetingId, status } = detail;
                        this.meetingStatuses[meetingId] = status;
                    }
                }"
            >

                @forelse($meetings as $meeting)

                    <tr
                        class="hover:bg-surface-container-lowest hover:shadow-sm transition-all duration-200 group cursor-pointer"
                        data-meeting-id="{{ $meeting->id }}"
                    >

                        {{-- Meeting Date --}}
                        <td class="px-md py-md text-body-sm font-sans text-on-surface">
                            {{ $meeting->meeting_time?->format('M d, Y') ?? '—' }}
                        </td>


                        {{-- Title --}}
                        <td class="px-md py-md text-body-md font-sans font-semibold text-on-surface group-hover:text-primary transition-colors">
                            {{ $meeting->title ?? 'Untitled' }}
                        </td>


                        {{-- Date Processed --}}
                        <td class="px-md py-md text-body-sm font-sans text-on-surface-variant">
                            {{ $meeting->created_at?->format('M d, Y') ?? '—' }}
                        </td>


                        {{-- Status --}}
                        @php
                            $statusConfig = [
                                'COMPLETED' => 'bg-secondary-container text-on-secondary-container',
                                'ANALYZING' => 'bg-tertiary-container text-on-tertiary-container',
                                'FAILED' => 'bg-error-container text-on-error-container border border-error-container',
                                'VALIDATED' => 'bg-surface-container text-on-surface-variant',
                                'DRAFT' => 'bg-surface-container text-on-surface-variant',
                            ];
                            $initialStatusClass = $statusConfig[$meeting->status] ?? 'bg-surface-container text-on-surface-variant';
                        @endphp
                        <td class="px-md py-md"
                            x-init="meetingStatuses['{{ $meeting->id }}'] = '{{ $meeting->status }}'">
                            <span x-text="getStatusLabel(meetingStatuses['{{ $meeting->id }}'] || '{{ $meeting->status }}')"
                                x-bind:class="getStatusClass(meetingStatuses['{{ $meeting->id }}'] || '{{ $meeting->status }}')"
                                class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono {{ $initialStatusClass }}">
                            </span>
                        </td>


                        {{-- Actions --}}
                        <td class="px-md py-md text-right">

                            <span
                                class="text-on-surface-variant hover:text-primary transition-colors p-xs"
                                aria-label="View details for {{ $meeting->title ?? 'this meeting' }}"
                            >
                                <span class="material-symbols-outlined">
                                    chevron_right
                                </span>
                            </span>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="px-md py-xl text-center text-body-md font-sans text-on-surface-variant"
                        >
                            {{ $hasFilters ? 'No meetings match your search.' : 'No meetings found.' }}
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>
</div>
