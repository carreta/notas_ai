@extends('layouts.app')

@section('title', 'Analyze Transcript')

@section('content')
<!-- Page Header -->
 
<div class="w-full max-w-3xl mb-xl text-centerh-96">
    <h1 class="font-sans text-display-lg text-center text-on-surface mb-sm">Analyze Transcript</h1>
    <p class="font-sans text-body-lg text-center text-on-surface-variant">Paste your meeting transcript below to generate a comprehensive synthesis and extract key action items.</p>
</div>

<!-- Analysis Request Form Card -->
<div class="w-full max-w-3xl bg-surface-container-lowest border border-outline-variant rounded-xl p-xl shadow-sm transition-shadow duration-300 hover:shadow-md relative overflow-hidden">
    <form action="{{ route('meetings.store') }}" method="POST" class="flex flex-col gap-lg">
        @csrf

        <!-- Form Row: Title & Date -->
        <div class="flex flex-col md:flex-row gap-lg">
            <!-- Title Input -->
            <div class="flex-1 flex flex-col gap-xs">
                <label class="font-mono text-label-md text-on-surface-variant" for="meeting-title">Meeting Title</label>
                <input class="w-full bg-surface border border-outline-variant rounded-lg px-md py-sm text-body-md font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition" id="meeting-title" name="meeting-title" placeholder="e.g., Q3 Planning Session" type="text" value="{{ old('meeting-title') }}">
                @error('meeting-title')
                    <p class="font-mono text-label-sm text-error mt-sm">{{ $message }}</p>
                @enderror
            </div>
            <!-- Date Input -->
            <div class="md:w-1/3 flex flex-col gap-xs">
                <label class="font-mono text-label-md text-on-surface-variant" for="meeting-date">Meeting Date</label>
                <div class="relative">
                    <input class="w-full bg-surface border border-outline-variant rounded-lg px-md py-sm text-body-md font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition appearance-none" id="meeting-date" name="meeting-date" type="date" value="{{ old('meeting-date', now()->format('Y-m-d')) }}">
                    
                </div>
                @error('meeting-date')
                    <p class="font-mono text-label-sm text-error mt-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Transcript Textarea -->
        <div class="flex flex-col gap-xs">
            <div class="flex justify-between items-end">
                <label class="font-mono text-label-md text-on-surface-variant" for="transcript">Meeting Transcript</label>
                <span class="font-mono text-label-sm text-outline">Plain text, VTT, or SRT</span>
            </div>
            <textarea class="w-full bg-surface border border-outline-variant rounded-lg p-md text-body-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition resize-y font-mono" id="transcript" name="transcript" placeholder="Paste your transcript here..." rows="12">{{ old('transcript') }}</textarea>
            @error('transcript')
                <p class="font-mono text-label-sm text-error mt-sm">{{ $message }}</p>
            @enderror

            <!-- Character Counter -->
            <div id="char-count" class="font-mono text-label-sm text-outline mt-md flex items-center gap-1 max-h-md">
                <span id="char-count-value">0</span> / <span id="char-count-max">50000</span> characters
            </div>
        </div>

        <!-- Form Row: Model Selection -->
        <div class="flex flex-col gap-xs">
            <label class="font-mono text-label-md text-on-surface-variant" for="model-selection">Model / Context Window</label>
            <div class="relative w-full md:w-1/2">
                <select class="w-full bg-surface border border-outline-variant rounded-lg pl-md pr-10 py-sm text-body-sm font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition appearance-none cursor-pointer" id="model-selection" name="model">
                    @foreach($models as $key => $model)
                        <option value="{{ $key }}" data-max-chars="{{ $model['max_chars'] }}" data-max-tokens="{{ $model['max_tokens'] }}" {{ old('model') === $key ? 'selected' : '' }}>
                            {{ $model['label'] }} ({{ number_format($model['max_chars']) }} chars / {{ number_format($model['max_tokens']) }} tokens)
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
            </div>
            @error('model')
                <p class="font-mono text-label-sm text-error mt-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Action -->
        <div class="pt-md mt-md border-t border-outline-variant/50 flex justify-end">
            <button class="bg-primary text-on-primary font-mono text-label-md px-xl py-sm rounded-lg hover:bg-primary-container transition-colors flex items-center gap-sm" type="submit">
                <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                Start Analysis
            </button>
        </div>
    </form>
</div>

<!-- Status & Progress Area -->
<div class="w-full max-w-3xl mt-lg flex flex-col gap-md" id="status-area">
    <!-- Progress/Error blocks will be inserted here by JS if needed --><!-- Example: Progress State (processing blue) -->
</div>



@endsection