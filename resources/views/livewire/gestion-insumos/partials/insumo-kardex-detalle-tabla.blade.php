{{-- kardex-detalle.blade.php --}}
<div class="my-5">

    {{-- FILTROS --}}
    <div class="flex flex-wrap gap-3 mb-4 items-end">
        {{-- Rango de fechas --}}
        <div class="flex flex-col gap-1">
            <x-label>Fecha desde</x-label>
            <x-input type="date" x-model="filtros.fechaDesde" @change="applyFilters()" />
        </div>
        <div class="flex flex-col gap-1">
            <x-label>Fecha hasta</x-label>
            <x-input type="date" x-model="filtros.fechaHasta" @change="applyFilters()" />
        </div>

        {{-- Búsqueda por factura / número de comprobante --}}
        <div class="flex flex-col gap-1">
            <x-label>Factura / N° Comprobante</x-label>
            <x-input x-model="filtros.comprobante" @input="applyFilters()" placeholder="Serie o número..." />
        </div>

        {{-- Filtro por lote --}}
        <div class="flex flex-col gap-1">
            <x-label>Lote</x-label>
            <x-input x-model="filtros.lote" @input="applyFilters()" placeholder="Lote..." />
        </div>
    </div>

    {{-- Botón limpiar --}}
    <x-button @click="clearFilters()" variant="ghost">
        Limpiar filtros
    </x-button>

    {{-- Contador de resultados --}}
    <span class="text-xs text-muted-foreground self-end ml-auto">
        <span x-text="filteredCount"></span> registro(s)
    </span>

    {{-- HANDSONTABLE --}}
    <div wire:ignore>
        <div x-ref="tableContainer"></div>
    </div>
</div>