<div>
    <div class="w-full max-w-screen overflow-auto text-center">
        <x-card class="max-w-4xl m-auto">
            <x-spacing>
             
                <x-label for="activar_cambiar_posicion" class="mt-4">
                    <x-checkbox id="activar_cambiar_posicion" class="mr-2" />
                    Activar para cambiar de posición
                </x-label>

                <div class="relative" id="campos-container">
                    @foreach ($campos as $campo)
                        
                        <div data-nombre="{{ $campo->nombre }}" class="campo {{ $campo->orden == 1 ? 'bg-lime-600 text-white' : 'bg-stone-300' }} break-work shadow-lg font-bold text-center flex items-center justify-center rounded-md p-3"
                                style="left: {{ $campo->pos_x }}px; top: {{ $campo->pos_y }}px; }}">


                            <div class="campo-content">
                                {{ $campo->nombre }}
                            </div>

                        </div>
                    @endforeach
                </div>

            </x-spacing>
        </x-card>
    </div>
</div>
