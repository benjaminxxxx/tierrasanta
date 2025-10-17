<div>
    <x-loading wire:loading />
    <div class="md:flex gap-5">
        <div class="flex-1">
            <x-card2>
                <div class="mt-6">
                    <x-h2>Descuentos de AFP y el Sistema Nacional de Pensiones</x-h2>
                    <x-label>
                        Para obtener los porcentajes actualizados de comisiones, prima de seguros y aportes
                        obligatorios,
                        visita el
                        <a class="text-blue-500 font-medium underline pointer" target="_blank"
                            href="https://www.sbs.gob.pe/app/spp/empleadores/comisiones_spp/Paginas/comision_prima.aspx">
                            enlace de la SBS
                        </a>.
                        Una vez que accedas al enlace, copia los datos relevantes de la tabla de comisiones y
                        pégalos en el
                        cuadro de abajo. Luego, haz clic en "Generar Nuevos Montos" para actualizar los valores.
                    </x-label>
                    <div class="mt-5">
                        <x-label for="fecha_inicio">Mes de devengue :</x-label>
                        <x-select wire:model.live="fecha_inicio" class="!w-auto">
                            <option value="">Seleccionar Mes</option>
                            @foreach ($fechas as $fecha)
                                <option value="{{ $fecha }}">{{ $fecha }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-textarea rows="4" class="mt-6 mb-2"
                        placeholder="Copie los datos de la tabla de la SBS y péguelos aquí"
                        wire:model="informacion"></x-textarea>

                </div>
                <x-table class="mt-6">
                    <x-slot name="thead">
                        @if ($descuentosSPHistorico && $descuentosSPHistorico->count() > 0)
                            <tr>
                                <x-th value="Código" />
                                <x-th value="Porcentaje de Descuento %" class="text-center md:w-[20rem]" />
                                <x-th value="Descuento Para Trabajadores Después de 65 Años"
                                    class="text-center  md:w-[20rem]" />
                            </tr>
                        @endif
                    </x-slot>
                    <x-slot name="tbody">
                        @if ($descuentosSPHistorico && $descuentosSPHistorico->count() > 0)
                            @foreach ($descuentosSPHistorico as $descuentoHistorico)
                                <x-tr>
                                    <x-th value="{{ $descuentoHistorico->descuento_codigo }}" />
                                    <x-td class="text-center">
                                        {{ $descuentoHistorico->porcentaje }} %
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $descuentoHistorico->porcentaje_65 }} %
                                    </x-td>
                                </x-tr>
                            @endforeach
                        @endif
                    </x-slot>
                </x-table>
                @if ($descuentosSPHistorico && $descuentosSPHistorico->count() > 0)
                    <x-flex class="justify-end mt-4">
                        <x-button wire:click="generarCalculo">
                            <i class="fa fa-save"></i> Actualizar Descuento
                        </x-button>
                        <x-button variant="danger" wire:click="eliminarDescuentos">
                            <i class="fa fa-remove"></i> Eliminar Descuentos
                        </x-button>
                    </x-flex>
                @else
                    <x-flex class="justify-end mt-4">
                        <x-button wire:click="generarCalculo">
                            <i class="fa fa-refresh"></i> Crear Nuevo Descuento
                        </x-button>
                    </x-flex>
                @endif
            </x-card2>
        </div>
        <div class="md:w-[32rem]">
            <x-h3 class="mb-3">
                Historial de Descuentos
            </x-h3>
            <x-card class="overflow-hidden">
                @if (is_array($fechasRegistradas) && count($fechasRegistradas) > 0)
                    @foreach ($fechasRegistradas as $fechasRegistrada)
                        @php

                            $fecha_actual_estilo = $fechasRegistrada == $fecha_inicio ? 'text-primaryText bg-primary' : ''; // Ajusta esto según tu lógica
                        @endphp
                        <a href="#" wire:click.prevent="cambiarFechaA('{{ $fechasRegistrada }}')" aria-current="true"
                            class="block w-full px-4 py-3 {{$fecha_actual_estilo}} text-center font-bold border-b border-gray-200 rounded-t-lg cursor-pointer dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                            {{ $fechasRegistrada }}
                        </a>
                    @endforeach
                @endif
            </x-card>
        </div>
    </div>
</div>