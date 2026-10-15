# Mejoras UI/UX de Ghost Store

Dirección: conservar marino `#10283F`, cian `#45C2D4`, primario `#075E78`, texto `#172B42`, superficies `#FFFFFF` y fondo `#F8FAFC`. Los campos usan un borde propio `#8493A5`; las tarjetas conservan sus separadores suaves. La tipografía del sistema mantiene una lectura rápida y no requiere descargas.

La operación guía la composición: título y acción frecuente al inicio, filtros identificados, datos necesarios para decidir y detalles secundarios desplegables. Los colores expresan estados y acciones; no se añaden adornos ni animaciones automáticas. Se conserva la identidad aprobada y se reduce la fragmentación en móvil.

```text
Escritorio: menú con nombres | título + nueva alta
                            | listado operativo | alta desplegable
Móvil:     título + nueva alta
           filtros compactos
           cliente / servicio / vencimiento / acción
           detalles de la fila bajo demanda
```

Los controles son los mismos en ambas vistas: no se duplican formularios ni comprobantes al adaptar tablas a tarjetas. La navegación permite fijar o contraer los nombres y conserva la preferencia local. Las contraseñas no se recuperan desde valores anteriores del formulario.

## Cambios aplicados

| Hallazgo | Resultado |
| --- | --- |
| U01. Altas al final de listados largos | Acciones de alta junto al título; formularios desplegables que aparecen antes del listado en móvil y reciben el foco. |
| U02. Tablas anchas y acciones amontonadas | Filas adaptadas a tarjetas en móvil, detalles secundarios desplegables y panel operativo reducido a seis columnas. La renovación utiliza un período y un botón explícitos. |
| U03. Confusión entre copiar y vender | «Registrar y copiar» informa la cantidad y el precio. Tras confirmar se cierra «Nueva entrega» y se destaca «Volver a copiar esta entrega», que conserva los cupos. Un portapapeles que no responde deja de bloquear la pantalla y ofrece copia manual. |
| U04. Alcance financiero incompleto | Filtros de servicio y de 6, 12 o 24 meses; ventas y pagos de ChatGPT y Claude incluidos, con los mismos filtros en CSV. El saldo registrado explica su cálculo. |
| U05. Cupos libres confundidos con vendibles | La cuenta seleccionada muestra los cupos para vender. Reserva, Gratis, inactividad y credenciales pendientes se distinguen con el motivo del bloqueo. |
| U06. Navegación dependiente del cursor | Menú con nombres por defecto, botón para contraer/expandir y preferencia persistente. Se eliminaron los estilos que lo expandían al mantener el cursor o el foco sobre él. |
| U07. Perfil oculto en móvil | Acceso a «Mi perfil» visible en la barra superior. |
| U08. Nombres y formatos inconsistentes | Estados legibles, fechas de presentación en día/mes/año y nombres de planes y duraciones coherentes. Los campos de fecha conservan el formato nativo. |
| U09. Búsqueda vacía engañosa | Mensajes específicos para búsquedas sin coincidencias y acción para limpiar filtros. |
| U10. Campos difíciles de identificar | Bordes de campos con mayor contraste, etiquetas de filtros, foco visible, salto al contenido y acciones móviles de mayor altura. |
| U11. Confirmaciones sin contexto | Las acciones destructivas identifican el registro y explican su consecuencia; cancelar recibe el foco inicial. |
| U12. Errores que ocultan o borran el formulario | Resumen con enlaces a los campos, errores asociados mediante ARIA y recuperación de datos no sensibles después de una edición rechazada. No se recuperan contraseñas ni tokens. |

## Alcance de reportes

«Renovaciones registradas» corresponde al historial general de movimientos, incluidos los registros heredados que ya existían allí. Los módulos de cuentas ChatGPT y Claude aportan sus ventas y sus pagos por fecha. Los pagos se cuentan como costo, sin añadir una venta. Reiniciar un ciclo o liberar un acceso no borra su ingreso histórico.

El saldo es la diferencia entre los ingresos y costos registrados en el intervalo; no distribuye el costo de un pago entre meses ni estima una utilidad devengada. El estado actual de la cartera general aparece por separado, sin atribuirlo al período financiero elegido.

## Verificación

Se usó una base temporal con la estructura instalada y datos ficticios para revisar escritorio, móvil de 390 y 360 píxeles, altas, validaciones, confirmaciones, búsquedas, distribución y reportes. No se escribieron registros de negocio reales durante estas pruebas.

Se comprobó el menú a 244 píxeles expandido y 64 contraído, con foco sobre el botón, recarga y teclado; en móvil se comprobó abrir y cerrar el panel. También se comprobó el registro de uno y dos dispositivos, la recuperación tras recarga y la copia posterior sin aumentar ocupación.

Pruebas automatizadas: `tests/chatgpt.php` (20), `tests/chatgpt_existentes.php` (22), `tests/claude.php` (61), `tests/chatgpt_textos.php` (8), `tests/run.php` (15) y `tests/ui_ux.php` (9): 135 casos correctos. La revisión de sintaxis PHP y JavaScript también pasó.
