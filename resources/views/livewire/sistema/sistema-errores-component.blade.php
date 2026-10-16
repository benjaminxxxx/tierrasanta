<div class="space-y-4">
    <div>
        <x-title>Errores del sistema</x-title>
        <x-subtitle>
            Errores inesperados que vieron los usuarios (de base de datos o de código), con la hora, la pantalla y el usuario.
            Los avisos normales ("no hay stock", "falta la fecha"…) no se guardan aquí.
        </x-subtitle>
    </div>

    <x-card class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                @foreach (['pendiente' => "Pendientes ({$pendientes})", 'revisado' => 'Revisados', 'resuelto' => 'Resueltos', 'todos' => 'Todos'] as $clave => $texto)
                    <button type="button" wire:click="$set('estado', '{{ $clave }}')"
                        class="px-3 py-2 text-sm font-semibold {{ $estado === $clave ? 'bg-primary text-white' : '' }}">{{ $texto }}</button>
                @endforeach
            </div>
            <div class="w-80">
                <x-input type="search" wire:model.live.debounce.400ms="buscar" label="Buscar"
                    placeholder="Ticket, mensaje, pantalla o componente…" />
            </div>
        </div>

        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th value="Ticket" />
                    <x-th value="Fecha y hora" />
                    <x-th value="Usuario" />
                    <x-th value="Pantalla / acción" />
                    <x-th value="Error" />
                    <x-th value="Veces" />
                    <x-th value="Estado" />
                    <x-th value="" />
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @forelse ($tickets as $t)
                    <x-tr wire:key="err-{{ $t->id }}">
                        <x-td class="font-bold text-center">#{{ $t->id }}</x-td>
                        <x-td class="whitespace-nowrap text-center">{{ $t->created_at->format('d/m/Y H:i:s') }}</x-td>
                        <x-td>{{ $t->usuario?->name ?? '—' }}</x-td>
                        <x-td class="max-w-xs">
                            <div class="truncate" title="{{ $t->url }}">{{ $t->ruta ?: parse_url((string) $t->url, PHP_URL_PATH) }}</div>
                            <div class="text-xs text-muted-foreground truncate">{{ $t->accion ?: class_basename((string) $t->componente) }}</div>
                        </x-td>
                        <x-td class="max-w-md">
                            <div class="text-xs text-muted-foreground">{{ class_basename($t->clase) }}</div>
                            <div class="truncate" title="{{ $t->mensaje }}">{{ \Illuminate\Support\Str::limit($t->mensaje, 140) }}</div>
                        </x-td>
                        <x-td class="text-center">{{ $veces[$t->huella] ?? 1 }}</x-td>
                        <x-td class="text-center">
                            <x-badge :color="['pendiente' => 'red', 'revisado' => 'yellow', 'resuelto' => 'green'][$t->estado] ?? 'gray'">
                                {{ \App\Models\ErrorSistema::ESTADOS[$t->estado] ?? $t->estado }}
                            </x-badge>
                        </x-td>
                        <x-td class="text-center">
                            <x-button variant="secondary" wire:click="ver({{ $t->id }})"><i class="fa fa-eye"></i></x-button>
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="8" class="text-center text-muted-foreground py-6">No hay errores con este filtro.</x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
        {{ $tickets->links() }}
    </x-card>

    <x-dialog-modal wire:model.live="mostrarDetalle" maxWidth="full">
        <x-slot name="title">
            @if ($detalle)
                <x-h3>Ticket #{{ $detalle->id }} · {{ class_basename($detalle->clase) }}</x-h3>
                <p class="text-xs text-muted-foreground">{{ $detalle->created_at->format('d/m/Y H:i:s') }} · {{ $detalle->usuario?->name ?? 'Sin usuario' }}</p>
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($detalle)
                <div class="space-y-4 text-sm">
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 font-mono text-xs whitespace-pre-wrap break-words">{{ $detalle->mensaje }}</div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
                        <div><dt class="inline font-semibold">Pantalla:</dt> <dd class="inline break-all">{{ $detalle->url }}</dd></div>
                        <div><dt class="inline font-semibold">Ruta:</dt> <dd class="inline">{{ $detalle->ruta ?? '—' }}</dd></div>
                        <div><dt class="inline font-semibold">Componente:</dt> <dd class="inline">{{ $detalle->componente ?? '—' }}</dd></div>
                        <div><dt class="inline font-semibold">Acción:</dt> <dd class="inline">{{ $detalle->accion ?? '—' }}</dd></div>
                        <div><dt class="inline font-semibold">Archivo:</dt> <dd class="inline break-all">{{ $detalle->archivo }}:{{ $detalle->linea }}</dd></div>
                        <div><dt class="inline font-semibold">Clase:</dt> <dd class="inline break-all">{{ $detalle->clase }}</dd></div>
                        <div><dt class="inline font-semibold">IP:</dt> <dd class="inline">{{ $detalle->ip }}</dd></div>
                        <div><dt class="inline font-semibold">Navegador:</dt> <dd class="inline text-xs">{{ $detalle->navegador }}</dd></div>
                        @if ($detalle->revisado_en)
                            <div><dt class="inline font-semibold">Revisado:</dt> <dd class="inline">{{ $detalle->revisor?->name }} · {{ $detalle->revisado_en->format('d/m/Y H:i') }}</dd></div>
                        @endif
                    </dl>

                    <details>
                        <summary class="cursor-pointer font-semibold">Traza</summary>
                        <pre class="mt-2 max-h-80 overflow-auto rounded bg-muted p-3 text-xs">{{ $detalle->traza }}</pre>
                    </details>

                    @if ($ocurrencias->count() > 1)
                        <div>
                            <p class="font-semibold mb-1">El mismo error pasó {{ $ocurrencias->count() }}{{ $ocurrencias->count() === 15 ? '+' : '' }} veces:</p>
                            <ul class="text-xs space-y-0.5">
                                @foreach ($ocurrencias as $o)
                                    <li>#{{ $o->id }} · {{ $o->created_at->format('d/m/Y H:i') }} · {{ $o->usuario?->name ?? '—' }} · {{ \App\Models\ErrorSistema::ESTADOS[$o->estado] ?? $o->estado }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <x-label value="Nota (qué era y cómo se corrigió)" />
                        <textarea wire:model="nota" rows="2" class="w-full rounded-md border border-input bg-background p-2 text-sm"></textarea>
                    </div>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex flex-wrap gap-2 justify-end">
                <x-button variant="secondary" wire:click="$set('mostrarDetalle', false)">Cerrar</x-button>
                <x-button variant="secondary" wire:click="marcar('pendiente')">Volver a pendiente</x-button>
                <x-button variant="warning" wire:click="marcar('revisado')">Revisado</x-button>
                <x-button variant="success" wire:click="marcar('resuelto')">Resuelto</x-button>
                <x-button variant="success" wire:click="marcar('resuelto', true)" title="Marca como resueltos todos los tickets de este mismo error">
                    Resolver todos los iguales
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="marcar,ver" />
</div>
