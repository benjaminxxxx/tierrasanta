# Estándar de servicios (`app/Services`)

Aplica al código **nuevo o al que se esté manteniendo**. No hace falta migrar lo existente de golpe:
cuando se toque un módulo, lo que se escriba o reescriba sigue esta regla.

## Ruta y nombre

```
app/Services/{Dominio}/{Concepto}/{Dominio}{Concepto}{Tipo}.php
```

Ejemplo: `app/Services/Campania/Cosecha/CampaniaCosechaConsulta.php`
→ `App\Services\Campania\Cosecha\CampaniaCosechaConsulta`

- **Dominio**: uno de los 13 existentes (Planilla, Cuadrilla, Riego, Campo, Campania, Cochinilla, Evaluacion,
  Producto, Almacen, Costos, Caja, Reporte, Sistema).
  - **Caja** es la fuente de verdad del dinero que entra y sale: ningún otro dominio escribe en `caja_*`; solo se
    cuadra contra ellos (Caja/Movimiento, Caja/Cierre, Caja/Arqueo, Caja/Importacion, Caja/Reporte).
- **Concepto**: la idea de negocio (Cosecha, Registro, Resumen, Tramo, Asistencia…). Es una carpeta: todas las
  piezas de un concepto cambian juntas y quedan juntas. **No** se crean carpetas por tipo (`Handsontable/`,
  `Procesos/`…): el tipo va en el nombre.
- **Tipo**: sufijo fijo de la tabla de abajo.
- El nombre lleva el dominio y el concepto aunque la carpeta ya los diga: así es único en todo el proyecto y se
  encuentra con Ctrl+P escribiendo `CampaniaCosecha…`, sin alias al importar.

## Tipos

| Sufijo | Para qué | Escribe en BD |
|---|---|---|
| `Consulta` | Solo lectura: arma datos para una vista, reporte o para otra clase (queries, relaciones, métodos del modelo). | No |
| `Crud` | Operaciones primitivas de **una** entidad (crear, actualizar, eliminar) sin reglas que crucen otras tablas. | Sí |
| `Proceso` | Recibe datos, los valida/transforma ("mastica") y guarda; puede tocar varias entidades. Siempre en transacción. Orquesta `Validador`, `Crud` y `Consulta`. | Sí |
| `Hst` | Adapta datos **de o para Handsontable** (arma filas, interpreta filas recibidas) y delega en un `Proceso`. | No (delega) |
| `Validador` | Reglas que deciden si algo se puede hacer. Lanza `ValidationException` o devuelve la lista de errores. | No |
| `Detector` | Detector de tareas pendientes: implementa `detectarTareasPendientes(?string $inicio, ?string $fin)` y se registra en `TareasPendientesComponent::DETECTORES`. | Solo `tareas_pendientes` |
| `Reglas` | Parámetros y funciones puras del negocio (plazos, listas de códigos, cálculos sin BD). | No |
| `Excel` | Importación / exportación de archivos. | Según el caso |

## Cuándo crear una clase nueva

- Si un método **depende de varios métodos existentes** (los combina), va en su propia clase `Proceso` o `Consulta`,
  no se agrega a una clase grande.
- Una clase = un propósito. Si el nombre necesita "Y" (`GuardarYNotificar`), son dos clases.
- Los componentes Livewire no llevan lógica de negocio: validan la forma del formulario y llaman a un `Proceso`
  o una `Consulta`.

## Ejemplo: `Campania/`

```
Campania/
  Cosecha/
    CampaniaCosechaReglas.php      plazos de venta, labores de cosecha / neutras
    CampaniaCosechaConsulta.php    estado de cosecha de cada campaña (cosechando, cosechada hace N días…)
    CampaniaCosechaDetector.php    tareas pendientes: cierres recomendados y ventas fuera de plazo
  Cobertura/
    CampaniaCoberturaConsulta.php  días con actividades que no caen en ninguna campaña
    CampaniaCoberturaDetector.php  tarea pendiente: actividades sin campaña del mes
  Registro/
    CampaniaRegistroConsulta.php   contexto del campo (últimas campañas, vigente), nombre sugerido
    CampaniaRegistroCrud.php       crear / editar / eliminar con auditoría (tabla auditorias)
    CampaniaRegistroValidador.php  solapamiento de fechas, nombre único
    CampaniaRegistroImpactoConsulta.php  qué se mueve si cambian las fechas
    CampaniaRegistroProceso.php    crear, actualizar datos, cambiar fechas, cerrar, eliminar
  CostoProduccion/
    CampaniaCostoProduccionReglas.php    secciones del reporte y qué origen va en cada una
    CampaniaCostoProduccionConsulta.php  partidas desde resumen_costo_diarios y reporte por ha en US$
    CampaniaCostoProduccionProceso.php   guarda una versión (historial) de los costos de la campaña
  Resumen/
    CampaniaResumenConsulta.php    listado de /campania/resumen
```

