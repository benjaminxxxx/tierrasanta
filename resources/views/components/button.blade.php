@props([
    'type' => 'button',
    'disabled' => false,
    'size' => 'base',    // xs, sm, base, lg, xl
    'variant' => 'primary', // primary, secondary, danger, etc.
    'target' => null,
])

@php
    // Detecta wire:click si no hay un target explícito.
    // Esto es clave para que el botón sepa a qué acción apuntar.
    if (!$target && $attributes->has('wire:click')) {
        $target = $attributes->get('wire:click');
    }

    // 🔹 Clases de CSS (Tu lógica aquí es correcta)
    $sizeClasses = [
        'xs'   => 'px-3 py-2 text-xs',
        'sm'   => 'px-3 py-2 text-sm',
        'base' => 'px-5 py-2.5 text-sm',
        'lg'   => 'px-5 py-3 text-base',
        'xl'   => 'px-6 py-3.5 text-base',
    ][$size] ?? 'px-5 py-2.5 text-sm';

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
    
    $baseClasses = 'font-medium rounded-lg focus:outline-none inline-flex items-center justify-center';

    $classes = collect([$baseClasses, $sizeClasses, $variantClasses])->join(' ');

    if ($disabled) {
        $classes .= ' opacity-50 cursor-not-allowed';
    } else {
        $classes .= ' cursor-pointer';
    }
@endphp

<button 
    {{ $attributes->merge(['type' => $type, 'class' => $classes]) }} 
    @if($disabled) disabled @endif
    
    {{-- Solo añade los atributos de carga si hay un target definido --}}
    @if ($target)
        wire:loading.attr="disabled"
        wire:target="{{ $target }}"
    @endif
>
    {{-- El spinner solo se renderiza y activa si hay un target --}}
    @if ($target)
        {{-- 1. SPINNER: Se muestra MIENTRAS carga. Sin margen para que quede centrado. --}}
        <svg wire:loading wire:target="{{ $target }}" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        {{-- 2. CONTENIDO: Se oculta MIENTRAS carga, manteniendo tu estructura interna. --}}
        <span wire:loading.remove wire:target="{{ $target }}">
            <div class="flex items-center justify-center gap-1">
                {{ $slot }}
            </div>
        </span>
    @else
        {{-- 3. Botón sin acción: Simplemente muestra el contenido, sin lógica de carga. --}}
        <div class="flex items-center justify-center gap-1">
            {{ $slot }}
        </div>
    @endif
</button>