<div x-data="planillaVacacionesBonos">
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer" class="mt-5"></div>
        </div>
    </x-card>
    <x-inferior-derecha>
        <x-button @click="$wire.dispatch('abrirCalculoVacaciones', {mes: {{ $mes }}, anio: {{ $anio }}})">
            <i class="fa fa-calculator"></i> Cálculo de vacaciones
        </x-button>
        <x-button @click="guardarInformacionBonoVacaciones">
            <i class="fa fa-save"></i> Guardar vacaciones y bonos
        </x-button>
    </x-inferior-derecha>
    <livewire:gestion-planilla.calculo-vacaciones-modal-component />
</div>


@script
<script>
    Alpine.data('planillaVacacionesBonos', () => ({
        tableData: @json($planilla),
        hot: null,
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        modifiedRowIndexes: @entangle('modifiedRowIndexes'),
        hasUnsavedChanges: @entangle('hasUnsavedChanges'),
        onBeforeUnload: null,
        init() {
            this.initTable();

            Livewire.on('setplanilla', ({ tableData }) => {
                this.$nextTick(() => {
                    this.tableData = tableData;
                    this.initTable();
                });
            });
            this.onBeforeUnload = (event) => {
                if (this.hasUnsavedChanges) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', this.onBeforeUnload);
        },
        destroy() {
            window.removeEventListener('beforeunload', this.onBeforeUnload);
        },
        initTable() {
            if (this.hot) {
                try { this.hot.destroy(); } catch (e) { }
                this.hot = null;
            }

            const container = this.$refs.tableContainer;

            const hot = new Handsontable(container, {
                ...window.HstConfig,
                data: this.tableData,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                columns: [
                    { data: 'nombres', width: 90, type: 'text', title: 'Empleado', readOnly: true, className: '!bg-muted' },
                    {
                        data: 'resumen_asistencia',
                        title: 'Asistencia<br/>del periodo',
                        readOnly: true,
                        renderer: 'html',
                        className: '!bg-muted',
                    },
                    { data: 'vacaciones_plame', type: 'text', title: 'Vacaciones<br/>según<br/>PLAME', readOnly: true, className: '!bg-muted' },
                    { data: 'vacaciones_plame_personalizado', type: 'numeric', title: 'Vacaciones<br/>personalizadas<br/>PLAME' },
                    { data: 'vacaciones_neto_pagadas', type: 'numeric', title: 'Vacaciones<br/>Neto<br/>Pagadas' },
                    { data: 'vacaciones_negro', type: 'numeric', title: 'Vacaciones<br/>Negro' },
                    { data: 'bonificacion_asistencia', type: 'numeric', title: 'Bonificacion<br/>100%<br/>Asistencia' },
                    { data: 'bonificacion_laboral', type: 'numeric', title: 'Bonificacion<br/>Laboral', readOnly: true, className: '!bg-muted' },
                ],
                height: 'auto',
                afterChange: (changes, source) => {
                    if (!changes || source === 'loadData') return;

                    changes.forEach(([row]) => {
                        const physicalRow = this.hot.toPhysicalRow(row);
                        if (!this.modifiedRowIndexes.includes(physicalRow)) {
                            this.modifiedRowIndexes.push(physicalRow);
                        }
                    });

                    if (['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) {
                        this.hasUnsavedChanges = true;
                    }
                },
            });

            this.hot = hot;
            this.hot.render();
        },
        guardarInformacionBonoVacaciones() {
            const datos = this.hot.getSourceData();
            const resultados = [];

            this.modifiedRowIndexes.forEach((rowIndex) => {
                const fila = datos[rowIndex];
                if (fila) {
                    resultados.push(fila);
                }
            });

            if (resultados.length === 0) {
                return;
            }

            $wire.guardarInformacionBonoVacaciones(resultados);
        },
    }));
</script>
@endscript