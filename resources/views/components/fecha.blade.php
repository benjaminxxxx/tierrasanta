{{--
    <x-fecha wire:model.live="fecha" label="Fecha" />
    Fecha que se escribe con máscara (dd/mm/aaaa: "02625" → 02/06/2025) o se elige con el ícono del almanaque (Hoy / Borrar).
    El valor del wire:model es "Y-m-d" (o null), igual que un input date: compatible con lo que ya viene de la base de
    datos y con wire:model, wire:model.live, .blur… Solo se envía cuando la fecha está completa y es válida.
    Sin wire:model: value="2026-10-20" y name="campo" (input oculto con Y-m-d para formularios normales).
    Lógica en resources/js/components/campo-fecha.js. x-input type="date", x-selector-dia y x-input-date lo usan.
--}}
@props([
    'label' => null,
    'help' => null,
    'error' => null,
    'disabled' => false,
    'readonly' => false,
    'size' => 'base',
    'id' => null,
    'min' => null,
    'max' => null,
    'value' => null,
    'name' => null,
    'placeholder' => 'dd/mm/aaaa',
])

@php
    $id = $id ?? 'fecha-' . Str::uuid();
    $wireModel = $attributes->wire('model');
    $modelo = $wireModel->value();
    $esVivo = $modelo && $wireModel->hasModifier('live');
    $bloqueado = $disabled || $readonly || $attributes->has('disabled') || $attributes->has('readonly');

    $sizeClasses = match ($size) {
        'xs' => 'h-7 pl-2 text-xs',
        'sm' => 'h-8 pl-2 text-sm',
        'lg' => 'h-11 pl-4 text-base',
        default => 'h-9 pl-3 text-sm',
    };
    // Mismas clases que x-input (diseño), con espacio a la derecha para los íconos
    $clases = "w-full min-w-[9.5rem] pr-14 rounded-md border border-input bg-background text-foreground shadow-xs transition-colors outline-none
               placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring focus-visible:ring-[3px]
               disabled:cursor-not-allowed disabled:opacity-50 tabular-nums {$sizeClasses}"
        . ($error ? ' border-destructive' : '')
        . ($bloqueado ? ' bg-muted text-muted-foreground' : '');
@endphp

<x-group-field>
    @if ($label)
        <x-label for="{{ $id }}">{{ $label }}</x-label>
    @endif

    <div x-data="campoFecha({
            valor: @if ($modelo) @entangle($wireModel) @else @js($value ? substr((string) $value, 0, 10) : null) @endif,
            min: @js($min),
            max: @js($max),
        })"
        {{ $attributes->only('class')->merge(['class' => 'relative']) }}>

        <input id="{{ $id }}" type="text" inputmode="numeric" autocomplete="off" maxlength="10"
            placeholder="{{ $placeholder }}" x-ref="entrada" x-model="texto"
            @input="alEscribir($event)" @blur="alSalir()" @keydown.enter.prevent="alSalir()"
            @mouseup="alHacerClic($event)" @keydown="alTeclear($event)"
            :class="error ? '!border-destructive !ring-destructive/30' : ''"
            @if ($esVivo) data-campo-en-vivo @endif
            @disabled($bloqueado)
            {{ $attributes->whereDoesntStartWith('wire:model')->except(['class', 'disabled', 'readonly'])->merge(['class' => $clases]) }} />

        @unless ($bloqueado)
            <div class="absolute inset-y-0 right-1 flex items-center gap-0.5">
                <button type="button" x-show="texto" x-cloak @click="limpiar()" tabindex="-1" title="Borrar fecha"
                    class="h-6 w-6 rounded text-muted-foreground hover:text-red-600 hover:bg-muted">
                    <i class="fa fa-times text-xs"></i>
                </button>
                <button type="button" @click="almanaque()" tabindex="-1" title="Elegir en el almanaque"
                    class="h-6 w-6 rounded text-muted-foreground hover:text-foreground hover:bg-muted">
                    <i class="fa fa-calendar-alt text-sm"></i>
                </button>
            </div>
        @endunless

        {{-- Ancla del almanaque: flatpickr crea su calendario aquí (wire:ignore para que Livewire no lo borre) --}}
        <div wire:ignore class="absolute left-0 top-full h-0 w-0">
            <input type="text" x-ref="ancla" class="hidden" tabindex="-1" aria-hidden="true" />
        </div>

        @if ($name)
            <input type="hidden" name="{{ $name }}" :value="valor ?? ''" />
        @endif

        {{-- Flotante: no empuja el diseño --}}
        <p x-show="error" x-cloak x-text="error"
            class="absolute left-0 top-full mt-1 z-20 whitespace-nowrap rounded border border-destructive/40 bg-background px-1.5 py-0.5 text-xs text-destructive shadow"></p>
    </div>

    @if ($help)
        <p class="text-xs text-muted-foreground">{{ $help }}</p>
    @endif
    @if ($error && $modelo)
        <x-input-error for="{{ $modelo }}" class="text-xs text-destructive" />
    @endif
</x-group-field>
