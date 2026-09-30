<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="xl">
        <x-slot name="title">
            Cerrar campaña {{ $resumen['nombre'] ?? '' }}
        </x-slot>
        <x-slot name="content">
            @if ($campaniaId)
                <div class="space-y-4">
                    <div class="rounded-lg border p-3 text-sm space-y-1">
                        <p><b>Campo:</b> {{ $resumen['campo'] }} · <b>Inicio:</b> {{ $resumen['inicio'] }}</p>
                        <p>
                            <b>Cosecha:</b>
                            {{ $resumen['cosecha'] ?? 'no hay cosecha registrada (ingresos de cochinilla ni labores de cosecha).' }}
                        </p>
                        @if ($resumen['posterior'])
                            <p class="text-red-600 dark:text-red-400">
                                <b>Después de la cosecha:</b> {{ $resumen['posterior'] }}. Esa labor ya es de la siguiente campaña.
                            </p>
                        @endif
                    </div>

                    <p class="text-sm text-muted-foreground">
                        La campaña termina con la cosecha: ciérrala el último día de cosecha, antes de cualquier otra labor
                        (preparado de tierra, limpieza, fumigación…).
                    </p>

                    <div class="flex flex-wrap items-end gap-3">
                        <x-input type="date" wire:model.live="fechaCierre" label="Fecha de cierre" class="w-auto" />
                        @if ($resumen['sugerida'] && $fechaCierre !== $resumen['sugerida'])
                            <x-button size="sm" variant="outline" wire:click="$set('fechaCierre', '{{ $resumen['sugerida'] }}')">
                                Usar la sugerida ({{ formatear_fecha($resumen['sugerida']) }})
                            </x-button>
                        @endif
                    </div>
                    @error('fechaCierre')
                        <x-danger>{{ $message }}</x-danger>
                    @enderror

                    @if ($avisos)
                        <x-warning>
                            <ul class="list-disc list-inside text-sm space-y-0.5">
                                @foreach ($avisos as $aviso)
                                    <li>{{ $aviso }}</li>
                                @endforeach
                            </ul>
                        </x-warning>
                    @endif
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-button variant="secondary" wire:click="$set('mostrar', false)">Cancelar</x-button>
                <x-button variant="danger" wire:click="confirmar" target="confirmar" :disabled="!$fechaCierre">
                    <i class="fa fa-lock"></i> Cerrar campaña
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
