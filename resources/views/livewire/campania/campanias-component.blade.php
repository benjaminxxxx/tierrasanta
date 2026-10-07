<div>
    <x-loading wire:loading />

    <x-flex class="justify-between">
        <x-breadcrumb :items="$breadcrumb"/>

        @can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR)
            <x-button @click="$wire.dispatch('registroCampania')">
                <i class="fa fa-plus"></i> Registrar nueva campaña
            </x-button>
        @endcan

    </x-flex>
    {{-- Contadores: también sirven de atajo para filtrar --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
        <button type="button" wire:click="filtrarEstado('vigentes')"
            class="text-left rounded-lg border p-4 bg-card transition-colors hover:bg-muted {{ $estado === 'vigentes' && !$soloPorCerrar ? 'ring-2 ring-primary' : '' }}">
            <p class="text-sm text-muted-foreground">Campañas vigentes{{ $campoSeleccionado ? " · campo {$campoSeleccionado}" : '' }}</p>
            <p class="text-3xl font-bold text-foreground">{{ $totales['vigentes'] }}</p>
        </button>
        <button type="button" wire:click="filtrarEstado('vigentes', true)"
            class="text-left rounded-lg border p-4 bg-card transition-colors hover:bg-muted {{ $soloPorCerrar ? 'ring-2 ring-red-500' : '' }}">
            <p class="text-sm text-muted-foreground">Cosechadas que conviene cerrar</p>
            <p class="text-3xl font-bold {{ $totales['por_cerrar'] ? 'text-red-600 dark:text-red-400' : 'text-foreground' }}">
                {{ $totales['por_cerrar'] }}
            </p>
        </button>
        <button type="button" wire:click="filtrarEstado('cerradas')"
            class="text-left rounded-lg border p-4 bg-card transition-colors hover:bg-muted {{ $estado === 'cerradas' ? 'ring-2 ring-primary' : '' }}">
            <p class="text-sm text-muted-foreground">Campañas cerradas</p>
            <p class="text-3xl font-bold text-foreground">{{ $totales['cerradas'] }}</p>
        </button>
    </div>

    <x-card class="mt-4">
        <x-flex class="justify-between flex-wrap gap-3">
            <x-flex class="flex-wrap items-end">
                <x-select-campo label="Campo" wire:model.live="campoSeleccionado" class="w-auto" />

                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Campaña" class="w-auto">
                        <option value="">Todas</option>
                        @foreach ($campanias as $campaniaId => $campaniaNombre)
                            <option value="{{ $campaniaId }}">{{ $campaniaNombre }}</option>
                        @endforeach
                    </x-select>
                @endif

                <x-select wire:model.live="estado" label="Estado" class="w-auto">
                    <option value="vigentes">Vigentes</option>
                    <option value="cerradas">Cerradas</option>
                    <option value="todas">Todas</option>
                </x-select>

                <div class="pb-2">
                    <x-input type="checkbox" wire:model.live="soloPorCerrar" label="Solo las que conviene cerrar" />
                </div>
            </x-flex>
            <x-flex class="items-end">
                <x-button variant="success" wire:click="descargarReporteCampania">
                    <i class="fa fa-file-excel"></i> Descargar reporte
                </x-button>
            </x-flex>
        </x-flex>
    </x-card>
    <div x-data="resumenCampanias">
        @php
            $columnBlocks = [

                [
                    'key' => 'poblacion',
                    'title' => 'Población de Plantas',
                    'color' => 'bg-red-600 !text-white',
                    'columns' => [
                        'Fecha de evaluación día cero',
                        'Nª de pencas madre día cero',
                        'Fecha de evaluación resiembra',
                        'Nª de pencas madre después de resiembra',
                    ],
                ],

                [
                    'key' => 'brotes',
                    'title' => 'Brotes por Piso',
                    'color' => 'bg-amber-600 !text-white',
                    'columns' => [
                        'Fecha evaluación brotes por piso',
                        'Actual brotes aptos 2° piso',
                        'Brotes 2° piso después de N días',
                        'Actual brotes aptos 3° piso',
                        'Brotes 3° piso después de N días',
                        'Total actual brotes 2° + 3° piso',
                        'Total brotes 2° + 3° piso después de N días',
                    ],
                ],

                [
                    'key' => 'infestacion',
                    'title' => 'Infestación',
                    'color' => 'bg-lime-700 !text-white',
                    'columns' => [
                        'Fecha de infestación',
                        'Tipo de infestador',
                        'Nº de infestadores',
                        'Kg de mamá',
                        'Nº de pencas',
                        'Nº infestadores x penca',
                        'Grs de cochinilla mamá x infestador',
                        'Tiempo de inicio a infestación',
                        'Kg de nitrógeno de inicio a infestación',
                        'Kg de fósforo de inicio a infestación',
                        'Kg de potasio de inicio a infestación',
                        'Kg de calcio de inicio a infestación',
                        'Kg de magnesio de inicio a infestación',
                        'Kg de manganeso de inicio a infestestación',
                        'Kg de zinc de inicio a infestación',
                        'Kg de fierro de inicio a infestación',
                        'Lt de SalTrad de inicio a infestación',
                        'm³ de agua desde inicio a infestación/ha',
                        'Lt de agua por penca de inicio a infestación',
                    ],
                ],
                [
                    'key' => 'reinfestacion',
                    'title' => 'Re-infestación',
                    'color' => 'bg-emerald-700 !text-white',
                    'columns' => [
                        'Fecha de re-infestación', //
                        'Tipo de infestador',//
                        'Nº de infestadores',//
                        'Kg de mamá', //
                        'Nº de pencas', //
                        'Nº infestadores x penca', //
                        'Grs de cochinilla mamá x infestador',//
                        'Tiempo de infestación a re-infestación',//
                        'Kg de nitrógeno de infestación a re-infestación',
                        'Kg de fósforo de infestación a re-infestación',
                        'Kg de potasio de infestación a re-infestación',
                        'Kg de calcio de infestación a re-infestación',
                        'Kg de magnesio de infestación a re-infestación',
                        'Kg de manganeso de infestación a re-infestación',
                        'Kg de zinc de infestación a re-infestación',
                        'Kg de fierro de infestación a re-infestación',
                        'Lt de SalTrad de infestación a re-infestación',
                        'm³ de agua desde infestación a re-infestación/ha',
                        'Lt de agua por penca de infestación a re-infestación',
                    ],
                ],
                [
                    'key' => 'cosecha',
                    'title' => 'Cosecha',
                    'color' => 'bg-cyan-700 !text-white',
                    'columns' => [
                        'Fecha de cosecha',
                        'Tiempo de infestación a cosecha',
                        'Tiempo de re-infestación a cosecha',
                        'Tiempo de inicio a cosecha',
                        'Kg de nitrógeno de inicio a cosecha',
                        'Kg de fósforo de inicio a cosecha',
                        'Kg de potasio de inicio a cosecha',
                        'Kg de calcio de inicio a cosecha',
                        'Kg de magnesio de inicio a cosecha',
                        'Kg de manganeso de inicio a cosecha',
                        'Kg de zinc de inicio a cosecha',
                        'Kg de fierro de inicio a cosecha',
                        'Lt de SalTrad de inicio a cosecha',
                        'm³ de agua desde inicio a cosecha/ha',
                        'Lt de agua por penca de inicio a cosecha',
                        'Cosecha (kg)',
                        'Rendimiento total (kg)',
                        'Rendimiento por infestador (gr)',
                        'Rendimiento por penca (gr)',

                        'PROYECCION COSECHA CONTEO',
                        'PROYECCION COSECHA PODA',
                        'DIFERENCIA PROYECCION CONTEO',
                        'DIFERENCIA PROYECCION PODA'

                    ],
                ],
                [
                    'key' => 'financiero',
                    'title' => 'ANALISIS FINANCIERO',
                    'color' => 'bg-blue-700 !text-white',
                    'columns' => [
                        'COSTO $',
                        'PRECIO VENTA $',
                        'VENTA TOTAL $',
                        'UTILIDAD O PERDIDA $',
                        'COSTO X KG',
                        '% UTILIDAD',
                    ],
                ]
            ];
        @endphp
        @can(\App\Constants\Permisos::CAMPAÑA_RESUMEN_VER)
            {{-- Bloques de columnas: se muestran u ocultan por grupo (se recuerda en este navegador) --}}
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-sm text-muted-foreground me-1">Columnas:</span>
                @foreach ($columnBlocks as $block)
                    <button type="button" @click="alternar('{{ $block['key'] }}')"
                        :class="bloques.{{ $block['key'] }} ? '{{ $block['color'] }} border-transparent' : 'bg-card text-muted-foreground'"
                        class="text-xs font-semibold uppercase rounded-full border px-3 py-1 transition-colors">
                        {{ $block['title'] }}
                        <span class="opacity-75">({{ count($block['columns']) }})</span>
                    </button>
                @endforeach
                <x-button size="xs" variant="outline" @click="todos(true)">Mostrar todo</x-button>
                <x-button size="xs" variant="outline" @click="todos(false)">Ocultar todo</x-button>
            </div>

            {{-- Sin scroll propio: la tabla va a su ancho completo y se recorre con el scroll de la página --}}
            <div class="mt-3 w-max min-w-full">
                <table class="border border-border rounded-lg shadow-sm w-full text-sm text-left rtl:text-right">
                    <thead class="text-xs uppercase bg-muted text-card-foreground">
                        <x-tr>
                            <x-th class="text-center" rowspan="2">N°</x-th>
                            <x-th class="text-center" rowspan="2">Acciones</x-th>
                            <x-th class="text-center" rowspan="2">Campaña</x-th>
                            <x-th class="text-center" rowspan="2">Campo</x-th>
                            <x-th class="text-center" rowspan="2">Área</x-th>
                            <x-th class="text-center" rowspan="2">Siembra</x-th>
                            <x-th class="text-center" rowspan="2">Inicio</x-th>
                            <x-th class="text-center" rowspan="2">Cierre</x-th>
                            <x-th class="text-center" rowspan="2">Etapa</x-th>
                            <x-th class="text-center" rowspan="2">Cosecha</x-th>

                            @foreach ($columnBlocks as $block)
                                <x-th class="text-center {{ $block['color'] }}" colspan="{{ count($block['columns']) }}"
                                    x-show="bloques.{{ $block['key'] }}">
                                    {{ $block['title'] }}
                                </x-th>
                            @endforeach
                        </x-tr>
                        <x-tr>
                            @foreach ($columnBlocks as $block)
                                @foreach ($block['columns'] as $col)
                                    <x-th class="text-center {{ $block['color'] }}" x-show="bloques.{{ $block['key'] }}">
                                        {{ $col }}
                                    </x-th>
                                @endforeach
                            @endforeach
                        </x-tr>
                    </thead>

                    <tbody>
                        @forelse ($campaniasGenerales as $campania)
                            @php $c = $cosecha[$campania->id] ?? null; @endphp
                            <x-tr class="bg-card" wire:key="campania-{{ $campania->id }}">
                                <x-td class="text-center">
                                    {{ $campaniasGenerales->firstItem() + $loop->index }}
                                </x-td>
                                <x-td class="text-center">
                                    @can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR)
                                        <x-dropdown align="left">
                                            <x-slot name="trigger">
                                                <x-button type="button" size="sm">
                                                    Opciones <i class="fa fa-chevron-down"></i>
                                                </x-button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link href="{{ route('campania.por_campo', ['campania' => $campania->id]) }}">
                                                    Gestionar detalle
                                                </x-dropdown-link>
                                                <x-dropdown-link class="cursor-pointer"
                                                    @click="$wire.dispatch('editarCampania', {campaniaId: {{ $campania->id }}})">
                                                    Editar campaña
                                                </x-dropdown-link>
                                                @if ($c && $c['recomendar_cierre'])
                                                    <x-dropdown-link class="cursor-pointer !text-red-600"
                                                        @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $campania->id }}, fecha: '{{ $c['fecha_cierre_sugerida']->toDateString() }}'})">
                                                        Cerrar campaña ({{ formatear_fecha($c['fecha_cierre_sugerida']) }})
                                                    </x-dropdown-link>
                                                @elseif (!$campania->fecha_fin)
                                                    <x-dropdown-link class="cursor-pointer"
                                                        @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $campania->id }}})">
                                                        Cerrar campaña
                                                    </x-dropdown-link>
                                                @endif
                                                <x-dropdown-link class="cursor-pointer !text-red-600"
                                                    wire:confirm="¿Estás seguro de eliminar esta campaña?"
                                                    wire:click="eliminarCampania({{ $campania->id }})">
                                                    Eliminar campaña
                                                </x-dropdown-link>
                                            </x-slot>
                                        </x-dropdown>
                                    @else
                                        -
                                    @endcan
                                </x-td>
                                <x-td class="text-center font-semibold">{{ $campania->nombre_campania }}</x-td>
                                <x-td class="text-center">{{ $campania->campo }}</x-td>
                                <x-td class="text-center">{{ $campania->area }}</x-td>
                                <x-td class="text-center">{{ formatear_fecha($campania->fecha_siembra) }}</x-td>
                                <x-td class="text-center">{{ formatear_fecha($campania->fecha_inicio) }}</x-td>
                                <x-td class="text-center">
                                    @if ($campania->fecha_fin)
                                        {{ formatear_fecha($campania->fecha_fin) }}
                                    @else
                                        <x-badge color="green">Vigente</x-badge>
                                    @endif
                                </x-td>
                                <x-td class="text-center whitespace-nowrap">
                                    @if ($e = $etapas[$campania->id] ?? null)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold text-white" style="background: {{ $e['color'] }}">{{ $e['nombre'] }}</span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </x-td>
                                <x-td class="text-left min-w-56">
                                    @include('livewire.campania.partials.campanias-estado-cosecha', ['estado' => $c])
                                </x-td>

                                @include('livewire.campania.partials.campanias-poblacion-plantas')
                                @include('livewire.campania.partials.campanias-brotes-x-piso')
                                @include('livewire.campania.partials.campanias-infestacion')
                                @include('livewire.campania.partials.campanias-reinfestacion')
                                @include('livewire.campania.partials.campanias-cosecha')
                                @include('livewire.campania.partials.campanias-analisis-financiero')
                            </x-tr>
                        @empty
                            <x-tr>
                                <x-td colspan="10" class="text-center text-muted-foreground py-6">
                                    No hay campañas con estos filtros.
                                </x-td>
                            </x-tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="my-4">
                {{ $campaniasGenerales->links() }}
            </div>
        @else
            <x-danger class="mt-4">
                No tienes permiso para ver el resumen de campañas. Por favor, contacta al administrador.
            </x-danger>
        @endcan
    </div>

    {{-- Modales de campaña: se montan solo en las páginas que los usan --}}
    <livewire:campania.campania-ficha-component />
    <livewire:campania.campania-cerrar-component />
</div>
@script
<script>
    Alpine.data('resumenCampanias', () => ({
        clave: 'campania.resumen.bloques',
        bloques: { poblacion: false, brotes: false, infestacion: false, reinfestacion: false, cosecha: false, financiero: false },
        init() {
            try {
                const guardado = JSON.parse(localStorage.getItem(this.clave) || 'null');
                if (guardado) this.bloques = { ...this.bloques, ...guardado };
            } catch (e) {}
        },
        guardar() {
            try { localStorage.setItem(this.clave, JSON.stringify(this.bloques)); } catch (e) {}
        },
        alternar(bloque) {
            this.bloques[bloque] = !this.bloques[bloque];
            this.guardar();
        },
        todos(visible) {
            Object.keys(this.bloques).forEach(b => this.bloques[b] = visible);
            this.guardar();
        },
    }));
</script>
@endscript
