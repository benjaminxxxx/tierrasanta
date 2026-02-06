{{-- components/select.blade.php --}}
@props([
    'id' => null,
    'label' => null,
    'error' => null,
    'disabled' => false,
    'size' => 'default', // small | default | large
])

@php
    $id = $id ?? 'select-' . Str::uuid();
    $model = $attributes->whereStartsWith('wire:model')->first();

    // Alturas consistentes (h-9 es el estándar)
    $sizeClasses = match ($size) {
        'small' => 'h-8 px-2 text-xs',
        'large' => 'h-11 px-4 text-base',
        default => 'h-9 px-3 text-sm',
    };

    $hasWidthClass = collect(explode(' ', $attributes->get('class')))
                        ->contains(fn ($c) => str_starts_with($c, 'w-'));
    $computedWidth = $hasWidthClass ? '' : 'w-full';

    // Colores semánticos de tu config
    $baseClasses = 'block rounded-md border border-input bg-background text-foreground shadow-xs transition-colors outline-none 
                    focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]
                    disabled:cursor-not-allowed disabled:opacity-50';

    $errorClasses = $error ? 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20' : '';

    $classes = trim("{$baseClasses} {$sizeClasses} {$computedWidth} {$errorClasses}");
@endphp

<x-group-field class="{{ $computedWidth }}">
    @if ($label)
        <x-label for="{{ $id }}">{{ $label }}</x-label>
    @endif

    <select id="{{ $id }}" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => $classes]) !!}>
        {{ $slot }}
    </select>

    @if ($error && $model)
        <x-input-error for="{{ $model }}" class="text-xs text-destructive" />
    @endif
</x-group-field>