{{-- Un solo x-data cubre toda la vista incluyendo el modal --}}
<div x-data="distribucionCombustibleForm" class="space-y-4">

    {{-- Modal gestión distribuciones --}}
    <x-dialog-modal wire:model.live="modalDistribucion" maxWidth="full">
        <x-slot name="title">
            Gestionar las Distribuciones
           
            @if($salida)
                <span class="ml-2 text-sm font-normal text-muted-foreground">
                    — {{ $salida->maquinaria->nombre }}
                    · {{ \Carbon\Carbon::parse($salida->fecha)->format('d/m/Y') }}
                    · {{ number_format($salida->ingreso_salida, 2) }} L
                </span>
            @endif
        </x-slot>

        <x-slot name="content">
            {{-- wire:ignore: Livewire no debe tocar el DOM del Handsontable --}}
            <div wire:ignore class="w-full h-[300px] overflow-auto">
                <div id="modalTableContainer"></div>
            </div>
            <x-checkbox id="respetarSalida" label="Respetar salida (evitar que la distribución se asigne a otra salida)" wire:model="respetarSalida" class="mt-4" />
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalDistribucion', false)">
                Cancelar
            </x-button>
            <x-button class="ms-3" @click="guardarModal()">
                <i class="fa fa-save mr-1"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('distribucionCombustibleForm', () => ({
        // Datos de listas (solo lectura, no necesitan entangle)
        listaCampos: @js($listaCampos),
        listaMaquinarias: @js($listaMaquinarias),

        isDark: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),
        hotModal: null,
        filasModificadasModal: [],

        init() {

            // Sincronizar tema dark/light con el Handsontable del modal
            this.$watch('isDark', (val) => {
                if (this.hotModal) {
                    this.hotModal.updateSettings({
                        themeName: val ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: this.getModalColumns(),
                    });
                }
            });

            Livewire.on('cargarDistribuciones', ({ distribuciones }) => {
                this.initModalTable(distribuciones);
            });
        },

        // ── Handsontable ────────────────────────────────────────────────────────

        initModalTable(data) {
            this.destruirModalTable();

            const container = document.getElementById('modalTableContainer');
            
            if (!container) return;

            this.filasModificadasModal = [];

            const hot = new Handsontable(container, {
                ...window.HstConfig,
                data: JSON.parse(JSON.stringify(data)), // copia para no mutar el entangle
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                colHeaders: true,
                rowHeaders: true,
                columns: this.getModalColumns(),
                stretchH: 'all',
                minSpareRows: 1,
                autoColumnSize: false,
                licenseKey: 'non-commercial-and-evaluation',

                afterChange: (changes, source) => {
                    if (source === 'loadData' || source === 'recalculado') return;
                    if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;

                    changes?.forEach(([row, prop]) => {
                        if (prop === 'hora_inicio' || prop === 'hora_fin') {
                            this.recalcularHoras(row);
                        }
                        if (!this.filasModificadasModal.includes(row)) {
                            this.filasModificadasModal = [...this.filasModificadasModal, row];
                        }
                    });
                },
            });

            this.hotModal = hot;
            this.hotModal.render();
        },

        destruirModalTable() {
            if (this.hotModal) {
                try { this.hotModal.destroy(); } catch (e) { }
                this.hotModal = null;
            }
            this.filasModificadasModal = [];
        },

        recalcularHoras(row) {
            const inicio = this.hotModal.getDataAtRowProp(row, 'hora_inicio');
            const fin = this.hotModal.getDataAtRowProp(row, 'hora_fin');
            if (!inicio || !fin) return;

            const [h1, m1] = inicio.split(':').map(Number);
            const [h2, m2] = fin.split(':').map(Number);
            const horas = ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60;

            if (horas > 0) {
                this.hotModal.setDataAtRowProp(
                    row, 'n_horas',
                    Math.round(horas * 100) / 100,
                    'recalculado'
                );
            }
        },

        getModalColumns() {
            const camposLabels = this.listaCampos.map(c => c.label);
            const camposRevMap = Object.fromEntries(this.listaCampos.map(c => [c.label, c.label]));


            const autocompleteCol = (labels, map, revMap, prop, title, width) => ({
                data: prop, title, type: 'autocomplete',
                source: labels, strict: false, allowInvalid: false, filter: true, width,
                renderer(instance, td, row, col, p, value) {
                    td.classList.remove('!text-gray-400', 'italic', '!text-red-500');
                    if (!value && value !== 0) {
                        td.classList.add('!text-gray-400', 'italic');
                        td.innerText = 'Buscar...';
                        return;
                    }
                    const label = revMap[value] ?? revMap[String(value)];
                    td.innerText = label ?? ('⚠️ ' + value);
                    if (!label) td.classList.add('!text-red-500');
                },
                validator(value, callback) {
                    if (!value || value === '') return callback(true);
                    if (revMap[value] || revMap[String(value)]) return callback(true);
                    if (typeof value === 'string' && map[value]) {
                        setTimeout(() => {
                            this.instance.setDataAtCell(this.row, this.col, map[value], 'validator');
                        }, 0);
                        return callback(true);
                    }
                    callback(false);
                },
            });

            const T = Handsontable.renderers;

            return [
                {
                    data: 'fecha', title: 'FECHA', width: 100,
                    type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true,
                    renderer: T.TextRenderer,
                },
                {
                    data: 'hora_inicio', title: 'INICIO', width: 75,
                    type: 'time', timeFormat: 'HH:mm', correctFormat: true,
                    renderer: T.TextRenderer,
                },
                {
                    data: 'hora_fin', title: 'FIN', width: 75,
                    type: 'time', timeFormat: 'HH:mm', correctFormat: true,
                    renderer: T.TextRenderer,
                },
                {
                    data: 'n_horas', title: 'HORAS', width: 65,
                    type: 'numeric', numericFormat: { pattern: '0.00' },
                    readOnly: true, className: '!bg-muted',
                    renderer: T.NumericRenderer,
                },
                autocompleteCol(camposLabels, camposRevMap, camposRevMap, 'campo_nombre', 'CAMPO', 120),
                {
                    data: 'labor_diaria', title: 'LABOR DIARIA', width: 200,
                    type: 'text', renderer: T.TextRenderer,
                },
            ];
        },

        // ── Guardar desde footer del modal ──────────────────────────────────────

        guardarModal() {
            if (!this.hotModal || this.filasModificadasModal.length === 0) {
                alert('Ninguna fila modificada.');
                return;
            }

            const data = [...this.filasModificadasModal]
                .map(i => this.hotModal.getSourceDataAtRow(i))
                .filter(f => f && Object.values(f).some(v => v !== null && v !== ''));

            $wire.guardarDistribuciones(data);
        },
    }));
</script>
@endscript