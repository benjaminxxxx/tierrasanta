<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <x-title>Costos por campaña</x-title>
            <x-subtitle>
                Lo que registra la BDD de costos para la campaña, mes a mes: mano de obra, maquinaria, insumos y la parte que le toca de
                los costos operativos (mano de obra indirecta / FDM, servicios fundo) y fijos que se distribuyen cada mes.
            </x-subtitle>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-select-campo label="Campo" wire:model.live="campoSeleccionado" class="w-auto" />
            <x-select wire:model.live="campaniaSeleccionada" label="Campaña" class="w-auto">
                <option value="">Seleccione una campaña</option>
                @foreach ($campanias as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre_campania }} ({{ $c->fecha_inicio?->format('d/m/Y') }}{{ $c->fecha_fin ? ' – ' . $c->fecha_fin->format('d/m/Y') : ', abierta' }})</option>
                @endforeach
            </x-select>
        </div>
    </div>

    @if (!$campania || !$datos)
        <x-card><p class="text-sm text-muted-foreground">Seleccione un campo y una campaña.</p></x-card>
    @else
        @can(\App\Constants\Permisos::CAMPAÑA_COSTOS_VER)
            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex flex-wrap gap-6">
                        <div><p class="text-xs text-muted-foreground uppercase">Campaña</p><p class="font-semibold">{{ $campania->nombre_campania }} · campo {{ $campania->campo }}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">Periodo</p><p class="font-semibold">{{ formatear_fecha($datos['inicio']) }} – {{ formatear_fecha($datos['fin']) }}{{ $datos['cerrada'] ? '' : ' (abierta)' }}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">Área</p><p class="font-semibold">{{ $datos['area'] ? number_format($datos['area'], 2) . ' ha' : '—' }}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">Costo total</p><p class="text-xl font-bold text-primary">S/ {{ number_format($datos['total'], 2) }}</p></div>
                        <div><p class="text-xs text-muted-foreground uppercase">Por hectárea</p><p class="text-xl font-bold">{{ $datos['por_ha'] !== null ? 'S/ ' . number_format($datos['por_ha'], 2) : '—' }}</p></div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-button wire:click="descargarBdd" title="Excel con todos los registros de la BDD de esta campaña">
                            <i class="fa fa-file-excel"></i> Descargar BDD
                        </x-button>
                        @if ($puedeRegenerar)
                            <x-button variant="secondary" wire:click="descargarBdd(true)"
                                wire:confirm="Se vuelven a leer planilla, cuadrilla, riego, insumos, servicios, maquinaria y gastos generales de este campo en el rango de la campaña y se reemplaza su BDD. ¿Continuar?"
                                title="Vuelve a leer las fuentes solo de este campo en el rango de la campaña, actualiza la BDD y descarga el Excel">
                                <i class="fa fa-rotate"></i> Regenerar y descargar
                            </x-button>
                        @endif
                        <x-button variant="secondary" href="{{ route('campania.por_campo', ['campania' => $campania->id]) }}">
                            <i class="fa fa-folder-open"></i> Detalle de la campaña
                        </x-button>
                    </div>
                </div>
            </x-card>

            @if ($datos['meses_con_avisos'])
                <x-warning>
                    {{ $datos['meses_con_avisos'] }} mes(es) con el costo incompleto: falta distribuir los costos fijos/operativos del mes
                    (Costos Mensuales → Distribuir) o calcular su mano de obra indirecta. Mientras tanto la campaña solo tiene sus costos directos.
                </x-warning>
            @endif

            <x-card>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-muted-foreground">
                            <tr class="border-b border-border">
                                <th class="text-left py-2">Mes</th>
                                @foreach ($datos['secciones'] as $nombre)
                                    <th class="text-right px-2">{{ ucfirst(mb_strtolower($nombre)) }}</th>
                                @endforeach
                                <th class="text-right px-2">Total</th>
                                <th class="text-left pl-3">Estado del mes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datos['meses'] as $m)
                                <tr class="border-b border-border {{ $m['fuera_de_rango'] ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                    <td class="py-1.5 whitespace-nowrap">
                                        {{ $m['nombre'] }}
                                        @if ($m['fuera_de_rango'])
                                            <span class="block text-xs text-amber-700 dark:text-amber-400">fuera del rango de la campaña</span>
                                        @endif
                                    </td>
                                    @foreach ($datos['secciones'] as $clave => $nombre)
                                        <td class="text-right px-2 {{ $m['montos'][$clave] ? '' : 'text-muted-foreground' }}">
                                            {{ $m['montos'][$clave] ? number_format($m['montos'][$clave], 2) : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="text-right px-2 font-semibold">{{ number_format($m['total'], 2) }}</td>
                                    <td class="pl-3 text-xs">
                                        @if ($m['en_curso'])
                                            <span class="text-muted-foreground">En curso</span>
                                        @elseif (!$m['avisos'])
                                            <span class="text-green-700 dark:text-green-400"><i class="fa fa-check"></i> Completo</span>
                                        @endif
                                        @foreach ($m['avisos'] as $aviso)
                                            <a class="block text-red-600 dark:text-red-400 hover:underline"
                                                href="{{ str_contains($aviso, 'indirecta') ? route('costos.mano_obra_indirecta', ['anio' => $m['anio'], 'mes' => $m['mes']]) : route('costos.mensuales') }}">
                                                <i class="fa fa-triangle-exclamation"></i> {{ $aviso }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-bold">
                                <td class="py-2">Total</td>
                                @foreach ($datos['secciones'] as $clave => $nombre)
                                    <td class="text-right px-2">{{ number_format($datos['totales'][$clave], 2) }}</td>
                                @endforeach
                                <td class="text-right px-2">{{ number_format($datos['total'], 2) }}</td>
                                <td></td>
                            </tr>
                            @if ($datos['total'] > 0)
                                <tr class="text-xs text-muted-foreground">
                                    <td>% del total</td>
                                    @foreach ($datos['secciones'] as $clave => $nombre)
                                        <td class="text-right px-2">{{ number_format($datos['totales'][$clave] / $datos['total'] * 100, 1) }} %</td>
                                    @endforeach
                                    <td colspan="2"></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-card>
        @else
            <x-danger>No tiene permiso para ver los costos de la campaña.</x-danger>
        @endcan
    @endif

    <x-loading wire:loading wire:target="descargarBdd,campoSeleccionado,campaniaSeleccionada" />
</div>
