<div>
    <x-dialog-modal wire:model.live="show" maxWidth="full">
        <x-slot name="title">
            Estadísticas de Asignación Familiar
        </x-slot>

        <x-slot name="content">
            <div class="mb-6">
                {{-- Ajusta este include a la firma real de tu componente --}}
                @include('comun.selector-mes')
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stats-card title="Con asignación" :value="$resumen['con_asignacion'] ?? 0" icon="fa-users"
                    description="Empleados que califican este mes" />

                <x-stats-card title="Con hijos registrados" :value="$resumen['total_con_hijos_registrados'] ?? 0"
                    icon="fa-child" description="Total con al menos un hijo activo" />

                <x-stats-card title="Entran en vigencia" :value="$resumen['entran_vigencia'] ?? 0"
                    icon="fa-calendar-plus" description="Comienzan a recibir este mes" />

                <x-stats-card title="Finalizan estudios" :value="$resumen['finalizan_estudios'] ?? 0"
                    icon="fa-calendar-minus" description="Culminan estudios superiores este mes" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="cerrar" variant="secondary">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>