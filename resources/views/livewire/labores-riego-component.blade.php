<div>
    <x-card>
        <x-spacing>
            <!-- Título para agregar nueva labor -->
            <form class="space-y-2" wire:submit="agregarLabor">
                <x-label for="nuevaLabor" value="Agregar Nueva Labor" />
                <div class="flex items-center">
                    <x-input id="nuevaLabor" required autocomplete="off" wire:model="nuevaLabor" type="text" class="!w-auto mr-3"
                        placeholder="Nombre de la nueva labor" autofocus />
                    <x-button type="submit" wire:loading.attr="disabled">
                        Agregar
                    </x-button>
                </div>
            </form>
        </x-spacing>
    </x-card>
    <!-- Grid de labores -->
    <div class="grid gap-5 grid-cols-1 md:grid-cols-3 lg:grid-cols-5">
        @foreach ($labores as $labor)
            <x-card class="flex flex-col justify-between space-y-3 mt-4">
                <x-spacing class="flex justify-center items-center">
                    <div class="text-center">
                        <!-- Nombre de la labor -->
                        <p class="font-bold text-lg w-full text-center mb-5">{{ $labor->nombre_labor }}</p>

                        <!-- Botón para eliminar la labor -->
                        <x-danger-button wire:click="eliminarLabor({{ $labor->id }})" wire:loading.attr="disabled">
                            Eliminar
                        </x-danger-button>
                    </div>
                </x-spacing>
            </x-card>
        @endforeach
    </div>
</div>
