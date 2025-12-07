<div>

    <div class="md:flex items-center gap-2 w-full md:w-auto">
        <!-- Botón para importar compras negro desde Kardex -->
        <div x-data="{ openFileDialog() { $refs.fileInputNegro.click() } }">
            <x-button variant="secondary" type="button" @click="openFileDialog()">
                <i class="fa fa-file-excel"></i> Importar Kardex Negro
            </x-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                x-ref="fileInputNegro" style="display: none;" wire:model.live="fileNegroDesdeKardex" />
        </div>

        <!-- Botón para importar compras blanco desde Kardex -->
        <div x-data="{ openFileDialog() { $refs.fileInputBlanco.click() } }">
            <x-button variant="secondary" type="button" @click="openFileDialog()">
                <i class="fa fa-file-excel"></i> Importar Kardex Blanco
            </x-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                x-ref="fileInputBlanco" style="display: none;" wire:model.live="fileBlancoDesdeKardex" />
        </div>
    </div>
    <x-loading wire:loading />
</div>
