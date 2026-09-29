<x-app-layout>
    @php
        $destino = 'combustible';
    @endphp
    <livewire:almacen.almacen-salida-productos-component :destino="$destino"/>
    <livewire:almacen.almacen-salida-productos-form-component :destino="$destino"/>

    <livewire:almacen.almacen-salida-kardex-component/>
    <livewire:producto.productos-form-component/>
    <livewire:almacen.distribucion-combustible-calculo-component/>
    <livewire:almacen.distribucion-combustible-form-component />
    
</x-app-layout>
