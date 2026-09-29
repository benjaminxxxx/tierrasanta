# legacy/

Código retirado de la aplicación que **no se borró** para poder restaurarlo si hace falta.

- La carpeta replica la ruta original: `legacy/app/Models/CompraProducto.php` estaba en `app/Models/CompraProducto.php`.
- Composer solo autocarga `app/` y Blade solo lee `resources/views/`, así que nada de aquí se ejecuta.
- **Las tablas de BD no se tocaron** (`compra_productos`, `tienda_comercials`, etc.) ni sus migraciones.
- Para restaurar un archivo: moverlo de vuelta a su ruta original (`git mv legacy/<ruta> <ruta>`) y reactivar
  lo que se desconectó (rutas, enlaces o etiquetas `<livewire:...>`), que está marcado con el comentario `LEGACY`.

## Qué hay y por qué

| Qué | Por qué se retiró |
|---|---|
| `app/Models/CompraProducto.php`, `app/Models/TiendaComercial.php` | Tablas antiguas; los datos ya se migraron a `compras` / `compra_detalles` / `proveedores`. |
| `app/Services/Insumo/CompraInsumoServicio.php` | CRUD de compras sobre `compra_productos`; solo lo usaba el componente antiguo de compras. |
| `app/Services/ProductoServicioCompras.php` | Métodos `actualizarCompra()` / `registrarCompraProducto()` que estaban en `App\Services\ProductoServicio`. |
| `app/Services/Contabilidad/GastoGeneralServicio.php`, `app/Livewire/GastoGeneralComponent.php` y vistas `gasto/general`, `livewire/gasto-general-component` | Pantalla "Gasto General" (su ruta ya estaba comentada); calculaba compras desde `compra_productos`. |
| `app/Livewire/GestionInsumos/InsumoKardexAsignacionComponent.php` y vistas `insumo-kardex-asignacion-*`, `kardex-asignacion-index` | "Asignar Entradas y Salidas (paso 2)" del kardex, sobre `compra_productos`. Ruta `gestion_insumos.kardex_asignacion` comentada en `routes/web.php`; enlace quitado del menú del kardex. |
| `app/Livewire/ProductosStockComponent.php` y su vista | Modal de stock que nunca se abría (nadie emitía `verStock`) y usaba `compra_productos`. Se quitó `<livewire:productos-stock-component/>` de `almacen/salida_productos` y `almacen/salida_combustible`. |
| `resources/views/livewire/gestion-almacen/compra-productos-component.blade.php` | Vista del componente antiguo de compras. |
| Archivos `* copy*.php` / `* copy*.js` | Copias de respaldo sueltas; nunca se cargaban. |
| `app/Livewire/GestionPlanilla/ContratosPlanillaFormComponent.php`, `.../AdministrarPlanillero/GestionPlanillaEmpleadosContratoFormComponent.php`, `.../AdministrarPlanillero/GestionPlanillaEmpleadosSueldoComponent.php`, `app/Livewire/GestionPlanilla/EmpleadoCargoComponent.php` y sus vistas | Modales sueltos de contratos, sueldos y cargo. Ahora son pestañas del panel del empleado (`GestionPlanillaEmpleadosFormComponent` + `app/Livewire/GestionPlanilla/Empleado/*TabComponent`). El panel escucha los mismos eventos (`abrirFormularioRegistroContrato`, `abrirFormularioRegistroEmpleadoSueldo`, `abrirFormularioRegistroEmpleadoCargo`), así que los botones existentes siguen funcionando. |

Retirado el 28/09/2026 (tablas antiguas) y el 28/09/2026 (modales de empleado → panel con pestañas).

| `app/Services/KardexServicio.php` | `procesarKardexConsolidado()` del reporte de kardex (por categorías, sin poner al día los kardex ni generar Excel). Reemplazado por `App\Services\Almacen\KardexReporteServicio` (por grupo operativo + Excel). Retirado el 29/09/2026. |

### Handsontable sin uso (29/09/2026)

