<x-app-layout>
    <livewire:riego.labores-riego-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['riego']" :flotante="true" :permiso="\App\Constants\Permisos::CAMPO_RIEGO_LABOR_GESTIONAR" />
</x-app-layout>