<x-app-layout>
    @php
        $destino = 'combustible';
    @endphp
    <livewire:almacen-salida-productos-component :destino="$destino"/>
    <livewire:almacen-salida-productos-form-component :destino="$destino"/>

    <livewire:almacen-salida-historial-por-compra-component/>
    <livewire:almacen-salida-kardex-component/>
    <livewire:productos-form-component/>
    <livewire:productos-stock-component/>
    <livewire:productos-compra-component/>
    <livewire:distribucion-combustible-component/>
    <livewire:gestion-almacen.distribucion-combustible-form-component />
    
</x-app-layout>
