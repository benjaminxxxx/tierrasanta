<x-app-layout>
    <livewire:cuadrilla.cuadrillero.cuadrilla-grupos-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['grupos']" :flotante="true" :permiso="\App\Constants\Permisos::CUADRILLA_GRUPO_GESTIONAR" />
</x-app-layout>