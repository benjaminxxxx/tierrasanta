{{-- resources/views/components/selector-dia.blade.php --}}
<div 
    x-data="{ 
        picker: null,
        value: @entangle($attributes->wire('model'))
    }"
    x-init="
        picker = flatpickr($refs.input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'Y-m-d',
            allowInput: true,
            defaultDate: value,
            onChange: function(selectedDates, dateStr) {
                value = dateStr;
            }
        });
        
        $watch('value', val => {
            if (picker && val !== picker.input.value) {
                picker.setDate(val, false);
            }
        });
    "
    wire:ignore
>
    <x-input 
        type="text"
        x-ref="input"
        class="w-auto"
        {{ $attributes->except('wire:model') }}
    />
</div>