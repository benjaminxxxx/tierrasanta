{{-- Modal "Reasignar código" (LaborReasignarComponent): se abre con reasignarCodigoLabor --}}
<div>
    <x-dialog-modal maxWidth="2xl" wire:model="mostrar">
        <x-slot name="title">
            Reasignar código {{ $labor?->codigo }}
        </x-slot>

        <x-slot name="content">
            @if ($labor)
                @php $fmt = fn($f) => $f ? \Illuminate\Support\Carbon::parse($f)->format('d/m/Y') : null; @endphp
                <div class="rounded-lg border border-border p-3 text-sm space-y-1">
                    <p>
                        Hoy el código <b>{{ $labor->codigo }}</b> es <b>{{ $labor->nombre_labor }}</b>
                        ({{ $labor->manoObra?->descripcion ?? 'sin mano de obra' }}){{ $labor->trashed() ? ' — desactivada' : '' }}.
                    </p>
                    <p class="text-muted-foreground">
                        {{ $ultimoUso ? 'Último registro: ' . $fmt($ultimoUso) . ' (' . \Illuminate\Support\Carbon::parse($ultimoUso)->diffForHumans() . ').' : 'Nunca se ha registrado.' }}
                        Desde la fecha que elijas, el código será la nueva labor. Los registros anteriores se siguen viendo como
                        <b>{{ $labor->nombre_labor }}</b> en reportes y costos.
                    </p>
                </div>

                <form wire:submit="guardar" id="frmReasignarLabor" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <x-input type="date" wire:model="desde" label="Nueva labor desde" error="desde" />
                    <x-input wire:model="nombre_labor" label="Nombre de la nueva labor" error="nombre_labor" />
                    <x-select wire:model="codigo_mano_obra" label="Mano de obra (obligatoria)" error="codigo_mano_obra">
                        <option value="">Seleccione un grupo</option>
                        @foreach ($manoObras as $codigo => $descripcion)
                            <option value="{{ $codigo }}">{{ $descripcion }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="unidades" label="Unidades" placeholder="Ejem: Kg, Lavaderos" error="unidades" />
                    <x-input type="number" wire:model="estandar_produccion" label="Estándar de producción" error="estandar_produccion" />
                    <x-select wire:model="tipo_asistencia_codigo" label="Representa una asistencia" error="tipo_asistencia_codigo">
                        <option value="">No — es una labor de trabajo</option>
                        @foreach ($tiposAsistencia as $t)
                            <option value="{{ $t->codigo }}">{{ $t->codigo }} — {{ $t->descripcion }}</option>
                        @endforeach
                    </x-select>
                    <div class="md:col-span-2">
                        <x-input wire:model="motivo" label="Motivo (opcional)" placeholder="Ej.: la labor anterior ya no se hace" />
                    </div>
                    <p class="md:col-span-2 text-xs text-muted-foreground">Los tramos de bonificación no pasan a la nueva labor: si tiene, agrégalos luego con Editar.</p>
                </form>

                @if (count($historia) > 1)
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold mb-1">Historia del código {{ $labor->codigo }}</h4>
                        <ol class="text-xs space-y-1 border-l-2 border-border pl-3">
                            @foreach ($historia as $h)
                                <li>
                                    <b>{{ $h['nombre_labor'] }}</b>
                                    <span class="text-muted-foreground">
                                        {{ $h['desde'] ? 'desde ' . $fmt($h['desde']) : 'desde siempre' }}
                                        {{ $h['hasta'] ? 'hasta ' . $fmt($h['hasta']) : '(actual)' }}
                                        {{ $h['motivo'] ? '· ' . $h['motivo'] : '' }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">Cancelar</x-button>
            <x-button type="submit" form="frmReasignarLabor"><i class="fa fa-right-left"></i> Reasignar código</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading wire:target="guardar" />
</div>
