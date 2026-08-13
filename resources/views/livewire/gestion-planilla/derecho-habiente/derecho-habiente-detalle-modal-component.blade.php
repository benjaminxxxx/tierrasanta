<x-dialog-modal wire:model.live="show" maxWidth="full">
    <x-slot name="title">
        Detalle de Asignación Familiar - {{ $empleadoNombre }}
    </x-slot>

    <x-slot name="content">
        <div class="mb-4">
            @if ($tieneAsignacion)
            <x-callout variant="success" heading="Corresponde asignación familiar (a la fecha actual)." />
            @else
            <x-callout variant="warning" heading="No corresponde asignación familiar (a la fecha actual)." />
            @endif
        </div>

        @if (empty($detalle))
        <p class="text-gray-400 text-sm">Este empleado no tiene hijos registrados activos.</p>
        @else
        <div class="space-y-3">
            @foreach ($detalle as $hijo)
            <div class="border rounded-md p-3 flex items-start gap-3 border-border bg-muted">
                <div class="mt-1">
                    @if ($hijo['califica'])
                    <i class="fa fa-check-circle text-green-600"></i>
                    @else
                    <i class="fa fa-times-circle text-gray-400"></i>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="font-medium">{{ $hijo['nombres'] }} <span class="text-gray-400 text-sm">({{
                            $hijo['documento'] }})</span></p>
                    <p class="text-sm text-gray-500">{{ $hijo['edad'] }} años</p>
                    <p class="text-sm mt-1">{{ $hijo['motivo'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-button wire:click="cerrar" variant="secondary">Cerrar</x-button>
    </x-slot>
</x-dialog-modal>