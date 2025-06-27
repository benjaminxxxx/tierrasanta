@props(['storageKey' => 'tabActivo'])

<div
    x-data="{
        selected: localStorage.getItem('{{ $storageKey }}') || '{{ $defaultValue ?? '' }}',
        setSelected(value) {
            this.selected = value;
            localStorage.setItem('{{ $storageKey }}', value);
        }
    }"
>
    {{ $slot }}
</div>
