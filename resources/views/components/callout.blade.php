@props([
    'variant' => 'secondary', // secondary, success, warning, danger
    'heading' => null,
    'icon'    => null,
])

@php
    // Variantes según el diseño de la imagen
    $variantClasses = [
        'secondary' => 'bg-gray-500/10 border-gray-500/20 text-gray-200 [&_svg]:text-gray-400',
        'success'   => 'bg-emerald-950/40 border-emerald-500/30 text-emerald-300 [&_svg]:text-emerald-400',
        'warning'   => 'bg-amber-950/40 border-amber-500/30 text-amber-300 [&_svg]:text-amber-400',
        'danger'    => 'bg-red-950/40 border-red-500/30 text-red-300 [&_svg]:text-red-400',
    ][$variant] ?? 'bg-gray-500/10 border-gray-500/20 text-gray-200 [&_svg]:text-gray-400';

    // Mapeo automático de icono por defecto si no se pasa explícitamente
    $iconType = $icon ?? [
        'secondary' => 'information-circle',
        'success'   => 'check-circle',
        'warning'   => 'exclamation-circle',
        'danger'    => 'x-circle',
    ][$variant] ?? 'information-circle';
@endphp

<div {{ $attributes->merge(['class' => "flex items-center gap-3 p-4 rounded-xl border text-sm font-medium transition-all {$variantClasses}"]) }}>
    {{-- Iconos en SVG rellenos --}}
    @if ($iconType === 'information-circle')
        <svg class="size-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
        </svg>
    @elseif ($iconType === 'check-circle')
        <svg class="size-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
    @elseif ($iconType === 'exclamation-circle')
        <svg class="size-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
    @elseif ($iconType === 'x-circle')
        <svg class="size-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
    @endif

    {{-- Texto / Contenido --}}
    <div>
        {{ $heading ?? $slot }}
    </div>
</div>