<x-card>
    <x-flex class="mb-4">
        <x-title>
            Tipo de Asistencias
        </x-title>
        <x-button type="button" @click="$wire.dispatch('nuevoTipoAsistencia')">
            <i class="fa fa-plus"></i> Registrar asistencias
        </x-button>
    </x-flex>

    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th class="text-center">Código</x-th>
                <x-th>Descripción</x-th>
                <x-th class="text-center">Horas Jornal</x-th>
                <x-th class="text-center">Color</x-th>
                <x-th class="text-center">Acciones</x-th>
            </x-tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($tipoAsistencias as $tipoAsistencia)
                <x-tr>
                    <x-th class="text-center">{{ $tipoAsistencia->codigo }}</x-th>
                    <x-td class="!text-left">{{ $tipoAsistencia->descripcion }}</x-td>
                    <x-td class="text-center font-bold text-lg">
                        @if ($tipoAsistencia->horas_jornal == 0)
                            <span class="text-red-600">
                                {{ $tipoAsistencia->horas_jornal }}
                            </span>
                        @else
                            <span class="text-green-500">
                                {{ $tipoAsistencia->horas_jornal }}
                            </span>
                        @endif
                    </x-td>
                    <x-td class="text-center">
                        <div class="m-auto flex flex-col items-center">
                            {{ $tipoAsistencia->color }}
                            <div style="background:{{ $tipoAsistencia->color }}"
                                class="block w-12 h-12 border-1 border-black rounded shadow-md">
                            </div>
                        </div>
                    </x-td>
                    <x-td class="text-center">
                        <x-button
                            @click="$wire.dispatch('editarTipoAsistencia',{tipoAsistenciaId:{{ $tipoAsistencia->id }}})"
                            wire:loading.attr="disabled">
                            <i class="fa fa-pencil"></i>
                        </x-button>
                        @php
                            $filtro = ['A', 'F', 'V'];

                        @endphp
                        @if (!in_array($tipoAsistencia->codigo, $filtro))
                            <x-button wire:click="eliminarTipoAsistencia({{ $tipoAsistencia->id }})"
                                wire:loading.attr="disabled" variant="danger">
                                <i class="fa fa-remove"></i>
                            </x-button>
                        @endif

                    </x-td>
                </x-tr>
            @endforeach
        </x-slot>
    </x-table>
    <div class="flex justify-end mt-5">
        <x-button wire:click="preguntarRestaurar">
            <i class="fa fa-refresh"></i> Restaurar Valores por Defecto
        </x-button>
    </div>

    <x-loading wire:loading wire:target="agregarTipoAsistencia" />

</x-card>
