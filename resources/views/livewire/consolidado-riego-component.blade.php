<div class="space-y-4">
    <x-title>
        Consolidado de riego
    </x-title>

    @include('comun.selector-dia')

    <x-card class="mt-4">


        <x-table class="mt-5">
            <x-slot name="thead">
                <tr>
                    <x-th value="N°" />
                    <x-th value="Tipo de Trabajador" />
                    <x-th value="Nombre del Regador" class="!text-left" />
                    <x-th value="Fecha" />
                    <x-th value="Hora de Inicio" />
                    <x-th value="Hora de Fin" />
                    <x-th value="Horas Riego" />
                    <x-th value="Horas Acum." />
                    <x-th value="Total Horas Jornal" />
                </tr>
            </x-slot>
            <x-slot name="tbody">
                @foreach ($consolidado_riegos as $indice => $consolidado)
                    <x-tr>
                        <x-th value="{{ $indice + 1 }}" />
                        <x-td value="{{ $consolidado->alias_origen }}" />
                        <x-td value="{{ $consolidado->trabajador_nombre }}" class="!text-left" />
                        <x-td value="{{ $consolidado->fecha }}" />
                        <x-td value="{{ $consolidado->hora_inicio }}" />
                        <x-td value="{{ $consolidado->hora_fin }}" />
                        <x-td value="{{ $consolidado->horas_regados }}" />
                        <x-td value="{{ $consolidado->horas_acumuladas }}" />
                        <x-td value="{{ $consolidado->horas_jornal }}" />
                    </x-tr>
                @endforeach
            </x-slot>
        </x-table>
    </x-card>

    <x-loading wire:loading />
</div>
