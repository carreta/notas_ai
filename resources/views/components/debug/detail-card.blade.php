@props(['label', 'value' => null])

<div class="bg-surface-container-low p-md rounded-lg border border-outline-variant min-w-0">
    <span class="font-mono text-label-sm text-secondary block mb-1">{{ $label }}</span>
    @if (isset($slot) && $slot->isNotEmpty())
        {{ $slot }}
    @else
        <span class="font-sans text-body-sm text-on-surface font-medium wrap-break-words">{{ $value }}</span>
    @endif
</div>
