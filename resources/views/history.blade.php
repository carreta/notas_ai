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

{{-- Livewire-driven search / filter toolbar + results table. --}}
@livewire('history-list')

{{-- ========================================================= --}}
{{-- ANALYSIS DETAIL MODAL --}}
{{-- ========================================================= --}}

@livewire(
    'analysis-detail-modal',
    [
        'analysisId' => $selectedAnalysisId
    ]
)

@endsection

@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', () => {

        /*
         * =====================================================
         * MEETING DETAIL MODAL (event delegation)
         * =====================================================
         *
         * The results table is rendered by a Livewire component and is
         * re-rendered on every (debounced) search/filter update. Attaching the
         * click listener to the document (instead of the table element) keeps
         * row-to-modal opening working across Livewire re-renders.
         */
        document.addEventListener('click', (event) => {

            const row = event.target.closest('tr[data-meeting-id]');

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
