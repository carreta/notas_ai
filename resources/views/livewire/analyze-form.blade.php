<div
    class="flex flex-col gap-lg w-full max-w-3xl"
    x-data="{
        characterCount: {{ $characterCount }},

        get maxChars() {
            return $wire.maxChars;
        },

        get maxTokens() {
            return $wire.maxTokens;
        },

        get overLimit() {
            return this.characterCount > this.maxChars;
        },

        get progressPercent() {
            const stages = {
                idle: 0,
                validating: 0,
                saving: 25,
                analyzing: 50,
                storing: 75,
                completed: 100,
            };

            return stages[$wire.stage] ?? 0;
        },

        get progressLabel() {
            const stages = {
                idle: '',
                validating: 'Validation in progress...',
                saving: 'Saving in progress...',
                analyzing: 'Analyzing in progress...',
                storing: 'Storing in progress...',
                completed: 'Completed',
            };

            return stages[$wire.stage] ?? '';
        },

        get submitButtonText() {
            const texts = {
                idle: 'Start Analysis',
                validating: 'Validating...',
                saving: 'Saving...',
                analyzing: 'Analyzing...',
                storing: 'Storing...',
                completed: 'Completed',
            };

            return texts[$wire.stage] ?? 'Start Analysis';
        }
    }"
    {{-- Validation process started --}}
    x-on:validation-started.window="
        $wire.validation();
    "

    {{-- Successful validation has completed. Give the user a brief
         processing delay before starting the save stage. --}}
    x-on:validation-passed.window="
        setTimeout(() => {
            $wire.save();
        }, 1000)
    "

    {{-- Data successfully saved has completed. Give the user a brief
         processing delay before starting the save stage. --}}
    x-on:save-passed.window="
        setTimeout(() => {
            $wire.analyze();
        }, 10)
    "

    {{-- Data successfully analyzed has completed. Give the user a brief
         processing delay before starting the store stage. --}}
    x-on:analyze-passed.window="
        setTimeout(() => {
            $wire.store();
        }, 1000)
    "

    {{-- Data successfully stored has completed. Give the user a brief
         processing delay before completing. --}}
    x-on:store-passed.window="
        setTimeout(() => {
            $wire.complete();
        }, 1000)
    "

    {{-- Retry analysis after an AI error has occurred --}}
    x-on:retry.window="
        setTimeout(() => {
            $wire.analyze();
        }, 1000)
    "  
