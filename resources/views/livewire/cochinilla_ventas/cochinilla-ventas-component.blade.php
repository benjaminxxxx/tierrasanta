<div>
    

    <x-tabs default-value="entrega_venta" storage-key="ventas-tab-cochinilla">
        <x-card>
            <x-tabs-list class="mb-4">
                @can('Ver entrega de venta')
                    <x-tabs-trigger value="entrega_venta">Entrega de venta</x-tabs-trigger>
                @endcan
                @can('Ver reporte de venta')
                    <x-tabs-trigger value="reporte_venta">Reporte de Venta</x-tabs-trigger>
                @endcan
                
                @can('Ver costo de venta y facturacion')
                    <x-tabs-trigger value="costo_venta">Costo de Venta y Facturación</x-tabs-trigger>
                @endcan
            </x-tabs-list>
        </x-card>
@can('Ver entrega de venta')
        <x-tabs-content value="entrega_venta">
            <livewire:cochinilla_ventas.cochinilla-venta-registro-entrega-component />
        </x-tabs-content>
@endcan
                @can('Ver reporte de venta')
        <x-tabs-content value="reporte_venta">
            <livewire:cochinilla_ventas.cochinilla-venta-reporte-component />
        </x-tabs-content>
        @endcan
        @can('Ver costo de venta y facturacion')
            <x-tabs-content value="costo_venta">
                <livewire:cochinilla_ventas.cochinilla-venta-facturada-component />
            </x-tabs-content>
        @endcan
    </x-tabs>

    <x-loading wire:loading />
</div>