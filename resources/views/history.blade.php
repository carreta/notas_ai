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
    <table class="w-full text-left border-collapse">
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
            @foreach($meetings as $meeting)
            <tr class="hover:bg-surface-container-lowest hover:shadow-sm transition-all duration-200 group">
                <td class="px-md py-md text-body-sm font-sans text-on-surface">{{ $meeting['date'] }}</td>
                <td class="px-md py-md text-body-md font-sans font-semibold text-on-surface group-hover:text-primary transition-colors">{{ $meeting['title'] }}</td>
                <td class="px-md py-md text-body-sm font-sans text-on-surface-variant">{{ $meeting['processed'] }}</td>
                <td class="px-md py-md">
                    @if($meeting['status'] === 'Completed')
                        <span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono bg-secondary-container text-on-secondary-container">Completed</span>
                    @elseif($meeting['status'] === 'In Progress')
                        <span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono bg-tertiary-container text-on-tertiary-container">
                            <span class="material-symbols-outlined text-[14px] mr-1 animate-spin">sync</span>
                            In Progress
                        </span>
                    @elseif($meeting['status'] === 'Failed')
                        <span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono bg-error-container text-on-error-container border border-error-container">Failed</span>
                    @endif
                </td>
                <td class="px-md py-md text-right">
                    <button class="text-on-surface-variant hover:text-primary transition-colors p-xs" aria-label="View details">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
