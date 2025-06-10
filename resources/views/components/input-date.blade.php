@props([
    'label' => null,
    'error' => null,
    'descripcion' => null,
    'fechaMin' => null, // Fecha mínima
    'fechaMax' => null, // Fecha máxima
])

<div class="relative">
    <div class="flex flex-col">
        @if ($label || $attributes->wire('model'))
            <x-label for="{{ $attributes->wire('model') }}">
                {{ $label ?? ucfirst(str_replace('_', ' ', $attributes->wire('model'))) }}
            </x-label>
        @endif

        <x-input type="date" {{ $attributes }} />

        @if ($descripcion)
            <small>{{ $descripcion }}</small>
        @endif
        @if ($error)
            <x-input-error for="{{ $error }}" />
        @endif
    </div>
</div>