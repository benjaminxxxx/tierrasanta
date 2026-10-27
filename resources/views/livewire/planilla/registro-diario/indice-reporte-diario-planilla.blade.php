<x-app-layout>
    <livewire:planilla.registro-diario.gestion-planilla-registro-diario-component/>
    <livewire:planilla.ordenar-planilla-mensual-component/>
    <livewire:campo.ver-labores-component />
    {{-- Clic derecho en la tabla: suspensiones del trabajador (el mismo modal de Permisos y suspensiones) --}}
    <livewire:planilla.asistencia.suspension-trabajador-modal-component />
    {{-- Clic derecho en la tabla: panel del empleado (ficha, historial laboral, pensión y pago) --}}
    <livewire:planilla.empleado.gestion-planilla-empleados-form-component />
</x-app-layout>
