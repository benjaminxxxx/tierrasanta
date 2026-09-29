<div>
    <x-loading wire:loading />
    <div x-data="{ openFileDialog() { $refs.fileInput.click() } }">
        <x-secondary-button type="button" @click="openFileDialog()" class="w-full md:w-auto">
            <i class="fa fa-file-excel"></i> Importar Salidas desde Kardex
        </x-secondary-button>
        <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" x-ref="fileInput"
            style="display: none;" wire:model.live="fileDesdeKardex" />
    </div>
</div>
