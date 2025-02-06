<div>
    <x-loading wire:loading />

    <div class="md:flex items-center gap-2 w-full md:w-auto">
        <!-- Botón para importar compras negro desde Kardex -->
        <div x-data="{ openFileDialog() { $refs.fileInputNegro.click() } }">
            <x-secondary-button type="button" @click="openFileDialog()" class="w-full md:w-auto">
                <i class="fa fa-file-excel"></i> Importar Kardex Negro
            </x-secondary-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                x-ref="fileInputNegro" style="display: none;" wire:model.live="fileNegroDesdeKardex" />
        </div>

        <!-- Botón para importar compras blanco desde Kardex -->
        <div x-data="{ openFileDialog() { $refs.fileInputBlanco.click() } }">
            <x-secondary-button type="button" @click="openFileDialog()" class="w-full md:w-auto">
                <i class="fa fa-file-excel"></i> Importar Kardex Blanco
            </x-secondary-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                x-ref="fileInputBlanco" style="display: none;" wire:model.live="fileBlancoDesdeKardex" />
        </div>
    </div>
</div>
