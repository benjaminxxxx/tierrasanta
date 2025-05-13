@props([
    'label' => null,
    'error' => null,
    'descripcion' => null,
    'fechaMin' => null, // Fecha mínima
    'fechaMax' => null, // Fecha máxima
])

@php
    $modelName = $attributes->wire('model');
@endphp

<div x-data="datepicker(@entangle($modelName), @js($fechaMin), @js($fechaMax))" class="relative">
    <div class="flex flex-col">
        @if ($label || $attributes->wire('model'))
            <x-label for="{{ $attributes->wire('model') }}">
                {{ $label ?? ucfirst(str_replace('_', ' ', $attributes->wire('model'))) }}
            </x-label>
        @endif

        <x-input type="date" x-ref="myDatepicker" x-model="value" />

        @if ($descripcion)
            <small>{{ $descripcion }}</small>
        @endif
        @if ($error)
            <x-input-error for="{{ $error }}" />
        @endif
    </div>
</div>
@script
    <script>
        Alpine.data('datepicker', (model, fechaMin, fechaMax) => ({
            value: model,
            pickr: null,
            init() {
                this.$nextTick(() => {
                    this.pickr = flatpickr(this.$refs.myDatepicker, {
                        dateFormat: "Y-m-d",
                        minDate: fechaMin || null, // ✅ Se pasa correctamente la fecha mínima
                        maxDate: fechaMax || null, // ✅ Se pasa correctamente la fecha máxima
                        defaultDate: this.value || null,
                        onChange: (selectedDates, dateStr) => {
                            this.value = dateStr;
                        }
                    });

                    this.$watch('value', (newVal) => {
                        if (this.pickr && this.pickr.input.value !== newVal) {
                            this.pickr.setDate(newVal, false);
                        }
                    });
                });
            }
        }));
    </script>
@endscript