# Propuestas de UI/UX — 19 de octubre de 2026

El siguiente avance debería concentrarse en la densidad de los listados, la prioridad de las acciones y la confianza en los filtros. Se conservan la paleta marino/cian, los encabezados oscuros, los divisores verticales, los modales y las tarjetas móviles ya implementados.

Las propuestas de este análisis se implementaron después de su aprobación. El detalle de funcionamiento y validación está en `docs/mejoras-ui-ux.md`, sección «Listados y protección de cambios».

## Hallazgos comprobados

### 1. El filtro de estado de Suscripciones se descarta

**Prioridad alta; corrección funcional.** Al elegir «Vencida» y pulsar Buscar, la URL incluye `estado=Vencida`, pero el selector vuelve a Todos. En la base de prueba mostró las nueve suscripciones al día, en lugar del resultado vacío esperado. Consultar `estado=VENCIDO` directamente sí devuelve el resultado correcto.

La vista usa el nombre traducido en `option.value`; el controlador admite únicamente los códigos de `Suscripcion::ESTADOS`. La corrección debe conservar los códigos internos como valores y los nombres legibles como etiquetas. Debe verificarse cada estado, su combinación con la búsqueda y la conservación del filtro al paginar o guardar.

Archivos: `app/views/suscripciones/index.php`, `app/controllers/SuscripcionesController.php`.

### 2. Las acciones hacen demasiado altas las filas de cuentas

**Prioridad alta; mejora visual.** Las acciones Abrir, Editar, Ajustar cupos e Historial se apilan en una columna; Distribuir o Pagar y distribuir añade otro control. Una fila de la cuenta actual midió 165,5 px en la prueba de escritorio. El encabezado de esa columna está vacío.

Propuesta: conservar una acción principal según la cuenta, Abrir como acceso secundario y un menú Más para las operaciones menos frecuentes. Añadir el título Acciones. Las operaciones seguirían usando los formularios, permisos y modales existentes. En Clientes, Eliminar puede pasar a Más, conservando su confirmación actual.

Archivos: `app/views/cuentas_compartidas/acciones_listado.php`, índices de ChatGPT, Claude y Clientes.

### 3. Hay datos repetidos en las filas

**Prioridad alta; mejora visual.** El nombre interno y el correo se muestran siempre, incluso cuando son iguales. «En distribución» y «Cuenta seleccionada» repiten la selección. El bloque de ocupación contiene varios rótulos y fechas en líneas independientes.

Propuesta: mostrar el correo una sola vez cuando coincida con el nombre; usar un único indicador de cuenta actual; resumir ocupación como `18 / 20` y agrupar las fechas con rótulos breves. Las fechas de inicio y primer llenado permanecen visibles en el listado. El indicador de ocupación del detalle puede reutilizarse en una versión pequeña para el índice, con el exceso expresado también en texto.

Archivos: `app/views/chatgpt/index.php`, `app/views/claude/index.php`, `app/views/cuentas_compartidas/fechas_distribucion.php`.

### 4. Los resúmenes desplazan el listado de escritorio

**Prioridad alta; mejora visual.** A 1280 × 800, la primera fila del índice ChatGPT comenzó aproximadamente a 872 px desde el inicio, por debajo de la primera pantalla. Los bloques de fechas, avisos y resumen se abren por defecto en escritorio.

Propuesta: mantener un resumen compacto de la cuenta actual, presentar los avisos urgentes de forma visible y dejar el detalle financiero o explicativo desplegable. La búsqueda y la cabecera del listado deberían entrar en la primera pantalla con los datos de prueba.

Archivos: `app/views/cuentas_compartidas/actual.php`, `app/views/chatgpt/resumen_avisos.php`, índices de cuentas y `public/js/ui.js`.

## Otras mejoras propuestas

| Prioridad | Mejora | Resultado esperado |
| --- | --- | --- |
| Media | Filtros rápidos en cuentas: actual, listas para distribuir, Gratis, en gracia, pendientes e inactivas; contador del resultado y limpieza individual de filtros. | Encontrar cuentas por su situación sin revisar todas las filas. Requiere ampliar la consulta y conservar el orden global de la cola. |
| Media | Paginación en ChatGPT/Claude cuando el volumen lo requiera. Clientes y Suscripciones ya la tienen. | Limitar listados largos, manteniendo búsqueda, filtros y posiciones de cola globales. |
| Media | Encabezado de tabla fijo en escritorio y anchos de columna previsibles. | Conservar la referencia de cada dato al desplazarse. Mantener los divisores verticales y las tarjetas móviles. |
| Media | Unificar altura de buscadores, etiquetas visibles y jerarquía de botones. | Una misma operación mantiene el mismo aspecto en cada sección. Las acciones principales se distinguen de las secundarias. |
| Media | Avisar al cerrar un modal únicamente si tiene cambios sin guardar. | Evitar perder datos introducidos. Cancelar un formulario intacto o cerrar una consulta sigue siendo inmediato. |
| Media | Destacar brevemente la fila modificada después de guardar. | Identificar dónde quedó el cambio, manteniendo el regreso al filtro y la posición actuales. |
| Media | Unificar fechas e importes y usar dígitos de ancho uniforme. | Lectura y comparación más rápidas. El detalle de cliente y Auditoría aún presentan algunas fechas sin el formato usado en otros listados. |
| Media | En el panel operativo, priorizar los pendientes del día y compactar la información financiera. | Llegar antes a contactar, renovar o entregar. Reutilizar los filtros y contadores actuales para evitar duplicaciones. |

## Orden recomendado

1. Corregir el filtro de Suscripciones.
2. Compactar acciones y datos repetidos de ChatGPT/Claude; añadir Acciones al encabezado.
3. Reducir los resúmenes de escritorio y unificar buscadores, fechas e importes.
4. Incorporar filtros de cuentas, paginación y cabeceras fijas donde aporten valor.
5. Añadir protección de cambios sin guardar, señalización de la fila editada y ajustes del panel.

Las tres primeras etapas son las que aportarían una mejora más inmediata al uso diario.

## Reglas que deben conservarse

- La cuenta seleccionada puede seguir entregando aunque llegue a 20 dispositivos de ChatGPT o a la referencia de Claude. Cualquier indicador visual de ocupación debe explicar la referencia y el exceso, sin presentar un bloqueo inexistente.
- Solo hay una cuenta actual por servicio; las mejoras visuales y los filtros no seleccionan otra automáticamente.
- Se conservan el orden de próximas cuentas y las fechas e historiales de distribución.
- Pagar y distribuir una cuenta Gratis sigue solicitando el costo real y mostrando la fecha del pago.
- Las acciones mantienen permisos, validaciones, tokens y confirmaciones. Los modales siguen devolviendo al contexto de origen.

## Alcance de esta revisión

El análisis inicial revisó las vistas, estilos, filtros y modales usando datos ficticios en la base efímera de `tests/modals.php --preview`. La implementación posterior añadió cobertura de regresión para los estados, filtros, paginación y contexto del listado. Las comprobaciones no modifican registros reales.
