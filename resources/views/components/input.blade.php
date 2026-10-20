{{-- components/input.blade.php --}}
@props([
    'disabled' => false,
    'id' => null,
    'type' => 'text',
    'label' => null,
    'help' => null,
    'size' => 'base', // xs, sm, base, lg
    'error' => null,
])

@php
    $id = $id ?? 'input-' . Str::uuid();
    $model = $attributes->whereStartsWith('wire:model')->first();

    $isDisabled = (bool) ($disabled || $attributes->has('disabled'));
    $isReadOnly = $attributes->has('readonly');

    $sizeClasses = match ($size) {
        'xs'   => 'h-7 px-2 text-xs',
        'sm'   => 'h-8 px-2 text-sm',
        'base' => 'h-9 px-3 text-sm',
        'lg'   => 'h-11 px-4 text-base',
        default => 'h-9 px-3 text-sm',
    };

    $baseClasses = 'w-full rounded-md border border-input bg-background text-foreground shadow-xs transition-colors outline-none 
                    file:border-0 file:bg-transparent file:text-sm file:font-medium
                    placeholder:text-muted-foreground
                    focus-visible:border-ring focus-visible:ring-ring focus-visible:ring-[3px]
                    disabled:cursor-not-allowed disabled:opacity-50';

    $errorClasses = $error ? 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive' : '';
    $readOnlyClasses = $isReadOnly && !$isDisabled ? 'bg-muted text-muted-foreground cursor-default' : '';

    $classes = trim("{$baseClasses} {$sizeClasses} {$errorClasses} {$readOnlyClasses}");

    // Campo en vivo (wire:model.live…): spinner al final del input mientras se actualiza esa propiedad, en vez del
    // overlay de pantalla completa (que no aparece mientras se escribe: ver app.js "escribiendo-en-vivo")
    $modelKey = collect(array_keys($attributes->getAttributes()))->first(fn($k) => str_starts_with($k, 'wire:model'));
    $esVivo = $model && $modelKey && str_contains($modelKey, '.live')
        && in_array($type, ['text', 'search', 'number', 'email', 'tel', 'url'], true);

    // Fecha con wire:model: picker moderno (x-selector-dia). El input nativo, al escribir el año con wire:model.live,
    // mandaba cada dígito (0002, 0020…) y el re-render dejaba "026". Si el campo usa Alpine (x-model, @change),
    // min/max o está deshabilitado, se queda el nativo.
    $usaPicker = $type === 'date' && $model && !$isDisabled && !$isReadOnly
        && !collect(array_keys($attributes->getAttributes()))->contains(fn($k) => str_starts_with($k, 'x-') || str_starts_with($k, '@')
            || in_array($k, ['min', 'max'], true));
@endphp

@if ($usaPicker)
    <x-selector-dia {{ $attributes }} :label="$label" :help="$help" :error="$error" />
@else
<x-group-field>
    @if ($type === 'checkbox')
        <div class="flex items-center gap-2">
            <input 
                id="{{ $id }}" 
                type="checkbox" 
                {{ $isDisabled ? 'disabled' : '' }} 
                {{ $attributes->class(['h-4 w-4 rounded border-muted-foreground bg-muted focus:ring-ring'])->except(['type', 'disabled']) }} 
            />
            @if ($label)
                <x-label for="{{ $id }}" class="cursor-pointer">{{ $label }}</x-label>
            @endif
        </div>
    @else
        @if ($label)
            <x-label for="{{ $id }}">{{ $label }}</x-label>
        @endif

        @if ($esVivo)
            <div class="relative">
                <input
                    id="{{ $id }}"
                    type="{{ $type }}"
                    {{ $isDisabled ? 'disabled' : '' }}
                    {{ $isReadOnly && !$isDisabled ? 'readonly' : '' }}
                    {{ $attributes->class([$classes, 'pr-8'])->except(['type', 'disabled', 'readonly']) }}
                />
                <span wire:loading.flex wire:target="{{ $model }}"
                    class="absolute inset-y-0 right-2 items-center pointer-events-none text-muted-foreground">
                    <i class="fa fa-spinner fa-spin text-xs"></i>
                </span>
            </div>
        @else
            <input
                id="{{ $id }}"
                type="{{ $type }}"
                {{ $isDisabled ? 'disabled' : '' }}
                {{ $isReadOnly && !$isDisabled ? 'readonly' : '' }}
                {{ $attributes->class([$classes])->except(['type', 'disabled', 'readonly']) }}
            />
        @endif

        @if ($help)
            <p id="{{ $id }}-help" class="text-xs text-muted-foreground">{{ $help }}</p>
        @endif

        @if ($error && $model)
            <x-input-error for="{{ $model }}" class="text-xs text-destructive" />
        @endif
    @endif
</x-group-field>
@endif
