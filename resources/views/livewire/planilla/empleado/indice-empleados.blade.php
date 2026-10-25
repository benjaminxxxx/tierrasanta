<x-app-layout title="Empleados">
    <livewire:planilla.empleado.gestion-planilla-empleados-component/>
    {{-- Panel único del empleado: perfil, datos, historial laboral, pensión y pago, derecho habientes --}}
    <livewire:planilla.empleado.gestion-planilla-empleados-form-component/>
    {{-- Botón flotante: Excel de empleados (permiso del libro: contratos; además cada hoja exige su permiso para modificarla) --}}
    <livewire:sistema.sistema-libro-excel-component libro="empleados" :flotante="true" />
</x-app-layout>
