<div class="space-y-4">
    <x-heading title="Permisos y Suspensiones"
        subtitle="Vacaciones, descansos médicos, licencias y faltas de los trabajadores, por mes" />

    {{-- Filtros y vistas --}}
    <x-card>
        <div class="flex flex-wrap items-end gap-3">
            <div class="inline-flex p-1 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 self-center">
                @foreach (['registro' => ['fa-list', 'Suspensiones'], 'estadisticas' => ['fa-chart-column', 'Estadísticas']] as $clave => [$icono, $texto])
                    <button type="button" wire:click="$set('vista', '{{ $clave }}')"
                        class="px-3 py-1.5 text-sm font-semibold rounded-md transition-all {{ $vista === $clave ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700' }}">
                        <i class="fa {{ $icono }} mr-1"></i> {{ $texto }}
                    </button>
                @endforeach
            </div>

            @if ($vista === 'registro')
                <x-select-anios wire:model.live="anio" class="w-auto" />
                <x-select-meses wire:model.live="mes" class="w-auto" />
                <div class="w-64">
                    <x-input wire:model.live.debounce.400ms="buscar" label="Buscar trabajador" placeholder="Nombre o DNI" />
                </div>
            @else
                <x-select-anios wire:model.live="estAnio" class="w-auto" />
                <x-select wire:model.live="estMes" label="Mes" class="w-auto">
                    <option value="">Todo el año</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->translatedFormat('F')) }}</option>
                    @endforeach
                </x-select>
                <div class="w-72" wire:key="est-emp">
                    <x-label value="Trabajador" />
                    <x-searchable-select :options="$empleadosTodos" placeholder="Todos" search-placeholder="Buscar trabajador..." wire:model.live="estEmpleadoId" />
                </div>
                @if ($estEmpleadoId)
                    <x-button variant="secondary" wire:click="$set('estEmpleadoId', '')">Quitar trabajador</x-button>
                @endif
            @endif

            <x-select wire:model.live="tipoPlanilla" label="Planilla" class="w-auto">
                <option value="">Todas</option>
                <option value="agraria">Agraria</option>
                <option value="oficina">Oficina</option>
                <option value="general">General</option>
            </x-select>
        </div>
    </x-card>

    @if ($vista === 'registro')
        {{-- ============================================================ SUSPENSIONES DEL MES POR TRABAJADOR --}}
        @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_VER)
            <x-card class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-foreground">Trabajadores con suspensiones en el mes ({{ $trabajadores->count() }})</h3>
                        <p class="text-xs text-muted-foreground">
                            Cada cuadrito es un día del mes. Para cambiar fechas o tipos, edita el trabajador: todos sus rangos se guardan juntos.
                            También puedes registrar rangos que aún no están en el registro diario (vacaciones o descansos ya programados).
                        </p>
                    </div>
                    @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                        <x-button @click="$wire.dispatch('nuevaSuspensionTrabajador', {mes: {{ (int) $mes }}, anio: {{ (int) $anio }}})"><i class="fa fa-user-plus"></i> Agregar trabajador con suspensión</x-button>
                    @endcan
                </div>

                <div class="divide-y divide-border border border-border rounded-lg">
                    @forelse ($trabajadores as $t)
                        <div class="p-3 flex flex-wrap items-center gap-3" wire:key="trab-{{ $t['plan_empleado_id'] }}">
                            <div class="w-64 min-w-0">
                                <p class="font-medium text-sm truncate" title="{{ $t['nombre'] }}">{{ $t['nombre'] }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $t['documento'] }} · {{ $t['tipo_planilla'] ? ucfirst($t['tipo_planilla']) : 'sin contrato' }} · {{ $t['total'] }} día(s)
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-1 w-56">
                                @foreach ($t['resumen'] as $r)
                                    <span class="text-xs px-1.5 py-0.5 rounded text-white" style="background: {{ $r['color'] }}" title="{{ $r['descripcion'] }}">
                                        {{ $r['codigo'] }} · {{ $r['dias'] }}d
                                    </span>
                                @endforeach
                            </div>
                            <div class="flex gap-px flex-1 min-w-[16rem]">
                                @foreach ($t['dias'] as $dia => $codigo)
                                    <div class="flex-1 h-5 rounded-sm {{ $codigo ? '' : 'bg-muted' }}"
                                        @if ($codigo) style="background: {{ \App\Services\Planilla\Suspension\PlanillaSuspensionConsulta::color($codigo) }}" @endif
                                        title="{{ $dia }}{{ $codigo ? ' · ' . $codigo : '' }}"></div>
                                @endforeach
                            </div>
                            @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                                <x-button size="sm" variant="secondary" @click="$wire.dispatch('editarSuspensionesTrabajador', {planEmpleadoId: {{ $t['plan_empleado_id'] }}, mes: {{ (int) $mes }}, anio: {{ (int) $anio }}})">
                                    <i class="fa fa-edit"></i> Editar
                                </x-button>
                            @endcan
                        </div>
                    @empty
                        <p class="p-4 text-sm text-muted-foreground text-center">
                            {{ $buscar || $tipoPlanilla ? 'Ningún trabajador coincide con el filtro.' : 'No hay suspensiones registradas este mes.' }}
                        </p>
                    @endforelse
                </div>
            </x-card>
        @else
            <x-danger>No tienes permiso para ver las suspensiones de los trabajadores.</x-danger>
        @endcan

        {{-- ============================================================ SUGERENCIAS DESDE EL REGISTRO DIARIO --}}
        @if ($mes && $anio)
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <x-card class="xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <h3 class="font-semibold text-foreground">Sugerencias desde el registro diario</h3>
                            <p class="text-xs text-muted-foreground">
                                Rangos armados con los días consecutivos de cada trabajador (los domingos sin registro se
                                incluyen en vacaciones, descanso médico y maternidad). Revisa y acepta de golpe.
                            </p>
                        </div>
                        @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                            @if (count($sugerencias))
                                <x-button wire:click="aceptarSugerencias" wire:confirm="¿Registrar las {{ count($seleccionadas) }} suspensiones marcadas?">
                                    <i class="fa fa-check-double"></i> Aceptar {{ count($seleccionadas) }} de {{ count($sugerencias) }}
                                </x-button>
                            @endif
                        @endcan
                    </div>

                    @if (count($sugerencias))
                        <div class="overflow-auto max-h-[28rem] border border-border rounded">
                            <table class="w-full text-xs">
                                <thead class="bg-muted sticky top-0 text-muted-foreground">
                                    <tr>
                                        <th class="p-2 w-8">
                                            <input type="checkbox" class="rounded" wire:click="alternarTodas" @checked(count($seleccionadas) === count($sugerencias))>
                                        </th>
                                        <th class="p-2 text-left">Trabajador</th>
                                        <th class="p-2 text-center">Asist.</th>
                                        <th class="p-2 text-left">Suspensión</th>
                                        <th class="p-2 text-left">Rango</th>
                                        <th class="p-2 text-center">Días</th>
                                        <th class="p-2 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sugerencias as $s)
                                        <tr class="border-t border-border hover:bg-muted/50" wire:key="sug-{{ $s['clave'] }}">
                                            <td class="p-2 text-center">
                                                <input type="checkbox" class="rounded" value="{{ $s['clave'] }}" wire:model.live="seleccionadas">
                                            </td>
                                            <td class="p-2">{{ $s['trabajador'] }}</td>
                                            <td class="p-2 text-center font-mono">{{ $s['codigos'] }}</td>
                                            <td class="p-2">{{ $s['tipo_suspension'] }}</td>
                                            <td class="p-2 whitespace-nowrap">
                                                {{ \Illuminate\Support\Carbon::parse($s['fecha_inicio'])->format('d/m') }}
                                                @if ($s['fecha_fin'] !== $s['fecha_inicio'])
                                                    – {{ \Illuminate\Support\Carbon::parse($s['fecha_fin'])->format('d/m') }}
                                                @endif
                                            </td>
                                            <td class="p-2 text-center">
                                                {{ $s['dias'] }}
                                                @if ($s['domingos'])
                                                    <span class="text-muted-foreground" title="Domingos incluidos">(+{{ $s['domingos'] }} dom.)</span>
                                                @endif
                                            </td>
                                            <td class="p-2 text-center">
                                                @php
                                                    $accion = [
                                                        'crear' => ['Nueva', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
                                                        'extender_fin' => ['Extiende', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                                                        'extender_inicio' => ['Extiende', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                                                        'unir' => ['Une dos', 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'],
                                                    ][$s['accion']];
                                                @endphp
                                                <span class="px-2 py-0.5 rounded {{ $accion[1] }}"
                                                    title="Queda: {{ $s['rango_final'][0] }} a {{ $s['rango_final'][1] ?? 'sin fin' }}">{{ $accion[0] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-success>No hay suspensiones por registrar con los vínculos actuales.</x-success>
                    @endif
                </x-card>

                <x-card>
                    <h3 class="font-semibold text-foreground">Por decidir</h3>
                    <p class="text-xs text-muted-foreground mb-3">
                        Códigos de asistencia sin suspensión vinculada. El vínculo se guarda en el tipo de asistencia y
                        vale para todos los meses; al vincular, sus días pasan a sugerencias.
                    </p>

                    @forelse ($porDecidir as $p)
                        <div class="border border-border rounded p-2 mb-2" wire:key="dec-{{ $p['codigo'] }}">
                            <div class="text-sm">
                                <span class="font-mono font-semibold">{{ $p['codigo'] }}</span> {{ $p['descripcion'] }}
                                <span class="text-xs text-muted-foreground">· {{ $p['dias'] }} día(s), {{ $p['trabajadores'] }} trabajador(es)</span>
                            </div>
                            @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                                <div class="flex gap-2 mt-2">
                                    <select wire:model="vinculos.{{ $p['codigo'] }}"
                                        class="flex-1 min-w-0 h-8 rounded-md border border-input bg-background text-foreground text-xs px-2">
                                        <option value="">— Elegir suspensión —</option>
                                        <option value="sin">No genera suspensión</option>
                                        @foreach ($tipos as $t)
                                            <option value="{{ $t['id'] }}">{{ $t['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <x-button size="sm" wire:click="vincularCodigo('{{ $p['codigo'] }}')">Vincular</x-button>
                                </div>
                            @endcan
                        </div>
                    @empty
                        <p class="text-sm text-green-700 dark:text-green-400"><i class="fa fa-check"></i> Todos los códigos tienen vínculo.</p>
                    @endforelse

                    @if (count($conflictos))
                        <h4 class="font-semibold text-sm text-foreground mt-4">Conflictos ({{ count($conflictos) }})</h4>
                        <p class="text-xs text-muted-foreground mb-2">Días registrados con otra suspensión: corrígelos editando el trabajador o en el registro diario.</p>
                        <ul class="text-xs space-y-1 max-h-48 overflow-auto">
                            @foreach ($conflictos as $c)
                                <li>
                                    {{ \Illuminate\Support\Carbon::parse($c['fecha'])->format('d/m') }} · {{ $c['trabajador'] }}:
                                    <b>{{ $c['codigo'] }}</b> ({{ $c['esperado'] }}) pero tiene {{ $c['registrado'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (count($parciales))
                        <h4 class="font-semibold text-sm text-foreground mt-4">Días mezclados ({{ count($parciales) }})</h4>
                        <p class="text-xs text-muted-foreground mb-2">
                            Asistencia A con parte del detalle en una labor de suspensión. El PLAME declara días completos, así
                            que no se sugieren; sus horas igual entran al costo en FDM con el código de la labor.
                        </p>
                        <ul class="text-xs space-y-1 max-h-48 overflow-auto">
                            @foreach ($parciales as $p)
                                <li>
                                    {{ \Illuminate\Support\Carbon::parse($p['fecha'])->format('d/m') }} · {{ $p['trabajador'] }}:
                                    <b>{{ $p['codigo'] }}</b> {{ $p['descripcion'] }} {{ rtrim(rtrim(number_format($p['horas_suspension'], 2), '0'), '.') }} h
                                    + {{ rtrim(rtrim(number_format($p['horas_trabajo'], 2), '0'), '.') }} h de trabajo
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        @endif

        {{-- Modal del trabajador: componente aparte (también lo abre el registro diario) --}}
    @else
        {{-- ============================================================ ESTADÍSTICAS --}}
        {{-- Subcomponente: se crea de nuevo con cada filtro (wire:key), así Chart.js siempre dibuja sobre un canvas nuevo --}}
        <livewire:planilla.asistencia.suspensiones-estadisticas-component
            :anio="(int) ($estAnio ?: now()->year)" :mes="$estMes ? (int) $estMes : null"
            :empleado-id="$estEmpleadoId ? (int) $estEmpleadoId : null" :tipo-planilla="$tipoPlanilla ?: null"
            wire:key="est-{{ $estAnio }}-{{ $estMes }}-{{ $estEmpleadoId }}-{{ $tipoPlanilla }}" />
    @endif

    <livewire:planilla.asistencia.suspension-trabajador-modal-component />
    <x-loading wire:loading wire:target="aceptarSugerencias,vincularCodigo,mes,anio,vista" />
</div>

@script
<script>
    // Dibuja el gráfico de Estadísticas. Vive en la página (se carga una vez) y lo usa el subcomponente
    // SuspensionesEstadisticasComponent, que se crea de nuevo con cada filtro: siempre recibe un canvas nuevo.
    window.dibujarGraficoSuspensiones = (canvas, g) => {
        if (!window.Chart) { setTimeout(() => window.dibujarGraficoSuspensiones(canvas, g), 300); return null; }
        if (!canvas || !canvas.isConnected) return null;
        Chart.getChart(canvas)?.destroy();
        const oscuro = document.documentElement.classList.contains('dark');
        const texto = oscuro ? '#D4D4D8' : '#3F3F46';
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: g.etiquetas,
                datasets: g.series.map(s => ({ label: s.label, data: s.datos, backgroundColor: s.color, stack: 'tipos' })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { color: texto, boxWidth: 12 } } },
                scales: {
                    x: { stacked: true, ticks: { color: texto } },
                    y: { stacked: true, beginAtZero: true, ticks: { color: texto, precision: 0 } },
                },
            },
        });
    };
</script>
@endscript
