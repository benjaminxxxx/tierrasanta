<div>
    <x-loading wire:loading />
    @if ($producto)
    <x-flex>
        <div x-data="{ openFileDialog() { $refs.fileInput.click() } }">
            <x-secondary-button type="button" @click="openFileDialog()" class="mt-4 md:mt-0 w-full md:w-auto">
                <i class="fa fa-file-excel"></i>
                Importar compras y salidas del Kardex
            </x-secondary-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" x-ref="fileInput"
                style="display: none;" wire:model.live="file" />
        </div>    
        <x-button type="button" @click="$wire.dispatch('EditarProducto',{id:{{$producto->id}}})" class="mt-4 md:mt-0 w-full md:w-auto">
            <i class="fa fa-edit"></i>
            Editar Producto
        </x-button>
    </x-flex>
    @endif
    
</div>
