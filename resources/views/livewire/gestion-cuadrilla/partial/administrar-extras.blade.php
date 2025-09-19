<x-modal wire:model="mostrarFormularioAdministracionExtras">
    <div x-data="administracion_extras">
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Administrar jornales extras
            </div>

            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-group-field>
                            <x-input-date label="Seleccione fecha" wire:model.live="fecha" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="Jornal x 8 horas" x-model="costoJornal"
                                @input="recalcularJornalExtra" />
                        </x-group-field>
                    </div>

                    <x-group-field class="mt-6">
                        <div @keydown.arrow-up.prevent="navigateList($event)"
                            @keydown.arrow-down.prevent="navigateList($event)"
                            @keydown.enter.prevent="navigateList($event)" @input="selectedIndex = 0">
                            <div>
                                <x-label>Buscar y agregar</x-label>
                                <div class="flex items-center gap-2">
                                    <div class="relative mt-2 w-full">
                                        <div
                                            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                                            <i class="fa fa-search"></i>
                                        </div>
                                        <x-input type="search" x-model="search" x-ref="buscador"
                                            class="!pl-10 uppercase w-full" autocomplete="off"
                                            placeholder="Busca por Nombres, Apellidos o Documento" required />

                                    </div>
                                    <x-button type="button" x-show="search && cuadrillerosFiltrados.length === 0"
                                        @click="registrarComoNuevo" title="Registrar Cuadrillero"
                                        class="whitespace-nowrap">
                                        <i class="fa fa-save"></i> Registrar como nuevo
                                    </x-button>
                                </div>
                                <ul
                                    class="mt-2 border shadow-2xl border-gray-700 rounded-lg absolute bg-white dark:bg-gray-700 max-h-[500px] overflow-y-auto z-[999]">
                                    <template x-for="(cuadrillero, index) in cuadrillerosFiltrados"
                                        :key="cuadrillero.id">
                                        <li :class="{ 'bg-primary text-primaryText': selectedIndex === index }"
                                            class="px-4 py-2 cursor-pointer hover:bg-primary hover:text-primaryText"
                                            @mouseenter="setSelectedIndex(index)"
                                            @click="agregarCuadrilleroTabla(cuadrillero)">
                                            <span x-text="cuadrillero.nombres"></span> - DNI: <span
                                                x-text="cuadrillero.dni"></span>
                                        </li>
                                    </template>
                                </ul>

                            </div>
                        </div>
                    </x-group-field>
                </div>
                <div wire:ignore class="mt-5">
                    <div x-ref="tableContainerExtras"></div>
                </div>
            </div>
        </div>

        <div
            class="flex flex-row justify-end px-6 py-4 bg-white dark:bg-gray-800 border-t border-gray-700 text-end rounded-b-lg gap-3">
            <x-flex class="flex-end">
                <x-secondary-button wire:click="$set('mostrarFormularioAdministracionExtras', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button @click="registrarHorasExtras" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar costo
                </x-button>
            </x-flex>
        </div>
    </div>

</x-modal>


