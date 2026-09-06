<div>
    <x-dialog-modal wire:model="mostrarFormularioProveedores" maxWidth="2xl">
        <x-slot name="title">
            <x-title>
                {{ $proveedorId ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
            </x-title>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="guardarProveedores" class="space-y-6">

                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Identificación
                    </span>
                    <div class="grid grid-cols-2 gap-5">
                        <x-input type="text" label="Razón Social" wire:model="razonSocial" class="uppercase" error="razonSocial" />
                        <x-input type="text" label="Nombre Comercial" wire:model="nombreComercial" class="uppercase" error="nombreComercial" />
                        <x-input type="text" label="RUC" wire:model="ruc" error="ruc" />
                        <x-input type="text" label="Número de contacto" wire:model="contacto" error="contacto" />
                        <x-input type="text" label="Tipo Contribuyente" wire:model="tipoContribuyente" error="tipoContribuyente" />
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Estado ante SUNAT
                    </span>
                    <div class="grid grid-cols-2 gap-5">
                        <x-input type="text" label="Condición" wire:model="condicion" error="condicion" />
                        <x-input type="text" label="Estado Contribuyente" wire:model="estadoContribuyente" error="estadoContribuyente" />
                        <x-input type="text" label="Estado Domicilio" wire:model="estadoDomicilio" error="estadoDomicilio" />
                        <x-input type="date" label="Fecha Inscripción" wire:model="fechaInscripcion" error="fechaInscripcion" />
                        <x-input type="date" label="Inicio de Actividades" wire:model="fechaInicioActividades" error="fechaInicioActividades" />
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Ubicación
                    </span>
                    <div class="grid grid-cols-2 gap-5">
                        <div class="col-span-2">
                            <x-input type="text" label="Dirección Fiscal" wire:model="direccionFiscal" error="direccionFiscal" />
                        </div>
                        <x-input type="text" label="Distrito" wire:model="distrito" error="distrito" />
                        <x-input type="text" label="Provincia" wire:model="provincia" error="provincia" />
                        <x-input type="text" label="Departamento" wire:model="departamento" error="departamento" />
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Actividad Económica
                    </span>
                    <div class="grid grid-cols-2 gap-5">
                        <x-input type="text" label="CIIU" wire:model="ciiu" error="ciiu" />
                        <x-input type="text" label="Actividad Comercio Exterior" wire:model="actividadComercioExterior" error="actividadComercioExterior" />
                    </div>
                </div>

            </form>
        </x-slot>
        <x-slot name="footer">
            <x-button type="button" variant="secondary" @click="$wire.set('mostrarFormularioProveedores', false)">
                Cancelar
            </x-button>
            <x-button type="submit" wire:click="guardarProveedores">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>