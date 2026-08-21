@extends('layouts.app')

@section('title', 'Meeting History')

@section('content')

<header class="w-full max-w-3xl mb-md">
    <h1 class="font-sans text-headline-lg text-on-surface mb-sm">
        Meeting History
    </h1>
    <p class="font-sans text-body-lg text-on-surface-variant">
        Review past analysis, extracted action items, and original transcripts.
    </p>
</header>


{{-- ========================================================= --}}
{{-- FILTERS --}}
{{-- ========================================================= --}}

<div class="w-full max-w-3xl bg-surface-container-low/30 border border-outline-variant rounded-xl p-md mb-lg">

    <form
        method="GET"
        action="{{ route('history') }}"
        class="flex flex-row flex-wrap items-end gap-md"
    >

        {{-- Search by title --}}
        <div class="min-w-50">
            <label
                for="search"
                class="block text-label-sm font-mono text-on-surface-variant mb-xs"
            >
                Search title
            </label>

            <input
                id="search"
                name="search"
                type="text"
                value="{{ $filters['search'] }}"
                placeholder="Enter meeting title..."
                class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-sm text-body-sm font-sans text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            />
        </div>


        {{-- Status filter --}}
        <div class="w-40">
            <label
                for="status"
                class="block text-label-sm font-mono text-on-surface-variant mb-xs"
            >
                Status
            </label>

            <select
                id="status"
                name="status"
                class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-sm text-body-sm font-sans text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            >
                <option value="">All Statuses</option>

                @foreach($statusOptions as $code => $label)
                    <option
                        value="{{ $code }}"
                        @selected($filters['status'] === $code)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>


        {{-- Start date --}}
        <div class="w-40">
            <label
                for="date_from"
                class="block text-label-sm font-mono text-on-surface-variant mb-xs"
            >
                From
            </label>

            <input
                id="date_from"
                name="date_from"
                type="date"
                value="{{ $filters['date_from'] }}"
                class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-sm text-body-sm font-sans text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            />
        </div>


        {{-- End date --}}
        <div class="w-40">
            <label
                for="date_to"
                class="block text-label-sm font-mono text-on-surface-variant mb-xs"
            >
                To
            </label>

            <input
                id="date_to"
                name="date_to"
                type="date"
                value="{{ $filters['date_to'] }}"
                class="w-full bg-surface-container-lowest border border-outline-variant rounded px-md py-sm text-body-sm font-sans text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            />
        </div>


        {{-- Filter actions --}}
        <div class="flex gap-sm">

            {{-- Apply filters --}}
            <button
                type="submit"
                class="bg-primary text-on-primary px-lg py-sm rounded text-label-md font-mono hover:bg-primary-container transition-colors h-10.5 flex items-center gap-xs"
            >
                <span class="material-symbols-outlined text-[18px]">
                    filter_alt
                </span>

                Apply Filter
            </button>


            {{-- Clear filters --}}
            @if(
                $filters['search'] !== ''
                || $filters['status'] !== ''
                || $filters['date_from'] !== ''
                || $filters['date_to'] !== ''
            )
                <a
                    href="{{ route('history') }}"
                    class="text-on-surface-variant hover:text-primary px-md py-sm rounded text-label-md font-mono transition-colors h-10.5 flex items-center"
                >
                    Clear
                </a>
            @endif

        </div>

    </form>

</div>


{{-- ========================================================= --}}
{{-- SORTING --}}
{{-- ========================================================= --}}

@php
    $curSort = $filters['sort'];
    $curDir = $filters['dir'];

    // Creates sortable header links while preserving active filters.
    $sortLink = function (string $col) use ($curSort, $curDir) {
        $isActive = $curSort === $col;

        // First click = ASC.
        // Clicking the same column again = DESC.
        $nextDir = ($isActive && $curDir === 'asc')
            ? 'desc'
            : 'asc';

        $params = array_merge(
            request()->only([
                'search',
                'status',
                'date_from',
                'date_to',
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
@endphp


{{-- ========================================================= --}}
{{-- HISTORY TABLE --}}
{{-- ========================================================= --}}

<div
    class="w-full max-w-3xl bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm"
>
    <table
        class="w-full text-left border-collapse"
        id="meetingsTable"
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
                                {{ $s->dir === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
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
                                {{ $s->dir === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
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
                                {{ $s->dir === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
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
                                {{ $s->dir === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
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


        <tbody class="divide-y divide-outline-variant text-on-surface">

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
                    <td class="px-md py-md">
                        @include(
                            'partials.status-badge',
                            ['status' => $meeting->status]
                        )
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
                        No meetings found.
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>
</div>


{{-- ========================================================= --}}
{{-- SHARED ANALYSIS DETAIL MODAL --}}
{{-- ========================================================= --}}

@livewire(
    'analysis-detail-modal',
    ['analysisId' => $selectedAnalysisId]
)


{{-- ========================================================= --}}
{{-- OPEN MEETING MODAL --}}
{{-- ========================================================= --}}

@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const table = document.getElementById('meetingsTable');

        if (!table) {
            return;
        }

        table.addEventListener('click', (event) => {

            const row = event.target.closest(
                'tr[data-meeting-id]'
            );

            if (!row) {
                return;
            }

            const meetingId = row.dataset.meetingId;

            Livewire.dispatch(
                'openMeetingModal',
                meetingId
            );
        });
    });
</script>

@endpush

@endsection
