# Mejoras UI/UX de Ghost Store

Dirección: conservar marino `#10283F`, cian `#45C2D4`, primario `#075E78`, texto `#172B42`, superficies `#FFFFFF` y fondo `#F8FAFC`. Los campos usan un borde propio `#8493A5`; las tarjetas conservan sus separadores suaves. La tipografía del sistema mantiene una lectura rápida y no requiere descargas.

La operación guía la composición: título y acción frecuente al inicio, filtros identificados, datos necesarios para decidir y detalles secundarios desplegables. Los colores expresan estados y acciones; no se añaden adornos ni animaciones automáticas. Se conserva la identidad aprobada y se reduce la fragmentación en móvil.

```text
Escritorio: menú compacto | título + nueva alta
                         | listado operativo | alta en modal
           expansión automática sobre el contenido; ancho reservado al fijarlo
Móvil:     título + nueva alta
           filtros compactos
           cliente / servicio / vencimiento / acción
           detalles de la fila bajo demanda
```

Los controles son los mismos en ambas vistas: no se duplican formularios ni comprobantes al adaptar tablas a tarjetas. La navegación se expande automáticamente y permite fijar los nombres, conservando esa preferencia local. Las contraseñas no se recuperan desde valores anteriores del formulario.

## Cambios aplicados

| Hallazgo | Resultado |
| --- | --- |
| U01. Altas al final de listados largos | Acciones de alta junto al título; formularios desplegables que aparecen antes del listado en móvil y reciben el foco. |
| U02. Tablas anchas y acciones amontonadas | Filas adaptadas a tarjetas en móvil, detalles secundarios desplegables y panel operativo reducido a seis columnas. La renovación utiliza un período y un botón explícitos. |
| U03. Confusión entre copiar y vender | «Registrar y copiar» informa la cantidad y el precio. Tras confirmar se cierra «Nueva entrega» y se destaca «Volver a copiar esta entrega», que conserva los cupos. Un portapapeles que no responde deja de bloquear la pantalla y ofrece copia manual. |
| U04. Alcance financiero incompleto | Filtros de servicio y de 6, 12 o 24 meses; ventas y pagos de ChatGPT y Claude incluidos, con los mismos filtros en CSV. El saldo registrado explica su cálculo. |
| U05. Cupos libres confundidos con vendibles | La cuenta seleccionada muestra los cupos para vender. Reserva, Gratis, inactividad y credenciales pendientes se distinguen con el motivo del bloqueo. |
| U06. Navegación dependiente del cursor | Menú compacto que se expande con cursor o teclado, cierre con demora breve y Escape, y opción persistente de fijarlo abierto. |
| U07. Perfil oculto en móvil | Acceso a «Mi perfil» visible en la barra superior. |
| U08. Nombres y formatos inconsistentes | Estados legibles, fechas de presentación en día/mes/año y nombres de planes y duraciones coherentes. Los campos de fecha conservan el formato nativo. |
| U09. Búsqueda vacía engañosa | Mensajes específicos para búsquedas sin coincidencias y acción para limpiar filtros. |
| U10. Campos difíciles de identificar | Bordes de campos con mayor contraste, etiquetas de filtros, foco visible, salto al contenido y acciones móviles de mayor altura. |
| U11. Confirmaciones sin contexto | Las acciones destructivas identifican el registro y explican su consecuencia; cancelar recibe el foco inicial. |
| U12. Errores que ocultan o borran el formulario | Resumen con enlaces a los campos, errores asociados mediante ARIA y recuperación de datos no sensibles después de una edición rechazada. No se recuperan contraseñas ni tokens. |
| U13. Encabezados que se confunden con las filas | Fondo marino, texto blanco y separador cian en todas las cabeceras. Las etiquetas de las tarjetas móviles usan texto oscuro y mayor peso. |

## Contraste de encabezados

El encabezado anterior usaba fondo `#F0F4F8` y texto `#536579`: el texto tenía una relación de contraste de 5,42:1, pero el fondo apenas se diferenciaba de las filas blancas (1,11:1). Además, la regla compartida de `.table` y `.table-dark` anulaba el aspecto oscuro de Bootstrap.

La revisión del 19 de octubre usa fondo `#10283F`, texto `#FFFFFF` (15,03:1) y borde inferior cian `#45C2D4` de 2 px. Los nombres de columna tienen peso 650 y tamaño de .85 rem. Los tokens `--gs-table-head-*` y las variables de tabla de Bootstrap se aplican a `thead`, de modo que el ajuste alcanza también a los historiales que no llevan la clase `table-dark`, conservando las filas claras.

En móvil se mantienen las tarjetas y la cabecera de tabla oculta; sus etiquetas usan `#172B42` con peso 600. Se comprobaron en navegador los colores calculados, el separador y la presentación a 1280 y 360 px. La base temporal de verificación pasó las 21 pruebas HTTP de modales.

