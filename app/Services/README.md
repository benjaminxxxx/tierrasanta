# Estándar de servicios (`app/Services`)

Aplica al código **nuevo o al que se esté manteniendo**. No hace falta migrar lo existente de golpe:
cuando se toque un módulo, lo que se escriba o reescriba sigue esta regla.

## Ruta y nombre

```
app/Services/{Dominio}/{Concepto}/{Dominio}{Concepto}{Tipo}.php
```

Ejemplo: `app/Services/Campania/Cosecha/CampaniaCosechaConsulta.php`
→ `App\Services\Campania\Cosecha\CampaniaCosechaConsulta`

- **Dominio**: uno de los 12 existentes (Planilla, Cuadrilla, Riego, Campo, Campania, Cochinilla, Evaluacion,
  Producto, Almacen, Costos, Reporte, Sistema).
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

## Auditoría

Todo `Crud` que cree, edite o elimine registros de negocio llama a `App\Services\Reporte\AuditoriaServicio::registrar()`
(acciones `crear`, `editar`, `eliminar`). Al eliminar se guarda el registro completo en `auditorias.cambios`, con
quién lo eliminó. Se consulta en Reportes → Auditoría.
