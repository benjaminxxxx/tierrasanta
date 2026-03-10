{{--
    Componente: x-proceso-timeline
    Props:
      - pasoActivo (int)  : paso activo 1-based
      - vertical (bool)   : false por defecto → horizontal

    Uso:
      <x-proceso-timeline :pasoActivo="2">
          <x-proceso-paso numero="1" titulo="Paso uno"> ... </x-proceso-paso>
          <x-proceso-paso numero="2" titulo="Paso dos"> ... </x-proceso-paso>
      </x-proceso-timeline>
--}}

@props(['pasoActivo' => 1, 'vertical' => false])

<div
    x-data="{ pasoActivo: {{ $pasoActivo }} }"
    class="proceso-timeline {{ $vertical ? 'proceso-timeline--vertical' : 'proceso-timeline--horizontal' }}"
>
    {{ $slot }}
</div>

<style>
.proceso-timeline {
    --pt-accent:      #d4a853;
    --pt-accent-glow: #d4a85338;
    --pt-done:        #5a8a6a;
    --pt-done-bg:     #5a8a6a22;
    --pt-idle:        #3a3a4a;
    --pt-idle-border: #4a4a5e;
    --pt-line:        #2e2e3e;
    --pt-text:        #e2e2f0;
    --pt-muted:       #8888aa;
    --pt-radius:      10px;
}

/* ══════════════ HORIZONTAL (defecto) ══════════════ */
.proceso-timeline--horizontal {
    display: flex;
    align-items: flex-start;
    width: 100%;
}
.proceso-timeline--horizontal .proceso-paso {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 0.5rem;
}
.proceso-timeline--horizontal .proceso-paso__rail {
    display: flex;
    align-items: center;
    width: 100%;
    margin-bottom: 0.75rem;
}
.proceso-timeline--horizontal .proceso-paso__linea-izq,
.proceso-timeline--horizontal .proceso-paso__linea-der {
    flex: 1;
    height: 2px;
    background: var(--pt-line);
    transition: background 0.4s ease;
    border-radius: 2px;
}
.proceso-timeline--horizontal .proceso-paso:first-child .proceso-paso__linea-izq,
.proceso-timeline--horizontal .proceso-paso:last-child  .proceso-paso__linea-der {
    background: transparent !important;
}
.proceso-timeline--horizontal .proceso-paso__contenido {
    text-align: center;
    width: 100%;
}
.proceso-timeline--horizontal .proceso-paso__cuerpo {
    text-align: center;
}

/* ══════════════ VERTICAL ══════════════ */
.proceso-timeline--vertical {
    display: flex;
    flex-direction: column;
}
.proceso-timeline--vertical .proceso-paso {
    display: flex;
    flex-direction: row;
    gap: 1.25rem;
}
.proceso-timeline--vertical .proceso-paso__rail {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 40px;
}
.proceso-timeline--vertical .proceso-paso__linea-izq { display: none; }
.proceso-timeline--vertical .proceso-paso__linea-der {
    width: 2px;
    flex: 1;
    min-height: 1.5rem;
    background: var(--pt-line);
    margin-top: 4px;
    border-radius: 2px;
    transition: background 0.4s ease;
}
.proceso-timeline--vertical .proceso-paso:last-child .proceso-paso__linea-der { display: none; }
.proceso-timeline--vertical .proceso-paso__contenido {
    flex: 1;
    padding-bottom: 2rem;
    padding-top: 0.4rem;
}
.proceso-timeline--vertical .proceso-paso:last-child .proceso-paso__contenido { padding-bottom: 0; }

/* ══════════════ ELEMENTOS COMPARTIDOS ══════════════ */
.proceso-paso__numero {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 700;
    border: 2px solid var(--pt-idle-border);
    background: var(--pt-idle);
    color: var(--pt-muted);
    transition: all 0.35s ease;
    position: relative;
    flex-shrink: 0;
    z-index: 1;
}
.proceso-paso__titulo {
    font-size: 0.68rem;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--pt-muted);
    transition: color 0.3s ease;
    margin-bottom: 0.4rem;
}
.proceso-paso__cuerpo {
    margin-top: 0.5rem;
    border-radius: var(--pt-radius);
    padding: 0.875rem 1rem;
    border: 1px solid transparent;
    transition: all 0.3s ease;
}
.proceso-paso__descripcion {
    font-size: 0.85rem;
    color: var(--pt-muted);
    line-height: 1.6;
    margin-bottom: 0.75rem;
    transition: color 0.3s ease;
}
.proceso-paso__acciones { margin-top: 0.75rem; }

/* ══════════════ ESTADOS ══════════════ */

/* COMPLETADO */
.proceso-paso--completado .proceso-paso__numero {
    background: var(--pt-done-bg);
    border-color: var(--pt-done);
    color: transparent;
}
.proceso-paso--completado .proceso-paso__numero::after {
    content: '';
    position: absolute;
    width: 12px;
    height: 7px;
    border-left: 2.5px solid var(--pt-done);
    border-bottom: 2.5px solid var(--pt-done);
    transform: rotate(-45deg) translateY(-1px);
}
.proceso-paso--completado .proceso-paso__titulo { color: var(--pt-done); }
.proceso-paso--completado .proceso-paso__linea-der { background: var(--pt-done); opacity: 0.4; }
.proceso-timeline--horizontal .proceso-paso--completado .proceso-paso__linea-der { background: var(--pt-done); opacity: 0.4; }

/* ACTIVO */
.proceso-paso--activo .proceso-paso__numero {
    background: var(--pt-accent-glow);
    border-color: var(--pt-accent);
    color: var(--pt-accent);
    box-shadow: 0 0 0 5px var(--pt-accent-glow);
    animation: pt-pulso 2.5s ease-in-out infinite;
}
.proceso-paso--activo .proceso-paso__titulo { color: var(--pt-accent); }
.proceso-paso--activo .proceso-paso__cuerpo {
    border-color: var(--pt-idle-border);
    background: rgba(255,255,255,0.03);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.proceso-paso--activo .proceso-paso__descripcion { color: var(--pt-text); }
.proceso-timeline--horizontal .proceso-paso--activo .proceso-paso__linea-izq { background: var(--pt-done); opacity: 0.4; }

/* PENDIENTE — visual atenuado pero botones completamente funcionales */
.proceso-paso--pendiente .proceso-paso__numero { opacity: 0.45; }
.proceso-paso--pendiente .proceso-paso__titulo { opacity: 0.5; }
.proceso-paso--pendiente .proceso-paso__descripcion { opacity: 0.55; }

@keyframes pt-pulso {
    0%, 100% { box-shadow: 0 0 0 5px var(--pt-accent-glow); }
    50%       { box-shadow: 0 0 0 9px transparent; }
}
</style>