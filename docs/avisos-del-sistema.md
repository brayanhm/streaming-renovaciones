# Avisos y confirmaciones

SweetAlert2 11.26.25 se sirve localmente desde `public/vendor/sweetalert2`. El diseño usa los colores y la tipografía del tema corporativo mediante `public/css/alerts.css`.

`public/js/alerts.js` define `GhostAlerts`:

- `success(mensaje)` o `toast(mensaje, tipo)`: aviso breve que se pausa al pasar el cursor o enfocar sus controles.
- `error(mensaje, titulo)` y `warning(mensaje, titulo)`: mensajes persistentes que el usuario cierra con **Entendido**.
- `confirm({text, title, button, danger})`: devuelve una promesa booleana y enfoca **Cancelar** inicialmente.

Los formularios que necesitan confirmación usan `data-confirm`, `data-confirm-title`, `data-confirm-button` y, cuando corresponde, `data-confirm-danger="1"`. El manejador compartido conserva la validación nativa y reenvía el formulario original con su botón de envío únicamente después de aceptar. Los formularios de entrega no solicitan confirmación.

Los mensajes PHP `flash()` se convierten en avisos de éxito/información o ventanas persistentes de advertencia/error. Si la biblioteca no está disponible, esos mensajes permanecen visibles en la página y las confirmaciones recurren al diálogo nativo del navegador.

En las entregas, el aviso de éxito aparece después de que se registre la venta y se copie el texto. Si la copia falla, se informa que la venta ya está registrada y el texto queda disponible para copiarlo sin consumir nuevos cupos. Las cuentas llenas o bloqueadas conservan sus mensajes junto al contador.
