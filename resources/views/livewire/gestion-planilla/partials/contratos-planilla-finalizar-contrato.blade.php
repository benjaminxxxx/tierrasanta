<x-dialog-modal wire:model.live="mostrarInformacionFinalizar" maxWidth="2xl">
    <x-slot name="title">
        Finalizar Contratos
    </x-slot>

    <x-slot name="content">
        @if ($contratoAFinalizar)
            <div
                class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800 mb-4">
                <div class="flex-shrink-0 bg-blue-500 p-2 rounded-full text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-bold text-blue-900 dark:text-blue-200 uppercase">
                        {{ $contratoAFinalizar->empleado->nombre_completo }}
                    </h3>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-blue-700 dark:text-blue-300">
                        <span><strong>DNI:</strong> {{ $contratoAFinalizar->empleado->documento }}</span>
                        <span><strong>Cargo:</strong> {{ $contratoAFinalizar->cargo->nombre ?? 'No asignado' }}</span>
                        <span><strong>Planilla:</strong> {{ ucfirst($contratoAFinalizar->tipo_planilla) }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                {{-- Fecha de Fin --}}
                <x-input label="Inicio: {{ \Carbon\Carbon::parse($contratoAFinalizar->fecha_inicio)->format('d/m/Y') }}"
                    type="date" wire:model="datosCierre.fecha_fin" error="datosCierre.fecha_fin" />

                {{-- Motivo Cese --}}
                <x-select label="Motivo Cese (SUNAT)" wire:model="datosCierre.motivo_cese_sunat" error="datosCierre.motivo_cese_sunat" class="w-full text-sm">
                    <option value="">Seleccione...</option>
                    <option value="01">Renuncia</option>
                    <option value="02">Renuncia con incentivos</option>
                    <option value="03">Despido nulo</option>
                    <option value="05">Jubilación</option>
                    <option value="09">Término de contrato</option>
                </x-select>

                {{-- Comentario --}}
                <x-input label="Comentario" type="text" placeholder="Ej. Mutuo disenso..."
                    wire:model="datosCierre.comentario_cese" error="datosCierre.comentario_cese" />
            </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-button variant="secondary" wire:click="$set('mostrarInformacionFinalizar', false)">
                Cancelar
            </x-button>

            <x-button wire:click="confirmarFinalizarContrato">
                Finalizar Contrato
            </x-button>
        </div>
    </x-slot>
</x-dialog-modal>
