@extends('layouts.app')

@section('title', 'Meeting Detail')

@section('content')
<div class="w-full max-w-4xl">
    <a href="{{ route('history') }}" class="inline-flex items-center gap-xs text-label-md font-mono text-on-surface-variant hover:text-primary transition-colors mb-lg">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to History
    </a>

    <header class="mb-lg">
        <h1 class="font-sans text-headline-lg text-on-surface mb-sm">{{ $meeting->title ?? 'Untitled Meeting' }}</h1>
        <div class="flex items-center gap-md text-label-sm font-label-sm text-on-surface-variant">
            <span class="flex items-center gap-xs">
                <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                {{ $meeting->meeting_time?->format('M d, Y') ?? 'No date' }}
            </span>
            <span class="flex items-center gap-xs">
                <span class="material-symbols-outlined text-[16px]">schedule</span>
                Created {{ $meeting->created_at?->format('M d, Y') ?? '—' }}
            </span>
            @include('partials.status-badge', ['status' => $meeting->status])
        </div>
    </header>

    @include('partials.analysis-result', ['analysis' => $meeting->analysis])
</div>
@endsection
