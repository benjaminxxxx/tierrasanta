# Tierra Santa — Guía de diseño

> Instrumento de trabajo sobre concreto blanco, con una sola chispa carmín. Superficies neutras y casi monocromas donde
> el color de la cochinilla marca la acción principal; el azul noche del logo enmarca la marca.

Basado en el estilo **Brex** de [Refero Styles](https://styles.refero.design/style/b58d92f6-68a8-4358-8fc9-6ea58e6d483b),
adaptado a un sistema administrativo: el naranja de Brex se reemplaza por el **carmín** y el **azul noche** del logo, y
se agregan densidad para tablas, colores de estado y modo oscuro.

**Cómo se aplica:** por módulo. Cada vez que se trabaje la interfaz de un módulo, se lee esta guía y se ajusta ese
módulo (vistas, componentes que use). No se cambian los tokens globales de `app.css` de golpe: los tokens nuevos
conviven con los actuales (`--primary`, `--accent`…) hasta que todos los módulos estén migrados.

---

## Colores

### Marca

| Nombre | Claro | Oscuro | Token / clase | Uso |
|---|---|---|---|---|
| Carmín | `#BC002D` | `#E0335A` | `--color-carmin` / `carmin` | **Solo la acción principal de cada región** (Guardar, Ingresar, Confirmar), foco de inputs, pestaña activa. Nunca de fondo decorativo. |
| Carmín hover | `#A00026` | `#EA5577` | `--color-carmin-hover` | Hover/pressed del botón principal. |
| Noche | `#10212D` | `#0B1820` | `--color-noche` / `noche` | Marco de marca: panel del login, encabezados oscuros. No como superficie genérica dentro del contenido. |

### Neutros

| Nombre | Claro | Oscuro | Uso |
|---|---|---|---|
| Papel (canvas) | `#FFFFFF` | `#0E1419` | Fondo de página y de tarjetas. |
| Niebla (fog) | `#F3F3F7` | `#151C22` | Fondo alterno: bandas, cabecera de tablas, inputs deshabilitados, filas "sin cambios". |
| Bruma (mist) | `#B9BBC6` | `#2C353D` | Bordes de 1px de inputs, tarjetas y divisores (reemplaza a las sombras). |
| Línea (hairline) | `#E4E5EB` | `#222B32` | Divisores suaves dentro de tablas y listas. |
| Acero (steel) | `#8B8D98` | `#7D8590` | Placeholders, íconos secundarios. Nunca para textos de más de una etiqueta. |
| Peltre (pewter) | `#6F737B` | `#9AA1AA` | Ayudas, captions, textos de apoyo. |
| Grafito | `#60646C` | `#B4BAC2` | Texto secundario y párrafos. |
| Tinta (ink) | `#0B0D10` | `#EDEEF2` | Texto principal y títulos. |

### Estado (solo para comunicar estado, no para decorar)

| Estado | Texto / ícono | Fondo suave | Uso |
|---|---|---|---|
| Éxito | `#15803D` | `#ECFDF3` | Guardado, "nuevo" en importaciones, saldos cuadrados. |
| Aviso | `#B45309` | `#FFF8EB` | "Con cambios", pendientes, datos por revisar. |
| Error | `#C81E1E` | `#FEF2F2` | Errores de validación, "con errores". Siempre con ícono o texto, para no confundirlo con el carmín de marca. |
| Info | `#1D4ED8` | `#EFF4FF` | Notas informativas. |

En modo oscuro el texto de estado usa la versión 400 (verde, ámbar, rojo, azul) y el fondo suave al 10–15 % de opacidad.

---

## Tipografía

**Inter** para todo (ya es la fuente del sistema). Pesos: 400 texto, 500 interfaz (botones, labels, navegación), 600
títulos y énfasis. Sin otras familias.

- Tracking negativo leve: `-0.01em` hasta 24px, `-0.02em` en títulos de 30px o más.
- `font-feature-settings: "cv11", "ss01"` opcional; sin ligaduras decorativas.
- Números en tablas: `tabular-nums` y alineados a la derecha.

| Rol | Tamaño | Peso | Interlineado |
|---|---|---|---|
| Caption / ayuda | 12px | 400 | 1.5 |
| Celda de tabla | 13px | 400 | 1.4 |
| Texto | 14px | 400 | 1.45 |
| Label / botón | 14px | 500 | 1.4 |
| Subtítulo | 16px | 600 | 1.4 |
| Título de sección | 20px | 600 | 1.35 |
| Título de página | 24px | 600 | 1.3 |
| Titular de marca (solo login) | 30–36px | 600 | 1.2 |

---

## Espaciado, radios y elevación

- **Unidad base 8px.** Separación entre elementos 8–16px, padding de tarjeta 16–24px, entre secciones 24–32px.
- **Densidad:** cómoda en formularios; **compacta en tablas** (fila 32–36px, celda 13px, padding horizontal 8–12px).
  A diferencia de Brex, aquí la densidad de datos manda en listados y Handsontable.
- **Radios:** 10px botones, inputs y tarjetas; 6px etiquetas, chips y celdas de estado; 16px modales y el panel del
  login. No mezclar radios dentro del mismo grupo de componentes.
- **Elevación:** sin sombras en tarjetas. Se separan con borde Bruma de 1px o con fondo Niebla. Solo lo que flota
  (modales, menús, toasts, menús de Handsontable) lleva una sombra suave: `0 8px 24px rgb(16 33 45 / 0.12)`.
- **Foco:** anillo carmín de 3px al 25 % (`ring-carmin/25`) + borde carmín.

---

## Componentes

### Botón principal (`x-button`, acción principal)
Fondo Carmín, texto blanco, 500–600, radio 10px, alto 36px (40px en login). Hover Carmín hover. **Uno por región**:
si hay dos acciones, la otra es secundaria.

### Botón secundario
Fondo Papel, borde Bruma 1px, texto Tinta. Hover fondo Niebla.

### Botón fantasma / enlace
Sin fondo ni borde, texto Tinta (o Grafito), subrayado en hover. Enlaces dentro de párrafos: Tinta subrayado, nunca carmín.

### Input / select (`x-input`, `x-select`, `x-fecha`)
Fondo Papel, borde Bruma 1px, radio 10px, alto 36px, placeholder Acero. Foco: borde carmín + anillo carmín/25. Error:
borde rojo de estado y mensaje debajo (que no empuje el diseño). Label arriba en 14px/500 Tinta.

### Tarjeta (`x-card`)
Fondo Papel, borde Bruma 1px (o Línea en tarjetas dentro de tarjetas), radio 10px, padding 16–24px, sin sombra.

### Tabla / listado
Cabecera fondo Niebla, texto 12px/500 Grafito en mayúsculas pequeñas o normal; filas separadas por Línea; hover fila
Niebla; números a la derecha con `tabular-nums`. Estados con texto de color de estado, no con fondos fuertes.

### Handsontable
Mismas reglas de tabla: cabeceras Niebla, bordes Línea, celdas de solo lectura en Grafito sobre Niebla, celda activa
con borde carmín. Menús contextuales con la sombra de elementos flotantes.

### Gráficos (Chart.js, `resources/js/components/inicio-grafico.js`)
Los colores de datos no son de marca: nunca carmín. Paleta validada (contraste y daltonismo) contra Papel claro y oscuro,
en este orden fijo: serie 1 `#2a78d6` / oscuro `#3987e5`, serie 2 `#eb6834` / `#d95926`, serie 3 `#1baf7a` / `#199e70`;
atenuado `#d5d7de` / `#2c353d`. Una serie: el dato destacado en serie 1 y el resto atenuado (el título dice qué se mide,
sin leyenda). Varias series: leyenda con la marca de color y el texto en tinta. Barras de 24px máx., 4px redondeados
solo en el extremo, 2px de separación entre segmentos apilados, cuadrícula Línea, ejes Peltre, un solo eje Y, sin
animación. Siempre con tooltip y "Ver tabla" (la serie 3 no llega a 3:1 sobre Papel claro).

### Modal (`x-dialog-modal`)
Fondo Papel, radio 16px, sombra flotante, título 16–20px/600. Pie con la acción principal carmín a la derecha y
"Cancelar" secundario.

### Pestañas
Texto Grafito; activa en Tinta con subrayado carmín de 2px.

### Etiquetas de estado (chips)
Radio 6px, texto de estado sobre su fondo suave, 12px/500.

### Barra lateral y encabezado
Estilo explorador de archivos: Inicio, "Acceso directo" (de cada usuario, con pin para quitar y arrastrar para ordenar) y
los grupos en árbol con flecha. Filas de 32px, radio 6px, texto 13px; hover Niebla; ítem activo en Tinta 500 sobre
Niebla con marca carmín de 3px a la izquierda. Excepción a "sin colores decorativos": cada grupo tiene un color de ícono
fijo (SistemaMenuReglas::COLORES, versión clara y oscura) para reconocerlo de un vistazo; el texto siempre en tinta.
Clic derecho en una opción: menú contextual (radio 10px, sombra flotante) con abrir, nueva pestaña y agregar / quitar.
Los grupos cargan sin expandir; abrir el de la página actual o recordar los abiertos son preferencias de cada usuario.

### Login y pantallas de acceso
Pantalla partida: panel de marca en Noche (logo `logo-horizontal.svg` en blanco, titular, una línea de descripción) y
formulario sobre Papel. En celular solo el formulario, con el logo a color arriba. Botón Ingresar carmín a todo el ancho.

---

## Sí

- Un solo color de marca (carmín) y solo para la acción principal de cada región.
- Bordes finos en lugar de sombras; Niebla para separar bandas.
- Textos en español, cortos y en tono de trabajo ("Guardar", "Subir Excel", "Con cambios").
- Números alineados a la derecha con `tabular-nums`; fechas en dd/mm/aaaa.
- Diseñar y probar los dos modos (claro y oscuro).

## No

- No agregar otro color de marca (azules, morados, verdes decorativos). Los colores de estado solo comunican estado.
- No usar carmín para errores ni para fondos grandes.
- No usar Noche como superficie genérica dentro del contenido.
- No usar sombras en tarjetas ni mezclar radios (8px aquí, 12px allá) en el mismo grupo.
- No usar Acero para párrafos (no tiene contraste suficiente).
- No centrar párrafos; solo títulos cortos.

---

## Tokens (CSS)

Definidos en `resources/css/app.css` (`:root` y `.dark`) y expuestos en `tailwind.config.js` como `carmin`, `noche`,
`fog`, `mist`, `hairline`, `steel`, `pewter`, `graphite`, `ink`.

```css
/* resources/css/app.css — canales RGB para usar opacidad: bg-carmin, ring-carmin/25, border-mist */
:root {
  --color-carmin: 188 0 45;        /* #BC002D */
  --color-carmin-hover: 160 0 38;  /* #A00026 */
  --color-noche: 16 33 45;         /* #10212D */
  --color-paper: 255 255 255;
  --color-fog: 243 243 247;
  --color-mist: 185 187 198;
  --color-hairline: 228 229 235;
  --color-steel: 139 141 152;
  --color-pewter: 111 115 123;
  --color-graphite: 96 100 108;
  --color-ink: 11 13 16;
}
.dark {
  --color-carmin: 224 51 90;       /* #E0335A */
  --color-carmin-hover: 234 85 119;
  --color-noche: 11 24 32;
  --color-paper: 14 20 25;
  --color-fog: 21 28 34;
  --color-mist: 44 53 61;
  --color-hairline: 34 43 50;
  --color-steel: 125 133 144;
  --color-pewter: 154 161 170;
  --color-graphite: 180 186 194;
  --color-ink: 237 238 242;
}
```

## Módulos migrados

| Módulo | Fecha |
|---|---|
| Login (pantalla de acceso) | 21/10/2026 |
| Inicio (dashboard) | 21/10/2026 |
| Labores (lista) y botón flotante "Recursos" | 21/10/2026 |
| Menú lateral (7 grupos con secciones) | 22/10/2026 |
| Escritorio de archivos del inicio | 22/10/2026 |
| Empleados: pestañas Historial laboral y Pensión y pago, botón flotante del Excel de empleados | 23/10/2026 |
| Producción de campo (página nueva) y "Producción sin bono" dentro de Bonificaciones | 23/10/2026 |
| Registro diario de planilla: solo el aviso "Ausencias programadas" (la tabla sigue sin migrar) | 24/10/2026 |
| Parámetros de planilla (tarjetas + modal de historial) y modal "Generar planilla" | 24/10/2026 |
| Menú lateral estilo explorador con Acceso directo por usuario (Planilla y Cuadrilla separados) | 24/10/2026 |
| Registro diario de riego: barra superior, fecha compacta y modal "Agregar regadores" (las tarjetas de cada regador siguen sin migrar) | 24/10/2026 |
| Campañas (/campania/resumen): vista Lista, contadores, filtros, menú y estado de cosecha (la Tabla técnica sigue sin migrar) | 24/10/2026 |
