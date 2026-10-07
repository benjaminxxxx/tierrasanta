{{-- Modal "Registro de labores" (LaborFormComponent): se abre con crearLabor / editarLabor --}}
<div>
    <x-dialog-modal maxWidth="lg" wire:model="mostrar">
        <x-slot name="title">
            Registro de labores
        </x-slot>
    
        <x-slot name="content">
            <form wire:submit="guardar" id="frmLabores">
                <div class="mt-4 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-input type="number" wire:model="codigo" label="Código de labor" error="codigo" :disabled="(bool) $codigoFijo" />
                            @if ($codigoFijo)
                                <p class="text-xs text-muted-foreground mt-1">
                                    <i class="fa fa-lock"></i> El código no se puede cambiar: {{ $codigoFijo }}. Para que este código pase a ser otra labor,
                                    usa <i class="fa fa-right-left"></i> Reasignar código en la lista.
                                </p>
                            @endif
                        </div>
                        <x-input wire:model="nombre_labor" label="Nombre de la labor" error="nombre_labor" />
                        <x-input type="number" wire:model="estandar_produccion" label="Estándar de producción"
                            error="estandar_produccion" />
    
    
                        <x-select wire:model="codigo_mano_obra" label="Mano de obra (obligatoria)" error="codigo_mano_obra">
                            <option value="">Seleccione un grupo</option>
                            @foreach ($manoObras as $manoObra)
                                <option value="{{ $manoObra->codigo }}">{{ $manoObra->descripcion }}</option>
                            @endforeach
                        </x-select>
    
                        <x-input wire:model="unidades" label="Unidades" placeholder="Ejem: Kg, Lavaderos"
                            error="unidades" />
    
                        <div class="md:col-span-2">
                            <x-select wire:model="tipo_asistencia_codigo" label="Representa una asistencia (labor de suspensión)"
                                error="tipo_asistencia_codigo">
                                <option value="">No — es una labor de trabajo</option>
                                @foreach ($tiposAsistencia as $t)
                                    <option value="{{ $t->codigo }}">{{ $t->codigo }} — {{ $t->descripcion }}</option>
                                @endforeach
                            </x-select>
                            <p class="text-xs text-muted-foreground mt-1">
                                Ej.: 97 Descanso médico → DM. En el registro diario solo se usa con campo FDM; sus horas
                                entran al costo en FDM con este código y el día cuenta para las suspensiones del PLAME.
                            </p>
                        </div>
                    </div>
    
                    <div x-data="{
                        tramos: @entangle('tramos'),
                        addTramo() {
                            this.tramos.push({ hasta: '', monto: '' });
                        },
                        removeTramo(index) {
                            this.tramos.splice(index, 1);
                        }
                    }" class="space-y-4">
                        <x-flex class="mt-3 mb-2">
                            <x-h3>Tramos de Bonificación</x-h3>
                            <x-button variant="secondary" @click="addTramo">
                                <i class="fa fa-plus"></i> Agregar Tramo
                            </x-button>
                        </x-flex>
    
                        <template x-for="(tramo, index) in tramos" :key="index">
                            <div class="flex items-center space-x-4 p-2">
                                <!-- Hasta -->
                                <div class="flex flex-col">
                                    <x-input type="number" label="Hasta (unidades)" x-model="tramo.hasta" />
                                </div>
    
                                <!-- Monto -->
                                <div class="flex flex-col">
                                    <x-input type="number" step="0.1" label="Se paga S/." x-model="tramo.monto" />
                                </div>
    
                                <!-- Remove button -->
                                <x-button variant="danger" @click="removeTramo(index)">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            </div>
                        </template>
    
    
                    </div>
                </div>
                <x-input type="checkbox" wire:model="se_paga_con_jornal" label="Se paga junto con el costo día" />
            </form>
        </x-slot>
    
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">
                Cancelar
            </x-button>
            <x-button type="submit" form="frmLabores">
                <i class="fa fa-save"></i> Guardar Labor
            </x-button>
        </x-slot>
    
    </x-dialog-modal>
    <x-loading wire:loading wire:target="guardar,crear,editar" />
</div>
