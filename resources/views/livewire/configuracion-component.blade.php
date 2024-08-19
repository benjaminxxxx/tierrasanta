<div>
    <div class="mb-6">
        <x-h2>Panel de Configuración</x-h2>
        <x-label>Administra las configuraciones de la aplicación aquí.</x-label>
    </div>
    <div class="grid gap-6 md:grid-cols-1 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($configuracionesObjeto as $configuracion)
            <x-card>
                <x-spacing>
                    <x-label>{{ $configuracion->descripcion }}</x-label>
                    <x-input 
                        wire:model.lazy="configuraciones.{{ $configuracion->codigo }}" 
                        value="{{ $configuracion->valor }}" 
                        class="mt-3 w-full" 
                    />
                </x-spacing>
            </x-card>
        @endforeach
    </div>
    <div class="mt-6">
        <x-button wire:click="save" type="button">
            Guardar Configuración
        </x-button>
    </div>
</div>
