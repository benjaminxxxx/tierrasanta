<div class="space-y-4">
    @php
        $n = fn($v, $dec = 2) => $v === null ? '—' : number_format((float) $v, $dec);
    @endphp

    <x-card>
        <x-flex class="justify-between flex-wrap gap-3">
            <div class="space-y-1">
                <p class="font-semibold">Costos de producción</p>
                <p class="text-sm text-muted-foreground">
                    Se toman de la BDD de costos (resumen diario) y se guardan como una versión: si la BDD cambia, vuelve a
                    consultar y queda una versión nueva. Montos por hectárea en US$ = costo S/ ÷ tipo de cambio ÷ área.
                </p>
            </div>
            <x-flex class="items-end flex-wrap">
                @if ($versiones->count() > 1)
                    <x-select wire:model.live="versionId" label="Versión" class="w-auto">
                        @foreach ($versiones as $v)
                            <option value="{{ $v->id }}">
                                {{ $v->created_at->format('d/m/Y H:i') }} · {{ $v->generadoPor?->name ?? '—' }}
                                {{ $loop->first ? '(última)' : '' }}
                            </option>
                        @endforeach
                    </x-select>
                @endif
                <x-button wire:click="consultar" target="consultar">
                    <i class="fa fa-sync"></i> {{ $version ? 'Volver a consultar' : 'Consultar costos' }}
                </x-button>
            </x-flex>
        </x-flex>
    </x-card>

    @if (!$version)
        <x-card>
            <p class="text-center text-muted-foreground py-6">
                Aún no se han consultado los costos de esta campaña. Pulsa <b>Consultar costos</b>.
            </p>
        </x-card>
    @else
        @php
            $s = $reporte['secciones'];
            $t = $reporte['totales'];
            $verde = 'bg-green-600 text-white font-bold';
            $verdeClaro = 'bg-lime-500 text-black font-bold';
        @endphp

        <x-card>
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm mb-3">
                <span><b>Área:</b> {{ $n($version->area, 4) }} ha</span>
                <span><b>Tipo de cambio:</b> {{ $n($version->tipo_cambio, 4) }}</span>
                <span><b>Costos del</b> {{ formatear_fecha($version->fecha_desde) ?? '—' }} <b>al</b> {{ formatear_fecha($version->fecha_hasta) ?? '—' }}</span>
                <span class="text-muted-foreground">{{ number_format($version->filas_origen) }} registros · consultado {{ $version->created_at->format('d/m/Y H:i') }}</span>
            </div>

            @foreach ($reporte['avisos'] as $aviso)
                <x-warning class="mb-3">{{ $aviso }}</x-warning>
            @endforeach
            @if (!empty($s['mano_obra']['grupos'][\App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas::SIN_MANO_OBRA]))
                <x-warning class="mb-3">
                    Hay labores sin mano de obra asignada: aparecen en "Sin mano de obra asignada". Asígnalas en
                    <a class="underline" href="{{ route('campo.labores', ['mano_obra' => 'sin']) }}" target="_blank">Labores</a>
                    y vuelve a consultar.
                </x-warning>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-border">
                    <thead>
                        <tr class="{{ $verde }}">
                            <th class="p-2 border border-border w-16"></th>
                            <th class="p-2 border border-border text-center">ÍTEM</th>
                            <th class="p-2 border border-border text-right w-36">EJEC. CANT HA</th>
                            <th class="p-2 border border-border text-right w-40">EJEC. COSTO US$ HA</th>
                            <th class="p-2 border border-border text-right w-40">COSTO S/</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- ================= COSTOS DE PRODUCCIÓN ================= --}}
                        <tr class="{{ $verdeClaro }}">
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border">COSTOS DE PRODUCCIÓN</td>
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['produccion']['usd_ha']) }}</td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['produccion']['soles']) }}</td>
                        </tr>
                        @foreach (['mano_obra', 'maquinaria', 'fertilizante', 'pesticida'] as $clave)
                            @include('livewire.campania.partials.costo-produccion-seccion', ['seccion' => $s[$clave], 'conGrupos' => $clave === 'mano_obra'])
                        @endforeach

                        {{-- ================= COSTO OPERATIVO ================= --}}
                        <tr class="{{ $verdeClaro }}">
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border">COSTO OPERATIVO</td>
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['operativo']['usd_ha']) }}</td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['operativo']['soles']) }}</td>
                        </tr>
                        @foreach (['costo_operativo', 'otros'] as $clave)
                            @include('livewire.campania.partials.costo-produccion-items', ['seccion' => $s[$clave], 'prefijo' => ''])
                        @endforeach

                        {{-- ================= COSTO FIJO ================= --}}
                        <tr class="{{ $verdeClaro }}">
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border">COSTO FIJO</td>
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['fijo']['usd_ha']) }}</td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['fijo']['soles']) }}</td>
                        </tr>
                        @include('livewire.campania.partials.costo-produccion-items', ['seccion' => $s['costo_fijo'], 'prefijo' => '3.'])

                        {{-- ================= TOTALES ================= --}}
                        <tr class="{{ $verdeClaro }}">
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border">TOTAL CAMPAÑA US$</td>
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['total']['usd_ha']) }}</td>
                            <td class="p-1.5 border border-border text-right"></td>
                        </tr>
                        <tr class="{{ $verdeClaro }}">
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border">TOTAL CAMPAÑA S/</td>
                            <td class="p-1.5 border border-border"></td>
                            <td class="p-1.5 border border-border text-right">
                                {{ $t['total']['usd_ha'] !== null && $version->tipo_cambio ? $n($t['total']['usd_ha'] * $version->tipo_cambio) : '—' }}
                            </td>
                            <td class="p-1.5 border border-border text-right">{{ $n($t['total']['soles']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-muted-foreground mt-2">
                "TOTAL CAMPAÑA S/" en la columna por ha = total US$/ha × tipo de cambio. La columna COSTO S/ es el total de la
                campaña (no por ha), para cuadrar con la BDD de costos.
            </p>
        </x-card>
    @endif
</div>