Las columnas también tienen divisores verticales de 1 px: `#8493A5` en las filas y `#69879F` sobre el encabezado marino. Se omite el borde de la última celda para evitar duplicarlo en el contorno. Las tarjetas móviles conservan sus celdas sin bordes verticales.

## Menú lateral automático

Actualizado el 19 de octubre de 2026 a solicitud del usuario. En escritorio (desde 992 px) comienza compacto, a 64 px. Entrar con el cursor o navegar con Tab muestra los nombres en un panel de 244 px sobre el contenido, sin mover tablas ni filtros. Al salir se contrae tras 240 ms; volver a entrar cancela el cierre. El foco de teclado dentro del menú lo mantiene abierto y Escape devuelve el foco al contenido y lo cierra.

**Fijar menú** reserva el ancho completo y conserva la elección en `localStorage`, con la clave `ghost.navPinned`. **Desfijar menú** recupera el funcionamiento automático. Las cabeceras mantienen su altura para que los enlaces no cambien de posición al expandir. Sin JavaScript los nombres permanecen visibles. En móvil se conserva el panel Bootstrap con sus controles de apertura y cierre.

La lógica está en `public/js/sidebar.js`. Se verificaron en navegador la expansión y contracción con cursor, Tab, Escape, fijación y persistencia tras recargar; el contenido permaneció a 64 px en modo automático y a 244 px al fijar. La suite HTTP de modales pasó sus 21 casos con datos ficticios en una base temporal.

## Alcance de reportes

«Renovaciones registradas» corresponde al historial general de movimientos, incluidos los registros heredados que ya existían allí. Los módulos de cuentas ChatGPT y Claude aportan sus ventas y sus pagos por fecha. Los pagos se cuentan como costo, sin añadir una venta. Reiniciar un ciclo o liberar un acceso no borra su ingreso histórico.

El saldo es la diferencia entre los ingresos y costos registrados en el intervalo; no distribuye el costo de un pago entre meses ni estima una utilidad devengada. El estado actual de la cartera general aparece por separado, sin atribuirlo al período financiero elegido.

## Verificación

Se usó una base temporal con la estructura instalada y datos ficticios para revisar escritorio, móvil de 390 y 360 píxeles, altas, validaciones, confirmaciones, búsquedas, distribución y reportes. No se escribieron registros de negocio reales durante estas pruebas.

Se comprobó el menú a 244 píxeles expandido y 64 contraído, con foco sobre el botón, recarga y teclado; en móvil se comprobó abrir y cerrar el panel. También se comprobó el registro de uno y dos dispositivos, la recuperación tras recarga y la copia posterior sin aumentar ocupación.

Pruebas automatizadas: `tests/chatgpt.php` (20), `tests/chatgpt_existentes.php` (22), `tests/claude.php` (61), `tests/chatgpt_textos.php` (8), `tests/run.php` (15) y `tests/ui_ux.php` (9): 135 casos correctos. La revisión de sintaxis PHP y JavaScript también pasó.

## Formularios en modales

Los ajustes móviles del 19 de octubre amplían a 44 px las zonas táctiles, presentan historiales como tarjetas y utilizan pantalla completa también en teléfonos horizontales. Las tablas remotas reutilizan el inicializador de tarjetas, sin duplicar contenido. El encabezado móvil usa accesos de perfil y salida con nombre accesible, y distribución acerca las acciones a la cuenta y su contador. Los avisos secundarios y las fechas del resumen se pueden desplegar. La comparación y la validación están en [Revisión móvil](revision-movil.md).

Los formularios de creación y edición de clientes, suscripciones, plataformas, planes, usuarios, cuentas principales, ChatGPT y Claude se abren desde la pantalla de origen. También se incluyen asignación de vigencias desde clientes, pagos, pago y selección desde el índice, revisión de vigencia, nuevo ciclo y edición conjunta de costos y precios.

El modal conserva el contexto del listado y, tras guardar, recarga esa misma URL y restaura la posición. Cancelar o cerrar devuelve el foco al botón de origen. Los formularios con una sola operación tienen su botón original de guardar en el pie fijo; no se duplican formularios ni tokens. Los errores se muestran junto a los campos y conservan los datos introducidos. En móvil el modal utiliza el espacio completo de la pantalla. Los filtros, informes e importaciones con previsualización mantienen sus pantallas de trabajo.

### Integración

- `public/js/modals.js` reutiliza un solo modal Bootstrap. Mueve los paneles locales `details[data-form-panel]` o `[data-modal-panel]` y los restaura al cerrar.
- Las pantallas editables se identifican con `[data-modal-page]`. Las cargas remotas retiran scripts y usan inicializadores conocidos en `public/js/form-behaviors.js`; los IDs y sus referencias se adaptan para evitar colisiones con formularios del listado.
- Los POST del modal llevan `X-GS-Modal: 1`. La redirección devuelve JSON con resultado y errores, conservando CSRF, permisos y reglas de cada controlador. Las respuestas no devuelven el contenido enviado ni credenciales.
- Los envíos normales conservan su redirección HTTP. Las confirmaciones de SweetAlert se muestran dentro del modal para mantener el foco y el teclado.

