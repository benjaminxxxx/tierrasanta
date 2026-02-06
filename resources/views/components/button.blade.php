@props([
    'type' => 'button',
    'disabled' => false,
    'variant' => 'default', // Shadcn: default, destructive, outline, secondary, ghost, link | Custom: success, warning, info, alternative
    'size' => 'default',    // default, xs, sm, lg, icon
    'href' => null,
    'target' => null,
])

@php
    // Si hay wire:click y no hay target, lo usamos para el loading
    $wireTarget = $target ?? $attributes->get('wire:click');

    // Mapeo de Variantes
    $variantClasses = [
        // --- Variantes SEMÁNTICAS (Shadcn Style) ---
        'default'     => 'bg-primary text-primary-foreground shadow-sm hover:bg-primary/90',
        'destructive' => 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
        'outline'     => 'border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground',
        'secondary'   => 'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80',
        'ghost'       => 'hover:bg-accent hover:text-accent-foreground',
        'link'        => 'text-primary underline-offset-4 hover:underline',

        // --- Variantes CLÁSICAS (Tailwind Colors) ---
        'success'     => 'text-white bg-green-600 hover:bg-green-700 focus:ring-green-500/50 dark:bg-green-500 dark:hover:bg-green-600',
        'warning'     => 'text-white bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-400/50',
        'danger'      => 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-500/50', // Alias de destructive si prefieres
        'info'        => 'text-white bg-blue-500 hover:bg-blue-600 focus:ring-blue-400/50',
        'alternative' => 'text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-primary focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700',
    ][$variant] ?? 'bg-primary text-primary-foreground';

    // Mapeo de Tamaños
    $sizeClasses = [
        'default' => 'h-9 px-4 py-2',
        'xs'      => 'h-7 rounded-md px-2.5 text-xs',
        'sm'      => 'h-8 rounded-md px-3 text-xs',
        'lg'      => 'h-10 rounded-md px-8 text-base',
        'xl'      => 'h-12 rounded-md px-10 text-base',
        'icon'    => 'h-9 w-9',
    ][$size] ?? 'h-9 px-4 py-2';

    $baseClasses = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0';
    
    $classes = "{$baseClasses} {$variantClasses} {$sizeClasses}";
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href) 
        href="{{ $href }}" 
        @if($target) target="{{ $target }}" @endif
    @else 
        type="{{ $type }}" 
    @endif

    {{ $attributes->merge(['class' => $classes]) }}

    @if($disabled) 
        {{ $href ? 'aria-disabled=true' : 'disabled' }} 
    @endif

    @if($wireTarget && !$href)
        wire:loading.attr="disabled"
        wire:target="{{ $wireTarget }}"
    @endif
>
    {{-- Spinner para Livewire --}}
    @if($wireTarget && !$href)
        <svg wire:loading wire:target="{{ $wireTarget }}" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif

    {{-- El contenido se envuelve en un span para manejar el loading visualmente --}}
    <span @if($wireTarget && !$href) wire:loading.remove wire:target="{{ $wireTarget }}" @endif class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</{{ $tag }}>