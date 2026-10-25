{{-- Campos de una remuneración. Variable: $conFecha. Modelo: r.* --}}
<div class="grid grid-cols-2 gap-3">
    @if ($conFecha)
        <x-fecha label="Desde *" wire:model="r.desde" />
    @endif
    <x-input type="number" step="0.01" label="Sueldo" wire:model="r.sueldo" help="Sueldo real del mes" />
    <x-input type="number" step="0.01" label="Remuneración básica" wire:model="r.remuneracion_basica" help="Opcional: sueldo formal; reemplaza la básica del mes" />
    <x-input type="number" step="0.01" label="Bonificación" wire:model="r.bonificacion" help="Se suma a la básica (0121)" />
    <x-input type="number" step="0.01" label="Compensación vacacional" wire:model="r.compensacion_vacacional" />
    <x-select label="Modalidad de pago" wire:model="r.modalidad_pago">
        @foreach (\App\Services\Planilla\Laboral\PlanillaLaboralReglas::MODALIDADES_PAGO as $mp)
            <option value="{{ $mp }}">{{ ucfirst($mp) }}</option>
        @endforeach
    </x-select>
    <x-select label="CTS y gratificación" wire:model="r.beneficios_mensuales">
        <option value="0">En dos tramos</option>
        <option value="1">Cada mes con el sueldo</option>
    </x-select>
    @if ($conFecha)
        <x-input label="Motivo" wire:model="r.motivo" placeholder="Aumento, ajuste…" class="col-span-2" />
    @endif
</div>
