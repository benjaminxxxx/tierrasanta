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
    // Generar id por defecto a partir de wire:model si no viene
    $id = $id ?? md5($attributes->wire('model'));
    $model = $attributes->whereStartsWith('wire:model')->first();

    // Si el usuario añadió disabled como atributo HTML: <x-input disabled />
    $attrHasDisabled = $attributes->has('disabled');
    // Si el usuario añadió readonly como atributo HTML: <x-input readonly />
    $attrHasReadonly = $attributes->has('readonly');

    // Normalizar valores definitivos
    $isDisabled = (bool) ($disabled || $attrHasDisabled);
    $isReadOnly = $attrHasReadonly || $attributes->has('readonly');

    // 🔹 Clases de tamaño
    $sizeClasses = match ($size) {
        'xs' => 'p-2 text-xs',
        'sm' => 'p-2 text-sm',
        'base' => 'p-2.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
        default => 'p-2.5 text-sm',
    };

    // 🔹 Base input
    $baseClasses = 'w-full border border-gray-300 text-gray-900 rounded-lg
                    focus:ring-blue-500 focus:border-blue-500
                    bg-gray-50
                    dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white
                    dark:focus:ring-blue-500 dark:focus:border-blue-500';

    // 🔹 Estado readonly/disabled
    $readonlyClasses = 'bg-gray-200 cursor-not-allowed
                        dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500';

    // Construir clases finales
    $classes = trim($baseClasses . ' ' . $sizeClasses . ($isReadOnly || $isDisabled ? ' ' . $readonlyClasses : ''));
@endphp

<x-group-field>
    @if ($type === 'checkbox')
        <div class="flex items-start gap-2">
            <input
                id="{{ $id }}"
                type="checkbox"
                {{ $isDisabled ? 'disabled' : '' }}
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
            {{-- Aplicar disabled/readonly según corresponda --}}
            {{ $isDisabled ? 'disabled' : '' }}
            {{ $isReadOnly && !$isDisabled ? 'readonly' : '' }}
            {!! $attributes->except(['disabled', 'readonly'])->merge(['class' => $classes]) !!}
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
