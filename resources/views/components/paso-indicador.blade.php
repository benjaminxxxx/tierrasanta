{{-- paso-indicador --}}
@props([
    'label',
    'completado' => null,
    'observacion' => 'Sin evaluar',
    'url' => null,
    'labelBoton' => 'Ir'
])

@php
$bloqueado = is_null($completado);
$popoverId = 'popover-' . uniqid();
@endphp

<div class="flex items-center gap-1 {{ $bloqueado ? 'opacity-35' : '' }}">

    <button
        data-popover-target="{{ $popoverId }}"
        type="button"
        class="flex items-center justify-center w-6 h-6 rounded-full text-xs
        {{ $completado === true ? 'bg-green-500/20 text-green-500' : '' }}
        {{ $completado === false ? 'bg-yellow-500/20 text-yellow-500' : '' }}
        {{ $bloqueado ? 'bg-muted text-muted-foreground cursor-not-allowed' : '' }}"
        {{ $bloqueado ? 'disabled' : '' }}
    >

        @if($completado === true)
            <i class="fa fa-check"></i>
        @elseif($completado === false)
            <i class="fa fa-exclamation"></i>
        @else
            <i class="fa fa-lock"></i>
        @endif

    </button>

    <div
        data-popover
        id="{{ $popoverId }}"
        role="tooltip"
        class="absolute z-10 invisible inline-block w-56 text-sm
        transition-opacity duration-300 opacity-0
        bg-card border border-border rounded-lg shadow-xs
        text-card-foreground"
    >

        <div class="px-3 py-2 bg-muted border-b border-border rounded-t-lg">
            <h3 class="font-semibold text-muted-foreground flex items-center gap-2">
                {{ $label }}
            </h3>
        </div>

        <div class="px-3 py-2">

            <p class="leading-snug">
                {{ $observacion }}
            </p>

            @if($url && $completado === false)
                <x-button href="{{ $url }}" class="mt-2">
                    <i class="fa fa-arrow-right"></i> {{ $labelBoton }}
                </x-button>
            @endif

        </div>

        <div data-popper-arrow></div>

    </div>

</div>