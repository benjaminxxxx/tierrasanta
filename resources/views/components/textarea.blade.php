@props([
    'disabled' => false,
    'id' => null,
    'label' => null,
    'help' => null,
    'size' => 'base', // xs, sm, base, lg
    'rows' => 3,
    'error' => null,
])

@php
    use Illuminate\Support\Str;

    // ID automático
    $id = $id ?? 'textarea-' . Str::uuid();

    // Detectar wire:model para errores
    $model = $attributes->whereStartsWith('wire:model')->first();

    // Flags HTML
    $attrHasDisabled = $attributes->has('disabled');
    $attrHasReadonly = $attributes->has('readonly');

    $isDisabled = (bool) ($disabled || $attrHasDisabled);
    $isReadOnly = $attrHasReadonly;

    // Tamaños
    $sizeClasses = match ($size) {
        'xs' => 'p-2 text-xs',
        'sm' => 'p-2 text-sm',
        'base' => 'p-2.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
        default => 'p-2.5 text-sm',
    };

    // Base
    $baseClasses = 'block w-full border border-border text-foreground rounded-lg bg-background resize-none ';

    // Estados
    $readonlyClasses = 'bg-gray-100 text-gray-700 cursor-default
                        dark:bg-gray-800 dark:text-gray-300';

    $disabledClasses = 'bg-gray-200 text-gray-500 cursor-not-allowed
                        dark:bg-gray-700 dark:text-gray-400';

    $stateClasses = '';

    if ($isDisabled) {
        $stateClasses = $disabledClasses;
    } elseif ($isReadOnly) {
        $stateClasses = $readonlyClasses;
    }

    $classes = trim($baseClasses . ' ' . $sizeClasses . ' ' . $stateClasses);
@endphp

<x-group-field>
    @if ($label)
        <x-label for="{{ $id }}">
            {{ $label }}
        </x-label>
    @endif

    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        {{ $isDisabled ? 'disabled' : '' }}
        {{ $isReadOnly && !$isDisabled ? 'readonly' : '' }}
        {!! $attributes->except(['disabled', 'readonly'])->merge(['class' => $classes]) !!}
    >{{ trim($slot) }}</textarea>

    @if ($help)
        <p id="{{ $id }}-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ $help }}
        </p>
    @endif

    @if ($error && $model)
        <x-input-error for="{{ $model }}" />
    @endif
</x-group-field>
