<div>
    <x-dialog-modal wire:model.live="mostrarFormularioInsumoKardexReporte" maxWidth="2xl">
        <x-slot name="title">
            <div>
                <div class="text-lg font-semibold text-foreground">
                    {{ $reporteId ? 'Editar Reporte de Kardex' : 'Crear un Reporte de Kardex de Insumos' }}
                </div>
                <p class="text-sm font-normal text-muted-foreground">
                    Reúne en un solo Excel los kardex del año, tipo y grupos operativos elegidos.
                </p>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                {{-- Datos generales --}}
                {{-- Nombre automático (mismo formato que InsKardexReporte::nombreAutomatico) --}}
                <div x-data="{
                        anio: $wire.entangle('anio').live,
                        tipo: $wire.entangle('tipoKardex'),
                        grupos: $wire.entangle('gruposSeleccionados'),
                        get nombre() {
                            const partes = (this.grupos || []).map(g => g.toUpperCase() + 'S');
                            const ultimo = partes.pop();
                            const textoGrupos = partes.length ? partes.join(', ') + ' Y ' + ultimo : (ultimo ?? '');
                            const base = ['KARDEX', this.anio, this.tipo].filter(v => v).join(' ');
                            return (base + (textoGrupos ? ' - ' + textoGrupos : '')).toUpperCase();
                        }
                    }" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-3">
                        <x-label>Nombre del reporte</x-label>
                        <div class="mt-1 flex h-9 items-center gap-2 rounded-md border border-input bg-muted px-3 text-sm font-semibold tracking-wide text-foreground">
                            <i class="fa fa-magic text-xs text-muted-foreground" title="Se genera automáticamente"></i>
                            <span class="truncate" x-text="nombre"></span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Se genera con el año, el tipo y los grupos elegidos.</p>
                    </div>
                    <x-input type="number" label="Año" x-model.debounce.400ms="anio" error="anio" />
                </div>

                {{-- Tipo de kardex --}}
                <div>
                    <x-label>Tipo de kardex</x-label>
                    <div class="mt-1 inline-flex rounded-lg border border-border bg-muted p-1">
                        @foreach (['blanco' => 'Blanco', 'negro' => 'Negro'] as $valor => $texto)
                            <button type="button" wire:click="$set('tipoKardex', '{{ $valor }}')"
                                @class([
                                    'px-5 py-1.5 text-sm font-medium rounded-md transition-colors',
                                    'bg-background text-foreground shadow-sm' => $tipoKardex === $valor,
                                    'text-muted-foreground hover:text-foreground' => $tipoKardex !== $valor,
                                ])>
                                <i class="fa fa-circle text-[8px] mr-1 {{ $valor === 'blanco' ? 'text-gray-300' : 'text-gray-800 dark:text-gray-400' }}"></i>
                                {{ $texto }}
                            </button>
                        @endforeach
                    </div>
                    <x-input-error for="tipoKardex" class="mt-1" />
                </div>

                {{-- Grupos operativos --}}
                <div>
                    <div class="flex items-baseline justify-between">
                        <x-label>Grupos operativos</x-label>
                        <span class="text-xs text-muted-foreground">El orden del Excel sigue el orden mostrado</span>
                    </div>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @php $seleccionOrdenada = \App\Models\InsKardexReporte::ordenarGrupos($gruposSeleccionados); @endphp
                        @foreach ($gruposDisponibles as $grupo)
                            @php
                                $activo = in_array($grupo, $gruposSeleccionados, true);
                                $pos = array_search($grupo, $seleccionOrdenada, true);
                                $color = $pos !== false
                                    ? \App\Models\InsKardexReporte::COLORES_GRUPO[$pos % count(\App\Models\InsKardexReporte::COLORES_GRUPO)]
                                    : null;
                                $n = $conteoKardex[$grupo] ?? 0;
                            @endphp
                            <button type="button" wire:click="toggleGrupo('{{ $grupo }}')" wire:key="grupo-{{ $grupo }}"
                                @class([
                                    'relative flex items-center gap-3 rounded-lg border p-3 text-left transition-all',
                                    'border-primary ring-2 ring-primary/30 bg-background' => $activo,
                                    'border-border bg-background hover:border-primary/50 opacity-80' => !$activo,
                                ])>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border"
                                    style="background-color: {{ $color ? '#' . $color : 'transparent' }}">
                                    @if ($activo)
                                        <i class="fa fa-check text-gray-700"></i>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold uppercase text-foreground">{{ $grupo }}</span>
                                    <span class="block text-xs text-muted-foreground">
                                        {{ $n }} kardex {{ $tipoKardex }} {{ $anio }}
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <x-input-error for="gruposSeleccionados" class="mt-1" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-button variant="secondary" wire:click="$set('mostrarFormularioInsumoKardexReporte', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                <x-button wire:click="guardarInsumoKardexReporte" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> {{ $reporteId ? 'Guardar cambios' : 'Registrar' }}
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading wire:target="guardarInsumoKardexReporte" />
</div>
