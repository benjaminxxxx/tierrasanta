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
    $id = $id ?? md5($attributes->wire('model'));
    $model = $attributes->whereStartsWith('wire:model')->first();

    // 🔹 Clases de tamaño
    $sizeClasses = match ($size) {
        'xs' => 'p-2 text-xs',
        'sm' => 'p-2 text-sm',
        'base' => 'p-2.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
        default => 'p-2.5 text-sm',
    };

    // 🔹 Base input
    $baseClasses = 'w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-lg
                    focus:ring-blue-500 focus:border-blue-500
                    dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white
                    dark:focus:ring-blue-500 dark:focus:border-blue-500';

    $classes = $baseClasses . ' ' . $sizeClasses;
@endphp

<x-group-field>
    @if ($type === 'checkbox')
        <div class="flex items-start gap-2">
            <input
                id="{{ $id }}"
                type="checkbox"
                {{ $disabled ? 'disabled' : '' }}
                {!! $attributes->merge([
                    'class' =>
                        'w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500
                         dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2
                         dark:bg-gray-700 dark:border-gray-600',
                ]) !!}
            />

            <div class="flex flex-col">
                @if ($label)
                    <label for="{{ $id }}" class="font-medium text-gray-900 dark:text-gray-300">
                        {{ $label }}
                    </label>
                @endif
                @if ($help)
                    <p id="{{ $id }}-help" class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $help }}
                    </p>
                @endif
            </div>
        </div>
    @else
        @if ($label)
            <x-label for="{{ $id }}">
                {{ $label }}
            </x-label>
        @endif

        <input
            id="{{ $id }}"
            type="{{ $type }}"
            {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge(['class' => $classes]) !!}
        />

        @if ($help)
            <p id="{{ $id }}-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $help }}
            </p>
        @endif
        @if ($error && $model)
            <x-input-error for="{{ $model }}" />
        @endif
    @endif
</x-group-field>
