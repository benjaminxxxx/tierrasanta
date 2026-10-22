<x-app-layout>
    <livewire:campo.labores-component/>
    <livewire:campo.labor-form-component/>
    <livewire:campo.labor-reasignar-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['labores', 'tramos']" :flotante="true" :permiso="\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR" />
</x-app-layout>