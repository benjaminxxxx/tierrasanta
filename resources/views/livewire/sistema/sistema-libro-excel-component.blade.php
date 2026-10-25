<div>
    @if ($visible)
        @if ($escritorio)
            {{-- Sin botón: lo abre el escritorio de archivos del inicio (evento abrirLibroExcel) --}}
        @elseif ($flotante)
            {{-- Botón flotante (DESIGN.md: superficie Papel con borde fino; flota, así que lleva sombra suave) --}}
            <button type="button" wire:click="abrirImportar"
                class="fixed bottom-6 right-6 z-[900] flex h-11 items-center gap-2 rounded-full border border-mist bg-paper px-4 text-sm font-medium text-ink
                       shadow-[0_8px_24px_rgb(16_33_45/0.12)] hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25"
                title="Descargar o subir {{ $titulos ? implode(' / ', $titulos) : mb_strtolower($tituloLibro) }} en Excel">
                <i class="fa fa-file-excel text-green-600"></i> {{ $libro === 'recursos' ? 'Recursos' : $tituloLibro }}
            </button>
        @else
            <x-card class="flex flex-wrap items-end gap-3 max-w-3xl">
                <div class="flex-1 min-w-[16rem]">
                    <h3 class="font-semibold text-foreground"><i class="fa fa-file-excel text-green-600"></i> {{ $tituloLibro }}</h3>
                    <p class="text-sm text-muted-foreground">
                        Descárgalo, corrígelo y súbelo: verás qué cambia antes de guardar. Sirve para mantener iguales los dos sistemas. Desde la página de
                        cada catálogo (botón "Recursos") se trabaja solo con su hoja, y desde el inicio con el escritorio de archivos.
                    </p>
                </div>
                <x-button variant="secondary" wire:click="exportar"><i class="fa fa-download"></i> Descargar</x-button>
                <x-button wire:click="abrirImportar"><i class="fa fa-upload"></i> Subir Excel</x-button>
            </x-card>
        @endif

        <x-dialog-modal wire:model.live="mostrarImportar" maxWidth="full">
            <x-slot name="title">{{ $flotante && $titulos ? 'Recursos en Excel: ' . implode(', ', $titulos) : ($flotante ? $tituloLibro : 'Subir ' . mb_strtolower($tituloLibro)) }}</x-slot>
            <x-slot name="content">
                <div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
                    @if ($flotante)
                        {{-- Dos pasos: primero descargar lo que hay (así no se pisa nada con datos viejos), luego subir --}}
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-[10px] border border-mist p-4">
                                <p class="text-sm font-semibold text-ink"><span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-fog text-xs">1</span> Descarga lo que hay</p>
                                <p class="mt-1 text-xs text-pewter">
                                    Antes de importar, baja el Excel con los datos actuales del sistema y trabaja sobre ese archivo. Así no se reemplaza nada con
                                    información vieja. Solo trae {{ implode(' y ', $titulos) }}.
                                </p>
                                <button type="button" wire:click="exportar"
                                    class="mt-3 h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink hover:bg-fog">
                                    <i class="fa fa-download"></i> Descargar {{ $nombreArchivo }}
                                </button>
                            </div>
                            <div class="rounded-[10px] border border-mist p-4">
                                <p class="text-sm font-semibold text-ink"><span class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-fog text-xs">2</span> Sube el archivo corregido</p>
                                <p class="mt-1 text-xs text-pewter">Verás qué es nuevo, qué cambia y qué tiene errores antes de guardar. Del archivo solo se lee {{ implode(' y ', $titulos) }}.</p>
                                <input type="file" wire:model="archivo" accept=".xlsx,.xlsm" class="mt-3 block text-sm file:mr-3 file:h-9 file:cursor-pointer file:rounded-[10px] file:border file:border-solid file:border-mist file:bg-paper file:px-3 file:text-sm file:font-medium file:text-ink hover:file:bg-fog text-graphite" />
                                <x-input-error for="archivo" />
                            </div>
                        </div>
                    @elseif (!$escritorio)
                        <div>
                            <x-label value="Archivo ({{ $nombreArchivo }})" />
                            <input type="file" wire:model="archivo" accept=".xlsx,.xlsm" class="block text-sm file:mr-3 file:h-9 file:cursor-pointer file:rounded-[10px] file:border file:border-solid file:border-mist file:bg-paper file:px-3 file:text-sm file:font-medium file:text-ink hover:file:bg-fog text-graphite" />
                            <x-input-error for="archivo" />
                        </div>
                    @endif
                    <span wire:loading wire:target="archivo" class="text-sm text-muted-foreground"><i class="fa fa-spinner fa-spin"></i> Leyendo y comparando con el sistema…</span>

                    @if ($analisis)
                        @php
                            $etiquetas = ['error' => ['Con errores', 'text-red-600'], 'cambia' => ['Con cambios', 'text-amber-600'], 'nuevo' => ['Nuevos', 'text-green-600'], 'igual' => ['Sin cambios', 'text-muted-foreground']];
                            $orden = ['error' => 0, 'cambia' => 1, 'nuevo' => 2, 'igual' => 3];
                            $pestanias = collect($analisis['hojas'])->map(fn($h) => ['titulo' => $h['titulo'], 'resumen' => $h['resumen']]);
                            if ($analisis['matriz']) {
                                $pestanias['matriz'] = ['titulo' => $analisis['matriz']['titulo'], 'resumen' => $analisis['matriz']['resumen']];
                            }
                        @endphp
                        @if (($analisis['version']['estado'] ?? '') === 'otro_sistema')
                            <div class="flex gap-3 rounded-[10px] bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:bg-blue-400/10 dark:text-blue-300">
                                <i class="fa fa-right-left mt-0.5"></i>
                                <p>Este archivo se descargó de <b>otro sistema</b>. Revisa la vista previa: se guardan las filas nuevas o con cambios respecto a los datos de este sistema.</p>
                            </div>
                        @elseif (($analisis['version']['estado'] ?? '') !== 'al_dia')
                            {{-- Marca de versión: solo se guarda un archivo descargado con los datos actuales --}}
                            <div class="flex gap-3 rounded-[10px] bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-400/10 dark:text-red-300">
                                <i class="fa fa-clock-rotate-left mt-0.5"></i>
                                <div>
                                    @if (($analisis['version']['estado'] ?? '') === 'desactualizada')
                                        <p class="font-semibold">Los datos cambiaron en el sistema después de que descargaste este archivo
                                            ({{ \Illuminate\Support\Carbon::parse($analisis['version']['descargado'])->format('d/m/Y H:i') }}).</p>
                                    @else
                                        <p class="font-semibold">Este archivo no se descargó del sistema (o es una versión antigua).</p>
                                    @endif
                                    <p class="text-xs">Puedes revisar la vista previa, pero para guardar descarga el Excel de nuevo, pasa tus cambios a ese archivo y vuelve a subirlo.</p>
                                </div>
                            </div>
                        @endif
                        @foreach ($analisis['avisos'] as $a)
                            <p class="text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-exclamation-triangle"></i> {{ $a }}</p>
                        @endforeach
                        @if (!empty($analisis['faltantes']))
                            {{-- Orden de carga: lo referido (mano de obra, tipos de suspensión, roles…) tiene que existir antes --}}
                            <div class="flex gap-3 rounded-[10px] bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                                <i class="fa fa-triangle-exclamation mt-0.5"></i>
                                <div>
                                    <p class="font-semibold">Primero hay que cargar {{ implode(' y ', array_keys($analisis['faltantes'])) }}.</p>
                                    @foreach ($analisis['faltantes'] as $hojaFalta => $valores)
                                        <p class="text-xs">{{ $hojaFalta }}: faltan {{ count($valores) }} ({{ \Illuminate\Support\Str::limit(implode(', ', $valores), 160) }}).</p>
                                    @endforeach
                                    <p class="mt-1 text-xs">
                                        Súbelo desde su página (botón "Recursos") o carga el Excel completo en Sistema → Configuración, que guarda las hojas en el orden correcto.
                                        Las filas que dependen de eso no se guardan hasta entonces.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-2">
                            @foreach ($pestanias as $clave => $p)
                                <button type="button" wire:click="$set('pestania', '{{ $clave }}')"
                                    class="text-left rounded-lg border p-2 bg-card hover:bg-muted {{ $pestania === $clave ? 'ring-2 ring-primary' : '' }}">
                                    <p class="text-xs font-semibold">{{ $p['titulo'] }}</p>
                                    <p class="text-xs">
                                        @foreach ($etiquetas as $estado => [$texto, $color])
                                            @if (!empty($p['resumen'][$estado]))
                                                <span class="{{ $color }} mr-1"><b>{{ $p['resumen'][$estado] }}</b> {{ mb_strtolower($texto) }}</span>
                                            @endif
                                        @endforeach
                                        @if (empty($p['resumen']))
                                            <span class="text-muted-foreground">sin filas</span>
                                        @endif
                                    </p>
                                </button>
                            @endforeach
                        </div>

                        <div class="max-h-[50vh] overflow-y-auto">
                            @if ($pestania === 'matriz' && $analisis['matriz'])
                                @php $r = $analisis['matriz']; @endphp
                                @foreach ($r['avisos'] as $a)
                                    <p class="text-xs text-muted-foreground">{{ $a }}</p>
                                @endforeach
                                <table class="w-full text-sm mt-2">
                                    <thead class="text-xs text-muted-foreground bg-muted sticky top-0"><tr><th class="text-left px-2 py-1">Estado</th><th class="text-left">{{ $r['etiquetas']['nombre'] ?? 'Columna' }}</th><th class="text-right">{{ $r['etiquetas']['marcas'] ?? 'Marcas' }}</th><th class="text-left px-2">Detalle</th></tr></thead>
                                    <tbody>
                                        @foreach (collect($r['filas'])->sortBy(fn($f) => $orden[$f['estado']]) as $f)
                                            <tr class="border-t border-border align-top {{ $f['estado'] === 'igual' ? 'opacity-50' : '' }}">
                                                <td class="px-2 py-1"><span class="{{ $etiquetas[$f['estado']][1] }} text-xs font-semibold">{{ $etiquetas[$f['estado']][0] }}</span></td>
                                                <td class="font-semibold">{{ $f['nombre'] }}</td>
                                                <td class="text-right">{{ count($f['marcas']) }}</td>
                                                <td class="text-xs px-2">
                                                    @if ($f['estado'] === 'cambia')
                                                        @if ($f['agrega'])<p class="text-green-700 dark:text-green-400">Agrega: {{ implode(', ', $f['agrega']) }}</p>@endif
                                                        @if ($f['quita'])<p class="text-red-600">Quita: {{ implode(', ', $f['quita']) }}</p>@endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($r['solo_sistema'])
                                    <p class="text-xs text-muted-foreground mt-2">Solo en el sistema (no se tocan): {{ implode(', ', $r['solo_sistema']) }}</p>
                                @endif
                            @elseif ($pestania && isset($analisis['hojas'][$pestania]))
                                @php $h = $analisis['hojas'][$pestania]; @endphp
                                <table class="w-full text-sm">
                                    <thead class="text-xs text-muted-foreground bg-muted sticky top-0"><tr><th class="text-left px-2 py-1">Estado</th><th class="text-right px-2">Fila</th><th class="text-left">Identificación</th><th class="text-left px-2">Detalle</th></tr></thead>
                                    <tbody>
                                        @foreach (collect($h['filas'])->sortBy(fn($f) => $orden[$f['estado']]) as $f)
                                            <tr class="border-t border-border align-top {{ $f['estado'] === 'igual' ? 'opacity-50' : '' }}">
                                                <td class="px-2 py-1"><span class="{{ $etiquetas[$f['estado']][1] }} text-xs font-semibold">{{ $etiquetas[$f['estado']][0] }}</span></td>
                                                <td class="text-right px-2 text-muted-foreground">{{ $f['fila'] }}</td>
                                                <td class="font-semibold">{{ $f['clave'] }}</td>
                                                <td class="text-xs px-2">
                                                    @foreach ($f['errores'] as $e)<p class="text-red-600">{{ $e }}</p>@endforeach
                                                    @foreach ($f['cambios'] as $c)<p class="text-amber-700 dark:text-amber-400">{{ $c }}</p>@endforeach
                                                    @foreach ($f['avisos'] as $a)<p class="text-muted-foreground">{{ $a }}</p>@endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($h['solo_sistema'])
                                    <p class="text-xs text-muted-foreground mt-2"><b>{{ count($h['solo_sistema']) }}</b> en el sistema y no en el Excel (no se borran):
                                        {{ implode(', ', array_slice($h['solo_sistema'], 0, 50)) }}{{ count($h['solo_sistema']) > 50 ? '…' : '' }}</p>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </x-slot>
            <x-slot name="footer">
                @php $aplicar = $analisis ? ($analisis['resumen']['nuevo'] ?? 0) + ($analisis['resumen']['cambia'] ?? 0) : 0; @endphp
                <div class="flex gap-2 items-center">
                    @if ($analisis && !empty($analisis['bloqueado']))
                        <span class="text-xs text-red-600 mr-2"><i class="fa fa-ban"></i> Este libro solo se guarda completo: corrige las filas con errores en el Excel y vuelve a subirlo.</span>
                    @elseif ($analisis)
                        <span class="text-xs text-muted-foreground mr-2">Las filas con errores no se guardan: corrígelas en el Excel y vuelve a subirlo.</span>
                    @endif
                    <x-button variant="secondary" wire:click="$set('mostrarImportar', false)">Cancelar</x-button>
                    @if ($analisis && $aplicar && empty($analisis['bloqueado']) && \App\Services\Sistema\LibroExcel\SistemaLibroExcelProceso::permiteGuardar($analisis['version']))
                        <button type="button" wire:click="importar" wire:confirm="Se guardarán {{ $aplicar }} fila(s) nuevas o con cambios. ¿Confirmar?"
                            class="h-9 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                            <i class="fa fa-check"></i> Guardar {{ $aplicar }} cambio(s)
                        </button>
                    @endif
                </div>
            </x-slot>
        </x-dialog-modal>

        <x-loading wire:loading wire:target="exportar,importar" />
    @endif
</div>
