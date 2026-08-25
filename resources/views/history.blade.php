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

<form
    id="historyFilters"
    method="GET"
    action="{{ route('history') }}"
    class="w-full max-w-3xl mb-lg flex flex-wrap items-end gap-md"
>
    {{-- Search --}}
    <div class="flex flex-col gap-xs">
        <label
            for="search"
            class="text-label-sm font-mono text-on-surface-variant uppercase tracking-wider"
        >
            Search title
        </label>

        <input
            id="search"
            name="search"
            type="text"
            value="{{ $filters['search'] }}"
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
            class="border border-outline-variant rounded px-md py-sm text-body-md font-sans text-on-surface bg-surface-container-lowest"
        >
            <option value="">
                All statuses
            </option>

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
            value="{{ $filters['date_from'] }}"
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
            value="{{ $filters['date_to'] }}"
            class="border border-outline-variant rounded px-md py-sm text-body-md font-sans text-on-surface bg-surface-container-lowest"
        />
    </div>

    {{-- Manual Filter --}}
    <button
        type="submit"
        class="border border-primary rounded px-md py-sm text-label-md font-mono text-primary hover:bg-surface-container-low flex items-center gap-xs transition-colors"
    >
        <span class="material-symbols-outlined text-[18px]">
            filter_list
        </span>

        Filter
    </button>

    {{-- Clear --}}
    @if(
        $filters['search'] !== ''
        || $filters['status'] !== ''
        || $filters['date_from'] !== ''
        || $filters['date_to'] !== ''
    )
        <a
            href="{{ route('history') }}"
            class="px-md py-sm text-label-md font-mono text-on-surface-variant hover:text-primary transition-colors"
        >
            Clear
        </a>
    @endif
</form>


{{-- ========================================================= --}}
{{-- SORTING --}}
{{-- ========================================================= --}}

@php
    $curSort = $filters['sort'];
    $curDir = $filters['dir'];

    /*
     * Creates sortable links while preserving all active filters.
     */
    $sortLink = function (string $col) use ($curSort, $curDir) {
        $isActive = $curSort === $col;

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
                            [
                                'status' => $meeting->status
                            ]
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
{{-- ANALYSIS DETAIL MODAL --}}
{{-- ========================================================= --}}

@livewire(
    'analysis-detail-modal',
    [
        'analysisId' => $selectedAnalysisId
    ]
)


{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}

@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', () => {

        /*
         * =====================================================
         * MEETING DETAIL MODAL
         * =====================================================
         */

        const table = document.getElementById('meetingsTable');

        if (table) {

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
        }


        /*
         * =====================================================
         * AUTOMATIC FILTERING
         * =====================================================
         */

        const filtersForm =
            document.getElementById('historyFilters');

        const searchInput =
            document.getElementById('search');

        const statusSelect =
            document.getElementById('status');

        const dateFromInput =
            document.getElementById('date_from');

        const dateToInput =
            document.getElementById('date_to');


        if (!filtersForm) {
            return;
        }


        /*
         * =====================================================
         * RESTORE SEARCH FOCUS
         * =====================================================
         *
         * The GET filter reloads the page.
         *
         * We remember whether the user was typing and the
         * cursor position before submitting.
         *
         * After Laravel renders the new page, focus and cursor
         * position are restored automatically.
         */

        if (
            searchInput
            && sessionStorage.getItem('historySearchFocus') === '1'
        ) {

            searchInput.focus();

            const storedCursor =
                sessionStorage.getItem(
                    'historySearchCursor'
                );

            const cursorPosition =
                storedCursor !== null
                    ? Number(storedCursor)
                    : searchInput.value.length;

            /*
             * Make sure the cursor does not exceed
             * the current input length.
             */
            const safeCursorPosition =
                Math.min(
                    cursorPosition,
                    searchInput.value.length
                );

            searchInput.setSelectionRange(
                safeCursorPosition,
                safeCursorPosition
            );

            sessionStorage.removeItem(
                'historySearchFocus'
            );

            sessionStorage.removeItem(
                'historySearchCursor'
            );
        }


        /*
         * =====================================================
         * SEARCH WITH DEBOUNCE
         * =====================================================
         *
         * Wait 400ms after the user stops typing before
         * sending the GET request.
         */

        let searchTimeout = null;

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                () => {

                    clearTimeout(searchTimeout);

                    /*
                     * Remember that the search input
                     * should receive focus after reload.
                     */
                    sessionStorage.setItem(
                        'historySearchFocus',
                        '1'
                    );

                    /*
                     * Remember cursor position.
                     */
                    sessionStorage.setItem(
                        'historySearchCursor',
                        String(
                            searchInput.selectionStart
                            ?? searchInput.value.length
                        )
                    );

                    /*
                     * Wait until user pauses typing.
                     */
                    searchTimeout = setTimeout(
                        () => {

                            filtersForm.requestSubmit();

                        },
                        400
                    );
                }
            );
        }


        /*
         * =====================================================
         * STATUS AUTO FILTER
         * =====================================================
         */

        if (statusSelect) {

            statusSelect.addEventListener(
                'change',
                () => {

                    filtersForm.requestSubmit();

                }
            );
        }


        /*
         * =====================================================
         * DATE FROM AUTO FILTER
         * =====================================================
         */

        if (dateFromInput) {

            dateFromInput.addEventListener(
                'change',
                () => {

                    filtersForm.requestSubmit();

                }
            );
        }


        /*
         * =====================================================
         * DATE TO AUTO FILTER
         * =====================================================
         */

        if (dateToInput) {

            dateToInput.addEventListener(
                'change',
                () => {

                    filtersForm.requestSubmit();

                }
            );
        }

    });
</script>

@endpush

@endsection
