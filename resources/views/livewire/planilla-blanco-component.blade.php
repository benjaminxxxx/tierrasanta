<div>
    <x-flex class="justify-between">
        <div>
            <x-title>
                Planilla Mensual
            </x-title>
            <x-subtitle>
                Gestión y consolidación de datos mensuales para la generación del PLAME
            </x-subtitle>
        </div>
        @include('comun.selector-mes-base')
    </x-flex>

    <livewire:planilla-blanco-detalle-component wire:key="{{ $mes }}-{{ $anio }}" :mes="$mes" :anio="$anio" />
    <livewire:gestion-planilla.validar-sueldo-planilla-component wire:key="{{ $mes }}-{{ $anio }}" :mes="$mes"
        :anio="$anio" />
    <!-- Componente de Validación de Fecha de Nacimiento -->
    <livewire:gestion-planilla.validar-fecha-nacimiento-planilla-component wire:key="fechas-nac-{{ $mes }}-{{ $anio }}"
        :mes="$mes" :anio="$anio" />
        
    <x-loading wire:loading />
</div>