>
    @php
        $isProcessing = in_array($stage, [
            'validating',
            'saving',
            'analyzing',
            'storing',
            'completed',
        ]);
    @endphp

    <form
        wire:submit="submit"
        class="flex flex-col gap-lg w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-xl shadow-sm transition-shadow duration-300 hover:shadow-md relative overflow-visible"
        data-stage="{{ $stage }}"
    >
        @csrf

        <!-- Title & Date -->
        <div class="flex flex-col md:flex-row gap-lg">
            <div class="flex-1 flex flex-col gap-xs" x-data="{ titleHasInteracted: false, titleShowError: false, titleErrorTimer: null }">
                <label
                    class="font-mono text-label-md text-on-surface-variant"
                    for="meeting_title"
                >
                    Meeting Title
                </label>

                <input
                    type="text"
                    wire:model="meeting_title"
                    id="meeting_title"
                    required
                    class="w-full bg-surface border border-outline-variant rounded-lg px-md py-sm text-body-md font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition"
                    :class="{'border-error! bg-error-container! focus:border-error! focus:ring-error! border-2': titleShowError}"
                    placeholder="e.g., Q3 Planning Session"
                    x-on:focus="titleHasInteracted = true; titleErrorTimer = setTimeout(() => { if (!$wire.meeting_title) titleShowError = true; }, 2000)"
                    x-on:blur="clearTimeout(titleErrorTimer); if (!$wire.meeting_title) titleShowError = true;"
                    x-on:input="if ($wire.meeting_title) titleShowError = false;"
                >

                <template x-if="titleShowError">
                    <p class="font-sans text-body-sm text-error italic mt-xs" role="alert">
                        Title is required
                    </p>
                </template>
            </div>

            <div
                class="md:w-1/3 flex flex-col gap-xs"
                x-data="datePicker('{{ $maxDate }}')"
                data-max-date="{{ $maxDate }}"
                x-on:validation-started.window="dateSubmitAttempted = true; checkError()"
            >
                <label
                    class="font-mono text-label-md text-on-surface-variant flex items-center gap-1"
                    for="meeting_date"
                >
                    Meeting Date
                    <span class="font-sans text-body-sm text-error" aria-hidden="true">*</span>
                </label>

                <div class="relative" x-on:click.outside="close()">
                    <button
                        type="button"
                        id="meeting_date"
                        aria-haspopup="dialog"
                        :aria-expanded="open"
                        aria-required="true"
                        @click="toggle()"
                        class="w-full flex items-center justify-between gap-sm bg-surface border border-outline-variant rounded-lg px-md py-sm text-body-md font-sans focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition"
                        :class="{ 'border-error! bg-error-container! focus:border-error! focus:ring-error! border-2': hasError, 'text-on-surface': selectedIso, 'text-outline-variant': !selectedIso }"
                    >
                        <span x-text="displayValue() || 'Select meeting date'"></span>
                        <span class="material-symbols-outlined text-outline pointer-events-none text-[18px]">
                            calendar_today
                        </span>
                    </button>

                    <!-- Calendar dropdown -->
                    <div
                        x-show="open"
                        x-cloak
                        role="dialog"
                        aria-label="Meeting date picker"
                        class="absolute right-0 top-full z-50 mt-xs w-[22rem] max-w-[calc(100vw-2rem)] bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg p-md"
                        x-on:keydown.escape.window="open = false"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between gap-sm mb-sm">
                            <button
                                type="button"
                                @click="prevMonth()"
                                :disabled="!canGoPrev()"
                                class="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed"
                                aria-label="Previous month"
                            >
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </button>

                            <div class="flex items-center gap-1">
                                <select
                                    aria-label="Select month"
                                    x-model.number="viewMonth"
                                    class="bg-surface border border-outline-variant rounded-md px-1 py-1 text-body-sm font-sans text-on-surface focus:outline-none focus:border-primary"
                                >
                                    <template x-for="(name, idx) in months" :key="idx">
                                        <option :value="idx" :disabled="monthDisabled(idx)" x-text="name"></option>
                                    </template>
                                </select>

                                <select
                                    aria-label="Select year"
                                    x-model.number="viewYear"
                                    class="bg-surface border border-outline-variant rounded-md px-1 py-1 text-body-sm font-sans text-on-surface focus:outline-none focus:border-primary"
                                >
                                    <template x-for="year in yearOptions()" :key="year">
                                        <option :value="year" x-text="year"></option>
                                    </template>
                                </select>
                            </div>

                            <button
                                type="button"
                                @click="nextMonth()"
                                :disabled="!canGoNext()"
                                class="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed"
                                aria-label="Next month"
                            >
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </button>
                        </div>

                        <!-- Weekday header -->
                        <div class="grid grid-cols-7 gap-1 mb-1">
                            <template x-for="wd in weekdays" :key="wd">
                                <div class="text-center text-label-sm font-mono text-outline py-1" x-text="wd"></div>
                            </template>
                        </div>

                        <!-- Day grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(cell, i) in calendarDays" :key="i">
                                <div class="aspect-square">
                                    <button
                                        x-show="!cell.empty"
                                        type="button"
                                        :disabled="cell.disabled"
                                        :aria-disabled="cell.disabled"
                                        :aria-pressed="cell.isSelected"
                                        :aria-current="cell.isToday ? 'date' : false"
                                        @click="selectDay(cell)"
                                        class="w-full h-full flex items-center justify-center rounded-lg text-body-sm font-sans transition-colors"
                                        :class="cell.isSelected
                                            ? 'bg-primary text-on-primary font-semibold'
                                            : (cell.isToday
                                                ? 'border border-primary text-primary font-semibold hover:bg-surface-container-low'
                                                : (cell.disabled
                                                    ? 'text-outline-variant opacity-50 cursor-not-allowed'
                                                    : 'text-on-surface hover:bg-surface-container-low cursor-pointer'))"
                                        x-text="cell.day"
                                    ></button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <template x-if="errorMessage">
                    <p class="font-sans text-body-sm text-error italic mt-xs" role="alert" x-text="errorMessage"></p>
                </template>
            </div>
        </div>

        <!-- Transcript Textarea -->
        <div class="flex flex-col gap-xs" x-data="{ transcriptHasInteracted: false, transcriptShowError: false, transcriptErrorTimer: null }">
            <div class="flex justify-between items-end">
                <label
                    class="font-mono text-label-md text-on-surface-variant"
                    for="meeting_text"
                >
                    Meeting Transcript
                </label>

                <span class="font-mono text-label-sm text-outline">
                    Plain text, VTT, or SRT
                </span>
            </div>

            <textarea
                wire:model="meeting_text"
                id="meeting_text"
                rows="12"
                required
                placeholder="Paste your transcript here..."
                x-on:input="characterCount = $event.target.value.length; if ($wire.meeting_text.trim()) transcriptShowError = false;"
                x-on:focus="transcriptHasInteracted = true; transcriptErrorTimer = setTimeout(() => { if (!$wire.meeting_text.trim()) transcriptShowError = true; }, 2000)"
                x-on:blur="clearTimeout(transcriptErrorTimer); if (!$wire.meeting_text.trim()) transcriptShowError = true;"
                class="w-full bg-surface border border-outline-variant rounded-lg p-md text-body-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition resize-y font-mono"
                :class="{ 'border-error! bg-error-container! focus:border-error! focus:ring-error! border-2': transcriptShowError }"
            ></textarea>

            <template x-if="transcriptShowError">
                <p class="font-sans text-body-sm text-error italic mt-xs" role="alert">
                    Transcript is required
                </p>
            </template>

            <!-- Character Counter -->
            <div
                class="font-mono text-label-sm mt-sm flex items-center gap-1"
                :class="{ 'text-error': overLimit }"
            >
                <span x-text="characterCount"></span>
                /
                <span x-text="maxChars"></span>
                characters

                <template x-if="overLimit">
                    <span class="material-symbols-outlined text-error">
                        warning
                    </span>
                </template>
            </div>
        </div>

        <!-- Model Select -->
        <div class="flex flex-col gap-xs">
            <label
                class="font-mono text-label-md text-on-surface-variant"
                for="model"
            >
                Model / Context Window
            </label>

            <div class="relative w-full md:w-1/2">
                <select
                    wire:model.live="model"
                    id="model"
                    class="w-full bg-surface border border-outline-variant rounded-lg pl-md pr-10 py-sm text-body-sm font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition appearance-none cursor-pointer"
                >
                    @foreach($models as $key => $modelConfig)
                        <option value="{{ $key }}">
                            {{ $modelConfig['label'] }}
                            ({{ number_format($modelConfig['max_chars']) }} chars /
                            {{ number_format($modelConfig['max_tokens']) }} tokens)
                        </option>
                    @endforeach
                </select>

                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">
                    expand_more
                </span>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-md mt-md border-t border-outline-variant/50 flex justify-end">
            <button
                type="submit"
                wire:loading.attr="disabled"
                @disabled($isProcessing || $errors->any())
                class="bg-primary text-on-primary font-mono text-label-md px-xl py-sm rounded-lg hover:bg-primary-container transition-colors flex items-center gap-sm {{ ($isProcessing || $errors->any()) ? 'opacity-50 cursor-not-allowed' : '' }}"
            >
                <span class="material-symbols-outlined text-[18px]">
                    auto_awesome
                </span>

                <span x-text="submitButtonText"></span>
            </button>
        </div>
    </form>

    <!-- Error Area -->
    @if($errors->any())
        <div class="flex flex-col gap-sm">
            @foreach($errors->getMessages() as $field => $messages)
                <div
                    class="flex flex-row error-field bg-error-container border border-error/20 rounded-lg p-md"
                    wire:key="error-{{ $field }}"
                >
                    <div class="flex flex-1 flex-col">
                        <p class="font-mono text-label-md text-on-error-container flex items-center gap-sm">
                            <span
                                class="material-symbols-outlined text-error"
                                style="font-variation-settings: 'FILL' 1;"
                            >
                                error
                            </span>

                            {{-- Actual validation error --}}
                            {{ $messages[0] }}
                        </p>

                        {{-- Dynamic error description --}}
                        @if(isset($errorDescriptions[$field]))
                            <p class="font-sans text-body-sm text-on-error-container/70 mt-xs">
                                @if($field === 'meeting_text' && $lastErrorWasAi && $aiErrorDescription)
                                    {{-- For AI errors, show detailed actionable description instead of generic user message --}}
                                    {{ $aiErrorDescription }}
                                @else
                                    {{ $messages[0] }}
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Retry button for AI analysis failures --}}
                    @if($field === 'meeting_text' && $lastErrorWasAi && $stage === 'idle')
                        <div class="flex justify-end">
                            <button
                                wire:click="retryAnalyze"
                                wire:loading.attr="disabled"
                                class="bg-error text-on-error font-mono text-label-sm px-md py-xs rounded-lg hover:bg-error/90 transition-colors flex items-center gap-xs"
                            >
                                <span class="material-symbols-outlined text-[16px]">
                                    refresh
                                </span>
                                <span>Retry Analysis</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Progress Bar -->
    @if(
        $stage !== 'idle' &&
        $stage !== 'completed'
    )
        <div
            class="w-full bg-surface-container border border-surface-tint/20 rounded-lg p-md flex items-center gap-md"
            wire:key="progress-bar"
        >
            <div class="relative w-8 h-8 flex items-center justify-center">
                <svg
                    class="animate-spin text-primary w-6 h-6"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>

                    <path
                        class="opacity-75"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l2-2.647z"
                        fill="currentColor"
                    ></path>
                </svg>
            </div>

            <div class="flex-1">
                <p
                    class="font-mono text-label-md text-on-surface"
                    x-text="progressLabel"
                ></p>

                <div class="w-full bg-outline-variant/30 h-1.5 rounded-full mt-sm overflow-hidden">
                    <div
                        class="bg-primary h-full rounded-full transition-all duration-500"
                        :style="'width: ' + progressPercent + '%'"
                    ></div>
                </div>
            </div>

            <span
                class="font-mono text-label-sm text-outline"
                x-text="progressPercent + '%'"
            ></span>
        </div>
    @endif
</div>