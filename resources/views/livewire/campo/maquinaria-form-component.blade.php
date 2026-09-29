<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    {{ $maquinaria_id ? 'Editar maquinaria' : 'Registro de Maquinarias' }}
                </x-h3>
                <button wire:click="$set('mostrarFormulario',false)" class="focus:outline-none">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="store" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                {{-- Foto --}}
                <div class="md:row-span-3">
                    <x-label>Foto (opcional)</x-label>
                    <div class="mt-1 aspect-square w-full rounded-lg border border-dashed border-border bg-muted flex items-center justify-center overflow-hidden">
                        @if ($foto)
                            <img src="{{ $foto->temporaryUrl() }}" class="max-h-full max-w-full object-contain" alt="Vista previa">
                        @elseif ($fotoActual && !$quitarFoto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($fotoActual) }}" class="max-h-full max-w-full object-contain" alt="Foto">
                        @else
                            <i class="fa fa-tractor text-4xl text-muted-foreground"></i>
                        @endif
                    </div>
                    <input type="file" accept="image/*" wire:model="foto" class="mt-2 block w-full text-xs">
                    <div wire:loading wire:target="foto" class="text-xs text-muted-foreground">Subiendo…</div>
                    @if ($fotoActual && !$foto)
                        <label class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                            <input type="checkbox" wire:model.live="quitarFoto" class="rounded"> Quitar foto
                        </label>
                    @endif
                    <p class="text-[11px] text-muted-foreground mt-1">Se guarda sin deformar (lado mayor 1024 px).</p>
                    <x-input-error for="foto" />
                </div>

                <div>
                    <x-label for="nombre">Nombre de Maquinaria</x-label>
                    <x-input type="text" wire:model="nombre" class="uppercase" id="nombre" />
                    <x-input-error for="nombre" />
                </div>
                <div>
                    <x-label for="alias_blanco">Alias para el Kardex Blanco</x-label>
                    <x-input type="text" class="uppercase" wire:model="alias_blanco" id="alias_blanco" />
                    <x-input-error for="alias_blanco" />
                </div>

                <div>
                    <x-label for="placa">Placa (opcional)</x-label>
                    <x-input type="text" class="uppercase" wire:model="placa" id="placa" placeholder="Sin placa" />
                    <x-input-error for="placa" />
                </div>
                <div>
                    <x-select label="Combustible que usa" wire:model.live="combustible_producto_id" error="combustible_producto_id">
                        <option value="">— Sin definir —</option>
                        @foreach ($combustibles as $id => $nombreCombustible)
                            <option value="{{ $id }}">{{ $nombreCombustible }}</option>
                        @endforeach
                    </x-select>
                    <p class="text-[11px] text-muted-foreground mt-1">Una salida de otro combustible para esta máquina se avisa en tareas pendientes.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="usa_distribucion" class="rounded mt-1">
                        <span>
                            <span class="text-sm font-medium text-foreground">Distribuye su trabajo por campo</span>
                            <span class="block text-xs text-muted-foreground">
                                Desmárcalo para máquinas que no reparten su trabajo en campos (ej. motos de los trabajadores):
                                su combustible va directo a FDM y no se exige distribución.
                            </span>
                        </span>
                    </label>
                </div>

                <div>
                    <x-select label="Consumo estimado" wire:model.live="consumo_modo" error="consumo_modo">
                        <option value="">— No registrar —</option>
                        @foreach (\App\Models\Maquinaria::MODOS_CONSUMO as $clave => $etiqueta)
                            <option value="{{ $clave }}">{{ $etiqueta }}</option>
                        @endforeach
                    </x-select>
                </div>
                @if ($consumo_modo)
                    <div>
                        <x-label for="consumo_estimado">
                            {{ $consumo_modo === 'km' ? "Kilómetros por {$unidad}" : "{$unidad} por hora de encendido" }}
                        </x-label>
                        <x-input type="number" step="0.001" wire:model="consumo_estimado" id="consumo_estimado"
                            placeholder="{{ $consumo_modo === 'km' ? 'ej. 35' : 'ej. 0.5' }}" />
                        <x-input-error for="consumo_estimado" />
                    </div>
                @endif
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('mostrarFormulario',false)" class="mr-2">Cerrar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3" wire:loading.attr="disabled" wire:target="store,foto">Guardar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
