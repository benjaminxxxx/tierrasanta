<x-app-layout title="Parámetros de Planilla">
    
    <livewire:planilla.parametros-planilla-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['configuracion', 'configuracion_fecha']" :flotante="true" :permiso="\App\Constants\Permisos::PLANILLA_PARAMETRO_GESTIONAR" />
</x-app-layout>
