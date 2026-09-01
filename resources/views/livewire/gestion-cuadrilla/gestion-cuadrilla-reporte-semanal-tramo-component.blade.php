<div x-data="reporteSemanalTramo" @keydown.window.prevent.ctrl.s="registrarHoras"
    @keydown.window.prevent.meta.s="registrarHoras" class="">
    <x-card class="my-5 mb-20">

        @include('livewire.gestion-cuadrilla.partial.reporte-semanal-tabla')
        @include('livewire.gestion-cuadrilla.partial.reporte-semanal-resumen')
        @include('livewire.gestion-cuadrilla.partial.personalizar-costo-hora-form')
        @include('livewire.gestion-cuadrilla.partial.reordenar-grupo-form')
        @include('livewire.gestion-cuadrilla.partial.reemplazar-cuadrillero')
    </x-card>


    <livewire:gestion-cuadrilla.gestion-cuadrilla-reporte-pago-component />

    <x-loading wire:loading />
    <style>
        body .handsontable .htDimmed {
            color: #000 !important;
        }
    </style>
</div>
@script
<script>
    Alpine.data('reporteSemanalTramo', () => ({
        ocurrioModificaciones: false,
        reporteSemanal: @js($handsontableData),
        datosCompletos: @js($handsontableData),   // fuente de verdad, sin filtrar
        scrolleado: false,
        mostrarBusquedaFlotante: false,
        busquedaNombre: '',
        filtroGrupo: '',
        reporteSemanal: [],
        headers: [],
        totalDias: @js($totalDias),
        hot: null,
        diasSemana: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
        headersDias: [],
        fechaInicio: @js($tramoLaboral->fecha_inicio),
        fechaFin: @js($tramoLaboral->fecha_fin),
        init() {
            this.headersDias = this.generarEncabezados(this.fechaInicio, this.fechaFin);
            this.reporteSemanal = this.datosCompletos;
            this.initTable();
            /*Livewire.on('recargarTablaTramos', (data) => {
                this.reporteSemanal = data[0];
                this.initTable();
            });*/
            Livewire.on('recargarTablaTramos', (data) => {
                this.datosCompletos = data[0];
                this.listaGrupos = data[1] ?? this.listaGrupos;
                // limpiar filtros al cambiar de tramo evita quedarte viendo
                // un grupo que quizá no exista en el nuevo tramo
                this.filtroGrupo = '';
                this.busquedaNombre = '';
                this.aplicarFiltros();
            });

            // Controla la visibilidad del botón lupa según el scroll
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.scrolleado = window.scrollY > 200; // ajusta el umbral a tu gusto
                        ticking = false;
                    });
                    ticking = true;
                }
            });

            this.$watch('filtroGrupo', () => {
                this.aplicarFiltros();
                this.$nextTick(() => this.subirScrollTabla());
            });

            this.$watch('busquedaNombre', () => {
                this.aplicarFiltros();
                this.$nextTick(() => this.subirScrollTabla());
            });
        },
        abrirBusquedaFlotante() {
            this.mostrarBusquedaFlotante = true;
            this.$nextTick(() => {
                this.$refs.inputBusquedaFlotante?.focus();
            });
        },
        subirScrollTabla() {
            const el = this.$refs.tableContainerSemana;
            if (!el) return;

            // Offset para no dejar el borde superior de la tabla pegado al viewport
            const offset = 100;
            const top = el.getBoundingClientRect().top + window.scrollY - offset;

            window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
        },
        // Separa el array plano en grupos {header, miembros} + fila de totales aparte
        agruparFilas(filas) {
            const grupos = [];
            let grupoActual = null;
            let filaTotales = null;

            filas.forEach((fila) => {
                if (fila.es_totales) {           // <-- ver nota más abajo
                    filaTotales = fila;
                    return;
                }
                if (fila.header) {
                    grupoActual = { header: fila, miembros: [] };
                    grupos.push(grupoActual);
                } else if (grupoActual) {
                    grupoActual.miembros.push(fila);
                }
            });

            return { grupos, filaTotales };
        },
        initTable() {

            if (this.hot) {
                this.hot.destroy();
            }

            const container = this.$refs.tableContainerSemana;
            this.hot = new Handsontable(container, {
                data: this.reporteSemanal,
                themeName: 'ht-theme-main-dark-auto',
                nestedHeaders: [
                    [
                        '',
                        '',
                        {
                            label: 'HORAS TRABAJADAS',
                            colspan: this.totalDias,
                            headerClassName: 'htCenter'
                        },
                        {
                            label: 'COSTO JORNAL',
                            colspan: this.totalDias,
                            headerClassName: 'htCenter'
                        },
                        {
                            label: 'BONOS POR DIA',
                            colspan: this.totalDias,
                            headerClassName: 'htCenter'
                        },
                        '', '', ''
                    ],
                    ['N°', 'NOMBRES', ...this.headersDias, ...this.headersDias, ...this
                        .headersDias, 'TOTAL JORNAL', 'TOTAL BONOS', 'TOTAL'
                    ]
                ],
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                width: '100%',
                stretchH: 'all',
                filters: true,
                rowHeaders: false,
                fixedColumnsLeft: 2,
                contextMenu: {
                    items: {
                        "customize_cuadrillero": {
                            name: 'Personalizar costo por día',
                            callback: () => this.customizeCuadrillero(),
                            hidden: () => !this.tieneCuadrilleroId()
                        },
                        "reemplazar_cuadrillero": {
                            name: 'Reemplazar Cuadrillero',
                            callback: () => this.reemplazarCuadrillero(),
                            hidden: () => !this.tieneCuadrilleroId()
                        },
                        "eliminar_cuadrillero": {
                            name: 'Eliminar Cuadrillero',
                            callback: () => this.eliminarCuadrillero(),
                            hidden: () => !this.tieneCuadrilleroId()
                        },
                        "agregar_cuadrilleros": {
                            name: 'Agregar Cuadrilleros al Grupo',
                            callback: () => this.agregarCuadrillerosAlGrupo(),
                            hidden: () => !this.esFilaEncabezado()
                        },
                        "eliminar_grupo": {
                            name: 'Eliminar Grupo',
                            callback: () => this.eliminarGrupo(),
                            hidden: () => !this.esFilaEncabezado()
                        }
                    }
                },
                afterChange: (changes, source) => {
                    if (!changes) return;
                    console.log(source);
                    // Fuentes válidas que deben disparar la lógica de cambio de color
                    const fuentesValidas = ['edit', 'CopyPaste.paste', 'Autofill'];

                    if (fuentesValidas.includes(source)) {
                        changes.forEach(([row, prop, oldVal, newVal]) => {
                            if (prop === 'codigo_grupo') {
                                const color = this.colorPorGrupo[newVal] || '#ffffff';
                                this.hot.setDataAtRowProp(row, 'color', color);
                            }
                        });

                        this.ocurrioModificaciones =
                            true; // Solo se activa si viene de fuente válida
                    }
                },

                licenseKey: 'non-commercial-and-evaluation',
                plugins: ['Filters'],
            });

            const filterField = document.getElementById('filterField');
            const columnSelector = document.getElementById('columns');
            if (filterField && columnSelector) {
                filterField.addEventListener('keyup', (event) => {
                    const filtersPlugin = this.hot.getPlugin('filters');
                    const columnIndex = parseInt(columnSelector.value, 10);

                    filtersPlugin.clearConditions();
                    filtersPlugin.addCondition(columnIndex, 'contains', [event.target.value]);
                    filtersPlugin.filter();
                    this.hot.render();
                });
            }
        },
        aplicarFiltros() {
            const grupoSeleccionado = this.filtroGrupo;
            const busqueda = this.busquedaNombre.trim().toLowerCase();

            const { grupos, filaTotales } = this.agruparFilas(this.datosCompletos);

            const gruposFiltrados = grupos
                .filter(g => !grupoSeleccionado || g.header.codigo_grupo === grupoSeleccionado)
                .map(g => {
                    if (!busqueda) return g;
                    return {
                        header: g.header,
                        miembros: g.miembros.filter(m =>
                            (m.nombres || '').toLowerCase().includes(busqueda)
                        ),
                    };
                })
                .filter(g => !busqueda || g.miembros.length > 0);

            const filas = gruposFiltrados.flatMap(g => [g.header, ...g.miembros]);

            if (filaTotales) filas.push(filaTotales);

            this.reporteSemanal = filas;

            if (this.hot) {
                this.hot.loadData(this.reporteSemanal);
            } else {
                this.initTable();
            }
        },
        abrirReordenarGruposForm() {
            if (this.ocurrioModificaciones) {
                alert('Guarda primero los cambios realizados dando clic en Actualizar Horas');
                return;
            }
            $wire.abrirReordenarGruposForm();
        },
        customizeCuadrillero() {

            const selected = this.hot.getSelected();
            let preciosamodificar = [];

            if (selected) {
                selected.forEach(range => {

                    const [startRow, , endRow] = range;
                    for (let row = startRow; row <= endRow; row++) {
                        const cuadrillero = this.hot.getSourceDataAtRow(row);
                        console.log(cuadrillero);
                        preciosamodificar.push(cuadrillero);
                    }
                });

                $wire.abrirPrecioPersonalizado(preciosamodificar);
            }
        },
        reemplazarCuadrillero() {

            const selected = this.hot.getSelected();

            if (!selected || selected.length === 0) return;

            const [startRow] = selected[0]; // primera fila del primer rango

            const registro = this.hot.getSourceDataAtRow(startRow);

            const cuadrillero_id = registro?.cuadrillero_id;

            if (!cuadrillero_id) return;

            $wire.reemplazarCuadrillero(cuadrillero_id);
        },
        agregarCuadrillerosAlGrupo() {
            if (this.ocurrioModificaciones) {
                alert('Guarda primero los cambios realizados dando clic en Actualizar Horas');
                return;
            }

            const selected = this.hot.getSelected();
            if (!selected || selected.length === 0) return;

            const [startRow] = selected[0];
            const registro = this.hot.getSourceDataAtRow(startRow);
            const codigo_grupo = registro?.codigo_grupo;

            if (!codigo_grupo) return;

            Livewire.dispatch('agregarCuadrillerosEnTramo', { codigo_grupo });
        },
        eliminarGrupo() {
            const selected = this.hot.getSelected();
            if (!selected || selected.length === 0) return;

            const [startRow] = selected[0];
            const registro = this.hot.getSourceDataAtRow(startRow);
            const codigo_grupo = registro?.codigo_grupo;

            if (!codigo_grupo) return;

            if (confirm(`¿Estás seguro de eliminar el grupo ${codigo_grupo}?`)) {
                $wire.eliminarGrupo(codigo_grupo);
            }
        },
        eliminarCuadrillero() {
            const selected = this.hot.getSelected();
            if (!selected || selected.length === 0) return;

            const [startRow] = selected[0];
            const registro = this.hot.getSourceDataAtRow(startRow);
            const cuadrillero_id = registro?.cuadrillero_id;
            const codigo_grupo = registro?.codigo_grupo;

            if (!cuadrillero_id || !codigo_grupo) return;

            if (confirm(`¿Estás seguro de eliminar a ${registro.nombres} del grupo?`)) {
                $wire.eliminarCuadrillero(cuadrillero_id, codigo_grupo);
            }
        },
        esFilaEncabezado() {
            if (!this.hot) return false;
            const selected = this.hot.getSelectedLast();
            if (!selected) return false;
            const rowData = this.hot.getSourceDataAtRow(selected[0]);
            return !!(rowData && rowData.header);
        },

        tieneCuadrilleroId() {
            if (!this.hot) return false;
            const selected = this.hot.getSelectedLast();
            if (!selected) return false;
            const rowData = this.hot.getSourceDataAtRow(selected[0]);
            return !!(rowData && rowData.cuadrillero_id);
        },
        toLocalDate(dateLike) {
            if (dateLike instanceof Date) {
                // normaliza a medianoche local
                return new Date(dateLike.getFullYear(), dateLike.getMonth(), dateLike.getDate());
            }
            if (typeof dateLike === 'string') {
                // espera 'YYYY-MM-DD'
                const [y, m, d] = dateLike.split('-').map(Number);
                return new Date(y, m - 1, d);
            }
            throw new Error('Fecha inválida');
        },
        generarEncabezados(fechaInicio, fechaFin) {
            const fi = this.toLocalDate(fechaInicio);
            const ff = this.toLocalDate(fechaFin);

            const headers = [];
            for (let d = new Date(fi); d <= ff; d.setDate(d.getDate() + 1)) {
                const dia = this.diasSemana[d.getDay()];
                const num = d.getDate();
                headers.push(`${dia}<br/>${num}`);
            }
            return headers;
        },
        generarColumnasDinamicas() {
            const cols = [{
                data: 'orden',
                title: 'N°',
                type: 'numeric',
                width: 25,
                readOnly: true,
                className: '!text-center !bg-gray-200 !text-black font-bold'
            }, {
                data: 'nombres',
                title: 'Nombre',
                type: 'text',
                readOnly: true,
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    // renderer base de texto (v16)
                    Handsontable.renderers.TextRenderer(instance, td, row, col, prop, value,
                        cellProperties);

                    const rowData = instance.getSourceDataAtRow(row) || {};

                    td.style.backgroundColor = rowData.color || '#e5e7eb';

                    td.classList.remove('htDimmed');
                    if (rowData.header) {
                        td.style.color = '#000';
                        td.style.fontWeight = 'bold';
                    } else {
                        td.classList.add('!text-black');
                        td.style.color = '#000';
                        td.style.fontWeight = '';
                    }
                }
            },];

            // 🟦 Asistencia (día_1, día_2, ...)
            for (let i = 1; i <= this.totalDias; i++) {
                cols.push({
                    data: `dia_${i}`,
                    title: this.headers[i - 1] ?? '-',
                    type: 'numeric',
                    strict: true,
                    filter: false,
                    allowInvalid: false,
                    className: '!text-center !text-lg',
                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.NumericRenderer.apply(this, arguments);
                        if (value > 0) {
                            td.style.color = '';
                        } else {
                            td.style.color = 'rgba(255,0,0,0.8)';
                        }
                    }

                });
            }


            // 🟨 Costos (jornal_1, jornal_2, ...)
            for (let i = 1; i <= this.totalDias; i++) {
                const mas = this.totalDias;
                cols.push({
                    data: `jornal_${i}`,
                    title: this.headers[mas + i - 1] ?? '-',
                    className: '!text-center !bg-gray-200 !text-black',
                    readOnly: true
                });
            }

            // 🟩 Bonos (bono_1, bono_2, ...)
            for (let i = 1; i <= this.totalDias; i++) {
                const mas = this.totalDias * 2;
                cols.push({
                    data: `bono_${i}`,
                    title: this.headers[mas + i - 1] ?? '-',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0.00'
                    },
                    className: '!text-center !bg-gray-200 !text-black',
                    readOnly: true
                });
            }

            // 🔢 Totales
            cols.push(

                {
                    data: 'total_costo',
                    title: 'Total<br/>Jornal',
                    type: 'numeric',
                    readOnly: true,
                    className: '!bg-yellow-200 !text-center !font-bold !text-black'
                }, {
                data: 'total_jornal',
                title: 'Total<br/>Bono',
                type: 'numeric',
                readOnly: true,
                className: '!bg-yellow-200 !text-center !font-bold !text-black'
            }, {
                data: 'total',
                title: 'Total',
                type: 'numeric',
                readOnly: true,
                className: '!bg-yellow-200 !text-center !font-bold !text-black'
            }
            );

            return cols;
        },
        agregarCuadrillerosEnTramo() {

            if (this.ocurrioModificaciones) {
                alert('Guarda primero los cambios realizados dando clic en Actualizar Horas');
                return;
            }
            Livewire.dispatch('agregarCuadrillerosEnTramo');
        },
        registrarHoras() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            // Filtra las filas vacías
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            this.ocurrioModificaciones = false;
            $wire.storeTableDataGuardarHoras(filteredData);
        }
    }));
</script>
@endscript