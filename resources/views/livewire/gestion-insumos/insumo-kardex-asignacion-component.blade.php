<div x-data="asignacionKardex">
    @include('livewire.gestion-insumos.partials.insumo-kardex-asignacion-header')
    <div class="container mx-auto space-y-6 mt-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna Centro: Sin asignar --}}
            @include('livewire.gestion-insumos.partials.insumo-kardex-asignacion-center-column')

            {{-- Columna Kardex Blanco --}}
            @include('livewire.gestion-insumos.partials.insumo-kardex-asignacion-kardex-blanco')

            {{-- Columna Kardex Negro --}}
            @include('livewire.gestion-insumos.partials.insumo-kardex-asignacion-kardex-negro')

        </div>

        {{-- Botones contextuales --}}
        @include('livewire.gestion-insumos.partials.insumo-kardex-asignacion-contextual-buttons')
    </div>


</div>
@script
    <script>
        Alpine.data('asignacionKardex', () => ({
            allSalidas: @js($salidas),
            changes: new Map(),
            stockInicialBlancoValor: @js($stockInicialBlanco),
            stockInicialNegroValor: @js($stockInicialNegro),
            compras: @js($compras),
            selected: new Set(),
            lastSelectedId: null,
            originalCompras: [],

            init() {
                this.originalCompras = this.compras.map(c => ({
                    id: c.id,
                    tipo_kardex: c.tipo_kardex,
                }))
            },

            hasComprasChanges() {
                return this.compras.some(compra => {
                    const original = this.originalCompras.find(o => o.id === compra.id)
                    if (!original) return false

                    return original.tipo_kardex !== compra.tipo_kardex
                })
            },
            
            // Getter para obtener el Kardex Blanco ordenado
            get kardexBlancoItems() {
                return this.filtrarYOrdenar('blanco');
            },
            

            // Getter para obtener el Kardex Negro ordenado
            get kardexNegroItems() {
                return this.filtrarYOrdenar('negro');
            },
            get sinAsignar() {
                return this.allSalidas.filter(s => this.resolveTipoSalida(s) === null);
            },
            get stockInicialBlanco() {
                return this.stockInicialBlancoValor ?? 0; // viene del backend
            },
            get stockInicialNegro() {
                return this.stockInicialNegroValor ?? 0; // viene del backend
            },

            get totalComprasBlanco() {
                return this.totalCompras('blanco');
            },
            get totalComprasNegro() {
                return this.totalCompras('negro');
            },
            get totalSalidasBlanco() {
                return this.totalSalidas('blanco');
            },
            get totalSalidasBlanco() {
                return this.totalSalidas('blanco');
            },
            get totalSalidasNegro() {
                return this.totalSalidas('negro');
            },
            get balanceBlanco() {
                return (
                    this.stockInicialBlanco +
                    this.totalComprasBlanco -
                    this.totalSalidasBlanco
                );
            },
            get balanceNegro() {
                return (
                    this.stockInicialNegro +
                    this.totalComprasNegro -
                    this.totalSalidasNegro
                );
            },
            totalCompras(tipo) {
                return this.compras
                    .filter(c => c.tipo_kardex === tipo)
                    .reduce((sum, c) => sum + Number(c.cantidad), 0);
            },
            totalSalidas(tipo) {
                return this.allSalidas
                    .filter(s => this.resolveTipoSalida(s) === tipo)
                    .reduce((sum, s) => sum + Number(s.cantidad), 0);
            },
            estadoSalida(salida) {
                if (this.changes.has(salida.id)) {
                    return this.changes.get(salida.id); // 'blanco' | 'negro' | null
                }

                return salida.tipo_kardex; // BD
            },
            hasSelectedIn(tipo) {
                for (const id of this.selected) {
                    const salida = this.allSalidas.find(s => s.id === id);
                    if (this.resolveTipoSalida(salida) === tipo) {
                        return true;
                    }
                }
                return false;
            },
            get hasChanges() {
                return this.changes.size > 0 || this.hasComprasChanges()
            },
            filtrarYOrdenar(tipoDestino) {
                // Compras: solo las del tipo
                const compras = this.compras
                    .filter(c => c.tipo_kardex === tipoDestino);

                // Salidas: cambios tienen prioridad sobre BD
                const salidasAsignadas = this.allSalidas.filter(s => {
                    if (this.changes.has(s.id)) {
                        return this.changes.get(s.id) === tipoDestino;
                    }

                    return s.tipo_kardex === tipoDestino;
                });

                const unificado = [...compras, ...salidasAsignadas];

                return unificado.sort((a, b) => {
                    const fechaA = new Date(a.fecha);
                    const fechaB = new Date(b.fecha);

                    if (fechaA - fechaB !== 0) {
                        return fechaA - fechaB;
                    }

                    if (a.tipo === 'entrada' && b.tipo === 'salida') return -1;
                    if (a.tipo === 'salida' && b.tipo === 'entrada') return 1;

                    return 0;
                });
            },
            moverCompra(compra) {
                const tipoActual = compra.tipo_kardex
                const nuevoTipo = tipoActual === 'blanco' ? 'negro' : 'blanco'

                // 1. Quitar salidas actualmente vinculadas a esta compra
                //this.quitarSalidasCompra(compra)

                // 2. Cambiar tipo de kardex de la compra
                compra.tipo_kardex = nuevoTipo

                // 3. Reasignar automáticamente según el nuevo kardex
                //this.autoAjustarCompra(compra)
            },
            quitarDeKardex(salidaId) {
                this.changes.set(salidaId, null) // fuerza desasignación
                this.selected.delete(salidaId)
            },
            quitarSeleccionados(tipo) {
                this.selected.forEach(id => {
                    const salida = this.allSalidas.find(s => s.id === id)
                    if (!salida) return

                    if (this.resolveTipoSalida(salida) === tipo) {
                        this.changes.set(id, null)
                    }
                })

                this.selected.clear()
                this.lastSelectedId = null
            },
            resolveTipoSalida(salida) {
                if (this.changes.has(salida.id)) {
                    return this.changes.get(salida.id) // puede ser null
                }

                return salida.tipo_kardex
            },
            autoAjustarCompra(compra) {
                const tipo = compra.tipo_kardex; // blanco o negro
                let stockDisponible = compra.cantidad;

                // 1. Salidas ya asignadas a este kardex y posteriores a la compra
                const salidasAsignadas = this.allSalidas.filter(s =>
                    this.resolveTipoSalida(s) === tipo &&
                    new Date(s.fecha) >= new Date(compra.fecha)
                );

                // 2. Restar lo ya cubierto
                salidasAsignadas.forEach(s => {
                    stockDisponible -= s.cantidad;
                });
                if (stockDisponible <= 0) return;

                // 3. Salidas sin asignar, ordenadas por fecha
                const salidasDisponibles = this.allSalidas
                    .filter(s => !this.resolveTipoSalida(s))
                    .filter(s => new Date(s.fecha) >= new Date(compra.fecha))
                    .sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

                // 4. Asignar hasta cubrir stock
                for (const salida of salidasDisponibles) {

                    if (stockDisponible <= 0) break;

                    if (salida.cantidad <= stockDisponible) {
                        this.changes.set(salida.id, tipo);
                        stockDisponible -= salida.cantidad;
                    } else {
                        // NO dividir automáticamente
                        break;
                    }
                }
            },
            selectOne(item, shiftKey) {
                const salidaId = item.id
                const grupo = this.getKardexDe(salidaId)

                let source = []

                if (grupo === 'blanco') {
                    source = this.kardexBlancoItems
                } else if (grupo === 'negro') {
                    source = this.kardexNegroItems
                } else {
                    source = this.sinAsignar
                }

                const ids = source.map(s => s.id)

                if (
                    shiftKey &&
                    this.lastSelectedId !== null &&
                    ids.includes(this.lastSelectedId)
                ) {
                    const currentIndex = ids.indexOf(salidaId)
                    const lastIndex = ids.indexOf(this.lastSelectedId)

                    if (currentIndex === -1 || lastIndex === -1) return

                    const start = Math.min(currentIndex, lastIndex)
                    const end = Math.max(currentIndex, lastIndex)

                    for (let i = start; i <= end; i++) {
                        this.selected.add(ids[i])
                    }
                } else {
                    this.selected.has(salidaId) ?
                        this.selected.delete(salidaId) :
                        this.selected.add(salidaId)

                    this.lastSelectedId = salidaId
                }
            },
            quitarSalidasCompra(compra) {
                const tipo = compra.tipo_kardex
                let restante = compra.cantidad

                const salidasVinculadas = this.allSalidas
                    .filter(s =>
                        this.resolveTipoSalida(s) === tipo &&
                        new Date(s.fecha) >= new Date(compra.fecha)
                    )
                    .sort((a, b) => new Date(a.fecha) - new Date(b.fecha))

                for (const salida of salidasVinculadas) {
                    if (restante <= 0) break

                    // CLAVE: forzar a null, incluso si venía de BD
                    this.changes.set(salida.id, null)

                    restante -= salida.cantidad
                }
            },
            getKardexDe(id) {
                const salida = this.allSalidas.find(s => s.id === id)
                if (!salida) return 'sin'

                if (this.changes.has(id)) {
                    return this.changes.get(id) // blanco | negro | null
                }

                return salida.tipo_kardex ?? 'sin'
            },
            asignarSeleccionados(destino) {
                this.selected.forEach(id => {
                    this.changes.set(id, destino);
                });
                this.selected.clear();
                this.lastSelectedId = null;
            },
            cancelarCambios() {
                this.changes.clear()
                this.selected.clear()
                this.lastSelectedId = null

                // revertir compras
                this.originalCompras.forEach(original => {
                    const compra = this.compras.find(c => c.id === original.id)
                    if (compra) compra.tipo_kardex = original.tipo_kardex
                })
            },
            confirmarCambios() {

                
                const payload = {
                    salidas: Array.from(this.changes.entries()).map(([id, tipo]) => ({
                        id,
                        tipo_kardex: tipo
                    })),
                    compras: this.compras.map(c => ({
                        id: c.id,
                        tipo_kardex: c.tipo_kardex
                    }))
                }

                $wire.confirmarAsignaciones(payload)
            }

        }));
    </script>
@endscript
