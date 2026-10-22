<x-app-layout>
    <livewire:cochinilla.cochinilla-excel-component modulo="ingreso" />
    
    <!--MODULO COCHINILLA INGRESO-->
    @livewire('cochinilla.cochinilla-ingreso-mapa-component')
    @livewire('cochinilla.cochinilla-ingreso-component')
    @livewire('cochinilla.cochinilla-ingreso-detalle-component')
    <livewire:cochinilla.cochinilla-reparto-origen-component />
    
    <livewire:cochinilla.cochinilla-venteado-form-component />
    <livewire:cochinilla.cochinilla-filtrado-form-component />
</x-app-layout>
