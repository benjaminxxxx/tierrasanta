<div>
    <x-card>
        <x-flex class="justify-between">
            <x-title>
                <a href="{{ route('gestion_insumos.kardex') }}"
                    class="underline text-blue-600 dark:text-blue-300">KARDEX</a> /
                {{ mb_strtoupper($insumoKardex->descripcion) }}
            </x-title>
            <x-flex>
                <div x-data="{ openFileDialog() { $refs.fileInputNegro.click() } }">
                    <x-button type="button" @click="openFileDialog()" class="uppercase">
                        <i class="fa fa-file-excel"></i> Importar Kardex {{ $insumoKardex->tipo }}
                    </x-button>
                    <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        x-ref="fileInputNegro" style="display: none;" wire:model.live="archivoExcelKardex" />
                </div>

                <x-button wire:click="generarDetalleKardexInsumo" class="uppercase">
                    <i class="fa fa-check"></i> Generar Resumen
                </x-button>

                @if ($insumoKardex->file)
                    <x-button href="{{ Storage::disk('public')->url($insumoKardex->file) }}">
                        <i class="fa fa-file-excel"></i> Descargar Reporte
                    </x-button>
                @endif
            </x-flex>
        </x-flex>
        <div class="mt-4">
            {{-- -TABLA --}}
            @include('livewire.gestion-insumos.partials.insumo-kardex-detalle-tabla')
        </div>
    </x-card>
    <x-loading wire:loading />
</div>