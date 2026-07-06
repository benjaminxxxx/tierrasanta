<div class="space-y-4">

    {{-- ENCABEZADO --}}
    <x-flex class="justify-between w-full">
        <x-breadcrumb :items="$breadcrumb" />
        <x-flex class="gap-2">
            <x-button variant="secondary" wire:click="exportarReporte">
                <i class="fa fa-file-excel me-1"></i> Exportar
            </x-button>
            <x-button wire:click="registrarInfestacion">
                <i class="fa fa-plus me-1"></i> Registrar Infestación
            </x-button>
            <x-button variant="secondary" wire:click="abrirVinculacion">
                <i class="fa fa-link me-1"></i> Vincular Campañas
            </x-button>
        </x-flex>
    </x-flex>

    {{-- FILTROS --}}
    <x-card>
        <x-subtitle>Filtros de búsqueda</x-subtitle>

        <x-flex>

            {{-- Campaña (distinct) --}}
            <div>
                <x-label>Campaña</x-label>
                <x-select wire:model.live="filtroCampania" class="w-auto">
                    <option value="">Todas las campañas</option>
                    @foreach ($listaCampanias as $campania)
                        <option value="{{ $campania }}">{{ $campania }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Campo (se filtra según campaña elegida) --}}
            <div>
                <x-label>
                    Campo
                    @if ($filtroCampania)
                        <span class="text-xs text-muted-foreground ml-1">(filtrado por campaña)</span>
                    @endif
                </x-label>
                <x-select wire:model.live="filtroCampo" class="w-auto">
                    <option value="">Todos los campos</option>
                    @foreach ($listaCampos as $campo)
                        <option value="{{ $campo }}">{{ $campo }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Tipo --}}
            <div>
                <x-label>Tipo</x-label>
                <x-select wire:model.live="filtroTipo" class="w-auto">
                    <option value="">Todos</option>
                    <option value="infestacion">Infestación</option>
                    <option value="reinfestacion">Reinfestación</option>
                </x-select>
            </div>

            {{-- Método --}}
            <div>
                <x-label>Método</x-label>
                <x-select wire:model.live="filtroMetodo" class="w-auto">
                    <option value="">Todos</option>
                    <option value="carton">Cartón</option>
                    <option value="tubo">Tubo</option>
                    <option value="malla">Malla</option>
                </x-select>
            </div>

            {{-- Campo origen --}}
            <div>
                <x-label>Campo Origen</x-label>
                <x-select wire:model.live="filtroCampoOrigen" class="w-auto">
                    <option value="">Todos</option>
                    @foreach ($listaCamposOrigen as $co)
                        <option value="{{ $co }}">{{ $co }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Fecha desde --}}
            <div>
                <x-label>Fecha desde</x-label>
                <x-selector-dia wire:model.live="filtroFechaDesde" class="w-auto" />
            </div>

            {{-- Fecha hasta --}}
            <div>
                <x-label>Fecha hasta</x-label>
                <x-selector-dia wire:model.live="filtroFechaHasta" class="w-auto" />
            </div>

            {{-- Botón limpiar --}}
            <div class="flex items-end">
                <x-button variant="secondary" wire:click="limpiarFiltros">
                    <i class="fa fa-times me-1"></i> Limpiar filtros
                </x-button>
            </div>

        </x-flex>

        {{-- Ordenamiento --}}
        <div class="mt-4 pt-3 border-t border-border">
            <x-label>Ordenar por</x-label>
            <div class="flex flex-wrap gap-2 mt-1">

                @php
                    $opcionesOrden = [
                        'fecha' => 'Fecha',
                        'tipo_infestacion' => 'Tipo',
                        'numero_envases' => 'N° Envases',
                        'capacidad_envase' => 'Und × Envase',
                    ];
                @endphp

                @foreach ($opcionesOrden as $key => $label)
                            <button wire:click="ordenarPor('{{ $key }}')" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs border transition
                                                                {{ $ordenCampo === $key
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'bg-background text-card-foreground border-border hover:border-primary' }}">
                                {{ $label }}
                                @if ($ordenCampo === $key)
                                    <i class="fa fa-arrow-{{ $ordenDireccion === 'asc' ? 'up' : 'down' }} text-[10px]"></i>
                                @endif
                            </button>
                @endforeach

            </div>
        </div>
    </x-card>

    {{-- RESULTADOS --}}
    <x-card>
        <x-flex class="justify-between mb-3">
            <x-subtitle>Resultados</x-subtitle>
            <span class="text-xs text-muted-foreground">
                {{ $total }} registro(s) encontrado(s)
            </span>
        </x-flex>

        @if ($infestaciones->isEmpty())
            <x-warning>No se encontraron infestaciones con los filtros aplicados.</x-warning>
        @else
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th>Tipo</x-th>
                            <x-th>Fecha</x-th>
                            <x-th>Campo</x-th>
                            <x-th>Área (ha)</x-th>
                            <x-th>Campaña</x-th>
                            <x-th>Kg Madres</x-th>
                            <x-th>Kg/Ha</x-th>
                            <x-th>C. Origen</x-th>
                            <x-th>Método</x-th>
                            <x-th>Und × Env</x-th>
                            <x-th>Envases</x-th>
                            <x-th>Infestadores</x-th>
                            <x-th>Mad/Inf</x-th>
                            <x-th>Inf/Ha</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($infestaciones as $item)
                                    <x-tr>
                                        <x-td>
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                                                                                        {{ $item->tipo_infestacion === 'infestacion'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                {{ ucfirst($item->tipo_infestacion ?? '—') }}
                                            </span>
                                            <x-button wire:click="verAuditoria({{ $item->id }})" variant="ghost" size="xs" class="ml-2">
                                                <i class="fa fa-eye"></i>
                                            </x-button>
                                        </x-td>
                                        <x-td>{{ $item->fecha ? \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') : '—' }}</x-td>
                                        <x-td class="font-medium">{{ $item->campo_nombre ?? '—' }}</x-td>
                                        <x-td class="text-right">{{ $item->area ? number_format($item->area, 3) : '—' }}</x-td>
                                        <x-td
                                            class="text-muted-foreground text-xs">{{ $item->campoCampania?->nombre_campania ?? '—' }}</x-td>
                                        <x-td
                                            class="text-right">{{ $item->kg_madres ? number_format($item->kg_madres, 2) : '—' }}</x-td>
                                        <x-td class="text-right text-muted-foreground">
                                            {{ ($item->area > 0 && $item->kg_madres)
                            ? number_format($item->kg_madres / $item->area, 3)
                            : '—' }}
                                        </x-td>
                                        <x-td>{{ $item->campo_origen_nombre ?? '—' }}</x-td>
                                        <x-td class="capitalize">{{ $item->metodo ?? '—' }}</x-td>
                                        <x-td class="text-right">{{ $item->capacidad_envase ?? '—' }}</x-td>
                                        <x-td class="text-right">{{ $item->numero_envases ?? '—' }}</x-td>
                                        <x-td class="text-right font-medium">
                                            {{ ($item->capacidad_envase && $item->numero_envases)
                            ? number_format($item->capacidad_envase * $item->numero_envases, 0)
                            : '—' }}
                                        </x-td>
                                        <x-td class="text-right text-muted-foreground">
                                            @php
                                                $infestadores = ($item->capacidad_envase ?? 0) * ($item->numero_envases ?? 0);
                                            @endphp
                                            {{ ($infestadores > 0 && $item->kg_madres)
                            ? number_format($item->kg_madres / $infestadores, 3)
                            : '—' }}
                                        </x-td>
                                        <x-td class="text-right text-muted-foreground">
                                            {{ ($infestadores > 0 && $item->area > 0)
                            ? number_format($infestadores / $item->area, 2)
                            : '—' }}
                                        </x-td>
                                    </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
            </div>

            <div class="mt-4">
                {{ $infestaciones->links() }}
            </div>
        @endif
    </x-card>
    <x-dialog-modal wire:model.live="modalVinculacion" maxWidth="2xl">

        <x-slot name="title">
            Vincular infestaciones con campañas
        </x-slot>

        <x-slot name="content">

            {{-- ── Resumen rápido ──────────────────────────────────────────────── --}}
            <div class="flex gap-4 mb-5">
                <div class="flex-1 rounded-lg border border-border bg-card p-3 text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ count($listoParaVincular) }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-0.5">Listas para vincular</p>
                </div>
                <div class="flex-1 rounded-lg border border-border bg-card p-3 text-center">
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ count($conConflicto) }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-0.5">Con conflicto</p>
                </div>
            </div>

            {{-- ── LISTAS PARA VINCULAR ─────────────────────────────────────────── --}}
            @if (count($listoParaVincular) > 0)
                <div class="mb-5">
                    <x-subtitle class="mb-2 text-green-700 dark:text-green-400">
                        <i class="fa fa-check-circle me-1"></i>
                        Listos para vincular
                    </x-subtitle>

                    <div class="overflow-x-auto rounded-lg border border-green-200 dark:border-green-800 h-[200px]">
                        <table class="w-full text-sm">
                            <thead class="bg-green-50 dark:bg-green-950 text-green-800 dark:text-green-200">
                                <tr>
                                    <th class="text-left px-3 py-2 font-medium">Campo</th>
                                    <th class="text-left px-3 py-2 font-medium">Fecha</th>
                                    <th class="text-left px-3 py-2 font-medium">Tipo</th>
                                    <th class="text-left px-3 py-2 font-medium">Se vinculará con</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-green-100 dark:divide-green-900">
                                @foreach ($listoParaVincular as $item)
                                                    <tr class="bg-white dark:bg-card">
                                                        <td class="px-3 py-2 font-medium text-card-foreground">
                                                            {{ $item['campo_nombre'] }}
                                                        </td>
                                                        <td class="px-3 py-2 text-muted-foreground">
                                                            {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y') }}
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs
                                                                                            {{ $item['tipo_infestacion'] === 'infestacion'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                                {{ ucfirst($item['tipo_infestacion']) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <span
                                                                class="inline-flex items-center gap-1 text-green-700 dark:text-green-400 font-medium">
                                                                <i class="fa fa-tag text-xs"></i>
                                                                {{ $item['campania_nombre'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ── CON CONFLICTO ───────────────────────────────────────────────── --}}
            @if (count($conConflicto) > 0)
                <div>
                    <x-subtitle class="mb-2 text-yellow-700 dark:text-yellow-400">
                        <i class="fa fa-exclamation-triangle me-1"></i>
                        Con conflicto — requieren corrección manual
                    </x-subtitle>

                    <div class="overflow-x-auto rounded-lg border border-yellow-200 dark:border-yellow-800 h-[200px]">
                        <table class="w-full text-sm">
                            <thead class="bg-yellow-50 dark:bg-yellow-950 text-yellow-800 dark:text-yellow-200">
                                <tr>
                                    <th class="text-left px-3 py-2 font-medium">Campo</th>
                                    <th class="text-left px-3 py-2 font-medium">Fecha</th>
                                    <th class="text-left px-3 py-2 font-medium">Tipo</th>
                                    <th class="text-left px-3 py-2 font-medium">Motivo del conflicto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-yellow-100 dark:divide-yellow-900">
                                @foreach ($conConflicto as $item)
                                                    <tr class="bg-white dark:bg-card">
                                                        <td class="px-3 py-2 font-medium text-card-foreground">
                                                            {{ $item['campo_nombre'] }}
                                                        </td>
                                                        <td class="px-3 py-2 text-muted-foreground">
                                                            {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y') }}
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs
                                                                                            {{ $item['tipo_infestacion'] === 'infestacion'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                                {{ ucfirst($item['tipo_infestacion']) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            @php
                                                                $iconos = [
                                                                    'sin_campanias' => 'fa-ban text-red-500',
                                                                    'multiples_abiertas' => 'fa-code-branch text-orange-500',
                                                                    'fuera_de_rango' => 'fa-calendar-times text-yellow-600',
                                                                ];
                                                                $icono = $iconos[$item['motivo_tipo']] ?? 'fa-question text-gray-400';
                                                            @endphp
                                                            <span class="inline-flex items-start gap-1.5 text-yellow-700 dark:text-yellow-300">
                                                                <i class="fa {{ $icono }} mt-0.5 shrink-0"></i>
                                                                <span>{{ $item['motivo'] }}</span>
                                                            </span>
                                                        </td>
                                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (count($listoParaVincular) > 0)
                        <p class="mt-2 text-xs text-muted-foreground">
                            <i class="fa fa-info-circle me-1"></i>
                            Puedes vincular los registros sin conflicto ahora y resolver los anteriores después.
                        </p>
                    @endif
                </div>
            @endif

            {{-- ── Sin nada huérfano ───────────────────────────────────────────── --}}
            @if (count($listoParaVincular) === 0 && count($conConflicto) === 0)
                <x-warning>No hay infestaciones sin campaña asignada.</x-warning>
            @endif

        </x-slot>

        <x-slot name="footer">
            {{-- Cerrar sin hacer nada --}}
            <x-button variant="secondary" wire:click="$set('modalVinculacion', false)">
                @if (count($conConflicto) > 0 && count($listoParaVincular) === 0)
                    Cerrar y resolver conflictos
                @else
                    Cancelar
                @endif
            </x-button>

            {{-- Confirmar solo si hay registros listos --}}
            @if (count($listoParaVincular) > 0)
                <x-button wire:click="vincularConfirmados" wire:loading.attr="disabled" class="ms-2">
                    <span wire:loading.remove wire:target="vincularConfirmados">
                        <i class="fa fa-link me-1"></i>
                        Vincular {{ count($listoParaVincular) }} infestación(es)
                    </span>
                    <span wire:loading wire:target="vincularConfirmados">
                        <i class="fa fa-spinner fa-spin me-1"></i> Vinculando...
                    </span>
                </x-button>
            @endif
        </x-slot>

    </x-dialog-modal>
    <x-dialog-modal wire:model.live="modalAuditoria">
        <x-slot name="title">Historial de auditoría</x-slot>

        <x-slot name="content">

            {{-- Resumen: creado por / última edición --}}
            @php
                $entradaCreacion = collect($auditoriaHistorial)->firstWhere('accion', 'crear');
                $ultimaEdicion = collect($auditoriaHistorial)
                    ->where('accion', 'editar')
                    ->sortByDesc('fecha_accion')
                    ->first();
            @endphp

            <div class="flex gap-6 mb-4 text-xs text-muted-foreground border-b border-border pb-3">
                <div>
                    <span class="font-semibold text-card-foreground">Creado por:</span>
                    {{ $entradaCreacion['usuario_nombre'] ?? '—' }}
                    @if ($entradaCreacion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($entradaCreacion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
                <div>
                    <span class="font-semibold text-card-foreground">Última edición:</span>
                    {{ $ultimaEdicion['usuario_nombre'] ?? '—' }}
                    @if ($ultimaEdicion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($ultimaEdicion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Historial completo --}}
            @forelse($auditoriaHistorial as $entrada)
                    <div class="mb-4 border-b border-border pb-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold uppercase
                                                        {{ $entrada['accion'] === 'crear'
                ? 'text-green-600'
                : ($entrada['accion'] === 'eliminar'
                    ? 'text-red-600'
                    : 'text-yellow-600') }}">
                                {{ $entrada['accion'] }}
                            </span>
                            <span class="text-gray-400 text-xs">
                                {{ \Carbon\Carbon::parse($entrada['fecha_accion'])->format('d/m/Y H:i') }}
                                — {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                            </span>
                        </div>

                        @if (!empty($entrada['cambios']))
                            @if ($entrada['accion'] === 'editar')
                                <table class="mt-2 w-full text-xs text-gray-700">
                                    <thead>
                                        <tr class="text-left text-gray-400">
                                            <th class="pr-4">Campo</th>
                                            <th class="pr-4">Antes</th>
                                            <th>Después</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($entrada['cambios']['antes'] ?? [] as $campo => $valorAntes)
                                            <tr>
                                                <td class="pr-4 font-medium text-muted-foreground">{{ $campo }}</td>
                                                <td class="pr-4 text-red-500">{{ $valorAntes ?? '—' }}</td>
                                                <td class="text-green-600">
                                                    {{ $entrada['cambios']['despues'][$campo] ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <pre
                                    class="mt-2 text-xs bg-muted rounded p-2 overflow-auto max-h-40">{{ json_encode(array_values($entrada['cambios'])[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        @endif

                        @if ($entrada['observacion'])
                            <p class="mt-1 text-xs text-card-foreground italic">{{ $entrada['observacion'] }}</p>
                        @endif
                    </div>
            @empty
                <p class="text-sm text-card-foreground">Sin historial de cambios.</p>
            @endforelse

        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalAuditoria', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>