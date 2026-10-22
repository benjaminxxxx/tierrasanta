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

### Gasto de cuadrilla por campaña (30/09/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Services/Cuadrilla/CuadrillaServicio.php` | `calcularGastoCuadrilla()` usaba variables que no existían (`$documento`, `$empleadoNombre`, `$horasTotales`, `$horasTrabajadas`, `$costoHora`, `$totalCosto`, `$totalBono`): habría fallado al ejecutarse. `obtenerGruposCuadrilla()` no se usaba. |
| `app/Services/Campania/CampaniaHistorialServicioGastos.php` | Métodos `actualizarGastosyConsumos()`, `gastoPlanilla()` y `gastoCuadrilla()` que estaban en `App\Services\Campania\CampaniaHistorialServicio`. Nadie llamaba a `actualizarGastosyConsumos()`, que era el único camino hacia `CuadrillaServicio`. |
| `app/Services/Planilla/PlanillaSueldoGastoServicioGastos.php` | Métodos `calcularGastoPlanilla()`, `procesarExcelGastoPlanilla()`, `obtenerPlanillas()`, `calcularFactor()` y `procesarExcelPlanillaDetalle()` que estaban en `App\Services\Planilla\PlanillaSueldoGastoServicio`. `calcularGastoPlanilla()` solo lo llamaba `CampaniaHistorialServicio::gastoPlanilla()` (retirado arriba); los tres siguientes solo los usaba él y `procesarExcelPlanillaDetalle()` no tenía llamadas. |

### Modales de campaña reemplazados (30/09/2026)

Los modales de campaña de `app/Livewire/GestionCampania/` se habían quitado del menú global (para que no se cargaran
en todas las páginas) y nadie más escuchaba sus eventos, así que "Registrar nueva campaña" y "Editar campaña" no hacían
nada. **Siguen aquí como referencia**; los reemplazan:

| En legacy | Reemplazo en app/ |
|---|---|
| `app/Livewire/GestionCampania/CampaniasFormNuevoComponent.php` + vista | `App\Livewire\Campania\CampaniaFichaComponent` (wizard de registro) |
| `app/Livewire/GestionCampania/CampaniasFormComponent.php` + vista | `App\Livewire\Campania\CampaniaFichaComponent` (pestañas de edición) y `CampaniaCerrarComponent` |

Se montan solo en las páginas que los usan (`/campania/resumen`, `/campania/por-campo`, `/campo/campos`) y guardan
con `App\Services\Campania\Registro\CampaniaRegistroProceso`.

### Reporte diario de actividades (30/09/2026)

El bloque "actividades" de `/reporte/diario` se mostraba pero no se usaba, y dependía de la vista SQL
`v_reporte_actividades_diario`, tan lenta (minutos incluso con `LIMIT 1`) que bloqueaba la tabla `actividades`.

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Reporte/ReporteDiarioActividadesComponent.php` + vista `reporte/reporte-diario-actividades-component` | Bloque sin uso de la página Reporte Diario. Se quitó su `<livewire:...>` de `reporte-diario-component` (comentario `LEGACY`); el bloque de asistencias sigue. |
| `app/Services/Planilla/RegistroDiario/ActividadesResumenServicio.php` | Solo lo usaba ese componente. Era lo único que consultaba la vista (y volcaba el resultado en `rep_actividades_diarias`). |
| `app/Models/ReporteActividadDiario.php` | Modelo de la vista; no lo usaba nadie. |
| `app/Models/RepActividadDiaria.php` | Modelo de `rep_actividades_diarias`; solo lo usaba el servicio. |
| Vista SQL `v_reporte_actividades_diario` | Eliminada de la BD con la migración `2026_09_30_130000_drop_v_reporte_actividades_diario`. Su definición está en `legacy/database/views/v_reporte_actividades_diario.sql` y el `down` de la migración la recrea. |

La tabla `rep_actividades_diarias` (63 filas) no se tocó.

### Cálculos antiguos de plan_mensual_detalles (30/09/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Models/PlanMensualDetalleCalculosLegacy.php` (trait) | 17 accessors que estaban en `App\Models\PlanMensualDetalle` (`blanco_*`, `costo_total_*`, `sueldo_negro_*`, `remuneracion_basica*`). Calculaban la planilla blanco/negro con columnas que solo tienen datos hasta 01/2026. Nadie los leía, pero estaban en `$appends`: se calculaban al serializar cada detalle, con una consulta por fila, y `getRemuneracionBasicaAttribute` sobrescribía la columna. Hoy esos valores vienen del PLAME (`PlanMensualPersonal`). Para restaurarlos: `use PlanMensualDetalleCalculosLegacy;` en el modelo. |

