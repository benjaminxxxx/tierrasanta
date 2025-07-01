<div>
    <x-loading wire:loading />

    <x-tabs default-value="entrega_venta" storage-key="ventas-tab-cochinilla">
        <x-card2>
            <x-tabs-list class="mb-4">
                <x-tabs-trigger value="entrega_venta">Entrega de venta</x-tabs-trigger>
                <x-tabs-trigger value="reporte_venta">Reporte de Venta</x-tabs-trigger>
                <x-tabs-trigger value="costo_venta">Costo de Venta</x-tabs-trigger>
                <x-tabs-trigger value="factura_venta">Facturacion de Venta</x-tabs-trigger>
            </x-tabs-list>
        </x-card2>

        <x-tabs-content value="entrega_venta">
            <livewire:cochinilla_ventas.cochinilla-venta-registro-entrega-component />
        </x-tabs-content>

        <x-tabs-content value="reporte_venta">
            <livewire:cochinilla_ventas.cochinilla-venta-reporte-component />
        </x-tabs-content>

        <x-tabs-content value="costo_venta">
            <livewire:cochinilla_ventas.cochinilla-venta-facturada-component />
        </x-tabs-content>

        <x-tabs-content value="factura_venta">
            Facturacion de venta
        </x-tabs-content>
    </x-tabs>
</div>