@extends('layouts.app')

@section('title', 'Analyze Transcript')

@section('content')
<div class="w-full max-w-3xl mb-lg text-center">
    <h1 class="font-sans text-display-lg text-center text-on-surface mb-sm">Analyze Transcript</h1>
    <p class="font-sans text-body-lg text-center text-on-surface-variant">Paste your meeting transcript below to generate a comprehensive synthesis and extract key action items.</p>
</div>

@livewire('analyze-form', ['models' => $models])
@endsection