@script
<script>
    Alpine.data('administracion_extras', () => ({
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
        search: '',
        cuadrillerosAgregados: @entangle('cuadrillerosAgregados'),
        mostrarFormularioAdministracionExtras: @entangle('mostrarFormularioAdministracionExtras'),
        fecha: new Date().toISOString().split('T')[0],
        costoJornal: 0,
        registros:[],

        listaExtras: @js($listaHandsontableExtras),
        get cuadrillerosFiltrados() {
            if (!this.search.trim()) return [];
            return this.cuadrillerosBuscar.filter(c =>
                (c.nombres?.toLowerCase() || '').includes(this.search.toLowerCase()) ||
                (c.dni?.toLowerCase() || '').includes(this.search.toLowerCase())
            );
        },
        init() {

            Livewire.on('abrirExtrasForm', (data) => {
                const fecha  = data[0];
                const registros  = data[1];
                this.mostrarFormularioAdministracionExtras = true;
                this.fecha = fecha;
                this.registros = registros;
                this.administrarExtras();
            });
        },
        initTable() {

        },
        administrarExtras() {
            if (this.hotExtras) {
                this.hotExtras.destroy();
            }
            const container = this.$refs.tableContainerExtras;
            console.log(container);
            this.hotExtras = new Handsontable(container, {
                data: this.registros,
                colHeaders: this.headers,
                rowHeaders: true,
                columns: [{
                    data: 'nombres',
                    type: 'text',
                    title: 'Nombre Cuadrilleros',
                    className: '!text-lg !text-black'
                }, {
                    data: 'horas',
                    type: 'numeric',
                    title: 'Horas',
                    className: '!text-center !text-lg !font-bold'
                }, {
                    data: 'costo_por_hora',
                    type: 'numeric',
                    title: 'CostoxH',
                    className: '!text-center !text-lg !font-bold'
                }, {
                    data: 'costo_jornal',
                    type: 'numeric',
                    title: 'Monto total',
                    className: '!text-center !text-lg !font-bold !bg-yellow-400',
                    readOnly: true
                }],
                width: '100%',
                //minSpareRows: 1,
                height: 'auto',
                stretchH: 'all',
                fixedColumnsLeft: 2,
                licenseKey: 'non-commercial-and-evaluation',
                afterChange: (changes, source) => {
                    if (!changes) return; // puede venir null en loadData

                    if (['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) {
                        for (const [row, prop] of changes) {
                            if (prop === 'horas' || prop === 'costo_por_hora') {
                                const horas = Number(this.hotExtras.getDataAtRowProp(row, 'horas')) || 0;
                                const cph = Number(this.hotExtras.getDataAtRowProp(row, 'costo_por_hora')) || 0;
                                const monto = horas * cph;

                                this.hotExtras.setDataAtRowProp(row, 'costo_jornal', monto, 'calculo');
                            }
                        }
                    }
                }


            });
        },
        navigateList(event) {
            if (this.cuadrillerosFiltrados.length === 0) return;

            if (event.key === 'ArrowDown') {
                this.selectedIndex = (this.selectedIndex + 1) % this.cuadrillerosFiltrados.length;
            } else if (event.key === 'ArrowUp') {
                this.selectedIndex = (this.selectedIndex - 1 + this.cuadrillerosFiltrados.length) % this.cuadrillerosFiltrados.length;
            } else if (event.key === 'Enter') {
                this.agregarCuadrilleroTabla(this.cuadrillerosFiltrados[this.selectedIndex]);
            }
        },
        agregarCuadrilleroTabla(cuadrillero) {
            const data = this.hotExtras.getSourceData();

            // evitar duplicado solo por nombre
            const yaExiste = data.some(c => c.nombres.toUpperCase() === cuadrillero.nombres.toUpperCase());
            if (yaExiste) {
                alert('El cuadrillero ya está agregado en la tabla');
                this.search = '';
                this.cuadrillerosFiltrados = [];
                this.$refs.buscador.focus();
                return;
            }

            const costo_por_hora = (Number(this.costoJornal) || 0) / 8;

            data.push({
                id: cuadrillero.id ?? null,
                nombres: cuadrillero.nombres,
                horas: 8,                       // valor por defecto
                costo_por_hora,                 // guardamos el cph por fila (aunque no sea una columna visible)
                costo_jornal: 8 * costo_por_hora
            });

            this.hotExtras.loadData(data);
            this.search = '';
            this.cuadrillerosFiltrados = [];
            this.$refs.buscador.focus();
        },

        registrarComoNuevo() {
            if (!this.search.trim()) return; // evita registrar vacío

            const cuadrillero = {
                id: null, // porque no existe en la BD
                nombres: this.search.trim().toUpperCase()
            };

            this.agregarCuadrilleroTabla(cuadrillero);
        },
        recalcularJornalExtra() {
            const rows = this.hotExtras.countRows();

            for (let i = 0; i < rows; i++) {
                const horas = Number(this.hotExtras.getDataAtRowProp(i, 'horas')) || 0;
                const cph = (this.costoJornal||0)/8;

                const nuevoMonto = horas * cph;
                this.hotExtras.setDataAtRowProp(i, 'costo_jornal', nuevoMonto, 'recalculo');
                this.hotExtras.setDataAtRowProp(i, 'costo_por_hora', cph, 'recalculo');
            }
        },
        registrarHorasExtras(){
       
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hotExtras.countRows(); row++) {
                const rowData = this.hotExtras.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            // Filtra las filas vacías
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            $wire.storeTableDataGuardarHorasExtras(filteredData,this.fecha);
        }


    }));
</script>
@endscript