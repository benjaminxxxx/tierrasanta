<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="complete">
        <x-slot name="title">
            @if ($campaniaId)
                Campaña {{ $resumen['nombre'] }} · campo {{ $resumen['campo'] }}
            @else
                Registrar nueva campaña
            @endif
        </x-slot>

        <x-slot name="content">
            @if (!$campaniaId)
                {{-- ======================================================== REGISTRO (wizard) --}}
                <ol class="flex flex-wrap items-center gap-2 mb-6 text-sm">
                    @foreach (\App\Livewire\Campania\CampaniaFichaComponent::PASOS as $n => $etiqueta)
                        <li class="flex items-center gap-2">
                            <span @class([
                                'w-7 h-7 rounded-full flex items-center justify-center font-bold',
                                'bg-primary text-primary-foreground' => $paso === $n,
                                'bg-green-600 text-white' => $paso > $n,
                                'bg-muted text-muted-foreground' => $paso < $n,
                            ])>{!! $paso > $n ? '<i class="fa fa-check"></i>' : $n !!}</span>
                            <span @class(['font-semibold' => $paso === $n, 'text-muted-foreground' => $paso !== $n])>{{ $etiqueta }}</span>
                            @if (!$loop->last)
                                <span class="w-8 border-t border-border"></span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($paso === 1)
                    <div class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Elige el campo. Una campaña empieza cuando la anterior se cerró (al terminar su cosecha).
                        </p>
                        <x-select-campo wire:model.live="campo" label="Campo" class="w-full md:w-80" />

                        @if ($contexto)
                            <p class="text-sm">Área del campo: <b>{{ $contexto['area'] ?? '—' }} ha</b></p>

                            @if ($contexto['ultimas'])
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm border border-border rounded">
                                        <thead class="bg-muted text-xs uppercase">
                                            <x-tr>
                                                <x-th>Últimas campañas</x-th>
                                                <x-th>Inicio</x-th>
                                                <x-th>Cierre</x-th>
                                                <x-th>Cosecha</x-th>
                                            </x-tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($contexto['ultimas'] as $c)
                                                <x-tr>
                                                    <x-td class="font-semibold">{{ $c['nombre'] }}</x-td>
                                                    <x-td class="text-center">{{ formatear_fecha($c['inicio']) }}</x-td>
                                                    <x-td class="text-center">
                                                        @if ($c['fin'])
                                                            {{ formatear_fecha($c['fin']) }}
                                                        @else
                                                            <x-badge color="green">Vigente</x-badge>
                                                        @endif
                                                    </x-td>
                                                    <x-td>@include('livewire.campania.partials.campanias-estado-cosecha', ['estado' => $c['estado']])</x-td>
                                                </x-tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <x-success>Este campo no tiene campañas: será la primera.</x-success>
                            @endif

                            @if ($contexto['vigente'])
                                @php $vigente = $contexto['vigente']; @endphp
                                <x-warning>
                                    <div class="space-y-3 text-sm">
                                        <p>
                                            La campaña <b>{{ $vigente['nombre'] }}</b> (desde {{ formatear_fecha($vigente['inicio']) }})
                                            sigue abierta. Antes de registrar la siguiente hay que cerrarla.
                                            @if ($vigente['estado']['fecha_cierre_sugerida'] ?? null)
                                                Su cosecha terminó el {{ formatear_fecha($vigente['estado']['ultima_cosecha']) }}.
                                            @endif
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <x-button variant="danger" wire:click="abrirCierreVigente">
                                                <i class="fa fa-lock"></i> Cerrar campaña vigente
                                            </x-button>
                                            <x-button variant="secondary" wire:click="elegirOtroCampo">
                                                Elegir otro campo
                                            </x-button>
                                        </div>
                                        <p class="text-xs">
                                            ¿La cerraste en otra pestaña?
                                            <a class="underline" target="_blank"
                                                href="{{ route('campania.resumen', ['cerrar' => $vigente['id']]) }}">Abrir el cierre en otra pestaña</a>
                                            ·
                                            <button type="button" class="underline font-semibold" wire:click="yaCerre">Ya cerré la campaña, continuar</button>
                                        </p>
                                    </div>
                                </x-warning>
                            @else
                                <x-flex class="justify-end">
                                    <x-button wire:click="irAFechas">Continuar <i class="fa fa-arrow-right"></i></x-button>
                                </x-flex>
                            @endif
                        @endif
                    </div>
                @elseif ($paso === 2)
                    <div class="space-y-4 max-w-3xl">
                        <p class="text-sm text-muted-foreground">
                            Indica cuándo empieza la campaña en el campo <b>{{ $campo }}</b>.
                            Si registras una campaña pasada que ya terminó, indica también su cierre: se creará cerrada.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input type="date" wire:model.live="fechaInicio" label="Fecha de inicio"
                                help="{{ $contexto['sugerido']['fecha_inicio'] ? 'Sugerida: el día siguiente al cierre de la anterior (' . formatear_fecha($contexto['sugerido']['fecha_inicio']) . ').' : '' }}" />
                            <x-input type="date" wire:model.live="fechaFin" label="Fecha de cierre (opcional)"
                                help="Vacía = campaña vigente." />
                        </div>
                        @error('fechaInicio') <x-danger>{{ $message }}</x-danger> @enderror
                        @error('fechaFin') <x-danger>{{ $message }}</x-danger> @enderror
                        @error('campania.fecha_inicio') <x-danger>{{ $message }}</x-danger> @enderror

                        @include('livewire.campania.ficha.huecos')

                        <x-flex class="justify-between">
                            <x-button variant="secondary" wire:click="volver"><i class="fa fa-arrow-left"></i> Volver</x-button>
                            <x-button wire:click="revisarFechasNuevas" target="revisarFechasNuevas">
                                Continuar <i class="fa fa-arrow-right"></i>
                            </x-button>
                        </x-flex>
                    </div>
                @else
                    <div class="space-y-4 max-w-3xl">
                        <x-success>
                            Campo <b>{{ $campo }}</b> · desde <b>{{ formatear_fecha($fechaInicio) }}</b>
                            {{ $fechaFin ? 'hasta ' . formatear_fecha($fechaFin) . ' (se creará cerrada)' : '(vigente)' }}
                        </x-success>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input type="number" wire:model="campania.area" error="campania.area" label="Área (ha)"
                                help="Área del campo: {{ $contexto['area'] ?? '—' }} ha. Cámbiala si solo se trabajará una parte." />
                            <x-input type="text" wire:model="campania.nombre_campania" error="campania.nombre_campania"
                                label="Nombre de la campaña" help="Sugerido a partir de la campaña anterior." />
                            <x-select wire:model="campania.variedad_tuna" error="campania.variedad_tuna" label="Variedad de tuna">
                                <option value="">—</option>
                                <option value="Tuna Blanca">Tuna Blanca</option>
                                <option value="Tuna Roja">Tuna Roja</option>
                            </x-select>
                        </div>
                        <x-flex class="justify-between">
                            <x-button variant="secondary" wire:click="volver"><i class="fa fa-arrow-left"></i> Volver</x-button>
                            <x-button variant="success" wire:click="registrar" target="registrar">
                                <i class="fa fa-save"></i> Registrar campaña
                            </x-button>
                        </x-flex>
                    </div>
                @endif
            @else
                {{-- ======================================================== EDICIÓN (pestañas) --}}
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3 rounded-lg border p-3 text-sm">
                    <div class="space-y-1">
                        <p>
                            <b>Fechas:</b> {{ formatear_fecha($resumen['inicio']) }} –
                            @if ($resumen['fin'])
                                {{ formatear_fecha($resumen['fin']) }}
                            @else
                                <x-badge color="green">Vigente</x-badge>
                            @endif
                            · <b>Área del campo:</b> {{ $resumen['area_campo'] ?? '—' }} ha
                        </p>
                        @include('livewire.campania.partials.campanias-estado-cosecha', ['estado' => $resumen['estado']])
                    </div>
                    @if (!$resumen['fin'])
                        <x-button size="sm" variant="danger" wire:click="abrirCierre">
                            <i class="fa fa-lock"></i> Cerrar campaña
                        </x-button>
                    @endif
                </div>

                <div class="flex flex-col lg:flex-row gap-6 items-start">
                    <nav class="flex lg:flex-col gap-1 shrink-0 lg:w-52 overflow-x-auto w-full">
                        @foreach (\App\Livewire\Campania\CampaniaFichaComponent::TABS as $clave => $etiqueta)
                            <button type="button" wire:click="$set('tab', '{{ $clave }}')" @class([
                                'px-4 py-2 rounded-md text-left whitespace-nowrap transition font-semibold text-sm',
                                'bg-muted text-foreground' => $tab === $clave,
                                'text-muted-foreground hover:text-foreground' => $tab !== $clave,
                            ])>{{ $etiqueta }}</button>
                        @endforeach
                    </nav>

                    <div class="flex-1 min-w-0 w-full">
                        @if ($tab === 'general')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <x-input type="text" wire:model="campania.nombre_campania" error="campania.nombre_campania" label="Nombre de la campaña" />
                                <x-input type="number" wire:model="campania.area" error="campania.area" label="Área (ha)"
                                    help="Si solo se trabaja una parte del campo." />
                                <x-input type="text" wire:model="campania.variedad_tuna" error="campania.variedad_tuna" label="Variedad de tuna" />
                                <x-input type="text" wire:model="campania.sistema_cultivo" error="campania.sistema_cultivo" label="Sistema de cultivo" />
                                <x-input type="number" wire:model="campania.pencas_x_hectarea" error="campania.pencas_x_hectarea" label="Pencas por hectárea" />
                                <x-input type="number" wire:model="campania.tipo_cambio" error="campania.tipo_cambio" label="Tipo de cambio" />
                            </div>
                            <x-flex class="justify-end mt-4">
                                <x-button wire:click="guardarGeneral" target="guardarGeneral"><i class="fa fa-save"></i> Guardar</x-button>
                            </x-flex>
                        @elseif ($tab === 'fechas')
                            <div class="space-y-4 max-w-3xl">
                                <p class="text-sm text-muted-foreground">
                                    Cambiar las fechas mueve costos y registros entre campañas. Se revisa que no se crucen con otra
                                    campaña ni dejen días con actividades sin campaña, y se muestra el impacto antes de guardar.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <x-input type="date" wire:model.live="fechaInicio" label="Fecha de inicio" />
                                    <x-input type="date" wire:model.live="fechaFin" label="Fecha de cierre" help="Vacía = campaña vigente." />
                                </div>
                                @error('fechaInicio') <x-danger>{{ $message }}</x-danger> @enderror
                                @error('fechaFin') <x-danger>{{ $message }}</x-danger> @enderror
                                @error('campania.fecha_inicio') <x-danger>{{ $message }}</x-danger> @enderror
                                @error('campania.fecha_fin') <x-danger>{{ $message }}</x-danger> @enderror

                                @include('livewire.campania.ficha.huecos')

                                @if ($avisosFechas)
                                    <x-warning>
                                        <div>
                                            <p class="font-semibold">
                                                Nuevas fechas: {{ formatear_fecha($fechaInicio) }} – {{ $fechaFin ? formatear_fecha($fechaFin) : 'vigente' }}
                                            </p>
                                            <ul class="list-disc list-inside text-sm mt-1 space-y-0.5">
                                                @foreach ($avisosFechas as $aviso)
                                                    <li>{{ $aviso }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </x-warning>
                                    <x-flex class="justify-end">
                                        <x-button variant="secondary" wire:click="$set('avisosFechas', null)">Revisar de nuevo</x-button>
                                        <x-button variant="warning" wire:click="confirmarCambioFechas" target="confirmarCambioFechas">
                                            <i class="fa fa-check"></i> Confirmar y guardar
                                        </x-button>
                                    </x-flex>
                                @else
                                    <x-flex class="justify-end">
                                        <x-button wire:click="revisarCambioFechas" target="revisarCambioFechas">Revisar cambios</x-button>
                                    </x-flex>
                                @endif
                            </div>
                        @else
                            <div x-data="{ tabActual: @js($tab) }">
                                @include('livewire.campania.ficha.' . $tab)
                            </div>
                            <x-flex class="justify-end mt-4">
                                <x-button wire:click="guardarDetalle" target="guardarDetalle"><i class="fa fa-save"></i> Guardar</x-button>
                            </x-flex>
                        @endif

                        @if ($errors->any() && !in_array($tab, ['fechas']))
                            <x-danger class="mt-4">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </x-danger>
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
