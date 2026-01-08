<div>
    <x-button variant="secondary" wire:click="verLabores"><i class="fa fa-eye"></i> Ver Labores</x-button>
    <x-dialog-modal wire:model="mostrarFormularioLabores" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="">
                    Lista de Labores
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-4 gap-2">
                @foreach ($labores as $labor)
                    @php
                        // Generar un identificador único de 5 números aleatorios
                        $randomId = rand(10000, 99999);
                    @endphp
                    <div class="category col-span-4 md:col-span-2 lg:col-span-1">
                        <div class="flex items-center mt-4 mb-2">
                            <x-label class="!font-bold ml-2 !mb-0">{{ $labor->id }} - {{ $labor->nombre_labor }}</x-label>
                        </div>
                       
                    </div>
                @endforeach

            </div>

        </x-slot>
        <x-slot name="footer">
            <x-button type="button" wire:click="$set('mostrarFormularioLabores', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
