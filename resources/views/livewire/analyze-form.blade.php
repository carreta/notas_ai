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
        class="flex flex-col gap-lg w-full bg-surface-container-lowest border border-outline-variant rounded-xl p-xl shadow-sm transition-shadow duration-300 hover:shadow-md relative overflow-hidden"
        data-stage="{{ $stage }}"
    >
        @csrf

        <!-- Title & Date -->
        <div class="flex flex-col md:flex-row gap-lg">
            <div class="flex-1 flex flex-col gap-xs">
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
                    class="w-full bg-surface border border-outline-variant rounded-lg px-md py-sm text-body-md font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition"
                    placeholder="e.g., Q3 Planning Session"
                >
            </div>

            <div class="md:w-1/3 flex flex-col gap-xs">
                <label
                    class="font-mono text-label-md text-on-surface-variant"
                    for="meeting_date"
                >
                    Meeting Date
                </label>

                <div class="relative">
                    <input
                        type="date"
                        wire:model="meeting_date"
                        id="meeting_date"
                        class="w-full bg-surface border border-outline-variant rounded-lg px-md py-sm text-body-md font-sans text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition appearance-none"
                    >
                </div>
            </div>
        </div>

        <!-- Transcript Textarea -->
        <div class="flex flex-col gap-xs">
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
                placeholder="Paste your transcript here..."
                x-on:input="characterCount = $event.target.value.length"
                class="w-full bg-surface border border-outline-variant rounded-lg p-md text-body-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary input-transition resize-y font-mono"
            ></textarea>

            <!-- Character Counter -->
            <div
                class="font-mono text-label-sm mt-md flex items-center gap-1"
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
                @disabled($isProcessing)
                class="bg-primary text-on-primary font-mono text-label-md px-xl py-sm rounded-lg hover:bg-primary-container transition-colors flex items-center gap-sm {{ $isProcessing ? 'opacity-50 cursor-not-allowed' : '' }}"
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
                    class="error-field bg-error-container border border-error/20 rounded-lg p-md"
                    wire:key="error-{{ $field }}"
                >
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
                            {{ $errorDescriptions[$field] }}
                        </p>
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