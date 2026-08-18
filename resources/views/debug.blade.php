@extends('layouts.app')

@section('title', 'Debug Console')

@section('content')
<!-- Header Section -->
<header class="w-full max-w-3xl mb-lg" id="page-header">
    <h1 class="font-sans text-headline-lg text-on-surface mb-xs">Debug Console</h1>
    <p class="font-sans text-body-lg text-secondary">System logs and prompt performance tracking.</p>
</header>

<!-- Main View: Meeting Debug List -->
<div class="view-transition view-active w-full max-w-3xl" id="view-main">
    <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
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
                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer group" onclick="showView('view-logs')">
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
</div>

<!-- Step 2: Meeting Logs Table -->
<div class="view-transition view-hidden w-full max-w-3xl" id="view-logs">
    <div class="mb-lg flex flex-col gap-sm">
        <button class="text-primary hover:text-primary-container transition-colors font-mono flex items-center gap-1 w-fit" onclick="showView('view-main')">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            Back to Meetings
        </button>
        <h2 class="font-sans text-on-surface">Logs for Q4 Strategy Planning Kickoff</h2>
    </div>
    <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-secondary">
                        <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Starting Time</th>
                        <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Duration</th>
                        <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Model</th>
                        <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap">Status</th>
                        <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-right">Tokens</th>
                        <th class="p-4 font-mono text-label-md font-bold whitespace-nowrap text-center">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant text-on-surface">
                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer group" onclick="openLogModal()">
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap text-secondary">14:30:00</td>
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap">45s</td>
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap">GPT-4o</td>
                        <td class="p-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border border-outline bg-surface-container text-on-surface font-mono text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span> Completed
                            </span>
                        </td>
                        <td class="p-4 font-mono text-label-md text-right text-secondary">1,250</td>
                        <td class="p-4 text-center text-secondary group-hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer group" onclick="openLogModal()">
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap text-secondary">14:28:12</td>
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap">12s</td>
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap">GPT-4o</td>
                        <td class="p-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border border-error-container bg-error-container text-on-error-container font-mono text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Failed
                            </span>
                        </td>
                        <td class="p-4 font-mono text-label-md text-right text-secondary">430</td>
                        <td class="p-4 text-center text-secondary group-hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-surface-container-lowest transition-colors cursor-pointer group" onclick="openLogModal()">
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap text-secondary">14:25:00</td>
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap">20s</td>
                        <td class="p-4 font-sans text-body-sm whitespace-nowrap">GPT-4o</td>
                        <td class="p-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full border border-outline bg-surface-container text-on-surface font-mono text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span> Completed
                            </span>
                        </td>
                        <td class="p-4 font-mono text-label-md text-right text-secondary">850</td>
                        <td class="p-4 text-center text-secondary group-hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Log Modal Overlay -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-lg bg-inverse-surface/80 backdrop-blur-sm" id="logModal">
    <div class="w-full max-w-4xl max-h-[90vh] bg-surface-container-lowest rounded-xl border border-outline-variant shadow-2xl flex flex-col overflow-hidden">
        <header class="flex items-center justify-between px-lg py-md border-b border-outline-variant bg-surface-container-low">
            <div class="flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary">description</span>
                <h2 class="font-sans text-headline-md text-on-surface">Log Details</h2>
            </div>
            <button class="p-2 rounded-full hover:bg-surface-container-high transition-colors text-on-surface-variant" onclick="closeLogModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="flex-1 overflow-y-auto p-lg space-y-xl bg-surface-container-lowest">
            <section class="border border-outline-variant rounded-xl overflow-hidden bg-surface-container-low">
                <div class="flex border-b border-outline-variant bg-surface-container-lowest">
                    <button class="flex-1 py-sm px-md text-center font-mono text-label-md font-medium text-primary border-b-2 border-primary bg-surface-container-low transition-colors" onclick="switchLogTab('metadata')">Metadata</button>
                    <button class="flex-1 py-sm px-md text-center font-mono text-label-md font-medium text-on-surface-variant border-b-2 border-transparent hover:text-on-surface hover:bg-surface-container-low transition-colors" onclick="switchLogTab('prompt')">Prompt View</button>
                </div>
                <div class="p-lg space-y-xl bg-surface-container-lowest" id="metadataTabContent">
                    <section>
                        <h3 class="font-sans text-body-sm text-on-surface-variant mb-md uppercase tracking-wider font-semibold">Metadata</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md">
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Meeting Title</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium truncate block">Q4 Strategy Planning Kickoff</span>
                            </div>
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Meeting Date</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium">Oct 24, 2023</span>
                            </div>
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Log Status</span>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed border border-primary-fixed-dim">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    <span class="font-mono text-label-md">Completed</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Duration</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium">45s</span>
                            </div>
                        </div>
                    </section>
                    <section>
                        <h3 class="font-sans text-body-sm text-on-surface-variant mb-md uppercase tracking-wider font-semibold">Model & Tokens</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md">
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Model</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium">GPT-4o</span>
                            </div>
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Prompt Version</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium">v2.1</span>
                            </div>
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Input Tokens</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium">1,250</span>
                            </div>
                            <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant">
                                <span class="font-mono text-label-sm text-on-surface-variant block mb-1">Output Tokens</span>
                                <span class="font-sans text-body-sm text-on-surface font-medium">342</span>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="p-lg space-y-xl bg-surface-container-lowest hidden" id="promptTabContent">
                    <section>
                        <h3 class="font-sans text-body-sm text-on-surface-variant mb-md uppercase tracking-wider font-semibold">System Prompt</h3>
                        <pre class="bg-surface p-md rounded-lg border border-outline-variant overflow-x-auto font-mono text-body-sm text-on-surface whitespace-pre-wrap">You are an expert meeting analyst. Your task is to synthesize the transcript and extract key decisions, action items, and open questions.</pre>
                    </section>
                    <section>
                        <h3 class="font-sans text-body-sm text-on-surface-variant mb-md uppercase tracking-wider font-semibold">User Prompt (Truncated)</h3>
                        <pre class="bg-surface p-md rounded-lg border border-outline-variant overflow-x-auto font-mono text-body-sm text-on-surface whitespace-pre-wrap">Analyze the following meeting transcript... [transcript content]</pre>
                    </section>
                </div>
            </section>
        </div>
        <footer class="p-md border-t border-outline-variant bg-surface-container-lowest flex justify-end">
            <button class="px-lg py-2 bg-primary text-on-primary font-mono text-label-md rounded-lg hover:bg-primary-container transition-colors" onclick="closeLogModal()">Close Details</button>
        </footer>
    </div>
</div>
@endsection