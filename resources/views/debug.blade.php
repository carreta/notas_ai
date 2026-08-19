@extends('layouts.app')

@section('title', 'Debug Console')

@section('content')
<header class="w-full max-w-3xl mb-lg" id="page-header">
    <h1 class="font-sans text-headline-lg text-on-surface mb-xs">Debug Console</h1>
    <p class="font-sans text-body-lg text-secondary">System logs and prompt performance tracking.</p>
</header>

<div class="w-full max-w-3xl bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant text-secondary">
                    <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Meeting Date</th>
                    <th class="p-4 font-mono text-label-md font-bold w-full">Title</th>
                    <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-right">Logs</th>
                    <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Prompt Version</th>
                    <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Status</th>
                    <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-on-surface">
                @foreach($meetings as $meeting)
                <tr class="hover:bg-surface-container-lowest transition-colors group">
                    <td class="p-4 font-sans text-body-sm whitespace-nowrap text-secondary">{{ $meeting['date'] }}</td>
                    <td class="p-4 font-sans text-body-md font-semibold">{{ $meeting['title'] }}</td>
                    <td class="p-4 font-mono text-label-md text-right"><span class="bg-surface-container-high px-2 py-1 rounded-full text-on-surface-variant">{{ $meeting['logs_count'] }}</span></td>
                    <td class="p-4 font-mono text-label-md text-secondary">{{ $meeting['prompt_version'] }}</td>
                    <td class="p-4 whitespace-nowrap">
                        @if($meeting['status'] === 'Completed')
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border border-outline bg-surface-container text-on-surface font-mono text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span> Completed
                            </span>
                        @elseif($meeting['status'] === 'Failed')
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border border-error-container bg-error-container text-on-error-container font-mono text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Failed
                            </span>
                        @elseif($meeting['status'] === 'In Progress')
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border border-outline-variant bg-surface-container-highest text-on-surface font-mono text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-tertiary-container animate-pulse"></span> In Progress
                            </span>
                        @endif
                    </td>
                    <td class="p-4 text-center text-secondary group-hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
