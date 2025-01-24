<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Avance de productividad
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('nuevoRegistro',{fecha:'{{ $fecha }}'})"
            class="w-full md:w-auto ">
            <i class="fa fa-plus"></i> Nuevo Registro
        </x-button>
    </div>
    <x-card class="w-full">
        <x-spacing>

            <div class="flex justify-between items-center w-full">
                <x-secondary-button wire:click="fechaAnterior">
                    <i class="fa fa-chevron-left"></i> <span class="hidden lg:inline">Fecha Anterior</span>
                </x-secondary-button>

                <div class="lg:flex gap-4 w-full lg:w-auto text-center">
                    <x-input type="date" wire:model.live="fecha"
                        class="text-center mx-2 !mt-0 !w-auto mb-3 lg:mb-0" />
                </div>

                <x-secondary-button wire:click="fechaPosterior" class="ml-3">
                    <span class="hidden lg:inline">Fecha Posterior</span> <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>
    <div class="my-5">
        @if ($reportesPorDia && $reportesPorDia->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($reportesPorDia as $reportePorDia)
                    <x-card class="w-full">
                        <x-spacing>
                            <table class="w-full mb-5">
                                <thead>
                                    <tr>
                                        <th class="text-center">Fecha</th>
                                        <th class="text-center">Campo</th>
                                        <th class="text-center">Labor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">{{ $reportePorDia->fechaCorta }}</td>
                                        <td class="text-center">{{ $reportePorDia->campo }}</td>
                                        <td class="text-center">{{ $reportePorDia->labor->nombre_labor }} ({{$reportePorDia->labor_id}})</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="overflow-x-auto">
                                <table class="table-auto w-full border-collapse border border-gray-300 rounded-lg">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-2 py-1 text-center text-gray-700 border border-gray-300">

                                            </th>
                                            @if ($reportePorDia->detalles && $reportePorDia->detalles->count() > 0)
                                                @foreach ($reportePorDia->detalles as $indice => $actividad)
                                                    <th
                                                        class="px-2 py-1 text-center text-gray-700 border border-gray-300">
                                                        KG. {{ $indice+1 }}
                                                    </th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th class="px-2 py-1 text-center text-gray-700 border border-gray-300">
                                                HRS. TRABAJADAS
                                            </th>
                                            @if ($reportePorDia->detalles && $reportePorDia->detalles->count() > 0)
                                                @foreach ($reportePorDia->detalles as $indice => $actividad)
                                                    <td class="px-2 py-1 text-center text-gray-700 border border-gray-300">
                                                        {{ $actividad->horas_trabajadas }}
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                        <tr>
                                            <th class="px-2 py-1 text-center text-gray-700 border border-gray-300">
                                                KG 
                                            </th>
                                            @if ($reportePorDia->detalles && $reportePorDia->detalles->count() > 0)
                                                @foreach ($reportePorDia->detalles as $indice => $actividad)
                                                    <td class="px-2 py-1 text-center text-gray-700 border border-gray-300">
                                                        {{ $actividad->kg }}
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </x-spacing>
                        <div class="bg-slate-200 w-full text-center py-1 !font-md">
                            <x-spacing class="!py-2">
                                <x-flex class="justify-between">
                                    <x-h3>
                                        {{ $reportePorDia->diaSemana }}
                                    </x-h3>
                                    <div class="gap-3">
                                        <x-secondary-button type="button" @click="$wire.dispatch('listarRegistro',{productividadId:'{{ $reportePorDia->id }}'})">
                                            <i class="fa fa-list"></i>
                                        </x-secondary-button>
                                        <x-button type="button" @click="$wire.dispatch('editarRegistro',{registroId:'{{ $reportePorDia->id }}'})">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-danger-button type="button" wire:click="preguntarEliminar({{$reportePorDia->id}})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </div>
                                </x-flex>
                            </x-spacing>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @else
            <x-card class="w-full">
                <x-spacing>
                    <x-warning>
                        Aún no existe ningún registro para la fecha especificada.
                    </x-warning>
                </x-spacing>
            </x-card>
        @endif
    </div>
</div>
