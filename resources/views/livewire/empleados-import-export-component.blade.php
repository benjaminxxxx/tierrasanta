<div>
    <x-loading wire:loading wire:target="file" />
    <x-loading wire:loading wire:target="export" />
    <div class="block md:flex items-center gap-5">
        <div x-data="{ openFileDialog() { $refs.fileInput.click() } }">
            <!-- Botón para abrir el diálogo de archivos -->
            <x-secondary-button type="button" @click="openFileDialog()" class="mt-4 md:mt-0 w-full md:w-auto">
                <i class="fa fa-file-excel"></i>
                Importar Empleados
            </x-secondary-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" x-ref="fileInput"
                style="display: none;" wire:model.live="file" />
        </div>
        <div>
            <x-secondary-button type="button" wire:click="export" class="mt-4 md:mt-0 w-full md:w-auto">
                <i class="fa fa-file-excel"></i>
                Exportar Empleados
            </x-secondary-button>
        </div>
    </div>
</div>