Componentes con tablas Handsontable que ninguna ruta ni vista cargaba:

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/PlanillaBlancoDetalleComponent.php`, `app/Livewire/PlanillaNegroDetalleComponent.php` y sus vistas `planilla-blanco-detalle-component`, `planilla-negro-detalle-component` | Ninguna vista usaba `<livewire:planilla-blanco-detalle-component>` / `planilla-negro-detalle-component`. |
| `app/Livewire/CochinillaVentas/CochinillaVentaRegistroComponent.php` + vista `cochinilla_ventas/registro-component`, `app/Livewire/CochinillaVentas/CochinillaVentaRegistroFormComponent.php` | Registro de ventas antiguo; /cochinilla/ventas usa `CochinillaVentaRegistroEntregaComponent`. La vista `cochinilla_ventas/registro-form-component` **se quedó** porque la sigue usando `CochinillaVentaRegistroEntregaFormComponent`. |
| `resources/views/livewire/campania-component/` (indice + grupo-*) | Ninguna clase renderizaba `livewire.campania-component.indice`; los grupo-* solo los incluía ese índice. Reemplazado por `gestion-campania/campania-x-campo-*`. |
| `public/js/handsontable.js`, `public/js/handsontable-14-6-1.min.js` | Ninguna vista los cargaba (la versión local está en `public/handsontable/` y la v18 viene del CDN). |

### Handsontable pasa a npm (29/09/2026)

| Qué | Por qué se retiró |
|---|---|
| `resources/views/comun/handsontable_deprecado.blade.php` | Elegía por ruta entre la copia local (v16.0.1) y el CDN "latest". Ahora Handsontable se carga desde npm en `resources/js/handsontable.js` (importado por `app.js`); la versión la fija `package.json`. Se quitó su `@include` de `layouts/app.blade.php`. |
| `public/handsontable/` (handsontable.full.min.js v16.0.1 + css) | Copia local antigua. |
| `resources/views/cuadrilla/asistencia.blade.php` | Vista sin ruta; solo cargaba la copia local. |

También se quitaron las etiquetas que cargaban la copia local en `resources/views/cuadrilla/gestion/reporte_diario.blade.php`.

### Reestructuración por dominios (29/09/2026)

Se ordenó `app/Services`, `app/Livewire`, `app/Http/Controllers`, `app/Exports` y `resources/views/livewire`
en los mismos 12 dominios: Planilla, Cuadrilla, Riego, Campo, Campania, Cochinilla, Evaluacion, Producto,
Almacen, Costos, Reporte y Sistema (lo técnico —Handsontable, Excel, Data, Kardex, Pago…— va anidado dentro).

Retirado aquí:

| Qué | Por qué |
|---|---|
| 84 clases sin uso (servicios, componentes, exports de hojas) y sus 21 vistas | Detectadas con análisis de referencias (incluye cascada: lo que solo usaba código muerto). Entre ellas el chat de IA (`Ai/*`), 17 exports de hojas antiguos y componentes reemplazados. |
| `app/Support/SugerenciaHelper.php` | Solo lo usaba un componente retirado. |
| `app/Services/Cuadrillas/CuadrilleroServicio.php`, `.../RegistroDiarioServicio.php` | El primero no se usaba; el método `reemplazarCuadrillero` del segundo se fusionó en `App\Services\Cuadrilla\RegistroDiarioServicio`. |
| Todos los controladores de `app/Http/Controllers/*` (menos `Controller.php`) | Solo devolvían una vista. Las rutas apuntan ahora al componente (página completa, título con `#[Title]`) o usan `Route::view`. Las validaciones de existencia pasaron al `mount` de cada componente. |
| 3 rutas POST de `ReporteDiarioController` | Apuntaban a métodos que ya no existían. |
| 42 vistas sin referencias | Envoltorios de controladores retirados y vistas sueltas sin componente. |

`tareas_pendientes.servicio` guarda el nombre de la clase detectora: la migración
`2026_09_29_120000_actualizar_servicio_tareas_pendientes_por_dominios` lo actualiza (con `down` para revertir).

Segunda parte (29/09/2026):
- Páginas que usaba `Route::view` movidas a `resources/views/livewire/<dominio>/*-indice` (antes en `almacen/`, `campo/`,
  `cochinilla/`, `configuracion/`, `consolidado/`, `maquinarias/`, `planilla/`, `productos/`, `cuadrilla/gestion/`).
  Se quedan fuera de los dominios solo `comun/` (selectores compartidos) y `reportes/` (plantillas PDF).
- `resources/views/planilla/asistencia.blade.php` y `resources/views/dashboard.blade.php` pasaron a legacy: ninguna
  ruta ni componente los usaba (solo coincidían con nombres de ruta).
- `app/Procesos/Cuadrillas/ReemplazarCuadrillero` → `App\Services\Cuadrilla\ReemplazarCuadrillero`;
  `app/Domain/DerechoHabiente/*` → `App\Services\Planilla\DerechoHabiente\*`.

| `app/Services/Planilla/RegistroDiario/PlanillaRegistroDiarioProcesoSuspensionesPendientes.php` | Sugerencias de suspensiones con año fijo 2026, sin unir domingos y con mapeo fijo en el componente. Reemplazado por `App\Services\Planilla\Asistencia\SugerenciaSuspensionServicio` (usa el vínculo de cada tipo de asistencia). Retirado el 29/09/2026. |
