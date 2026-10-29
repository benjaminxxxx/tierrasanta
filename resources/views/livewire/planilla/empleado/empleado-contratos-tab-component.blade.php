{{-- Historial laboral (DESIGN.md): contratos con sus asignaciones y remuneraciones. Un cambio = una fila con "desde". --}}
@php
    $estados = [
        'activo' => ['Vigente', 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-400/10'],
        'en_prueba' => ['En prueba', 'text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-400/10'],
        'pendiente' => ['Por iniciar', 'text-blue-700 bg-blue-50 dark:text-blue-400 dark:bg-blue-400/10'],
        'finalizado' => ['Finalizado', 'text-graphite bg-fog'],
    ];
    $f = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
    $m = fn($v) => $v === null ? '—' : number_format((float) $v, 2);
    $btn2 = 'inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnLink = 'text-xs text-graphite underline-offset-4 hover:text-ink hover:underline';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
    {{-- Contratos --}}
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-semibold text-ink">Historial laboral</h3>
                <p class="text-xs text-pewter">Cada contrato con su cargo, grupo y remuneración. Para un cambio agrega una fila con su fecha: no hay que cerrar la anterior.</p>
            </div>
            <button type="button" wire:click="nuevoContrato" class="{{ $btn2 }}"><i class="fa fa-plus"></i> Nuevo contrato</button>
        </div>

        @forelse ($contratos as $contrato)
            @php [$estadoTexto, $estadoClase] = $estados[$contrato->estado] ?? $estados['activo']; @endphp
            <div wire:key="contrato-{{ $contrato->id }}" class="rounded-[10px] border border-mist bg-paper">
                {{-- Cabecera del contrato --}}
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-hairline p-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-ink tabular-nums">{{ $f($contrato->fecha_inicio) }} – {{ $contrato->fecha_fin ? $f($contrato->fecha_fin) : 'sin fin' }}</span>
                            <span class="rounded-[6px] px-2 py-0.5 text-xs font-medium {{ $estadoClase }}">{{ $estadoTexto }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-graphite">
                            {{ ucfirst($contrato->tipo_contrato) }} · Planilla {{ $contrato->tipo_planilla }}
                            · {{ $contrato->tipo_ingreso === 'honorarios' ? 'Recibo por honorarios' : 'Planilla (5ta)' }}
                            @if ($contrato->fecha_fin_prueba) · Prueba hasta {{ $f($contrato->fecha_fin_prueba) }} @endif
                            @if ($contrato->motivo_cese_sunat) · Cese: {{ \App\Services\Planilla\Empleado\EmpleadoPerfilServicio::MOTIVOS_CESE[$contrato->motivo_cese_sunat] ?? $contrato->motivo_cese_sunat }} @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="editarContrato({{ $contrato->id }})" class="{{ $btnLink }}">Editar</button>
                        @if ($contrato->fecha_fin)
                            <button type="button" wire:click="reabrirContrato({{ $contrato->id }})" wire:confirm="¿Quitar la fecha de fin de este contrato?" class="{{ $btnLink }}">Reabrir</button>
                        @else
                            <button type="button" wire:click="abrirFinalizar({{ $contrato->id }})" class="{{ $btnLink }}">Finalizar</button>
                        @endif
                        <button type="button" wire:click="eliminarContrato({{ $contrato->id }})"
                            wire:confirm="¿Eliminar este contrato? Úsalo solo si se registró por error." class="text-xs text-red-700 underline-offset-4 hover:underline dark:text-red-400">Eliminar</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2">
                    {{-- Asignaciones --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium uppercase text-graphite">Cargo y grupo</span>
                            <button type="button" wire:click="nuevaAsignacion({{ $contrato->id }})" class="{{ $btnLink }}"><i class="fa fa-plus"></i> Cambio</button>
                        </div>
                        <table class="w-full text-[13px]">
                            <thead class="bg-fog text-left text-xs text-graphite">
                                <tr><th class="px-2 py-1.5 font-medium">Desde</th><th class="px-2 py-1.5 font-medium">Cargo</th><th class="px-2 py-1.5 font-medium">Grupo</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse ($contrato->asignaciones->sortByDesc('desde') as $x)
                                    <tr wire:key="asig-{{ $x->id }}" class="border-t border-hairline">
                                        <td class="px-2 py-1.5 tabular-nums">{{ $f($x->desde) }}</td>
                                        <td class="px-2 py-1.5">{{ $x->cargo?->nombre ?? '—' }}</td>
                                        <td class="px-2 py-1.5">
                                            @if ($x->grupo)
                                                <span class="inline-block size-2 rounded-full border border-mist" style="background-color: {{ $x->grupo->color }}"></span>
                                            @endif
                                            {{ $x->grupo_codigo ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right">
                                            <button type="button" wire:click="editarAsignacion({{ $x->id }})" class="text-steel hover:text-ink" title="Editar"><i class="fa fa-pen"></i></button>
                                            @unless ($x->desde->eq($contrato->fecha_inicio))
                                                <button type="button" wire:click="eliminarAsignacion({{ $x->id }})" wire:confirm="¿Quitar este cambio?" class="ml-2 text-steel hover:text-red-700" title="Quitar"><i class="fa fa-xmark"></i></button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-2 py-2 text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Sin cargo ni grupo: agrégalo desde el inicio del contrato.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Remuneraciones --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium uppercase text-graphite">Remuneración</span>
                            <button type="button" wire:click="nuevaRemuneracion({{ $contrato->id }})" class="{{ $btnLink }}"><i class="fa fa-plus"></i> Cambio</button>
                        </div>
                        <table class="w-full text-[13px]">
                            <thead class="bg-fog text-left text-xs text-graphite">
                                <tr><th class="px-2 py-1.5 font-medium">Desde</th><th class="px-2 py-1.5 text-right font-medium">Sueldo</th><th class="px-2 py-1.5 text-right font-medium" title="Remuneración básica (opcional)">Básica</th><th class="px-2 py-1.5 text-right font-medium">Bonif.</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse ($contrato->remuneraciones->sortByDesc('desde') as $x)
                                    <tr wire:key="rem-{{ $x->id }}" class="border-t border-hairline" title="{{ ucfirst($x->modalidad_pago) }}{{ $x->beneficios_mensuales ? ' · beneficios cada mes' : '' }}{{ $x->compensacion_vacacional ? ' · comp. vacacional ' . $m($x->compensacion_vacacional) : '' }}{{ $x->motivo ? ' · ' . $x->motivo : '' }}">
                                        <td class="px-2 py-1.5 tabular-nums">{{ $f($x->desde) }}</td>
                                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $m($x->sueldo) }}</td>
                                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $m($x->remuneracion_basica) }}</td>
                                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $m($x->bonificacion) }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right">
                                            <button type="button" wire:click="editarRemuneracion({{ $x->id }})" class="text-steel hover:text-ink" title="Editar"><i class="fa fa-pen"></i></button>
                                            @unless ($x->desde->eq($contrato->fecha_inicio))
                                                <button type="button" wire:click="eliminarRemuneracion({{ $x->id }})" wire:confirm="¿Quitar este cambio?" class="ml-2 text-steel hover:text-red-700" title="Quitar"><i class="fa fa-xmark"></i></button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-2 py-2 text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Sin remuneración: agrégala desde el inicio del contrato.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Sustento del cese: contabilidad da de baja en el T-Registro solo con este documento --}}
                @if ($contrato->fecha_fin && $contrato->motivo_cese_sunat || $contrato->documentos->isNotEmpty())
                    @php
                        $faltante = $contrato->fecha_fin
                            ? \App\Services\Planilla\Laboral\PlanillaLaboralReglas::documentoCeseFaltante($contrato->motivo_cese_sunat, $contrato->documentos->pluck('tipo'))
                            : null;
                        $tiposDoc = \App\Services\Planilla\Laboral\PlanillaLaboralReglas::TIPOS_DOCUMENTO;
                    @endphp
                    <div class="border-t border-hairline px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-medium uppercase text-graphite">Sustento del cese</span>
                            <button type="button" wire:click="abrirDocumento({{ $contrato->id }})" class="{{ $btnLink }}"><i class="fa fa-paperclip"></i> Adjuntar carta de renuncia</button>
                        </div>
                        @if ($faltante)
                            <p class="mt-2 rounded-[6px] bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                <i class="fa fa-triangle-exclamation"></i> Falta la {{ mb_strtolower($tiposDoc[$faltante]) }}: sin ella contabilidad no puede dar de baja en el T-Registro.
                            </p>
                        @endif
                        @if ($contrato->documentos->isNotEmpty())
                            <ul class="mt-2 divide-y divide-hairline text-[13px]">
                                @foreach ($contrato->documentos as $d)
                                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-1.5" wire:key="doc-{{ $d->id }}">
                                        <i class="fa {{ str_contains((string) $d->mime, 'pdf') ? 'fa-file-pdf' : 'fa-file-image' }} text-steel"></i>
                                        <span class="font-medium text-ink">{{ $tiposDoc[$d->tipo] ?? $d->tipo }}</span>
                                        @if ($d->fecha_documento)
                                            <span class="tabular-nums text-graphite">del {{ $f($d->fecha_documento) }}</span>
                                        @endif
                                        <span class="truncate text-xs text-pewter" title="{{ $d->nombre_original }}">{{ \Illuminate\Support\Str::limit($d->nombre_original, 40) }}</span>
                                        @if ($d->observacion)
                                            <span class="text-xs text-pewter">· {{ $d->observacion }}</span>
                                        @endif
                                        <span class="ml-auto flex items-center gap-3">
                                            <button type="button" wire:click="descargarDocumento({{ $d->id }})" class="{{ $btnLink }}"><i class="fa fa-download"></i> Descargar</button>
                                            <button type="button" wire:click="eliminarDocumento({{ $d->id }})" wire:confirm="¿Eliminar este documento?" class="text-xs text-red-700 underline-offset-4 hover:underline dark:text-red-400">Eliminar</button>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-[10px] border border-dashed border-mist p-8 text-center text-sm text-pewter">
                Este empleado no tiene contratos. Usa "Nuevo contrato".
            </div>
        @endforelse
    </div>

    {{-- Panel de edición --}}
    <div>
        @if ($panel)
            <div class="space-y-4 rounded-[10px] border border-mist bg-paper p-4 xl:sticky xl:top-2">
                @if ($panel === 'contrato')
                    <h4 class="text-base font-semibold text-ink">{{ $contratoId ? 'Editar contrato' : 'Nuevo contrato' }}</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <x-fecha label="Inicio *" wire:model="c.fecha_inicio" />
                        <x-fecha label="Fin" wire:model="c.fecha_fin" help="Vacío = indefinido" />
                        <x-select label="Tipo" wire:model="c.tipo_contrato">
                            @foreach (\App\Services\Planilla\Laboral\PlanillaLaboralReglas::TIPOS_CONTRATO as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </x-select>
                        <x-select label="Planilla" wire:model="c.tipo_planilla">
                            <option value="agraria">Agraria</option>
                            <option value="oficina">Oficina</option>
                            <option value="externa">Externa (solo registro)</option>
                        </x-select>
                        <x-select label="Ingreso" wire:model="c.tipo_ingreso">
                            <option value="planilla">Planilla (5ta)</option>
                            <option value="honorarios">Honorarios (4ta)</option>
                        </x-select>
                        <x-fecha label="Fin de prueba" wire:model="c.fecha_fin_prueba" />
                    </div>
                    @unless ($contratoId)
                        <p class="text-xs text-pewter">Cargo, grupo y remuneración desde el inicio del contrato (se sugiere lo último que tuvo):</p>
                        @include('livewire.planilla.empleado.partials.form-asignacion', ['conFecha' => false])
                        @include('livewire.planilla.empleado.partials.form-remuneracion', ['conFecha' => false])
                    @endunless
                    <p class="text-xs text-pewter">Pasar a otra planilla (por ejemplo, de agraria a seguridad) es un contrato nuevo. Una prórroga es cambiar la fecha de fin.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cerrarPanel" class="{{ $btn2 }}">Cancelar</button>
                        <button type="button" wire:click="guardarContrato" class="{{ $btnPrincipal }}"><i class="fa fa-check"></i> {{ $contratoId ? 'Guardar' : 'Crear contrato' }}</button>
                    </div>
                @elseif ($panel === 'asignacion')
                    <h4 class="text-base font-semibold text-ink">{{ $particulaId ? 'Editar cargo y grupo' : 'Cambio de cargo o grupo' }}</h4>
                    <p class="text-xs text-pewter">Contrato {{ $f($contratoPanel?->fecha_inicio) }} – {{ $contratoPanel?->fecha_fin ? $f($contratoPanel->fecha_fin) : 'sin fin' }}. Rige desde la fecha hasta el siguiente cambio.</p>
                    @include('livewire.planilla.empleado.partials.form-asignacion', ['conFecha' => true])
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cerrarPanel" class="{{ $btn2 }}">Cancelar</button>
                        <button type="button" wire:click="guardarAsignacion" class="{{ $btnPrincipal }}"><i class="fa fa-check"></i> Guardar</button>
                    </div>
                @elseif ($panel === 'remuneracion')
                    <h4 class="text-base font-semibold text-ink">{{ $particulaId ? 'Editar remuneración' : 'Cambio de remuneración' }}</h4>
                    <p class="text-xs text-pewter">Contrato {{ $f($contratoPanel?->fecha_inicio) }} – {{ $contratoPanel?->fecha_fin ? $f($contratoPanel->fecha_fin) : 'sin fin' }}. La fila es completa: lo que dejes vacío queda vacío.</p>
                    @include('livewire.planilla.empleado.partials.form-remuneracion', ['conFecha' => true])
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cerrarPanel" class="{{ $btn2 }}">Cancelar</button>
                        <button type="button" wire:click="guardarRemuneracion" class="{{ $btnPrincipal }}"><i class="fa fa-check"></i> Guardar</button>
                    </div>
                @elseif ($panel === 'finalizar')
                    <h4 class="text-base font-semibold text-ink">Finalizar contrato</h4>
                    <x-fecha label="Fecha de fin *" wire:model="cierre.fecha_fin" />
                    <x-select label="Motivo de cese (SUNAT) *" wire:model="cierre.motivo_cese_sunat">
                        <option value="">Seleccionar…</option>
                        @foreach (\App\Services\Planilla\Empleado\EmpleadoPerfilServicio::MOTIVOS_CESE as $codigo => $texto)
                            <option value="{{ $codigo }}">{{ $texto }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Comentario" wire:model="cierre.comentario_cese" />
                    <div>
                        <x-label value="Carta de renuncia / acta de despido" />
                        @include('livewire.planilla.empleado.partials.input-documento')
                        <p class="mt-1 text-xs text-pewter">Opcional ahora: si aún no la tienes, se puede adjuntar después y quedará en tareas pendientes.</p>
                    </div>
                    <p class="text-xs text-pewter">Sus datos y su usuario del sistema (si tiene) se conservan para una recontratación.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cerrarPanel" class="{{ $btn2 }}">Cancelar</button>
                        <button type="button" wire:click="confirmarFinalizar" class="{{ $btnPrincipal }}" wire:loading.attr="disabled" wire:target="archivo,confirmarFinalizar"><i class="fa fa-check"></i> Finalizar</button>
                    </div>
                @elseif ($panel === 'documento')
                    <h4 class="text-base font-semibold text-ink">Adjuntar carta de renuncia</h4>
                    <p class="text-xs text-pewter">
                        Contrato {{ $f($contratoPanel?->fecha_inicio) }} – {{ $contratoPanel?->fecha_fin ? $f($contratoPanel->fecha_fin) : 'sin fin' }}.
                        Escaneo o foto legible del documento firmado.
                    </p>
                    <x-select label="Tipo *" wire:model="doc.tipo">
                        @foreach (\App\Services\Planilla\Laboral\PlanillaLaboralReglas::TIPOS_DOCUMENTO as $codigo => $texto)
                            <option value="{{ $codigo }}">{{ $texto }}</option>
                        @endforeach
                    </x-select>
                    <x-fecha label="Fecha del documento" wire:model="doc.fecha_documento" help="La que figura en la carta o el acta" />
                    <div>
                        <x-label value="Archivo *" />
                        @include('livewire.planilla.empleado.partials.input-documento')
                    </div>
                    <x-input label="Observación" wire:model="doc.observacion" />
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cerrarPanel" class="{{ $btn2 }}">Cancelar</button>
                        <button type="button" wire:click="guardarDocumento" class="{{ $btnPrincipal }}" wire:loading.attr="disabled" wire:target="archivo,guardarDocumento"><i class="fa fa-paperclip"></i> Adjuntar</button>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-[10px] border border-dashed border-mist p-6 text-sm text-pewter">
                Elige "Nuevo contrato", "Cambio" o edita una fila para verla aquí.
            </div>
        @endif
    </div>

    <x-loading wire:loading wire:target="guardarContrato,guardarAsignacion,guardarRemuneracion,confirmarFinalizar,reabrirContrato,eliminarContrato" />
</div>