Las columnas históricas de `plan_mensual_detalles` no se tocaron (ver el docblock del modelo).

### Asistencia mensual antigua (02/10/2026)

`/planilla/asistencia` ahora abre la versión que era beta en `/planilla/asistencias` (`AsistenciaMensualComponent`, con
Handsontable y los sueldos de la planilla generada); `/planilla/asistencias` redirige ahí.

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Planilla/Asistencia/GestionPlanillaAsistenciasComponent.php`, `.../GestionPlanillaDetalleAsistenciasComponent.php` y vistas `planilla/asistencia/gestion-planilla-asistencias`, `gestion-planilla-detalle-asistencias` | Pantalla antigua de asistencia mensual (solo horas). La ruta `planilla.asistencia` apunta al componente nuevo (comentario `LEGACY` en `routes/web.php`). |
| `app/Services/Planilla/PlanillaServicioGastosMensuales.php` | Métodos `calcularGastosMensuales()`, `procesarCostosDiarios()` y `calcularSueldoRealLiquidado()` que estaban en `App\Services\Planilla\PlanillaServicio`: el botón "Recalcular pagos en planilla". Fallaba al guardar (escribía dos columnas que no existen en `plan_mensual_detalles`). El costo de cada día (`plan_registros_diarios.costo_dia`) ahora se calcula solo al generar la planilla (`PlanillaServicio::actualizarCostosDiarios`). |

`App\Services\Planilla\Handsontable\HSTPlanillaAsistencia` se queda en `app/`: lo usa el Excel de la planilla mensual.

### Acordeón del informe de campaña por campo (06/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `resources/views/livewire/campania/partials/accordion-section.blade.php` | Fila de encabezado del acordeón antiguo de `/campania/por-campo`. Las secciones ahora son tarjetas ordenables (arrastrar o flechas) con vista compacta, dentro de `campania-por-campo-informe-component` (Alpine `informeCampania`). Los parciales de cada sección no cambiaron. |

### Permisos y suspensiones (08/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Planilla/Asistencia/SuspensionesPlanillaComponent.php` (versión Handsontable), su vista y `app/Services/Planilla/PlanillaSuspensionProceso.php` | Tabla editable de suspensiones: cada fila se validaba contra la base por separado, así que correr el límite entre dos rangos seguidos obligaba a guardar uno, luego el otro (o eliminar y volver a crear). Además solo dejaba fechas de inicio dentro del mes. El componente nuevo (mismo nombre) agrupa por trabajador, edita en un modal y valida el conjunto (`App\Services\Planilla\Suspension\PlanillaSuspensionCrud`); permite rangos futuros. |

### Cuadrilla: resumen por tramo guardado (09/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Services/Cuadrilla/ResumenTramoServicio.php`, `app/Models/CuadResumenPorTramo.php` | Guardaban el cuadro resumen del tramo en `cuad_resumen_tramos` y solo se rehacía con "Recalcular resumen": después de un pago el cuadro quedaba desactualizado. Ahora `GestionCuadrillaResumenAcumuladoComponent` lo calcula al abrirlo con `ResumenTramoInformativoServicio` (desde el estado de pago del desglose). La tabla `cuad_resumen_tramos` queda en la base sin uso (se puede eliminar con una migración cuando se confirme). También se quitaron del reporte semanal (`GestionCuadrillaReporteSemanalTramoComponent`) las versiones comentadas de `guardarReporteSemanal` y `registrarCostoPersonalizado`, `listarResumenes`, `recalcularResumenTramo` (solo mostraba un mensaje) y `TramoLaboralServicio::generarResumen` (vacío). |

### Cuadrilla: código de la versión anterior (10/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Models/CuadOrdenSemanal.php` y 12 métodos de `CuadrilleroServicio` (`obtenerListaGruposOrdenados`, `listarHandsontableGastosAdicionales`, `guardarGastosAdicionalesXGrupo`, `guardarBonoCuadrillaobsoleta`, `obtenerHandsontableRegistrosPorActividad`, `obtenerHandsontableReporte`, `asignarGrupoPeriodo`, `registrarOrdenSemanal`, `guardarReporteSemanal`, `sincronizarOrdenGruposSemana`, `getLaborNombre`, `getLaborCodigo`) | De la versión por semanas anterior a los tramos: nadie los llamaba y varios usaban tablas que ya no existen (`cuad_orden_semanal`, el modelo `CuadGrupoOrden` ni existía). `guardarGastosAdicionalesXGrupo` además borraba y volvía a crear todos los gastos del rango, perdiendo su vínculo con el pago. Se quitaron de `app/` (quedan en el historial de git). |

