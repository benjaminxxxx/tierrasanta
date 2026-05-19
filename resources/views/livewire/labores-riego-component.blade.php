<div class="space-y-4">
    <x-title>
        Labores de Riego
    </x-title>
    <x-card class="mt-4">
        <!-- Título para agregar nueva labor -->
        <form class="space-y-2" wire:submit="agregarLabor">
            <x-label for="nuevaLabor" value="Nombre de la labor" />
            <div class="flex items-center">
                <x-input id="nuevaLabor" required autocomplete="off" wire:model="nuevaLabor" type="text"
                    class="!w-auto mr-3" placeholder="Nombre de la nueva labor" autofocus />
                @can(\App\Constants\Permisos::CAMPO_RIEGO_LABOR_GESTIONAR)
                    <x-button type="submit" wire:loading.attr="disabled">
                        <i class="fa fa-plus"></i> Agregar
                    </x-button>
                @endcan
            </div>
        </form>
    </x-card>
    <!-- Grid de labores -->

    @can(\App\Constants\Permisos::CAMPO_RIEGO_LABOR_VER)
        <div class="grid gap-5 grid-cols-1 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($labores as $labor)
                <x-card class="flex flex-col justify-between space-y-3 mt-4 text-center">
                    <x-label>
                        {{ $labor->nombre_labor }}
                    </x-label>
                    @can(\App\Constants\Permisos::CAMPO_RIEGO_LABOR_GESTIONAR)
                        <x-button variant="danger" wire:click="eliminarLabor({{ $labor->id }})" wire:loading.attr="disabled">
                            <i class="fa fa-trash"></i> Eliminar
                        </x-button>
                    @endcan

                </x-card>
            @endforeach
        </div>
    @else
        <x-danger>
            No tienes permisos para ver las labores de riego. Por favor, contacta al administrador.
        </x-danger>
    @endcan

    <x-loading wire:loading />
</div>