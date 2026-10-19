# Revisión móvil — 19 de octubre de 2026

El sistema funciona en los recorridos probados desde tamaños móviles. Los cinco hallazgos de esta revisión se corrigieron el 19 de octubre de 2026. Las observaciones iniciales se conservan abajo como referencia.

## Ajustes implementados y comprobados

- Menú, perfil, salida, cierres y botones de los modales tienen zonas táctiles de al menos 44 × 44 px en móvil. Perfil y salida usan iconos con nombres accesibles; la marca permanece en una línea.
- Renovaciones, distribución y Auditoría adaptan sus tablas a tarjetas. El inicializador `GhostTables.initialize` se reutiliza al cargar contenido remoto y evita envolver de nuevo una tabla ya preparada. Las fechas, los importes y los detalles financieros se conservan; en escritorio vuelven a mostrarse todas las columnas.
- Los modales ocupan toda la pantalla en teléfonos de hasta 575 px de ancho y también hasta 991 px cuando la altura es de 500 px o menos. Usan altura dinámica y espacio para las áreas seguras. A 740 × 360, el área desplazable del historial pasó de 165 a 239 px.
- Los avisos secundarios y las fechas del resumen comienzan plegados en móvil, con los avisos urgentes y las cuentas con gracia finalizada indicados en el encabezado del bloque. En escritorio se muestran abiertos. Las fechas siguen visibles en las filas de cuentas y se pueden desplegar en los resúmenes.
- Con los mismos datos ficticios a 360 × 740, la primera cuenta de ChatGPT pasó de aproximadamente 1115 a 684 px desde el inicio. Los botones de entrega comienzan a 357 y 460 px, frente a 755 y 862 px; ambas opciones caben en la primera pantalla. Las instrucciones, las fechas y la administración permanecen disponibles debajo.

Se repitieron las 21 pruebas HTTP de modales, sin fallos. En navegador se revisaron nueve secciones a 360, 390 y 768 px: sin desbordamiento horizontal de página o tablas y con todos los controles del encabezado de 44 × 44 px como mínimo. Se comprobaron guardar conservando la búsqueda, entrega y copia sin duplicar cupos, apertura/cierre del menú, detalles de Auditoría y renovaciones, recarga de consultas sin IDs duplicados y tablas completas en escritorio. No se detectaron errores de JavaScript en la consola. Las pruebas y escrituras se realizaron únicamente en la base efímera.

## Alcance

Prueba en navegador con tamaños de 360 × 740, 390 × 844, 740 × 360 (horizontal) y 768 × 1024 (tableta). También se comprobó el cambio a 1024 × 768 con el menú móvil abierto.

Se revisaron Panel, Contactar hoy, Importaciones, cuentas y distribución de ChatGPT y Claude, Clientes, Suscripciones, Reportes, Plataformas, Planes, Usuarios y Auditoría. Las pruebas usaron el servidor de `tests/modals.php --preview`, una base efímera y datos ficticios. Las 21 pruebas HTTP del runner pasaron. La revisión de Importaciones cubre su pantalla inicial, sin importar un archivo.

## Funcionamiento comprobado

- El menú abre desde su botón, expande grupos y permite navegar. Al seleccionar una sección se cierra. Al cambiar a escritorio se retira el fondo del menú y se recupera el desplazamiento de la página.
- Las vistas principales revisadas caben sin desbordamiento horizontal de toda la página. Clientes, Suscripciones y los índices de cuentas adaptan sus filas a tarjetas.
- El formulario de cuenta se adapta al ancho de 360 px, permite desplazarse por su contenido y mantiene las acciones en el pie.
- Los errores de un alta de cliente aparecen junto a los campos y señalan el primer dato pendiente. El número de celular tiene `inputmode="numeric"`; los campos visibles del formulario de cuenta usan fuente de 16 px.
- Editar un cliente ficticio conserva la búsqueda y regresa al listado filtrado después de guardar.
- Registrar una entrega ficticia de ChatGPT incrementó la ocupación de 2 a 3. Volver a copiar conservó los 3 ocupados.
- El historial de distribución se abre y cabe a 360 px con sus cuatro columnas. El historial de renovaciones abre correctamente, aunque presenta la limitación de lectura descrita abajo.

## Hallazgos iniciales corregidos

| Prioridad | Hallazgo verificado | Cambio recomendado |
| --- | --- | --- |
| Media | En el modal de cuenta, Guardar y Cancelar miden 38 px de alto; el cierre mide 32 × 32 px. Mostrar mide 42 px y Ajustar cupos 40 px. El botón de menú tiene aproximadamente 36 px de ancho. | Usar una zona táctil mínima de 44 × 44 px en menú, cierres y acciones del modal. Incluir su pie, que está fuera de `.app-main` y no recibe su regla móvil de altura mínima. |
| Media | A 360 px, el historial de renovaciones muestra una tabla de 469 px dentro de un área de 309 px. Monto, costo y ganancia requieren desplazamiento horizontal. Auditoría presenta 469 px de contenido en 326 px. | Presentar cada movimiento como tarjeta en móvil, con fecha, importe y detalles. Conservar la tabla en escritorio. El inicializador de tarjetas debe poder ejecutarse también sobre el contenido remoto de un modal. |
| Media | En horizontal, a 740 × 360, el historial usa un modal de 500 × 304 px y solo deja 165 px para el contenido desplazable. | Utilizar pantalla completa también con poca altura disponible o en teléfonos horizontales. Conservar el cierre y las acciones visibles. |
| Media | En ChatGPT, a 360 × 740, la primera cuenta comienza aproximadamente a 1115 px desde el inicio. En distribución, la primera acción de registrar comienza a 755 px y la segunda a 862 px. | Compactar el resumen de la cuenta y dejar los avisos secundarios desplegables en móvil. Acercar la selección y los botones de entrega al encabezado operativo. |
| Baja | A 360 px, Ghost Store y Mi perfil ocupan dos líneas; la barra superior llega a unos 67 px de alto. | Reducir el espacio de la marca y usar accesos compactos con etiquetas accesibles para perfil y salida. Mantener el menú con una zona táctil amplia. |

## Archivos relacionados

- Controles táctiles: `public/css/ui.css`, `public/css/modals.css` y `app/views/layouts/header.php`.
- Tablas y contenido remoto: `app/views/suscripciones/historial.php`, `app/views/usuarios/auditoria.php`, `public/js/ui.js` y `public/js/modals.js`.
- Tamaño del modal: `app/views/layouts/footer.php` y `public/css/modals.css`.
- Jerarquía de cuentas y entregas: `app/views/chatgpt/index.php`, `app/views/claude/index.php`, `app/views/cuentas_compartidas/actual.php` y `app/views/cuentas_compartidas/entrega.php`.

## Validación posterior a los ajustes

Repetir los tamaños anteriores y confirmar que las acciones caben, las tablas adaptadas no exigen desplazamiento horizontal, los botones principales quedan cerca del inicio y guardar conserva filtros y posición. Probar en un Android y un iPhone reales el teclado virtual, la selección de fechas, el portapapeles y las áreas seguras del navegador. La emulación realizada comprueba distribución y navegación, pero no reproduce esas condiciones físicas.