### Buscadores con lista: un solo `x-searchable-select` (11/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `resources/views/components/searchable.blade.php`, `searchable-dinamico.blade.php`, `searchable-select-stable.blade.php`, `select-dropdown.blade.php` | Cuatro variantes del mismo buscador. `searchable` y `searchable-dinamico` registraban las dos un `Alpine.data('searchable')` distinto (la última en cargar pisaba a la otra); `searchable-select-stable` era una copia sin uso; la lista pasada con `:options` quedaba congelada en Alpine aunque Livewire la cambiara; y cada una traía su `<script>` dentro de la vista, que no se ejecuta si el componente aparece después (por ejemplo dentro de un modal). Ahora todo es `x-searchable-select` con su lógica en `resources/js/components/searchable-select.js` (registrada una vez en `app.js`) y tres modos: `:options` (se recarga sola), `opciones="propiedad"` (enlazada, antes `searchable-dinamico`) y `buscar="metodo"` (búsqueda en el servidor, antes `select-dropdown`; `source` sigue aceptándose, y el evento `selectDropdownEtiqueta` también). `x-autocomplete` se mantiene aparte: es texto libre con sugerencias, no elige un id. |

### Planilla: modal duplicado del orden mensual (17/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `resources/views/livewire/planilla/registro-diario/partials/orden-planilla-mensual.blade.php` y, en `GestionPlanillaRegistroDiarioComponent`, `gestionarListaMensual()`, `$listaPlanilla` y `$mostrarListaPlanillaMensual` | Copia del modal "Orden Mensual de Empleados" incluida en el registro diario de planilla. Nunca se abría (el botón de esa pantalla abre `OrdenarPlanillaMensualComponent`, que es el que funciona) y sus botones llamaban a métodos que este componente no tiene. Además registraba el mismo `Alpine.data('ordenMensualPlanilla')` que el modal real. |

### Campañas: calendario de todas las campañas (17/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Campania/CampaniaCalendarioComponent.php`, `resources/views/livewire/campania/campania-calendario-component.blade.php`, `campania-calendario-indice.blade.php` y la ruta `campania.calendario` | Línea de tiempo de campañas por campo que solo enlazaba al detalle. Todo se centraliza en Campaña → Campañas (`/campania/resumen`): buscar, filtrar, avance vegetativo, etapa, informe al día o final, informes mensuales, editar, cerrar y eliminar. El permiso `Campañas Calendario` queda sin uso. |

### Costos Generales FDM (19/10/2026)

Reemplazado por **Costos → Mano de Obra Indirecta** (`/costos/mano-obra-indirecta`, `App\Services\Costos\ManoObraIndirecta\*`):
mismo cálculo (todo lo gastado en FDM) pero explicado por sección, en blanco y negro, guardado en el costo mensual y con
Excel de fórmulas. Las URLs `/costos/fdm` y `/fdm/costos_generales` redirigen a la pantalla nueva.

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Costos/FdmComponent.php`, `FdmCostosComponent.php` y vistas `livewire/costos/fdm-component`, `fdm-costos-component` | Pantalla `/costos/fdm`. Ruta `costos.fdm` quitada de `routes/web.php` (comentario `LEGACY`) y del menú de Contabilidad. |
| `app/Services/Costos/Fdm/` (`CostoServicio`, `CostoFdmServicio`, `PlanillaFdmServicio`, `CuadrillaFdmServicio`, `MaquinariaFdmServicio`) | Cálculos FDM por partes guardados en `parametros_mensuales` / `costo_mano_indirectas`. La planilla leía columnas que ya no se llenan; el combustible no contaba las máquinas sin distribución. |
| `app/Models/CostoFdmMensual.php` | Gastos FDM escritos a mano. La tabla `costo_fdm_mensuals` **se quedó** y el cálculo nuevo la sigue leyendo (sección "Otros gastos FDM"). |
| `public/templates/reporte_combustible_fdm.xlsx`, `reporte_gasto_cuadrilla_fdm.xlsx` | Plantillas de los Excel antiguos. |

Se quedaron porque otros módulos los usan: `ParametroMensual` (asistencias, planilla) y `CostoManoIndirecta` (resumen anual
de cuadrilla). Los permisos `CONTABILIDAD_FDM*` siguen en `Permisos.php`, sin uso.

### Costos por campaña antiguo (19/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Costos/GestionCampaniaCostos.php`, vistas `livewire/costos/gestion-campania-costos`, `indice-costos` | `/costos/campania` leía las distribuciones de `costo_mensual_distribuciones` y `costos_mensuales` por su cuenta (anterior a la BDD) y tenía su propio cierre de campaña. Reemplazado por `CostosCampaniaComponent` + `App\Services\Costos\Campania\CostosCampaniaConsulta`, que leen **solo la BDD** (`resumen_costo_diarios`) mes a mes, avisan los meses sin distribuir / sin mano de obra indirecta y descargan el Reporte BDD de la campaña. El cierre de campaña sigue en `CampaniaCerrarComponent`. |

