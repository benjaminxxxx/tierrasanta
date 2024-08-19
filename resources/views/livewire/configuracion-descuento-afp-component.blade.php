<div>

    <x-card>
        <x-spacing>
            <div class="mt-6">
                <x-h2>Descuentos de AFP y el Sistema Nacional de Pensiones</x-h2>
                <x-label>
                    Para obtener los porcentajes actualizados de comisiones, prima de seguros y aportes obligatorios, visita el 
                    <a class="text-blue-500 font-medium underline pointer" target="_blank" href="https://www.sbs.gob.pe/app/spp/empleadores/comisiones_spp/Paginas/comision_prima.aspx">
                        enlace de la SBS
                    </a>. 
                    Una vez que accedas al enlace, copia los datos relevantes de la tabla de comisiones y pégalos en el cuadro de abajo. Luego, haz clic en "Generar Nuevos Montos" para actualizar los valores.
                </x-label>
                <x-textarea rows="4" class="mt-6 mb-2"
                    placeholder="Copie los datos de la tabla de la SBS y péguelos aquí"
                    wire:model="informacion"></x-textarea>
                <div class="flex justify-end gap-3">
                    <x-button wire:click="generarCalculo">
                        Generar Nuevos Montos
                    </x-button>
                    <x-danger-button wire:click="limpiarMontos">
                        Limpiar Montos
                    </x-danger-button>
                </div>
            </div>
            <x-table class="mt-6">
                <x-slot name="thead">
                    <tr>
                        <x-th value="Código" />
                        <x-th value="Descripción" />
                        <x-th value="Porcentaje de Descuento %" class="text-center w-48" />
                        <x-th value="Descuento Para Trabajadores Después de 65 Años" class="text-center  w-48" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($descuentosSP->count())
                        @foreach ($descuentosSP as $descuento)
                            <x-tr>
                                <x-th value="{{ $descuento->codigo }}" />
                                <x-td value="{{ $descuento->descripcion }}" />
                                <x-td class="text-center">
                                    <x-input readonly wire:model.lazy="descuentos.{{ $descuento->codigo }}"
                                        class="!w-24 !px-3 !py-3 text-center" /> %
                                </x-td>
                                <x-td class="text-center">
                                    <x-input readonly wire:model.lazy="descuentos65.{{ $descuento->codigo }}"
                                        class="!w-24 !px-3 !py-3 text-center" /> %
                                </x-td>
                            </x-tr>
                        @endforeach
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
