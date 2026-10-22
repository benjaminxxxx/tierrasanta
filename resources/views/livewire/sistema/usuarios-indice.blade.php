<x-app-layout title="Usuarios">
    <livewire:sistema.usuario-list-component/>
    <livewire:sistema.usuario-form-component/>
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['usuarios']" :flotante="true" :permiso="\App\Constants\Permisos::SISTEMA_USUARIO_GESTIONAR" />
</x-app-layout>
