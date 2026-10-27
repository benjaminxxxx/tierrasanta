{{-- Modal "Suspensiones de un trabajador" (Permisos y suspensiones y registro diario). --}}
<div>
<x-dialog-modal wire:model.live="modal" maxWidth="full">
    <x-slot name="title">
        {{ $nombreModal ? 'Suspensiones de ' . $nombreModal : 'Agregar trabajador con suspensión' }}
    </x-slot>
    <x-slot name="content">
        <div class="space-y-3">
            @if (!$idsEnModal)
                <div class="w-96 max-w-full" wire:key="sel-emp-{{ $modal ? 'abierto' : 'cerrado' }}">
                    <x-label value="Trabajador (con contrato en el mes)" />
                    <x-searchable-select :options="$empleadosMes" placeholder="Selecciona un trabajador"
                        search-placeholder="Nombre del trabajador..." wire:model.live="modalEmpleadoId" />
                    <x-input-error for="modalEmpleadoId" />
                </div>
            @endif
            <p class="text-xs text-muted-foreground">
                Rangos del mes y los ya programados para después. Puedes mover el límite entre dos rangos seguidos en un solo guardado.
                Los rangos anteriores al mes no se muestran (búscalos en Estadísticas → trabajador).
            </p>

            <div class="space-y-2">
                @foreach ($rangos as $i => $r)
                    <div class="grid grid-cols-12 gap-2 items-start" wire:key="rango-{{ $i }}-{{ $r['id'] ?? 'n' }}">
                        <div class="col-span-12 md:col-span-4">
                            <x-select wire:model="rangos.{{ $i }}.tipo_suspension_id" error="rangos.{{ $i }}.tipo_suspension_id">
                                <option value="">Tipo de suspensión</option>
                                @foreach ($tipos as $t)
                                    <option value="{{ $t['id'] }}">{{ $t['label'] }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <x-input type="date" wire:model="rangos.{{ $i }}.fecha_inicio" error="rangos.{{ $i }}.fecha_inicio" />
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <x-input type="date" wire:model="rangos.{{ $i }}.fecha_fin" error="rangos.{{ $i }}.fecha_fin" />
                        </div>
                        <div class="col-span-10 md:col-span-3">
                            <x-input wire:model="rangos.{{ $i }}.observaciones" placeholder="Observación" />
                        </div>
                        <div class="col-span-2 md:col-span-1 text-right">
                            <x-button size="sm" variant="ghost" wire:click="quitarRango({{ $i }})" title="Quitar rango">
                                <i class="fa fa-trash text-red-600"></i>
                            </x-button>
                        </div>
                        @foreach (['tipo_suspension_id', 'fecha_inicio', 'fecha_fin'] as $campo)
                            @error("rangos.{$i}.{$campo}") <p class="col-span-12 text-xs text-red-600 -mt-1">{{ $message }}</p> @enderror
                        @endforeach
                    </div>
                @endforeach
            </div>
            @error('rangos') <x-warning>{{ $message }}</x-warning> @enderror

            <x-button variant="secondary" wire:click="agregarRango"><i class="fa fa-plus"></i> Agregar rango</x-button>
        </div>
    </x-slot>
    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('modal', false)">Cancelar</x-button>
        <x-button wire:click="guardarRangos"><i class="fa fa-save"></i> Guardar</x-button>
    </x-slot>
</x-dialog-modal>
    <x-loading wire:loading wire:target="guardarRangos,agregarRango,quitarRango,modalEmpleadoId" />
</div>