Las clases antiguas del dominio (`CampaniaServicio`, `CrudCampaniaServicio`, …) siguen funcionando. Se
reemplazan cuando se trabaje en lo que hacen.

## Ejemplo: `Planilla/Laboral/` (historial laboral)

Toda la lógica de contratos y sus partículas vive aquí; pantallas, planillas, PLAME y el Excel de empleados solo la
llaman. Si cambia una regla (qué día representa al mes, prorrateo, cupo de un cargo…) se cambia en un solo método.

```
Planilla/Laboral/
  PlanillaLaboralConsulta.php  qué rige en una fecha: contratoEn / contratoDelMes, asignacionEn, remuneracionEn,
                               pensionEn, pagoEn, delMes (todo lo del mes), fechaDelMes, estado del contrato,
                               ocupantesPorCargo y filtros SQL (por grupo, cargo, pensión vigentes)
  PlanillaLaboralReglas.php    validaciones sin base de datos: contratos que se cruzan, partículas fuera de contrato,
                               contratos sin partícula en su inicio (las usa el Excel y los formularios)
  PlanillaLaboralProceso.php   único punto de escritura: guardarEmpleado (persona), guardarContrato, contratar,
                               finalizar / reabrir / eliminar, guardarAsignacion / Remuneracion / Pension / Pago,
                               cambiarSueldos (masivo)
```

Modelo: el **contrato** es el periodo legal (inicio y fin; puede haber días sin contrato entre dos). Las
**partículas** solo tienen "desde": las del contrato (`plan_contrato_asignaciones` cargo y grupo,
`plan_contrato_remuneraciones` sueldo, básica, bonificación…) valen hasta la siguiente o el fin del contrato; las del
empleado (`plan_empleado_pensiones`, `plan_empleado_pagos`) lo acompañan entre contratos. Los datos naturales están en
`personas` (sin historial); `PlanEmpleado` los expone con los mismos nombres y scopes `buscar`, `porDocumento`,
`ordenarPor`, `alfabetico`.

## Auditoría

Todo `Crud` que cree, edite o elimine registros de negocio llama a `App\Services\Reporte\AuditoriaServicio::registrar()`
(acciones `crear`, `editar`, `eliminar`). Al eliminar se guarda el registro completo en `auditorias.cambios`, con
quién lo eliminó. Se consulta en Reportes → Auditoría.

## Ejemplo: `Campo/Produccion/` (producción de campo)

Fuente única de las cantidades que produce cada labor (kilos, lavadores, cajas…), con bono o sin bono.

```
Campo/Produccion/
  CampoProduccionReglas.php    unidad de una labor (catálogo produccion_unidades; "registra producción" la exige),
                               qué se compara con el ingreso de cochinilla y los % de aviso / anomalía
  CampoProduccionConsulta.php  cantidades con bono por trabajador (bonificaciones), filas sin bono, resumen por
                               día / campo / labor, totales por campo y cuadre con el ingreso
  CampoProduccionProceso.php   único punto de escritura: guardarConBono (lo llama bonificaciones), guardarSinBono,
                               asegurarActividad (días completos sin bono)
```

## Ejemplo: `Planilla/ListaMensual/` y `Planilla/Suspension/` (registro diario de planilla)

Quién aparece cada mes en el registro diario y cómo se reflejan ahí las vacaciones y permisos.

