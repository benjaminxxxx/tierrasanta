<div class="space-y-4">
    <div>
        <x-title>Cochinilla por recuperar</x-title>
        <x-subtitle>
            Cada envío de mamá: un campo se cosechó y su mamá se puso en otros campos; lo que vuelve como infestadores se registra
            con el campo que dio la mamá y es de su campaña. La mano de obra de infestar y recoger es de los campos infestados.
            Lo esperado en seco es la mamá fresca ÷ el factor configurado. Aviso después de {{ $diasAlerta }} días sin darlo por
            recuperado (los dos valores en Sistema → Configuración).
        </x-subtitle>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach (['por_recuperar' => ['Por recuperar', 'ring-primary'], 'alerta' => ["Con más de {$diasAlerta} días", 'ring-red-500'], 'recuperadas' => ['Recuperadas', 'ring-green-500']] as $clave => [$texto, $anillo])
            <button type="button" wire:click="$set('estado', '{{ $clave }}')"
                class="text-left rounded-lg border p-4 bg-card hover:bg-muted {{ $estado === $clave ? 'ring-2 ' . $anillo : '' }}">
                <p class="text-sm text-muted-foreground">{{ $texto }}</p>
                <p class="text-3xl font-bold {{ $clave === 'alerta' && $contadores['alerta'] ? 'text-red-600 dark:text-red-400' : 'text-foreground' }}">{{ $contadores[$clave] }}</p>
            </button>
        @endforeach
    </div>

    <x-card class="flex flex-wrap items-end gap-3">
        <div class="w-48"><x-input type="search" wire:model.live.debounce.400ms="campo" label="Campo" placeholder="Que dio o recibió mamá, ej. A5" /></div>
        <x-select wire:model.live="estado" label="Mostrar" class="w-auto">
            <option value="por_recuperar">Por recuperar</option>
            <option value="alerta">Con alerta</option>
            <option value="recuperadas">Recuperadas</option>
            <option value="todas">Todas (último año)</option>
        </x-select>
    </x-card>

    @forelse ($tandas as $t)
        <x-card wire:key="tanda-{{ $t['clave'] }}" class="{{ $t['alerta'] ? 'border-l-4 border-l-red-500' : ($t['cerrada'] ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-amber-400') }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold">{{ $t['campo'] }}</span>
                        <span class="text-sm text-muted-foreground">{{ $t['campania'] }}</span>
                        @if ($t['cerrada'])
                            <x-badge color="green">Recuperada</x-badge>
                        @elseif ($t['alerta'])
                            <x-badge color="red">{{ $t['dias'] }} días · revisar</x-badge>
                        @else
                            <x-badge color="yellow">{{ $t['dias'] }} días</x-badge>
                        @endif
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Mamá enviada del {{ formatear_fecha($t['inicio']) }}{{ $t['inicio'] !== $t['fin'] ? ' al ' . formatear_fecha($t['fin']) : '' }}
                        · {{ number_format($t['kg_madres'], 2) }} kg a {{ implode(', ', array_keys($t['destinos'])) }} · {{ number_format($t['infestadores']) }} infestadores
                    </p>
                    @if ($t['cerrada'])
                        <p class="text-xs text-green-700 dark:text-green-400">Dada por recuperada por {{ $t['cerrada']['por'] ?? '—' }} el {{ $t['cerrada']['en']?->format('d/m/Y H:i') }}{{ $t['cerrada']['nota'] ? ' · ' . $t['cerrada']['nota'] : '' }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs text-muted-foreground">Recuperado</p>
                    <p class="text-xl font-bold">{{ number_format($t['kg_recuperados'], 2) }} kg</p>
                    <p class="text-xs text-muted-foreground" title="Mamá fresca {{ number_format($t['kg_madres'], 2) }} kg ÷ factor {{ $t['factor_seco'] }}">
                        esperado ~{{ number_format($t['kg_esperado_seco'], 2) }} kg seco{{ $t['porcentaje_recuperado'] !== null ? ' · ' . $t['porcentaje_recuperado'] . ' %' : '' }}
                    </p>
                    @if ($puedeGestionar)
                        <div class="mt-1">
                            @if ($t['cerrada'])
                                <x-button size="sm" variant="secondary" wire:click="reabrir({{ \Illuminate\Support\Js::from($t['clave']) }})">Reabrir</x-button>
                            @else
                                <x-button size="sm" variant="success" wire:click="pedirCerrar({{ \Illuminate\Support\Js::from($t['clave']) }})"><i class="fa fa-check"></i> Dar por recuperado</x-button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-muted-foreground mb-1">Campos infestados con esta mamá</p>
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-muted-foreground"><th class="text-left">Campo</th><th class="text-right">Kg mamá (fresco)</th><th class="text-right">%</th><th class="text-right">Infestadores</th></tr></thead>
                        <tbody>
                            @foreach ($t['destinos'] as $destino => $d)
                                <tr class="border-t border-border">
                                    <td>{{ $destino }} <span class="text-xs text-muted-foreground">{{ formatear_fecha($d['desde']) }}{{ $d['desde'] !== $d['hasta'] ? '–' . formatear_fecha($d['hasta']) : '' }}{{ $d['reinfestacion'] ? ' · reinfestación' : '' }}</span></td>
                                    <td class="text-right">{{ number_format($d['kg_madres'], 2) }}</td>
                                    <td class="text-right">{{ $d['porcentaje'] }} %</td>
                                    <td class="text-right">{{ number_format($d['infestadores']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-muted-foreground mb-1">Retornos (ingresos de infestadores con campo {{ $t['campo'] }})</p>
                    @forelse ($t['ingresos'] as $ing)
                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-border py-1 text-sm" wire:key="ing-{{ $ing['id'] }}">
                            <div>Lote <b>{{ $ing['lote'] }}</b> · {{ formatear_fecha($ing['fecha']) }}</div>
                            <div class="font-semibold">{{ number_format($ing['kg'], 2) }} kg</div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">Todavía no vuelve nada. Los retornos se registran en Cochinilla → Ingreso (tipo "Infestador – …") con el campo {{ $t['campo'] }}, que dio la mamá.</p>
                    @endforelse
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-muted-foreground py-8">No hay envíos de mamá con este filtro.</x-card>
    @endforelse

    <x-dialog-modal wire:model.live="mostrarCerrar" maxWidth="lg">
        <x-slot name="title">Dar por recuperado el envío de mamá</x-slot>
        <x-slot name="content">
            <p class="text-sm mb-3">Ya no se esperan más infestadores de vuelta de esta mamá. Deja de figurar como "por recuperar" y no vuelve a avisar.</p>
            <x-input type="text" wire:model="nota" label="Nota (opcional)" placeholder="Ej. se recogieron todas las mallitas, el resto se perdió" class="w-full" />
        </x-slot>
        <x-slot name="footer">
            <div class="flex gap-2">
                <x-button variant="secondary" wire:click="$set('mostrarCerrar', false)">Cancelar</x-button>
                <x-button variant="success" wire:click="cerrar"><i class="fa fa-check"></i> Confirmar</x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="cerrar,reabrir,estado,campo" />
</div>
