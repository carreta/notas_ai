@props(['label', 'value' => null])

<div class="flex flex-col min-w-0 overflow-hidden bg-surface-container-low p-md rounded-lg border border-outline-variant">
    <span class="font-mono text-label-sm text-secondary block mb-1">{{ $label }}</span>

    @if (isset($slot) && $slot->isNotEmpty())
        <div class="mt-auto min-w-0 w-full max-w-full">
            {{ $slot }}
        </div>
    @else
        <span
            class="mt-auto block min-w-0 max-w-full whitespace-normal font-sans text-body-sm text-on-surface font-medium"
            style="overflow-wrap: anywhere; word-break: break-word;"
        >{{ $value }}</span>
    @endif
</div>