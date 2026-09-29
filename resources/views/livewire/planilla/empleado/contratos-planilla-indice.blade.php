<x-app-layout title="Contratos de Planilla">
    
    <livewire:planilla.empleado.contratos-planilla-component :id="$id"/>
    {{-- Los contratos se gestionan en el panel del empleado (pestaña Contratos) --}}
    <livewire:planilla.empleado.gestion-planilla-empleados-form-component/>
    
</x-app-layout>
