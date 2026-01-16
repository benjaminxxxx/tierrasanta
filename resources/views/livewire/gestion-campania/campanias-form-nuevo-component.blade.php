<div>
    <x-dialog-modal wire:model="mostrarFormulario">
        <x-slot name="title">
            <x-title>
                Registro de Campaña
            </x-title>
        </x-slot>
        <x-slot name="content">
            @if ($campaniaAnterior)
                <x-success>
                    Existe una campaña anterior llamada <b>{{ $campaniaAnterior->nombre_campania }}</b> donde la fecha
                    de inicio es <b>{{ $campaniaAnterior->fecha_inicio->format('Y-m-d') }}</b>, la nueva fecha de inicio
                    de esta campaña debe ser posterior a dicha fecha
                </x-success>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                <x-select-campo wire:model.live="campania.campo" error="campania.campo" label="Campo" />

                <x-input type="number" wire:model="campania.area" error="campania.area" label="Área" />

                <x-input type="date" wire:model="campania.fecha_inicio" error="campania.fecha_inicio"
                    label="Fecha de Inicio" />

                <x-input type="text" wire:model="campania.nombre_campania" error="campania.nombre_campania"
                    label="Nombre de la Campaña" />

                <x-select wire:model="campania.variedad_tuna" error="campania.variedad_tuna" fullWidth="true" label="Variedad de Tuna">
                    <option value="Tuna Blanca">Tuna Blanca</option>
                    <option value="Tuna Roja">Tuna Roja</option>
                </x-select>

            </div>
        </x-slot>
        <x-slot name="footer">
            <!--Boton cerrar y registrar, parametros action id, si el id existe se cambia el texto a actualizar-->
            <x-form-buttons action="guardarCampania" id=""/>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
