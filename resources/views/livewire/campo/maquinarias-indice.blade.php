<x-app-layout>
    
    <livewire:campo.maquinarias-component/>
    <livewire:campo.maquinaria-form-component/>
    <livewire:campo.maquinaria-consumo-anual-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['maquinarias']" :flotante="true" :permiso="\App\Constants\Permisos::CAMPO_MAQUINARIA_GESTIONAR" />
</x-app-layout>
