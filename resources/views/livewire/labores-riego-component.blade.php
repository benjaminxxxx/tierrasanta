<div>
    <x-h3>
        Labores de Riego
    </x-h3>
    <x-card2 class="mt-4">
        <!-- Título para agregar nueva labor -->
        <form class="space-y-2" wire:submit="agregarLabor">
            <x-label for="nuevaLabor" value="Nombre de la labor" />
            <div class="flex items-center">
                <x-input id="nuevaLabor" required autocomplete="off" wire:model="nuevaLabor" type="text"
                    class="!w-auto mr-3" placeholder="Nombre de la nueva labor" autofocus />
                <x-button type="submit" wire:loading.attr="disabled">
                    <i class="fa fa-plus"></i> Agregar
                </x-button>
            </div>
        </form>
    </x-card2>
    <!-- Grid de labores -->
    <div class="grid gap-5 grid-cols-1 md:grid-cols-3 lg:grid-cols-5">
        @foreach ($labores as $labor)
            <x-card2 class="flex flex-col justify-between space-y-3 mt-4 text-center">
                <x-label>
                    {{ $labor->nombre_labor }}
                </x-label>
                <x-danger-button wire:click="eliminarLabor({{ $labor->id }})" wire:loading.attr="disabled">
                    <i class="fa fa-trash"></i> Eliminar
                </x-danger-button>
            </x-card2>
        @endforeach
    </div>
    <x-loading wire:loading />
</div>