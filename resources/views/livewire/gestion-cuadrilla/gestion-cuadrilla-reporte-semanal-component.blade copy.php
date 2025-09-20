<div x-data="reporte_semanal_cuadrilleros">
    <div>
        <x-flex class="w-full justify-between">
            <x-flex class="my-3">
                <x-h3>
                    Registro Semanal OBSOLETO Cuadrilla
                </x-h3>
                
                <x-button @click="agregarCuadrillerosEnSemana">
                    <i class="fa fa-plus"></i> Agregar cuadrilleros
                </x-button>
                <x-button wire:click="administrarExtras">
                    <i class="fa fa-plus"></i> Administrar extras
                </x-button>
            </x-flex>
            <x-flex>
                <x-button @click="abrirReordenarGruposForm">
                    <i class="fas fa-sort"></i>
                    Reordenar grupos
                </x-button>

                <x-button-a href="{{ route('cuadrilleros.gestion') }}">
                    <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
                </x-button-a>
            </x-flex>
        </x-flex>


        <div class="flex justify-between items-center">
            <x-button wire:click="semanaAnterior">
                <i class="fa fa-chevron-left"></i> Semana Anterior
            </x-button>

            <div class="text-center">
                <form wire:submit.prevent="seleccionarSemana">
                    <div class="flex items-center space-x-2">
                        <x-select wire:model.live="anio">
                            <option value="">Año</option>
                            @for ($y = now()->year - 5; $y <= now()->year + 1; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-select>

                        <x-select wire:model.live="mes">
                            <option value="">Mes</option>
                            @foreach ($meses as $num => $nombre)
                                <option value="{{ $num }}">{{ $nombre }}</option>
                            @endforeach
                        </x-select>

                        <x-select wire:model.live="semanaNumero">
                            <option value="">Semana</option>
                            @for ($s = 1; $s <= 5; $s++)
                                <option value="{{ $s }}">Semana {{ $s }}</option>
                            @endfor
                        </x-select>
                    </div>
                </form>

            </div>

            <x-button wire:click="siguienteSemana">
                Semana Posterior <i class="fa fa-chevron-right"></i>
            </x-button>
        </div>

        <x-h3 class="text-center w-full my-3">
            <strong>Semana:</strong> {{ $semana->inicio }} - {{ $semana->fin }}
        </x-h3>

        <div wire:ignore>
            <x-flex>
                <x-select name="columns" id="columns" label="Filtro">
                    <option value="1">Nombre</option>
                    <option value="0">Código</option>
                </x-select>
                <x-group-field>
                    <x-label>
                        Descripción
                    </x-label>
                    <x-input id="filterField" type="text" placeholder="Buscar por código o nombre" />
                </x-group-field>
            </x-flex>
            <div x-ref="tableContainerSemana" class="mt-5"></div> 
        </div>
        <x-flex class="mt-5 justify-end">
            <div>
                <x-h3>
                    Cuadro resumen:
                </x-h3>
                <div class="border border-gray-600 rounded">
                    <x-table class="mt-5">
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>GRUPO</x-th>
                                <x-th>COSTO <br />PRODUCCIÓN</x-th>
                                <x-th>CONDICIÓN</x-th>
                                <x-th>FECHA</x-th>
                                <x-th>RECIBO</x-th>
                                <x-th>DEUDA ACUMULADA</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($listaGrupos as $grupo)

                                <x-tr>
                                    <x-th>{{ $grupo['nombre'] }}</x-th>
                                    <x-td class="text-right">{{ formatear_numero($grupo['costo_produccion']) }}</x-td>
                                    <x-td>CONDICIÓN</x-td>
                                    <x-td>FECHA</x-td>
                                    <x-td>RECIBO</x-td>
                                    <x-td>DEUDA ACUMULADA</x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </div>

            </div>
        </x-flex>

    </div>

    
    @include('livewire.gestion-cuadrilla.partial.agregar-cuadrilleros-semanales-form')
    @include('livewire.gestion-cuadrilla.partial.reordenar-grupo-form')
    @include('livewire.gestion-cuadrilla.partial.administrar-extras')

    <x-loading wire:loading wire:target="storeTableDataGuardarHoras" />
    <x-loading wire:loading wire:target="fecha" />
</div>
@script
<script>
    Alpine.data('reporte_semanal_cuadrilleros', () => ({
        listeners: [],
        codigo_grupo: @entangle('codigo_grupo'),
        fechaInicioSemana: @entangle('fechaInicioSemana'),
        ocurrioModificaciones: @entangle('ocurrioModificaciones'),
        reporteSemanal: @json($reporteSemanal),
        headers: @json($headers),
        totalDias: @json($totalDias),
        gruposDisponibles: @json($gruposDisponibles),
        colorPorGrupo: @json($colorPorGrupo),
        cuadrilleros: @json($cuadrilleros),
        hot: null,
        hotExtras: null,
        selectedIndex: 0,
        cuadrillerosFiltrados: [],
        cuadrillerosBuscar: @json($listaCuadrilleros),
        search: @entangle('search'),
        

        init() {
            this.$nextTick(() => {
                this.initTable();
                //this.initTableGastosAdicionales();
            });

            Livewire.on('actualizarTablaReporteSemanal', (data) => {
                console.log(data[0]);
                this.reporteSemanal = data[0];
                this.totalDias = data[1];
                this.headers = data[2];
                this.$nextTick(() => this.initTable());
            });
            
            //Agregar cuadrillero
            this.$watch('search', (value) => {

                this.selectedIndex = 0;
                if (value.trim() == '') {
                    this.cuadrillerosFiltrados = [];
                    return;
                }
                this.cuadrillerosFiltrados = this.cuadrillerosBuscar.filter(c =>
                    (c.nombres?.toLowerCase() || '').includes(value.toLowerCase()) ||
                    (c.dni?.toLowerCase() || '').includes(value.toLowerCase())
                );
            });
            //fin agregar cuadrillero

        },
        
        
        
        
        
        //agregar cuadrillero
        
        abrirReordenarGruposForm() {
            if (this.ocurrioModificaciones) {
                alert('Guarda primero los cambios realizados dando clic en Actualizar Horas');
                return;
            }
            $wire.abrirReordenarGruposForm();
        },
        
        
        
        
        agregarCuadrillerosEnSemana() {

            if (this.ocurrioModificaciones) {
                alert('Guarda primero los cambios realizados dando clic en Actualizar Horas');
                return;
            }
            $wire.agregarCuadrillerosEnSemana();
        }
        //fin agregar cuadrillero
    }));
</script>
@endscript