@props([
    'id' => null,
    'label' => null,
    'error' => null,
    'disabled' => false,
    'size' => 'default', // small | default | large
    'fullWidth' => false,
])

@php
    $id = $id ?? 'input-' . Str::uuid();
    $model = $attributes->whereStartsWith('wire:model')->first();

    // Clases según el tamaño
    $sizeClasses = match ($size) {
        'small' => 'p-2 text-sm',
        'large' => 'px-4 py-3 text-base',
        default => 'p-2.5 text-sm',
    };

    // Detecta si el usuario ya pasó su propia clase w-*
    $hasWidthClass = collect(explode(' ', $attributes->get('class')))
                        ->contains(fn ($c) => str_starts_with($c, 'w-'));

    // Si no pasó nada → w-full. Si pasó algo → no interferimos.
    $computedWidth = $hasWidthClass ? '' : 'w-full';
@endphp
<x-group-field class="{{ $computedWidth }}">
    @if ($label)
        <x-label for="{{ $id }}">{{ $label }}</x-label>
    @endif

    <select
        id="{{ $id }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => "
                block bg-gray-50 border border-gray-300 text-gray-900 rounded-lg
                focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600
                dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500
                $sizeClasses
                $computedWidth
            "
        ]) !!}
    >
        {{ $slot }}
    </select>

    @if ($error && $model)
        <x-input-error for="{{ $error }}" />
    @endif
</x-group-field>
