{{-- Corregir lote / eliminar una fila suelta de venteado o filtrado (trait ConCorreccionFilaCochinilla). $que = 'venteado' | 'filtrado' --}}
<x-dialog-modal wire:model.live="mostrarCorreccion" maxWidth="lg">
    <x-slot name="title">{{ $correccionAccion === 'eliminar' ? 'Eliminar ' . $que : 'Corregir el n° de lote del ' . $que }}</x-slot>
    <x-slot name="content">
        @php
            $fila = $correccionId
                ? ($que === 'venteado' ? \App\Models\CochinillaVenteado::find($correccionId) : \App\Models\CochinillaFiltrado::find($correccionId))
                : null;
        @endphp
        @if ($fila)
            <p class="text-sm mb-3">Lote <b>{{ $fila->lote }}</b> · {{ formatear_fecha($fila->fecha_proceso) }} ·
                {{ number_format($fila->kilos_ingresado ?? $fila->kilos_ingresados, 2) }} kg ingresados</p>
        @endif
        <div class="space-y-3">
            @if ($correccionAccion === 'lote')
                <x-input type="number" min="1" step="1" wire:model="correccionLote" class="w-full" label="N° de lote correcto"
                    placeholder="Ej. 1517" />
                <p class="text-xs text-muted-foreground">Si ese lote ya tiene ingreso, la fila se une a él.</p>
            @else
                <p class="text-xs text-muted-foreground">La fila se borra, pero queda guardada completa en la auditoría.</p>
            @endif
            <x-input type="text" wire:model="correccionMotivo" class="w-full" label="Motivo (obligatorio)"
                placeholder="{{ $correccionAccion === 'lote' ? 'Ej. se digitó 1571 en vez de 1517' : 'Ej. registro duplicado' }}" />
            <p class="text-xs text-muted-foreground">Queda en la auditoría con tu nombre, la fecha y este motivo.</p>
        </div>
    </x-slot>
    <x-slot name="footer">
        <div class="flex gap-2">
            <x-button variant="secondary" wire:click="$set('mostrarCorreccion', false)">Cancelar</x-button>
            @if ($correccionAccion === 'eliminar')
                <x-button variant="danger" wire:click="aplicarCorreccion"><i class="fa fa-trash"></i> Eliminar</x-button>
            @else
                <x-button wire:click="aplicarCorreccion"><i class="fa fa-check"></i> Corregir lote</x-button>
            @endif
        </div>
    </x-slot>
</x-dialog-modal>
