<div x-data="cargaKardex" class="space-y-4">
    <x-flex class="justify-between flex-wrap gap-3">
        <div>
            <x-title>Carga de KARDEX anual</x-title>
            <x-subtitle>
                Sube el macro (hoja INDICE + una hoja por código de existencia) y se importa kardex por kardex. El archivo
                queda guardado: si algo falla, corrige el macro, súbelo de nuevo y reprocesa solo lo que falló.
            </x-subtitle>
        </div>
        <x-flex class="items-end flex-wrap">
            @if ($cargas->isNotEmpty())
                <x-select wire:model.live="cargaId" label="Carga" class="w-auto" x-bind:disabled="procesando">
                    @foreach ($cargas as $c)
                        <option value="{{ $c->id }}">
                            #{{ $c->id }} · {{ $c->anio }} {{ $c->tipo_kardex }} · {{ $c->nombre_original }} ({{ $c->created_at->format('d/m/Y H:i') }})
                        </option>
                    @endforeach
                </x-select>
            @endif
            <x-button variant="outline" wire:click="$toggle('mostrarNueva')" x-bind:disabled="procesando">
                <i class="fa fa-plus"></i> Nueva carga
            </x-button>
        </x-flex>
    </x-flex>

    {{-- ================= Nueva carga ================= --}}
    @if ($mostrarNueva)
        <x-card>
            <form wire:submit="subir" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <x-label value="Macro de KARDEX (.xlsm / .xlsx)" />
                    <input type="file" wire:model="archivo" accept=".xlsm,.xlsx" class="block w-full text-sm mt-1" />
                    <div wire:loading wire:target="archivo" class="text-xs text-muted-foreground mt-1">Subiendo archivo…</div>
                    <x-input-error for="archivo" class="mt-1" />
                </div>
                <x-input type="number" wire:model="anio" label="Año del macro" error="anio" />
                <x-select wire:model="tipo" label="Tipo de kardex" error="tipo">
                    <option value="negro">Negro</option>
                    <option value="blanco">Blanco</option>
                </x-select>
                <div class="md:col-span-4 flex justify-end">
                    <x-button type="submit" target="subir" wire:loading.attr="disabled" wire:target="archivo,subir">
                        <i class="fa fa-upload"></i> Guardar macro y leer índice
                    </x-button>
                </div>
            </form>
        </x-card>
    @endif

    {{-- ================= Carga seleccionada ================= --}}
    @if ($carga)
        <x-card>
            <div class="flex flex-wrap justify-between gap-4">
                <div class="text-sm space-y-1">
                    <p><b>{{ $carga->nombre_original }}</b> · versión {{ $carga->version_archivo }}</p>
                    <p>Año <b>{{ $carga->anio }}</b> · kardex <b>{{ $carga->tipo_kardex }}</b>
                        · subido por {{ $carga->subido_por_nombre ?? '—' }} el {{ $carga->created_at->format('d/m/Y H:i') }}</p>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <x-badge color="green">Éxito: {{ $conteo['exito'] ?? 0 }}</x-badge>
                        <x-badge color="red">Error: {{ $conteo['error'] ?? 0 }}</x-badge>
                        <x-badge color="yellow">Sin producto: {{ $conteo['sin_producto'] ?? 0 }}</x-badge>
                        <x-badge color="indigo">Verificados: {{ $conteo['verificado'] ?? 0 }}</x-badge>
                        <x-badge color="blue">Pendientes: {{ $conteo['pendiente'] ?? 0 }}</x-badge>
                        <x-badge color="gray">Sin movimientos: {{ $conteo['sin_movimientos'] ?? 0 }}</x-badge>
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm border rounded-md px-3 h-9"
                        x-bind:class="procesando ? 'opacity-50 pointer-events-none' : ''">
                        <i class="fa fa-file-upload"></i> Subir macro corregido
                        <input type="file" wire:model="archivoCorregido" accept=".xlsm,.xlsx" class="hidden" />
                    </label>
                    <span wire:loading wire:target="archivoCorregido" class="text-xs text-muted-foreground">Subiendo…</span>
                    <x-button variant="outline" x-show="!procesando" @click="iniciar('verificar')"
                        title="Revisa producto, hoja y todas las observaciones de cada hoja, sin importar nada">
                        <i class="fa fa-check-double"></i> Verificar
                    </x-button>
                    <x-button variant="success" x-show="!procesando" @click="iniciar('importar_verificados')"
                        title="Importa y regenera solo los kardex verificados sin observaciones">
                        <i class="fa fa-file-import"></i> Importar verificados ({{ $conteo['verificado'] ?? 0 }})
                    </x-button>
                    <x-button x-show="!procesando" @click="iniciar('ejecutar_pendientes')"
                        title="Verifica e importa todo lo que aún no se importó con éxito">
                        <i class="fa fa-play"></i> Ejecutar pendientes
                    </x-button>
                    <x-button variant="warning" x-show="!procesando"
                        @click="if (confirmarTodos) { iniciar('ejecutar_todos'); confirmarTodos = false } else { confirmarTodos = true; setTimeout(() => confirmarTodos = false, 4000) }"
                        title="Vuelve a importar TODOS, incluidos los que ya se importaron con éxito">
                        <i class="fa fa-redo"></i> <span x-text="confirmarTodos ? '¿Seguro? pulsa otra vez' : 'Ejecutar todos'"></span>
                    </x-button>
                    <x-button variant="danger" x-show="procesando" x-cloak @click="detener = true">
                        <i class="fa fa-stop"></i> Detener
                    </x-button>
                </div>
            </div>
            <x-input-error for="archivoCorregido" class="mt-2" />

            {{-- Avance --}}
            <div class="mt-4" x-show="total > 0" x-cloak>
                <div class="flex justify-between text-sm mb-1">
                    <span x-text="procesando ? ('Procesando… ' + (actual ? 'último: ' + actual : '')) : (detener ? 'Detenido' : 'Terminado')"></span>
                    <span x-text="hechos + ' / ' + total + ' · ' + exitos + ' sin observaciones · ' + fallos + ' con observaciones'"></span>
                </div>
                <div class="w-full h-3 rounded-full bg-muted overflow-hidden">
                    <div class="h-3 bg-green-600 transition-all" x-bind:style="'width:' + (total ? (hechos / total * 100) : 0) + '%'"></div>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-flex class="justify-between mb-3">
                <p class="font-semibold">Índice del macro</p>
                <x-select wire:model.live="filtroEstado" class="w-auto">
                    <option value="">Todos</option>
                    <option value="exito">Éxito</option>
                    <option value="error">Error</option>
                    <option value="sin_producto">Sin producto</option>
                    <option value="verificado">Verificados</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="sin_movimientos">Sin movimientos</option>
                </x-select>
            </x-flex>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-border">
                    <thead class="bg-muted text-xs uppercase">
                        <tr>
                            <th class="p-2 border border-border">Fila</th>
                            <th class="p-2 border border-border">Código</th>
                            <th class="p-2 border border-border text-left">Insumo</th>
                            <th class="p-2 border border-border text-right">Entradas</th>
                            <th class="p-2 border border-border text-right">Salidas</th>
                            <th class="p-2 border border-border">Estado</th>
                            <th class="p-2 border border-border text-left">Resultado</th>
                            <th class="p-2 border border-border"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detalles as $d)
                            @php
                                $color = [
                                    'exito' => 'bg-green-50 dark:bg-green-950/40',
                                    'verificado' => 'bg-indigo-50 dark:bg-indigo-950/40',
                                    'error' => 'bg-red-50 dark:bg-red-950/40',
                                    'sin_producto' => 'bg-amber-50 dark:bg-amber-950/40',
                                ][$d->estado] ?? '';
                                $etiqueta = [
                                    'exito' => ['green', 'Éxito'], 'verificado' => ['indigo', 'Verificado'], 'error' => ['red', 'Error'], 'sin_producto' => ['yellow', 'Sin producto'],
                                    'pendiente' => ['blue', 'Pendiente'], 'sin_movimientos' => ['gray', 'Sin movimientos'],
                                ][$d->estado] ?? ['gray', $d->estado];
                            @endphp
                            <tr class="{{ $color }}" wire:key="det-{{ $d->id }}">
                                <td class="p-1.5 border border-border text-center">{{ $d->fila }}</td>
                                <td class="p-1.5 border border-border text-center font-semibold">{{ $d->codigo_existencia }}</td>
                                <td class="p-1.5 border border-border">
                                    {{ $d->nombre }}
                                    @if ($d->producto_nombre && \App\Support\FormatoHelper::normalizarNombre($d->producto_nombre) !== \App\Support\FormatoHelper::normalizarNombre($d->nombre))
                                        <span class="text-xs text-muted-foreground">({{ $d->producto_nombre }})</span>
                                    @endif
                                </td>
                                <td class="p-1.5 border border-border text-right whitespace-nowrap">
                                    {{ $d->entradas_cantidad !== null ? number_format($d->entradas_cantidad, 2) : '—' }}
                                    <span class="block text-xs text-muted-foreground">S/ {{ $d->entradas_importe !== null ? number_format($d->entradas_importe, 2) : '—' }}</span>
                                </td>
                                <td class="p-1.5 border border-border text-right whitespace-nowrap">
                                    {{ $d->salidas_cantidad !== null ? number_format($d->salidas_cantidad, 2) : '—' }}
                                    <span class="block text-xs text-muted-foreground">S/ {{ $d->salidas_importe !== null ? number_format($d->salidas_importe, 2) : '—' }}</span>
                                </td>
                                <td class="p-1.5 border border-border text-center"><x-badge :color="$etiqueta[0]">{{ $etiqueta[1] }}</x-badge></td>
                                <td class="p-1.5 border border-border text-xs">
                                    {{-- Observaciones del importador: una por línea, con **negritas** --}}
                                    <div class="whitespace-pre-line max-h-64 overflow-y-auto">{!! preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($d->mensaje)) !!}</div>
                                    @if ($d->procesado_at)
                                        <span class="block text-muted-foreground">
                                            {{ $d->procesado_at->format('d/m/Y H:i') }} · intento {{ $d->intentos }} · macro v{{ $d->version_archivo }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-1.5 border border-border text-center whitespace-nowrap">
                                    @if ($d->estado !== 'sin_movimientos')
                                        @if ($d->estado !== 'exito')
                                            <x-button size="xs" variant="ghost" x-bind:disabled="procesando"
                                                wire:click="verificar({{ $d->id }})" target="verificar({{ $d->id }})">
                                                Verificar
                                            </x-button>
                                        @endif
                                        <x-button size="xs" variant="outline" x-bind:disabled="procesando"
                                            wire:click="procesar({{ $d->id }})" target="procesar({{ $d->id }})">
                                            {{ $d->estado === 'exito' ? 'Regenerar' : 'Importar' }}
                                        </x-button>
                                    @endif
                                    @if ($d->kardex_id && $d->estado === 'exito')
                                        <x-button size="xs" variant="ghost" target="_blank"
                                            href="{{ route('almacen.kardex.detalle', ['insumoKardexId' => $d->kardex_id]) }}">
                                            <i class="fa fa-external-link-alt"></i>
                                        </x-button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-4 text-center text-muted-foreground">Sin filas con este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @elseif (!$mostrarNueva)
        <x-card><p class="text-center text-muted-foreground py-6">Aún no hay cargas. Pulsa <b>Nueva carga</b>.</p></x-card>
    @endif
</div>
@script
<script>
    Alpine.data('cargaKardex', () => ({
        procesando: false,
        detener: false,
        total: 0,
        hechos: 0,
        exitos: 0,
        fallos: 0,
        actual: '',
        confirmarTodos: false,
        // modo: verificar | importar_verificados | ejecutar_pendientes | ejecutar_todos
        async iniciar(modo) {
            const ids = await $wire.idsPara(modo);
            if (!ids.length) {
                this.total = 0;
                return;
            }
            const soloVerificar = modo === 'verificar';
            const ok = soloVerificar ? 'verificado' : 'exito';
            Object.assign(this, { procesando: true, detener: false, total: ids.length, hechos: 0, exitos: 0, fallos: 0, actual: '' });
            for (const id of ids) {
                if (this.detener) break;
                try {
                    const r = soloVerificar ? await $wire.verificar(id) : await $wire.procesar(id);
                    this.actual = r.nombre;
                    r.estado === ok ? this.exitos++ : this.fallos++;
                } catch (e) {
                    this.fallos++;
                }
                this.hechos++;
            }
            this.procesando = false;
        },
    }));
</script>
@endscript
