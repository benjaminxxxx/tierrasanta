<div>

    <x-flex class="w-full justify-between my-5">
        <x-h3>
            {{ ucfirst($infestacionTexto) }}
        </x-h3>
        <x-flex>
            <x-button type="button" wire:click="sincronizarInformacion">
                <i class="fa fa-sync"></i> Sincronizar datos
            </x-button>
        </x-flex>
    </x-flex>


    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>
                        Resumen de las {{ $tipo == 'infestacion' ? 'infestaciones' : 'Reinfestaciones' }}
                    </x-h3>
                    <x-label>
                        Presione el botón <b>Sincronizar</b> datos para obtener la información de
                        {{ $infestacionTexto }} de la
                        campaña seleccionada.
                    </x-label>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                        </x-slot>
                        <x-slot name="tbody">
                            @if ($tipo == 'infestacion')
                                <x-tr>
                                    <x-th>Fecha {{ $infestacionTexto }}</x-th>
                                    <x-td x-data="{ editando: false }" class="bg-cyan-100">
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="infestacion_fecha" label="" />
                                                <x-button type="button" wire:click=""
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->infestacion_fecha) }}</span>

                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Tiempo de siembra o inicio de campaña a infestación</x-th>
                                    <x-td>{{ $campania->infestacion_duracion_desde_campania }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Número de pencas a la infestación</x-th>
                                    <x-td
                                        class="bg-lime-100">{{ number_format($campania->infestacion_numero_pencas, 0) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Kg totales de madres</x-th>
                                    <x-td
                                        class="bg-purple-100">{{ number_format($campania->infestacion_kg_totales_madre, 0) }}</x-td>
                                </x-tr>
                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_kg_madre_infestador_carton) &&
                                            $campania->infestacion_kg_madre_infestador_carton != 0))
                                    <x-tr>
                                        <x-th>Kg de madres para infestador cartón</x-th>
                                        <x-td
                                            class="bg-orange-100">{{ number_format($campania->infestacion_kg_madre_infestador_carton, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_kg_madre_infestador_tubos) &&
                                            $campania->infestacion_kg_madre_infestador_tubos != 0))
                                    <x-tr>
                                        <x-th>Kg de madres para infestador tubos</x-th>
                                        <x-td
                                            class="bg-indigo-100">{{ number_format($campania->infestacion_kg_madre_infestador_tubos, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_kg_madre_infestador_mallita) &&
                                            $campania->infestacion_kg_madre_infestador_mallita != 0))
                                    <x-tr>
                                        <x-th>Kg de madres para infestador mallita</x-th>
                                        <x-td
                                            class="bg-stone-100">{{ number_format($campania->infestacion_kg_madre_infestador_mallita, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-th>Procedencia de las madres</x-th>
                                    <x-td></x-td>
                                </x-tr>
                                @foreach ($campania->procedencias_madres as $procedencia)
                                    <x-tr>
                                        <x-td>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}</x-td>
                                        <x-td>{{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</x-td>
                                    </x-tr>
                                @endforeach

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_cantidad_madres_por_infestador_carton_alias) &&
                                            $campania->infestacion_cantidad_madres_por_infestador_carton_alias != '0gr.'))
                                    <x-tr>
                                        <x-th>Cantidad de madres por infestador cartón</x-th>
                                        <x-td>{{ $campania->infestacion_cantidad_madres_por_infestador_carton_alias }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_cantidad_madres_por_infestador_tubos_alias) &&
                                            $campania->infestacion_cantidad_madres_por_infestador_tubos_alias != '0gr.'))
                                    <x-tr>
                                        <x-th>Cantidad de madres por infestador tubo</x-th>
                                        <x-td>{{ $campania->infestacion_cantidad_madres_por_infestador_tubos_alias }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_cantidad_madres_por_infestador_mallita_alias) &&
                                            $campania->infestacion_cantidad_madres_por_infestador_mallita_alias != '0gr.'))
                                    <x-tr>
                                        <x-th>Cantidad de madres por infestador mallita</x-th>
                                        <x-td>{{ $campania->infestacion_cantidad_madres_por_infestador_mallita_alias }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_cantidad_infestadores_carton) &&
                                            $campania->infestacion_cantidad_infestadores_carton != 0))
                                    <x-tr>
                                        <x-th>Cantidad de infestadores cartón</x-th>
                                        <x-td>{{ number_format($campania->infestacion_cantidad_infestadores_carton, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_cantidad_infestadores_tubos) &&
                                            $campania->infestacion_cantidad_infestadores_tubos != 0))
                                    <x-tr>
                                        <x-th>Cantidad de infestadores tubos</x-th>
                                        <x-td>{{ number_format($campania->infestacion_cantidad_infestadores_tubos, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->infestacion_cantidad_infestadores_mallita) &&
                                            $campania->infestacion_cantidad_infestadores_mallita != 0))
                                    <x-tr>
                                        <x-th>Cantidad de infestadores mallita</x-th>
                                        <x-td>{{ number_format($campania->infestacion_cantidad_infestadores_mallita, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-th>Fecha recojo y vaciado de infestadores</x-th>
                                    <x-td x-data="{ editando: false }">
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="infestacion_fecha_recojo_vaciado_infestadores"
                                                    label="" />
                                                <x-button type="button"
                                                    wire:click="registrarCambiosFechaRecojoVaciadoInfestadores"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->infestacion_fecha_recojo_vaciado_infestadores) }}</span>
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>

                                </x-tr>
                                <x-tr>
                                    <x-th>Permanencia infestadores (días)</x-th>
                                    <x-td>{{ $campania->infestacion_permanencia_infestadores }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Fecha colocación de malla</x-th>
                                    <x-td x-data="{ editando: false }">
                                        <!-- Formulario de edición (cuando editando es true) -->
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="infestacion_fecha_colocacion_malla"
                                                    label="" />
                                                <!-- Botón de guardar -->
                                                <x-button type="button" wire:click="registrarFechaColocacionMalla"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <!-- Botón de cancelar -->
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <!-- Vista de solo lectura (cuando editando es false) -->
                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->infestacion_fecha_colocacion_malla) }}</span>
                                                <!-- Botón para habilitar la edición -->
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>

                                <x-tr>
                                    <x-th>Fecha retiro de malla</x-th>
                                    <x-td x-data="{ editando: false }">
                                        <!-- Formulario de edición (cuando editando es true) -->
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="infestacion_fecha_retiro_malla"
                                                    label="" />
                                                <!-- Botón de guardar -->
                                                <x-button type="button" wire:click="registrarFechaRetiroMalla"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <!-- Botón de cancelar -->
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <!-- Vista de solo lectura (cuando editando es false) -->
                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->infestacion_fecha_retiro_malla) }}</span>
                                                <!-- Botón para habilitar la edición -->
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>

                                <x-tr>
                                    <x-th>Permanencia de malla (días)</x-th>
                                    <x-td>{{ $campania->infestacion_permanencia_malla }}</x-td>
                                </x-tr>

                            @endif
                            @if ($tipo == 'reinfestacion')
                                <x-tr>
                                    <x-th>Fecha {{ $infestacionTexto }}</x-th>
                                    <x-td x-data="{ editando: false }" class="bg-cyan-100">
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="reinfestacion_fecha" label="" />
                                                <x-button type="button" wire:click="registrarCambiosReinfestacionFecha"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->reinfestacion_fecha) }}</span>
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Tiempo de infestación a re-infestación</x-th>
                                    <x-td>{{ $campania->reinfestacion_duracion_desde_infestacion }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Número de pencas a la re-infestación</x-th>
                                    <x-td
                                        class="bg-lime-100">{{ number_format($campania->reinfestacion_numero_pencas, 0) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Kg totales de madres</x-th>
                                    <x-td
                                        class="bg-purple-100">{{ number_format($campania->reinfestacion_kg_totales_madre, 0) }}</x-td>
                                </x-tr>
                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_kg_madre_infestador_carton) &&
                                            $campania->reinfestacion_kg_madre_infestador_carton != 0))
                                    <x-tr>
                                        <x-th>Kg de madres para infestador cartón</x-th>
                                        <x-td
                                            class="bg-orange-100">{{ number_format($campania->reinfestacion_kg_madre_infestador_carton, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_kg_madre_infestador_tubos) &&
                                            $campania->reinfestacion_kg_madre_infestador_tubos != 0))
                                    <x-tr>
                                        <x-th>Kg de madres para infestador tubos</x-th>
                                        <x-td
                                            class="bg-indigo-100">{{ number_format($campania->reinfestacion_kg_madre_infestador_tubos, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_kg_madre_infestador_mallita) &&
                                            $campania->reinfestacion_kg_madre_infestador_mallita != 0))
                                    <x-tr>
                                        <x-th>Kg de madres para infestador mallita</x-th>
                                        <x-td
                                            class="bg-stone-100">{{ number_format($campania->reinfestacion_kg_madre_infestador_mallita, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-th>Procedencia de las madres</x-th>
                                    <x-td></x-td>
                                </x-tr>
                                @php
                                    $procedencias = [];
                                    if ($campania->reinfestacion_procedencia_madres) {
                                        // Asegurar que sea un array, deserializando si es necesario
                                        if (is_string($campania->reinfestacion_procedencia_madres)) {
                                            try {
                                                $procedencias =
                                                    json_decode($campania->reinfestacion_procedencia_madres, true) ?:
                                                    [];
                                            } catch (\Exception $e) {
                                                $procedencias = [];
                                            }
                                        } elseif (is_array($campania->reinfestacion_procedencia_madres)) {
                                            $procedencias = $campania->reinfestacion_procedencia_madres;
                                        }
                                    }
                                @endphp

                                @if (count($procedencias) > 0)
                                    @foreach ($procedencias as $procedencia)
                                        <x-tr>
                                            <x-td>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}</x-td>
                                            <x-td>{{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</x-td>
                                        </x-tr>
                                    @endforeach
                                @endif
                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_cantidad_madres_por_infestador_carton_alias) &&
                                            $campania->reinfestacion_cantidad_madres_por_infestador_carton_alias !== '0gr.'))
                                    <x-tr>
                                        <x-th>Cantidad de madres por infestador cartón</x-th>
                                        <x-td>{{ $campania->reinfestacion_cantidad_madres_por_infestador_carton_alias }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_cantidad_madres_por_infestador_tubos_alias) &&
                                            $campania->reinfestacion_cantidad_madres_por_infestador_tubos_alias !== '0gr.'))
                                    <x-tr>
                                        <x-th>Cantidad de madres por infestador tubo</x-th>
                                        <x-td>{{ $campania->reinfestacion_cantidad_madres_por_infestador_tubos_alias }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_cantidad_madres_por_infestador_mallita_alias) &&
                                            $campania->reinfestacion_cantidad_madres_por_infestador_mallita_alias !== '0gr.'))
                                    <x-tr>
                                        <x-th>Cantidad de madres por infestador mallita</x-th>
                                        <x-td>{{ $campania->reinfestacion_cantidad_madres_por_infestador_mallita_alias }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_cantidad_infestadores_carton) &&
                                            $campania->reinfestacion_cantidad_infestadores_carton != 0))
                                    <x-tr>
                                        <x-th>Cantidad de infestadores cartón</x-th>
                                        <x-td>{{ number_format($campania->reinfestacion_cantidad_infestadores_carton, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_cantidad_infestadores_tubos) &&
                                            $campania->reinfestacion_cantidad_infestadores_tubos != 0))
                                    <x-tr>
                                        <x-th>Cantidad de infestadores tubos</x-th>
                                        <x-td>{{ number_format($campania->reinfestacion_cantidad_infestadores_tubos, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    $mostrarVacios ||
                                        (!is_null($campania->reinfestacion_cantidad_infestadores_mallita) &&
                                            $campania->reinfestacion_cantidad_infestadores_mallita != 0))
                                    <x-tr>
                                        <x-th>Cantidad de infestadores mallita</x-th>
                                        <x-td>{{ number_format($campania->reinfestacion_cantidad_infestadores_mallita, 0) }}</x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-th>Fecha recojo y vaciado de infestadores</x-th>
                                    <x-td x-data="{ editando: false }">
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date
                                                    wire:model="reinfestacion_fecha_recojo_vaciado_infestadores"
                                                    label="" />
                                                <x-button type="button"
                                                    wire:click="registrarCambiosFechaRecojoVaciadoReInfestadores"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->reinfestacion_fecha_recojo_vaciado_infestadores) }}</span>
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>

                                </x-tr>
                                <x-tr>
                                    <x-th>Permanencia infestadores (días)</x-th>
                                    <x-td>{{ $campania->reinfestacion_permanencia_infestadores }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th>Fecha colocación de malla</x-th>
                                    <x-td x-data="{ editando: false }">
                                        <!-- Formulario de edición (cuando editando es true) -->
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="reinfestacion_fecha_colocacion_malla"
                                                    label="" />
                                                <!-- Botón de guardar -->
                                                <x-button type="button"
                                                    wire:click="registrarFechaColocacionMallaReinfestacion"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <!-- Botón de cancelar -->
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <!-- Vista de solo lectura (cuando editando es false) -->
                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->reinfestacion_fecha_colocacion_malla) }}</span>
                                                <!-- Botón para habilitar la edición -->
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>

                                <x-tr>
                                    <x-th>Fecha retiro de malla</x-th>
                                    <x-td x-data="{ editando: false }">
                                        <!-- Formulario de edición (cuando editando es true) -->
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input-date wire:model="reinfestacion_fecha_retiro_malla"
                                                    label="" />
                                                <!-- Botón de guardar -->
                                                <x-button type="button"
                                                    wire:click="registrarFechaRetiroMallaReinfestacion"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <!-- Botón de cancelar -->
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <!-- Vista de solo lectura (cuando editando es false) -->
                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ formatear_fecha($campania->reinfestacion_fecha_retiro_malla) }}</span>
                                                <!-- Botón para habilitar la edición -->
                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>

                                <x-tr>
                                    <x-th>Permanencia de malla (días)</x-th>
                                    <x-td>{{ $campania->reinfestacion_permanencia_malla }}</x-td>
                                </x-tr>

                            @endif


                        </x-slot>

                    </x-table>
                </x-spacing>
            </x-card>
        @endif

        <div class="flex-1 overflow-auto">
            <x-card>
                <x-spacing>
                    <x-h3>
                        Lista de {{ $tipo == 'infestacion' ? 'infestaciones' : 'Reinfestaciones' }}
                    </x-h3>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center">
                                    N°
                                </x-th>
                                <x-th class="text-center">
                                    Fecha de<br />{{ $infestacionTexto }}
                                </x-th>
                                <x-th class="text-center">
                                    Campo
                                </x-th>
                                <x-th class="text-center">
                                    Campaña
                                </x-th>
                                <x-th class="text-center">
                                    Kg Madres
                                </x-th>
                                <x-th class="text-center">
                                    Campo Origen
                                </x-th>
                                <x-th class="text-center">
                                    Método
                                </x-th>
                                <x-th class="text-center">
                                    N° Envases
                                </x-th>
                                <x-th class="text-center">
                                    Capacidad Envase
                                </x-th>
                                <x-th class="text-center">
                                    Infestadores
                                </x-th>
                                <x-th class="text-center">
                                    Madres por Infestador
                                </x-th>
                                <x-th class="text-center">
                                    Infestadores por Ha
                                </x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($infestaciones as $indiceInfestacion => $infestacion)
                                <x-tr>
                                    <x-td class="text-center">
                                        {{ $indiceInfestacion + 1 }}
                                    </x-td>
                                    <x-td
                                        class="text-center {{ $indiceInfestacion + 1 == $infestaciones->count() ? 'bg-cyan-100' : '' }}">
                                        {{ formatear_fecha($infestacion->fecha) }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->campo_nombre }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->campoCampania->nombre_campania }}
                                    </x-td>
                                    <x-td class="text-center bg-purple-100">
                                        {{ $infestacion->kg_madres }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->campo_origen_nombre }}
                                    </x-td>
                                    <x-td
                                        class="text-center {{ $infestacion->metodo == 'carton'
                                            ? 'bg-orange-100'
                                            : ($infestacion->metodo == 'tubos'
                                                ? 'bg-indigo-100'
                                                : ($infestacion->metodo == 'mallita'
                                                    ? 'bg-stone-100'
                                                    : '')) }}">
                                        {{ $infestacion->metodo }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->numero_envases }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->capacidad_envase }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->infestadores }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->madres_por_infestador_alias }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $infestacion->infestadores_por_ha_alias }}
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </x-spacing>
            </x-card>
            @if ($campania)
                <div class="mt-4">
                    @_@livewire('consulta-actividad-diaria-component', [
                        'campaniaId' => $campania->id,
                    ])
                </div>
            @endif

        </div>

    </x-flex>

    <x-loading wire:loading />
</div>
