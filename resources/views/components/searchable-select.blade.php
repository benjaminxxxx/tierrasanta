{{--
    Buscar en una lista y elegir (flechas ↑ ↓ y Enter). Un solo componente para todos los casos:

    1) Lista desde el servidor (se recarga sola si Livewire la cambia):
         <x-searchable-select :options="$empleados" wire:model.live="empleadoId" />
       $options: [['id' => 1, 'name' => 'Ana'], …], una colección o un arreglo [id => nombre].

    2) Lista enlazada a una propiedad del componente (el padre la cambia cuando quiera):
         <x-searchable-select opciones="cuadrilleros" wire:model="cuadrilleroId" />

    3) Búsqueda en el servidor mientras se escribe (listas grandes):
         <x-searchable-select buscar="getProductos" wire:model="productoId" :etiqueta="$productoNombre" />
       El método recibe el texto y devuelve [['id' => …, 'name' => …], …]. "etiqueta" es el texto a mostrar si el
       valor ya viene elegido; el servidor puede cambiarlo con
       $this->dispatch('searchableEtiqueta', model: 'productoId', etiqueta: '…').
--}}
@props([
    'options' => null,
    'opciones' => null,
    'buscar' => null,
    'source' => null, // nombre anterior de "buscar" (x-select-dropdown)
    'etiqueta' => null,
    'placeholder' => null,
    'searchPlaceholder' => 'Buscar...',
    'minimo' => 2,
    'name' => null,
    'disabled' => false,
])

@php
    $wireModel = $attributes->wire('model');
    $modelo = $wireModel->value();
    $metodo = $buscar ?: $source;
    $modo = $metodo ? 'buscar' : ($opciones ? 'enlazada' : 'fija');

    // Normaliza la lista: [['id','name']], colección de esos, o [id => nombre]
    $lista = [];
    if ($modo === 'fija') {
        $origen = $options instanceof \Illuminate\Support\Collection ? $options->all() : (array) ($options ?? []);
        foreach ($origen as $clave => $opcion) {
            $opcion = $opcion instanceof \Illuminate\Contracts\Support\Arrayable ? $opcion->toArray() : $opcion;
            $lista[] = is_array($opcion) || is_object($opcion)
                ? ['id' => data_get($opcion, 'id'), 'name' => (string) data_get($opcion, 'name')]
                : ['id' => $clave, 'name' => (string) $opcion];
        }
    }
    $textoVacio = $placeholder ?: $searchPlaceholder;
@endphp

<div
    x-data="searchableSelect({
        modo: @js($modo),
        valor: @entangle($wireModel),
        opciones: @if ($modo === 'enlazada') @entangle($opciones) @else [] @endif,
        metodo: @js($metodo),
        modelo: @js($modelo),
        minimo: @js((int) $minimo),
        etiqueta: @js($etiqueta),
    })"
    @if ($modo === 'fija') data-opciones="{{ json_encode($lista, JSON_UNESCAPED_UNICODE) }}" @endif
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative w-full']) }}
    @click.outside="cerrar()"
    @keydown.escape="cerrar()"
>
    @if ($name)
        <input type="hidden" name="{{ $name }}" :value="valor ?? ''">
    @endif

    <x-searchable-select.panel :modo="$modo" :minimo="$minimo" :disabled="$disabled" :placeholder="$textoVacio" />
</div>
