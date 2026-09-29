@props(['storageKey' => 'tabActivo', 'defaultValue' => '', 'remember' => true])

<div
    x-data="{
        selected: {{ $remember ? "localStorage.getItem('$storageKey') || '$defaultValue'" : "'$defaultValue'" }},
        setSelected(value) {
            this.selected = value;
            if ({{ $remember ? 'true' : 'false' }}) {
                localStorage.setItem('{{ $storageKey }}', value);
            }
        }
    }"
    x-on:reset-tab.window="selected = '{{ $defaultValue }}'; {{ $remember ? "localStorage.setItem('$storageKey', '$defaultValue')" : '' }}"
>
    {{ $slot }}
</div>
