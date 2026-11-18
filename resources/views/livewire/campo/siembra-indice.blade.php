<x-app-layout title="Siembras">
    
    <livewire:campo.siembra-list-component />
    <livewire:campo.siembra-form-component />

    {{-- Tarjeta de la campaña (clic en la campaña de un relleno) y los modales a los que lleva --}}
    <livewire:campania.campania-tarjeta-component />
    <livewire:campania.campania-ficha-component />
    <livewire:campania.campania-cerrar-component />
    <livewire:campania.campania-corregir-fechas-component />
    <livewire:campania.campania-mapa-etapas-component />
    <livewire:campania.campania-historia-component />
    
</x-app-layout>