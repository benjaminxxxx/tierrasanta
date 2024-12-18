<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Asistencias Cuadrilla
        </x-h3>
        @if ($currentSemana)
            <x-secondary-button type="button" @click="$wire.dispatch('editarSemana',{semanaId:{{$currentSemana}}})">
                <i class="fa fa-edit"></i> Editar semana actual
            </x-secondary-button>
        @endif
        <livewire:cuadrilla-asistencia-form-component />
        <x-button-a href="{{route('reporte.pago_cuadrilla')}}">
            <i class="fa fa-cash-register"></i> Ver pagos cuadrilleros
        </x-button-a>
    </div>
    <x-card>
        <x-spacing>
            <div class="flex justify-between items-center w-full">
                <x-secondary-button wire:click="fechaAnterior" type="button"
                    style="visibility: {{ $haySemanaAnterior ? 'visible' : 'hidden' }};">
                    <i class="fa fa-chevron-left"></i> <span class="hidden md:inline">Semana Anterior</span>
                </x-secondary-button>

                <div class="md:flex items-center gap-5">
                    <x-secondary-button wire:click="buscarSemana" type="button" class="ml-3">
                        <i class="fa fa-search"></i> Buscar semana
                    </x-secondary-button>
                </div>

                <x-secondary-button wire:click="fechaPosterior" type="button" class="ml-3"
                    style="visibility: {{ $haySemanaPosterior ? 'visible' : 'hidden' }};">
                    <span class="hidden md:inline">Semana Posterior</span> <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>

        </x-spacing>
    </x-card>

    <x-card class="mt-5">
        <x-spacing class="flex items-center justify-center">
            @if ($currentSemana)
                @if ($grupos && count($grupos) > 0)
              
                    <livewire:cuadrilla-asistencia-detalle-component :cuaAsistenciaSemanalId="$currentSemana"
                        wire:key="cuadrillaAsistencia-{{ $currentSemana }}" />
                @endif

                <div class="flex items-center justify-end">
                    <x-danger-button type="button" wire:click="confirmarEliminarRegistroSemanal">
                        <i class="fa fa-trash"></i> Eliminar Registro Semanal
                    </x-danger-button>
                </div>
            @else
                <x-h3 class="w-full text-center">Aún no hay un registro de asistencia para esta semana.</x-h3>

            @endif
        </x-spacing>
    </x-card>

    <x-dialog-modal wire:model="estaBuscadorAbierto" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Buscador de asistencia semanal
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('estaBuscadorAbierto', false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="flex items-center gap-5">
                <x-select wire:model="busquedaAnio" wire:change="filtrarSemanas">
                    <option value="">Seleccione Año</option>
                    @if ($aniosDisponibles)
                        @foreach ($aniosDisponibles as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    @endif
                </x-select>

                @if ($busquedaAnio)
                    <x-select wire:model="busquedaMes" wire:change="filtrarSemanas">
                        <option value="">Seleccione Mes</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </x-select>
                @endif
            </div>

            <div class="my-5">
                <ul>
                    @foreach ($semanas as $semana)
                        <li>
                            <a href="#" class="underline text-indigo-600 hover:text-indigo-700 font-lg my-3 block"
                                wire:click.prevent="seleccionarSemana({{ $semana->id }})">{{ $semana->titulo }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('estaBuscadorAbierto', false)"
                class="mr-2">Cerrar</x-secondary-button>
        </x-slot>
    </x-dialog-modal>

</div>
