{{--
    Componente: x-proceso-paso
    Props:
      - numero      (int)    : número del paso, 1-based
      - titulo      (string) : etiqueta corta
      - descripcion (string) : texto explicativo (opcional, también puede ir en $slot)

    Estados (calculados automáticamente por Alpine desde el padre):
      - completado : numero < pasoActivo
      - activo     : numero === pasoActivo
      - pendiente  : numero > pasoActivo

    IMPORTANTE: Ningún estado bloquea los botones — solo cambia color del indicador
    y opacidad del texto descriptivo. Los botones/links funcionan siempre.
--}}

@props([
    'numero'      => 1,
    'titulo'      => '',
    'descripcion' => '',
])

<div
    x-data="{ numero: {{ $numero }} }"
    :class="{
        'proceso-paso--completado': numero < pasoActivo,
        'proceso-paso--activo':     numero === pasoActivo,
        'proceso-paso--pendiente':  numero > pasoActivo,
    }"
    class="proceso-paso"
>
    {{-- Rail: línea izquierda + círculo + línea derecha --}}
    <div class="proceso-paso__rail">
        <div class="proceso-paso__linea-izq"></div>

        <div class="proceso-paso__numero">
            <span>{{ $numero }}</span>
        </div>

        <div class="proceso-paso__linea-der"></div>
    </div>

    {{-- Contenido --}}
    <div class="proceso-paso__contenido text-center">
        <div class="proceso-paso__titulo">{{ $titulo }}</div>

        <div class="proceso-paso__cuerpo">
            @if ($descripcion)
                <p class="proceso-paso__descripcion">{{ $descripcion }}</p>
            @endif

            @if ($slot->isNotEmpty())
                <div class="proceso-paso__acciones">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</div>