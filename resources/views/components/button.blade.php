@props([
    'type' => 'button',
    'disabled' => false,
    'size' => 'xs',    // xs, sm, base, lg, xl
    'variant' => 'primary', // primary, secondary, danger, etc.
    'target' => null,
    'href' => null, // 👈 Nuevo: soporta enlaces
])

@php
    // Detectar wire:click si no hay target explícito
    if (!$target && $attributes->has('wire:click')) {
        $target = $attributes->get('wire:click');
    }

    // Tamaños
    $sizeClasses = [
        'xs'   => 'px-3 py-2 text-xs',
        'sm'   => 'px-3 py-2 text-sm',
        'base' => 'px-5 py-2.5 text-sm',
        'lg'   => 'px-5 py-3 text-base',
        'xl'   => 'px-6 py-3.5 text-base',
    ][$size] ?? 'px-5 py-2.5 text-sm';

    // Variantes
    $variantClasses = [
        'primary'     => 'text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800',
        'secondary'   => 'text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700',
        'dark'        => 'text-white bg-gray-800 hover:bg-gray-900 focus:ring-4 focus:ring-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700',
        'light'       => 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700',
        'success'     => 'text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
        'danger'      => 'text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900',
        'warning'     => 'text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300 dark:focus:ring-yellow-900',
        'alternative' => 'py-2.5 px-5 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700',
    ][$variant] ?? 'text-white bg-blue-700 hover:bg-blue-800';

    // Base y estado
    $baseClasses = 'font-medium rounded-lg focus:outline-none inline-flex items-center justify-center w-full md:w-auto';
    $classes = collect([$baseClasses, $sizeClasses, $variantClasses])->join(' ');
    $classes .= $disabled ? ' opacity-50 cursor-not-allowed' : ' cursor-pointer';
@endphp

{{-- Si tiene href => usar <a>, si no => <button> --}}
@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($disabled) aria-disabled="true" tabindex="-1" @endif
    >
        <div class="flex items-center justify-center gap-1">
            {{ $slot }}
        </div>
    </a>
@else
    <button
        {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}
        @if($disabled) disabled @endif
        @if ($target)
            wire:loading.attr="disabled"
            wire:target="{{ $target }}"
        @endif
    >
        @if ($target)
            {{-- Spinner mientras carga --}}
            <svg wire:loading wire:target="{{ $target }}" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            {{-- Contenido (se oculta mientras carga) --}}
            <span wire:loading.remove wire:target="{{ $target }}">
                <div class="flex items-center justify-center gap-1">
                    {{ $slot }}
                </div>
            </span>
        @else
            {{-- Botón normal --}}
            <div class="flex items-center justify-center gap-1">
                {{ $slot }}
            </div>
        @endif
    </button>
@endif
