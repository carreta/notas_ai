@extends('layouts.app')

@section('title', '500 — Server Error')

@section('content')
<div class="w-full max-w-3xl text-center py-xl">
    <h1 class="font-sans text-display-lg text-on-surface mb-sm">Something Went Wrong</h1>
    <p class="font-sans text-body-lg text-on-surface-variant mb-xl">An unexpected error occurred. Please try again later.</p>
    <a href="{{ route('home') }}" class="inline-flex items-center gap-sm bg-primary text-on-primary font-mono text-label-md px-xl py-sm rounded-lg hover:bg-primary-container transition-colors">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Return to Analyze
    </a>
</div>
@endsection
