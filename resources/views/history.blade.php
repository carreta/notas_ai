@extends('layouts.app')

@section('title', 'Meeting History')

@section('content')
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

<div class="w-full max-w-3xl bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
    <table class="w-full text-left border-collapse" id="meetingsTable">
        <thead class="bg-surface-container-low border-b border-outline-variant">
            <tr>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs">
                        Meeting Date
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs">
                        Title
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs">
                        Date Processed
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold">
                    <div class="flex items-center gap-xs">
                        Status
                        <span class="material-symbols-outlined text-[16px]">unfold_more</span>
                    </div>
                </th>
                <th class="px-md py-sm text-label-sm font-mono text-on-surface-variant uppercase tracking-wider font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant text-on-surface">
            @forelse($meetings as $meeting)
            <tr
                class="hover:bg-surface-container-lowest hover:shadow-sm transition-all duration-200 group cursor-pointer"
                data-meeting-id="{{ $meeting->id }}"
            >
                <td class="px-md py-md text-body-sm font-sans text-on-surface">{{ $meeting->meeting_time?->format('M d, Y') ?? '—' }}</td>
                <td class="px-md py-md text-body-md font-sans font-semibold text-on-surface group-hover:text-primary transition-colors">{{ $meeting->title ?? 'Untitled' }}</td>
                <td class="px-md py-md text-body-sm font-sans text-on-surface-variant">{{ $meeting->created_at?->format('M d, Y') ?? '—' }}</td>
                <td class="px-md py-md">
                    @include('partials.status-badge', ['status' => $meeting->status])
                </td>
                <td class="px-md py-md text-right">
                    <span class="text-on-surface-variant hover:text-primary transition-colors p-xs" aria-label="View details for {{ $meeting->title ?? 'this meeting' }}">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-md py-xl text-center text-body-md font-sans text-on-surface-variant">
                    No meetings yet. Submit a transcript to see it here.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@livewire('analysis-detail-modal', ['analysisId' => $selectedAnalysisId])

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('meetingsTable');
        if (table) {
            table.addEventListener('click', (e) => {
                const row = e.target.closest('tr[data-meeting-id]');
                if (row) {
                    const meetingId = row.dataset.meetingId;
                    Livewire.dispatch('openMeetingModal', meetingId);
                }
            });
        }
    });
</script>
@endpush

@endsection
