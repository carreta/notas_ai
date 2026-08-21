@extends('layouts.app')

@section('title', '419 — Page Expired')

@section('content')
<div class="w-full max-w-3xl text-center py-xl">
    <h1 class="font-sans text-display-lg text-on-surface mb-sm">Page Expired</h1>
    <p class="font-sans text-body-lg text-on-surface-variant mb-xl">Your session has expired. Please refresh and try again.</p>
    <a href="{{ route('home') }}" class="inline-flex items-center gap-sm bg-primary text-on-primary font-mono text-label-md px-xl py-sm rounded-lg hover:bg-primary-container transition-colors">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Return to Analyze
    </a>
</div>
@endsection