### Verificación

`php tests/modals.php` crea una base `ghost_modal_test_*`, importa exclusivamente las definiciones de estructura y agrega datos ficticios. Incluye `tests/modals_extended.php` para renovaciones, reenvíos, cambios de precio, fechas sin cobros, historiales, permisos, contactos y perfil; `tests/uiux.php` verifica los estados, filtros, paginación y retorno al contexto. Al terminar elimina su base, servidor y archivos temporales. `php tests/modals.php --preview` permite revisar la interfaz antes de presionar Enter para limpiar. El router de pruebas solo funciona con PHP CLI Server y ese prefijo de base; nunca habilita acceso en Apache.

### Acciones operativas y consultas

- **Renovar** abre un formulario con cliente, plan, período, importe y nuevo vencimiento. Las opciones se calculan en el servidor con las mismas modalidades y calendario de la renovación. El POST comprueba dentro de la transacción los datos mostrados y utiliza un token por operación para rechazar reenvíos.
- **Editar vencimiento / Reactivar** muestra ambas fechas y explica que la corrección no registra un ingreso. Se abre desde el panel o el detalle del cliente.
- **Historial de renovaciones** y **Historial de distribución** se consultan en un modal amplio, sin formularios de escritura, con contexto del cliente o cuenta y botón **Cerrar**.
- **Ajustar cupos** utiliza un formulario independiente para ChatGPT y Claude, accesible desde índice, detalle y edición general. Mantiene permisos, validación de ocupación anterior y confirmación; el historial de ajustes queda disponible dentro del formulario.
- **Completar contacto** muestra las cuentas asociadas y tiene **Guardar** y **Guardar y siguiente** en el pie. Al continuar se recarga la misma URL con sus filtros y se abre el siguiente pendiente de ese resultado. Cuando ya no hay registros se informa que la cola terminó.
- **Mi perfil** se abre desde el encabezado y permite elegir usuario o contraseña dentro del mismo modal. Los errores señalan el campo correspondiente y las respuestas no incluyen contraseñas.

Los enlaces `data-modal-link` cargan estas vistas y permiten cambiar de contenido dentro del mismo modal. Las vistas de consulta pueden indicar `modalSize=wide`; los formularios con una operación conservan todos sus botones originales en el pie fijo, sin duplicar formularios ni tokens.

### Listados y protección de cambios

- Suscripciones envía los códigos internos del estado y muestra sus etiquetas en español. Se conserva el estado con búsqueda y paginación.
- ChatGPT y Claude incorporan filtros de cuenta actual, listas, Gratis, pendientes e inactivas; ChatGPT añade gracia. Pendientes reúne cuentas activas con credenciales o revisión de fechas sin completar. Los contadores respetan la búsqueda; el resumen abarca todos los resultados filtrados, antes de paginar.
- La paginación admite 10, 25, 50 o 100 registros. La cola se numera antes de aplicar el filtro y la página; buscar por texto muestra disponibilidad sin inventar una nueva posición global. Distribuir mantiene `q`, `filtro`, `page` y `por_pagina`.
- Las filas muestran ocupación respecto a la referencia, exceso explícito y ambas fechas. El nombre y correo no se repiten cuando coinciden. Abrir y la operación principal quedan visibles; Más contiene edición, ajuste de cupos e historial. Eliminar cliente conserva su confirmación dentro de Más.
- Los encabezados permanecen fijos dentro del listado de escritorio; mantienen fondo marino, texto blanco y divisores verticales. En móvil se conservan las tarjetas. Los buscadores tienen etiquetas visibles y controles de 44 px; importes con dos decimales y fechas locales consistentes.
- Los resúmenes financieros y fechas ampliadas empiezan cerrados. Los avisos urgentes permanecen abiertos. El panel muestra Pendientes de hoy al ingresar y ofrece contacto, renovación y entregas de ambas plataformas.
- El modal compara los campos con su estado inicial en memoria. Solo pide confirmación si hay cambios; deshacerlos evita el aviso. Descartar restablece también los paneles locales. La protección se aplica al cierre, al cambio de formulario y al salir de la página. Los valores y contraseñas no se guardan en almacenamiento del navegador.
- Al guardar se restauran URL, desplazamiento de página y desplazamiento interno de tablas; la fila que abrió el formulario se resalta durante cuatro segundos si sigue en el resultado. El almacenamiento de retorno contiene únicamente estos metadatos.

Validación local: 24 pruebas HTTP de modales/UI, 95 de Claude y distribución compartida, y 20 de ChatGPT. Revisión en navegador a 1280 px y 360 px, incluyendo cierres limpios, cambios revertidos, descarte de un formulario local, sustitución protegida y regreso al filtro con fila resaltada. Con datos ficticios, la primera fila ChatGPT pasó de aproximadamente 872 px a 610 px y de 165,5 px a 128,2 px de alto. No requiere cambios de esquema.
