<div class="space-y-4">
    @php
        $fmt = fn($v) => number_format((float) $v, 2);
        $puede = $this->puedeGestionar;
    @endphp

    <x-flex class="justify-between flex-wrap gap-3">
        <div>
            <x-title>Caja de oficina — {{ ucfirst($nombreMes) }}</x-title>
            <x-subtitle>El dinero que pasa físicamente por la oficina. Lo que no entra realmente se registra con su inverso (efecto cero) y el detalle se lleva en la caja de movimientos.</x-subtitle>
        </div>
        <x-flex class="flex-wrap gap-2">
            <x-button variant="secondary" wire:click="$set('modalEnvios', true)"><i class="fa fa-history"></i> Envíos</x-button>
            @if ($puede)
                <x-button variant="secondary" wire:click="$set('modalImportar', true)"><i class="fa fa-upload"></i> Importar Excel</x-button>
                <x-button wire:click="$set('modalEnviar', true)" :disabled="!$porEnviar">
                    <i class="fa fa-paper-plane"></i> Enviar a caja de movimientos ({{ $porEnviar }})
                </x-button>
            @endif
        </x-flex>
    </x-flex>

    @if (count($enviosPendientes))
        <x-warning>
            {{ count($enviosPendientes) }} envío(s) con {{ collect($enviosPendientes)->sum('cambios') }} cambio(s) aún no se anexan en la caja de movimientos.
            <button type="button" class="underline font-semibold" wire:click="$set('modalEnvios', true)">Ver detalles</button>
        </x-warning>
    @endif

    <x-card>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <x-select label="Año" wire:model.live="anio">
                @foreach ($anios as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </x-select>
            <x-select label="Mes" wire:model.live="mes">
                <option value="">Todo el año</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">{{ ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->locale('es')->translatedFormat('F')) }}</option>
                @endforeach
            </x-select>
            <x-select label="Mostrar" wire:model.live="estado">
                <option value="">Todas las filas</option>
                <option value="pendiente">Sin enviar</option>
                <option value="cero">Con efecto cero (inversos)</option>
            </x-select>
            <div class="col-span-2">
                <x-input type="search" label="Buscar" wire:model.live.debounce.400ms="buscar" placeholder="Beneficiario, detalle, N° doc…" />
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Saldo anterior</span>
            <span class="text-xl font-semibold tabular-nums">S/ {{ $fmt($datos['saldo_anterior']) }}</span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Ingresos</span>
            <span class="text-xl font-semibold tabular-nums text-green-700 dark:text-green-400">S/ {{ $fmt($datos['totales']['ingresos']) }}</span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Egresos</span>
            <span class="text-xl font-semibold tabular-nums text-red-700 dark:text-red-400">S/ {{ $fmt($datos['totales']['egresos']) }}</span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Disponible en oficina {{ $cerrado ? '· mes cerrado' : '' }}</span>
            <span class="text-xl font-bold tabular-nums">S/ {{ $fmt($datos['saldo_final']) }}</span>
        </x-card>
    </div>

    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <span class="text-sm text-muted-foreground">
                {{ number_format(count($datos['filas'])) }} de {{ number_format($datos['totales']['movimientos']) }} movimiento(s).
                @if ($cerrado)
                    El mes está cerrado: solo lectura.
                @elseif ($puede && !$mes)
                    Elige un mes para modificar movimientos.
                @endif
            </span>
            @if ($puede && $mesEditable)
                <x-button wire:click="nuevo"><i class="fa fa-plus"></i> Nuevo movimiento</x-button>
            @endif
        </div>
        <div class="overflow-auto" style="max-height: calc(100vh - 260px)">
            <table class="w-full text-xs">
                <thead class="bg-muted text-muted-foreground sticky top-0">
                    <tr>
                        <th class="p-2 text-left">N° Caja</th>
                        <th class="p-2 text-left">Cond.</th>
                        <th class="p-2 text-left">Fecha</th>
                        <th class="p-2 text-left">Beneficiario</th>
                        <th class="p-2 text-left">Gastos B+N</th>
                        <th class="p-2 text-left">Categoría</th>
                        <th class="p-2 text-left">N° Doc</th>
                        <th class="p-2 text-right">Importe S/</th>
                        <th class="p-2 text-right">Disponible</th>
                        <th class="p-2 text-left">Estado</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($datos['filas'] as $f)
                        <tr class="border-t border-border {{ $f['es_inverso'] || $f['tiene_inverso'] ? 'bg-purple-50 dark:bg-purple-900/20' : '' }}" wire:key="of-{{ $f['id'] }}">
                            <td class="p-2 whitespace-nowrap">{{ $f['numero_caja'] }}</td>
                            <td class="p-2 whitespace-nowrap">{{ $f['condicion'] }}</td>
                            <td class="p-2 whitespace-nowrap tabular-nums">{{ $f['fecha'] }}</td>
                            <td class="p-2">{{ $f['beneficiario'] }}</td>
                            <td class="p-2">
                                {{ $f['descripcion'] }}
                                @if ($f['es_inverso'])
                                    <span class="px-1.5 py-0.5 rounded font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Inverso</span>
                                @elseif ($f['tiene_inverso'])
                                    <span class="px-1.5 py-0.5 rounded font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">No entra a caja</span>
                                @endif
                            </td>
                            <td class="p-2 whitespace-nowrap">{{ $f['categoria'] }}</td>
                            <td class="p-2">{{ $f['numero_documento'] }}</td>
                            <td class="p-2 text-right whitespace-nowrap tabular-nums {{ $f['importe'] < 0 ? 'text-red-700 dark:text-red-400' : '' }}"
                                @if ($f['importe_detalle']) title="Operación: {{ $f['importe_detalle'] }}" @endif>{{ $fmt($f['importe']) }}</td>
                            <td class="p-2 text-right whitespace-nowrap tabular-nums">{{ $fmt($f['disponible']) }}</td>
                            <td class="p-2 whitespace-nowrap">
                                @if ($f['pendiente_envio'])
                                    <span class="text-amber-700 dark:text-amber-400">{{ $f['enviado'] ? 'Modificada, sin enviar' : 'Sin enviar' }}</span>
                                @else
                                    <span class="text-muted-foreground">Enviada</span>
                                @endif
                            </td>
                            <td class="p-2 text-right whitespace-nowrap">
                                @if ($puede && $mesEditable)
                                    @if (!$f['es_inverso'] && !$f['tiene_inverso'] && !$f['es_saldo_inicial'])
                                        <button type="button" class="text-purple-700 dark:text-purple-300 hover:underline" title="El dinero no entra realmente a caja: generar su inverso"
                                            wire:click="generarInverso({{ $f['id'] }})"
                                            wire:confirm="¿El dinero no entra realmente a la caja de oficina? Se generará la fila inversa (efecto cero)."><i class="fa fa-right-left"></i> Inverso</button>
                                    @endif
                                    @if (!$f['es_inverso'])
                                        <button type="button" class="text-blue-600 hover:underline ml-2" title="Editar" wire:click="editar({{ $f['id'] }})"><i class="fa fa-edit"></i></button>
                                    @endif
                                    <button type="button" class="text-red-600 hover:underline ml-2" title="Eliminar" wire:click="confirmarEliminar({{ $f['id'] }})"><i class="fa fa-trash"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="p-6 text-center text-muted-foreground">Sin movimientos en este periodo. Importa el Excel de caja para empezar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($cuadre && $cuadre['iniciada'])
        @include('livewire.caja.partials.cuadre', ['cuadre' => $cuadre, 'nombreMes' => $nombreMes])
    @endif

    {{-- Modal: movimiento --}}
    <x-dialog-modal wire:model="modalForm" maxWidth="full">
        <x-slot name="title">{{ $movimientoId ? 'Editar movimiento de caja de oficina' : 'Nuevo movimiento de caja de oficina' }}</x-slot>
        <x-slot name="content">
            <form wire:submit="guardar" id="frmCajaOficina" class="space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                    <div class="col-span-2">
                        <x-label>Tipo</x-label>
                        <div class="flex rounded-md border border-input overflow-hidden h-9">
                            <button type="button" wire:click="$set('tipo', 'INGRESO')"
                                class="flex-1 text-sm font-semibold {{ $tipo === 'INGRESO' ? 'bg-green-600 text-white' : 'bg-background text-foreground' }}">Ingreso</button>
                            <button type="button" wire:click="$set('tipo', 'EGRESO')"
                                class="flex-1 text-sm font-semibold {{ $tipo === 'EGRESO' ? 'bg-red-600 text-white' : 'bg-background text-foreground' }}">Egreso</button>
                        </div>
                    </div>
                    <x-input type="date" label="Fecha" wire:model.live="fecha" error="fecha" />
                    <x-select label="Semana" wire:model="semana" error="semana">
                        @foreach (range(1, 5) as $s)
                            <option value="{{ $s }}">SEM-{{ $s }}</option>
                        @endforeach
                    </x-select>
                    <x-input type="number" label="N° de caja (opcional)" wire:model="numero_caja" error="numero_caja" :disabled="$es_contable" />
                    <x-select label="Condición" wire:model="condicion" error="condicion" :disabled="$es_contable">
                        <option value="NEG">NEG.</option>
                        <option value="BLA">BLA.</option>
                    </x-select>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="es_contable" class="rounded">
                    Viene de la caja contable (sin N° de caja; siempre BLA.)
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <x-input label="Beneficiario" wire:model="beneficiario" error="beneficiario" />
                    <div class="md:col-span-2">
                        <x-input label="Gastos B+N (descripción)" wire:model="descripcion" error="descripcion" />
                    </div>
                    <x-input label="Categoría" wire:model="categoria" placeholder="F/E001-208, Contable…" />
                    <x-input label="Código" wire:model="codigo" />
                    <x-input label="T. Doc (o cualquier observación)" wire:model="tipo_documento" />
                    <x-input label="N° Doc (recibo, rendición, concepto…)" wire:model="numero_documento" />
                    <x-input label="Situación cheque (OP, depósito…)" wire:model="situacion_cheque" />
                </div>
                <div class="rounded-lg border border-border p-3 space-y-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="pagado_en_dolares" class="rounded"> Se pagó en dólares
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        @if ($pagado_en_dolares)
                            <x-input type="number" step="0.01" label="Importe US$" wire:model.live.debounce.400ms="importe_usd" error="importe_usd" />
                            <x-input type="number" step="0.0001" label="TC del pago" wire:model.live.debounce.400ms="tipo_cambio_operacion" error="tipo_cambio_operacion" />
                        @else
                            <div class="md:col-span-2">
                                <x-input label="Importe S/ (número u operación: 100-15+3.5)" wire:model.live.debounce.400ms="importe_texto" error="importe_texto" />
                            </div>
                        @endif
                        <div>
                            <span class="text-xs text-muted-foreground block">Se registrará</span>
                            <span class="text-lg font-bold tabular-nums {{ $tipo === 'EGRESO' ? 'text-red-600' : 'text-green-600' }}">
                                @if ($this->importeCalculado !== null)
                                    {{ $tipo === 'EGRESO' ? '−' : '+' }} S/ {{ number_format($this->importeCalculado, 2) }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <x-input type="number" step="0.0001" label="TC del día (dolarizado)" wire:model="tipo_cambio" />
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">Los clasificadores y sub-grupos se ponen en la caja de movimientos. Si el dinero no entra realmente a la oficina, guarda la fila y usa "Inverso" en la tabla.</p>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalForm', false)">Cancelar</x-button>
            <x-button type="submit" form="frmCajaOficina"><i class="fa fa-save"></i> Guardar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: eliminar --}}
    <x-dialog-modal wire:model="modalEliminar" maxWidth="md">
        <x-slot name="title">Eliminar movimiento</x-slot>
        <x-slot name="content">
            <p class="text-sm">{{ $eliminarResumen }}</p>
            <div class="mt-3">
                <x-textarea label="Motivo de la eliminación" wire:model="motivoEliminacion" error="motivo" rows="2" />
            </div>
            <p class="text-xs text-muted-foreground mt-2">Queda en el historial de caja. Si la fila ya se envió, la eliminación viaja en el siguiente envío.</p>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalEliminar', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="eliminar"><i class="fa fa-trash"></i> Eliminar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: enviar --}}
    <x-dialog-modal wire:model="modalEnviar" maxWidth="md">
        <x-slot name="title">Enviar a la caja de movimientos</x-slot>
        <x-slot name="content">
            <p class="text-sm">Se enviarán <b>{{ $porEnviar }}</b> fila(s) nuevas, modificadas o eliminadas desde el último envío. En la caja de movimientos verán el aviso y las anexarán; mientras tanto los envíos se acumulan.</p>
            <div class="mt-3">
                <x-textarea label="Nota (opcional)" wire:model="notaEnvio" rows="2" placeholder="Ej.: caja de la semana 2" />
            </div>
            @error('envio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalEnviar', false)">Cancelar</x-button>
            <x-button wire:click="enviar"><i class="fa fa-paper-plane"></i> Enviar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: envíos --}}
    <x-dialog-modal wire:model="modalEnvios" maxWidth="full">
        <x-slot name="title">Envíos a la caja de movimientos</x-slot>
        <x-slot name="content">
            @include('livewire.caja.partials.envios', ['envios' => $enviosRecientes, 'puedeAnexar' => false])
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalEnvios', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: importar --}}
    <x-dialog-modal wire:model="modalImportar" maxWidth="lg">
        <x-slot name="title">Importar Excel de caja a la caja de oficina</x-slot>
        <x-slot name="content">
            <p class="text-sm mb-3">
                Lee la hoja <b>BASE</b> del mismo Excel de caja, sin clasificadores, sub-grupos ni saldos por fuente. Solo agrega las filas
                que aún no están: se puede subir el mismo Excel las veces que sea. Los meses cerrados no se tocan.
            </p>
            <input type="file" wire:model="archivoImportar" accept=".xlsx,.xlsm" class="text-sm">
            @error('archivoImportar') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            @error('archivo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-muted-foreground mt-3">Las filas que ya están en la caja de movimientos quedan vinculadas y no se envían de nuevo; las demás quedan por enviar.</p>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalImportar', false)">Cancelar</x-button>
            <x-button wire:click="importar"><i class="fa fa-upload"></i> Importar</x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="anio,mes,estado,buscar,importar,enviar,eliminar,generarInverso,guardar" />
</div>
