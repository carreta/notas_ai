@php
    // Safe presentation mapping for the persisted meeting status.
    // The label is derived only from the actual stored status; a FAILED
    // meeting is never relabeled as COMPLETED here.
    $statusConfig = [
        'COMPLETED' => ['label' => 'Completed', 'class' => 'bg-secondary-container text-on-secondary-container'],
        'ANALYZING' => ['label' => 'Analyzing', 'class' => 'bg-tertiary-container text-on-tertiary-container'],
        'FAILED' => ['label' => 'Failed', 'class' => 'bg-error-container text-on-error-container border border-error-container'],
        'VALIDATED' => ['label' => 'Validated', 'class' => 'bg-surface-container text-on-surface-variant'],
        'DRAFT' => ['label' => 'Draft', 'class' => 'bg-surface-container text-on-surface-variant'],
    ];

    $cfg = $statusConfig[$status] ?? ['label' => (string) $status, 'class' => 'bg-surface-container text-on-surface-variant'];
@endphp
<span class="inline-flex items-center px-xs py-0.5 rounded text-label-sm font-mono {{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
