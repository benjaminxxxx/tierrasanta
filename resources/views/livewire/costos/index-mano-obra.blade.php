<x-app-layout>
    <livewire:costos.mano-obra-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['mano_obra']" :flotante="true" :permiso="\App\Constants\Permisos::CAMPO_MANO_OBRA_GESTIONAR" />
</x-app-layout>