### Selector de fecha flatpickr (20/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `resources/views/components/selector-dia.blade.php` (versión flatpickr + `wire:ignore` + hook `commit`) | Reemplazado por `<x-fecha>` (`resources/views/components/fecha.blade.php` + `resources/js/components/campo-fecha.js`): se escribe con máscara dd/mm/aaaa (relleno de ceros automático) o se elige en el almanaque (Hoy / Borrar), y solo envía la fecha cuando está completa. `x-selector-dia` ahora es un alias de `x-fecha`; `x-input type="date"` también lo usa. |

### Migraciones anteriores a la consolidación (21/10/2026)

`legacy/database/migrations/` tiene las 198 migraciones históricas. Se reemplazaron por **una migración por tabla**
(`database/migrations/2026_09_01_*`), generadas del esquema final y en orden de dependencias (los triggers van en la
migración de su tabla). Los datos iniciales que cargaban algunas migraciones pasaron a los seeders
(`ConfiguracionTableSeeder`, `PermisosSeeder`, `LaboresSeeder`).

- **Instalación nueva:** `php artisan migrate --seed`.
- **Base existente (dominio antiguo):** respaldo, desplegar el código y luego
  1. `php artisan migrate --path=legacy/database/migrations` (las anteriores que falten);
  2. `php artisan migraciones:consolidar --verificar` (compara el esquema real con `database/consolidacion/esquema.json`);
  3. `php artisan migraciones:consolidar` (o `--forzar` si las diferencias son aceptables): reemplaza los registros de
     la tabla `migrations` por los nombres nuevos. No toca tablas ni datos.

### Seeders de datos (21/10/2026)

`legacy/database/seeders/` tiene los seeders con datos de la empresa (campos, labores, configuración, usuarios…). Los
reemplaza el **Excel de recursos** (Sistema → Configuración, o `php artisan libro:excel recursos importar RECURSOS.xlsx --guardar`).
`DatabaseSeeder` solo crea lo necesario para entrar: permisos, rol y usuario Super Admin (`SUPERADMIN_EMAIL`,
`SUPERADMIN_PASSWORD` en el .env) y las tablas SUNAT. `PlanTipoAsistenciaSeeder` sigue en `database/seeders` porque lo
usa "Restaurar por defecto" de tipos de asistencia.

### Dashboard anterior (21/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Livewire/Sistema/DashboardComponent.php` y `livewire/sistema/dashboard-component` | Reemplazado por el inicio nuevo (`SistemaInicioComponent` + `Services/Sistema/Inicio/SistemaInicioConsulta`), con el diseño de `DESIGN.md`: indicadores del mes, gráficos del año, alertas y accesos. |
| `app/Livewire/Cochinilla/CochinillaInfestacionStatsComponent.php`, `CochinillaInfestacionResumenGeneralComponent.php` y sus vistas | Bloques de infestación del dashboard anterior (9 tarjetas). El inicio muestra el resumen del mes; el detalle está en Cochinilla → Infestación. |
| `app/Models/EstadisticaMensual.php` | Estadísticas guardadas a mano con el botón "Actualizar" del dashboard anterior. El inicio calcula en vivo. La tabla `estadistica_mensuales` se conserva. |

### Importador antiguo de labores (21/10/2026)

| Qué | Por qué se retiró |
|---|---|
| `app/Services/Campo/Labor/ImportarLaborProceso.php` | Importaba labores desde un Excel propio (hojas `LABORES` / `TRAMOS` con tablas con nombre). Lo reemplaza el botón flotante "Recursos" de la página de labores (Excel de recursos, hojas Labores y Tramos de bono, con vista previa). Se quitó el botón "Importar desde Excel" y `updatedFileLabores` de `LaboresComponent`. |

### Tablas antiguas fuera de la instalación nueva (22/10/2026)

`legacy/database/migraciones-retiradas/` tiene las migraciones de `tienda_comercials` y `compra_productos` (compras antiguas, ya migradas a `compras` / `compra_detalles` / `proveedores`). Una instalación nueva ya no las crea. En el dominio antiguo las tablas siguen con sus datos (el verificador de `migraciones:consolidar` las mostrará como tablas extra).
