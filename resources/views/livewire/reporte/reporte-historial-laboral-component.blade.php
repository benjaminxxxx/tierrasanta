<div class="space-y-4">
    <div>
        <x-title>Historial laboral</x-title>
        <x-subtitle>
            Qué hizo un trabajador día a día: campos, labores, horarios, con cuántos compañeros y su producción contra el estándar.
            El mismo trabajador puede estar en cuadrilla con otro nombre (trabajo extra en feriados o en la tarde): búscalo por partes
            del nombre y elige todas sus identidades.
        </x-subtitle>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        {{-- Búsqueda y selección --}}
        <x-card class="xl:col-span-1 space-y-3">
            <x-input type="search" wire:model.live.debounce.400ms="texto" label="Buscar trabajador" placeholder="Nombre, apellido o DNI (ej. juan quispe)" />
            <x-input type="checkbox" wire:model.live="incluirCuadrilla" label="Incluir cuadrilleros" />

            @if (mb_strlen(trim($texto)) >= 2)
                <div class="max-h-72 overflow-y-auto divide-y divide-border border border-border rounded-md">
                    @forelse ($resultados as $r)
                        @php $elegido = in_array($r['clave'], $seleccionados, true); @endphp
                        <button type="button" wire:key="res-{{ $r['clave'] }}" @if (!$elegido) wire:click="agregar('{{ $r['clave'] }}')" @endif
                            class="w-full text-left px-3 py-2 text-sm {{ $elegido ? 'opacity-50 cursor-default' : 'hover:bg-muted' }}">
                            <span class="font-medium">{{ $r['nombre'] }}</span>
                            <span class="block text-xs text-muted-foreground">
                                {{ $r['detalle'] }}{{ $r['documento'] ? ' · DNI ' . $r['documento'] : '' }}{{ $elegido ? ' · ya elegido' : '' }}
                            </span>
                        </button>
                    @empty
                        <p class="px-3 py-2 text-sm text-muted-foreground">Sin resultados{{ $incluirCuadrilla ? '' : ' en planilla (marca "Incluir cuadrilleros")' }}.</p>
                    @endforelse
                </div>
            @endif

            @if ($elegidos)
                <div>
                    <p class="text-xs font-semibold uppercase text-muted-foreground mb-1">Historial de</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($elegidos as $e)
                            <span wire:key="sel-{{ $e['clave'] }}" class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs {{ $e['tipo'] === 'planilla' ? 'border-blue-300 bg-blue-50 dark:bg-blue-900/30' : 'border-amber-300 bg-amber-50 dark:bg-amber-900/30' }}">
                                <i class="fa {{ $e['tipo'] === 'planilla' ? 'fa-id-card' : 'fa-users' }}"></i> {{ $e['nombre'] }}
                                <button type="button" wire:click="quitar('{{ $e['clave'] }}')" class="ml-1 hover:text-red-600" title="Quitar"><i class="fa fa-times"></i></button>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($sugerencias)
                <div class="rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-2">
                    <p class="text-xs font-semibold mb-1">¿También trabajó en cuadrilla como…?</p>
                    @foreach ($sugerencias as $s)
                        <button type="button" wire:key="sug-{{ $s['clave'] }}" wire:click="agregar('{{ $s['clave'] }}')"
                            class="w-full text-left px-2 py-1 text-sm rounded hover:bg-amber-100 dark:hover:bg-amber-900/40">
                            <i class="fa fa-plus text-xs"></i> {{ $s['nombre'] }}
                            <span class="block text-xs text-muted-foreground pl-4">{{ $s['detalle'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </x-card>

        {{-- Periodo y resumen --}}
        <div class="xl:col-span-2 space-y-4">
            <x-card class="flex flex-wrap items-end gap-3">
                <x-select wire:model.live="modo" label="Periodo" class="w-auto">
                    <option value="dia">Un día</option>
                    <option value="mes">Un mes</option>
                    <option value="anio">Un año</option>
                    <option value="rango">Rango de fechas</option>
                </x-select>
                @if ($modo === 'dia')
                    <x-input type="date" wire:model.live="dia" label="Día" />
                @elseif ($modo === 'mes')
                    <x-select wire:model.live="mes" label="Mes" class="w-auto">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->translatedFormat('F')) }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model.live="anio" label="Año" class="w-auto">
                        @foreach (range(now()->year, 2016) as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </x-select>
                @elseif ($modo === 'anio')
                    <x-select wire:model.live="anio" label="Año" class="w-auto">
                        @foreach (range(now()->year, 2016) as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </x-select>
                @else
                    <x-input type="date" wire:model.live="desde" label="Desde" />
                    <x-input type="date" wire:model.live="hasta" label="Hasta" />
                @endif
                <p class="text-xs text-muted-foreground self-center">{{ formatear_fecha($desde) }}{{ $desde !== $hasta ? ' – ' . formatear_fecha($hasta) : '' }}</p>
                @if ($historial)
                    <x-button class="ml-auto" wire:click="descargar"><i class="fa fa-file-excel"></i> Descargar Excel</x-button>
                @endif
            </x-card>

            @if (!$historial)
                <x-card><p class="text-sm text-muted-foreground">Busca y elige al trabajador para ver su historial.</p></x-card>
            @else
                @php $r = $historial['resumen']; $p = $r['produccion']; @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <x-card><p class="text-xs text-muted-foreground">Planilla</p><p class="text-xl font-bold">{{ $r['dias_planilla'] }} días</p><p class="text-xs">{{ number_format($r['horas_planilla'], 1) }} h · S/ {{ number_format($r['costo_planilla'], 2) }}</p></x-card>
                    <x-card><p class="text-xs text-muted-foreground">Cuadrilla</p><p class="text-xl font-bold">{{ $r['dias_cuadrilla'] }} días</p><p class="text-xs">{{ number_format($r['horas_cuadrilla'], 1) }} h · S/ {{ number_format($r['costo_cuadrilla'], 2) }}</p></x-card>
                    <x-card><p class="text-xs text-muted-foreground">Planilla y cuadrilla el mismo día</p><p class="text-xl font-bold {{ $r['dias_doble'] ? 'text-amber-600' : '' }}">{{ $r['dias_doble'] }}</p><p class="text-xs">posible trabajo extra</p></x-card>
                    <x-card>
                        <p class="text-xs text-muted-foreground">Producción vs estándar</p>
                        @if ($p['evaluadas'])
                            <p class="text-xl font-bold {{ $p['promedio_pct'] >= 100 ? 'text-green-600' : 'text-red-600' }}">{{ $p['promedio_pct'] }} %</p>
                            <p class="text-xs">{{ $p['sobre'] }} sobre · {{ $p['debajo'] }} debajo (de {{ $p['evaluadas'] }})</p>
                        @else
                            <p class="text-sm">Sin producción registrada</p>
                        @endif
                    </x-card>
                </div>

                @if ($r['ausencias'])
                    <p class="text-sm text-muted-foreground">Planilla sin trabajo: {{ collect($r['ausencias'])->map(fn($n, $t) => "{$n} día(s) de {$t}")->implode(', ') }}.</p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-card>
                        <p class="text-sm font-semibold mb-2">Horas por labor</p>
                        @foreach (array_slice($r['por_labor'], 0, 10) as $l)
                            <div class="flex justify-between text-sm border-t border-border py-0.5"><span>{{ $l['labor'] }}</span><span>{{ number_format($l['horas'], 1) }} h</span></div>
                        @endforeach
                    </x-card>
                    <x-card>
                        <p class="text-sm font-semibold mb-2">Producción por labor</p>
                        @forelse ($p['por_labor'] as $l)
                            <div class="text-sm border-t border-border py-0.5">
                                <div class="flex justify-between"><span>{{ $l['labor'] }}</span><span class="{{ $l['promedio_pct'] >= 100 ? 'text-green-600' : 'text-red-600' }} font-semibold">{{ $l['promedio_pct'] }} %</span></div>
                                <p class="text-xs text-muted-foreground">promedio {{ $l['produccion_promedio'] }} {{ $l['unidades'] }} · estándar {{ $l['estandar'] }} · {{ $l['sobre'] }} sobre / {{ $l['debajo'] }} debajo de {{ $l['veces'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">Las labores del periodo no tienen producción registrada.</p>
                        @endforelse
                    </x-card>
                </div>

                <x-card>
                    <p class="text-sm font-semibold mb-2">Día a día</p>
                    <div class="space-y-2">
                        @forelse ($historial['dias'] as $d)
                            <div wire:key="dia-{{ $d['fecha'] }}" class="border-l-4 pl-3 py-1 {{ $d['doble'] ? 'border-amber-400' : 'border-border' }}">
                                <p class="text-xs font-semibold text-muted-foreground">
                                    {{ ucfirst(\Illuminate\Support\Carbon::parse($d['fecha'])->translatedFormat('l d/m/Y')) }}
                                    @if ($d['doble'])
                                        <span class="text-amber-600">· planilla y cuadrilla el mismo día</span>
                                    @endif
                                </p>
                                @foreach ($d['entradas'] as $e)
                                    <p class="text-sm">
                                        {{ $e['texto'] }}
                                        @if ($e['costo'] !== null)
                                            <span class="text-xs text-muted-foreground">(S/ {{ number_format($e['costo'], 2) }})</span>
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">No hay registros de estas identidades en el periodo.</p>
                        @endforelse
                    </div>
                </x-card>
            @endif
        </div>
    </div>

    <x-loading wire:loading wire:target="agregar,quitar,modo,mes,anio,dia,desde,hasta,descargar,incluirCuadrilla" />
</div>