```
Planilla/ListaMensual/
  PlanillaListaMensualReglas.php   orden de un mes nuevo (copia el del último mes con lista, los nuevos al final, sin
                                   huecos), hasta qué mes se genera sola la lista
  PlanillaListaMensualProceso.php  generar (al abrir el registro diario), sincronizarEmpleado (lo llama
                                   PlanillaLaboralProceso después de cada cambio de contrato), guardarOrden ("Gestionar
                                   lista" solo ordena)
Planilla/Suspension/
  PlanillaSuspensionDiarioProceso.php  reconciliar: un día con suspensión lleva su código (V, DM…) en el registro diario y
                                       uno sin suspensión no; regresoAnticipado ("Regresó hoy")
  PlanillaSuspensionConsulta.php       vigentesEn: aviso del registro diario (quién está ausente ese día y si cuadra)
```

Seguridad: un contrato agrario no se elimina, acorta ni pasa a otra planilla si deja registros diarios sin contrato
(`PlanillaLaboralConsulta::registrosDiariosSinContrato`); primero se eliminan esos registros.

## Ejemplo: `Planilla/Mensual/` (planilla agraria del mes)

Una sola tabla por empleado y mes: `plan_mensual_detalles` (modelo `PlanMensualDetalle`). La fila nace con la lista del
registro diario y "Generar planilla" (`PlanillaServicio::generarProyeccion`) llena su cálculo y `calculado_en`. Para
planilla, PLAME y costos se usa `PlanMensualDetalle::calculados()->delMes($mes, $anio)`; `orden` es el del registro
diario y `orden_planilla` el de la planilla. `plan_mensual_personals` ya no existe (ver legacy/README.md).

```
Planilla/Mensual/
  PlanillaMensualReglas.php  fórmulas sin base de datos: proyección (CTS, gratificaciones, EsSalud, vida ley, jornal,
                             costo hora, diferencia en negro…), lo pagado según horas (sueldo, aportes, costo por hora)
                             y aporteSeguro (factor 1.18). El modelo las expone como atributos (proyectado_cts…).
```

## Ejemplo: `Planilla/Parametro/` (parámetros de planilla)

Las tasas del mes (RMV, EsSalud, gratificaciones, CTS, vida ley, 4ta…) y las comisiones AFP tienen **una sola fuente**:
`configuracion_historial` y `plan_prima_comision_historicos`. Las planillas las leen al vuelo (valor vigente el día 1
del mes); ya no se copian a `plan_mensuales` ni a `plan_mensual_sp_desc` (retirada). `$plan->rmv`, `$plan->essalud`…
siguen funcionando: el modelo `PlanMensual` los lee de aquí.

```
Planilla/Parametro/
  PlanillaParametroReglas.php    catálogo de lo que necesita el sistema (nombre, unidad, qué planilla lo usa, para qué),
                                 regla del día 1 del mes, formato
  PlanillaParametroConsulta.php  delMes, afpDelMes, descuentosSp (HAB F… y SNP calculados), faltantes / exigir (mensaje
                                 de qué falta y dónde), meses con planilla generada, tarjetas e historial de la pantalla
  PlanillaParametroProceso.php   único punto de escritura (pantalla, modal de Generar planilla, Descuentos AFP, Excel):
                                 agregar / editar / eliminar con motivo y auditoría, "hasta" automático; si el cambio
                                 altera un mes con planilla generada lanza ParametroAfectaPlanillas (meses y qué cambia)
                                 y solo guarda confirmado, regenerando esas planillas (agraria y oficina, con su Excel)
```

## Ejemplo: `Sistema/Menu/` (menú lateral)

```
Sistema/Menu/
  SistemaMenuReglas.php    definición del menú (grupos con ícono y color, opciones con permiso, módulo e ícono) y los
                           accesos directos de fábrica (lo que se llena todos los días)
  SistemaMenuConsulta.php  qué ve cada usuario (Super Admin todo, Administrador casi todo, el resto permiso + módulo)
                           y sus accesos directos (los suyos o los de fábrica, solo los que puede ver)
  SistemaMenuProceso.php   agregar / quitar / ordenar accesos directos (usuario_preferencias.accesos_directos)
```
Una opción nueva del menú se agrega solo en `SistemaMenuReglas::grupos()`.

## Ejemplo: `Riego/Regador/` (agregar regadores)

```
Riego/Regador/
  RiegoRegadorConsulta.php  quién puede regar en una fecha: planilleros con contrato agrario vigente, cuadrilleros
                            activos (sin los ya registrados) y los habituales (más días de riego en los últimos 45)
  RiegoRegadorProceso.php   agrega regadores a una fecha validando que sigan pudiendo regar ese día
```
