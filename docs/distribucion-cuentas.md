# Distribución de ChatGPT y Claude

Cada servicio tiene una sola cuenta seleccionada. El administrador configura la capacidad al crear o editar cada cuenta. Las cuentas existentes conservan su capacidad y sus accesos; la migración no elige una cuenta automáticamente.

## Operación

1. Abrir una cuenta con credenciales completas, activa y pagada o en gracia.
2. Usar **Seleccionar para distribución** para cambiar la cuenta actual conservando todos los accesos y cupos ocupados. También permite volver a una cuenta que había quedado en reserva.
3. Cuando la ocupación alcanza la capacidad, la cuenta muestra **Llena**. Los vendedores no pueden superar el límite; el administrador puede registrar ventas adicionales solamente en la cuenta seleccionada. Una venta de dos dispositivos ChatGPT consume dos cupos.
4. Para retirar accesos anteriores y reutilizar una cuenta, cerrar sus sesiones fuera del sistema, desplegar **Iniciar nuevo ciclo** y confirmar el cierre en todos los dispositivos. El ciclo reinicia la ocupación, selecciona la cuenta y conserva los historiales de ventas, pagos y reinicios.

Registrar un pago o cambiar la selección no reinicia la ocupación. Un formulario repetido de ciclo devuelve el resultado original sin liberar ventas nuevas.

## Entregas al cliente

En el menú de cada servicio, **Cuenta en distribución** muestra la cuenta seleccionada, su plan y los cupos ocupados y libres. Reemplaza la pantalla de edición de textos y también está disponible para los operadores.

**Copiar entrega** registra la venta y copia el mensaje para el cliente. ChatGPT ofrece botones de uno o dos dispositivos, que ocupan uno o dos cupos respectivamente; Claude ofrece un dispositivo, que ocupa un cupo. Se usan los textos existentes y un mensaje predeterminado cuando están vacíos.

La última entrega registrada queda disponible para **Volver a copiar**, sin registrar otra venta. Recargar la página o repetir una solicitud conserva la ocupación. Si el navegador impide copiar, el mensaje queda disponible para copiarlo manualmente y los cupos ya registrados no se duplican.

Las rutas antiguas `/chatgpt/plantillas` y `/claude/plantillas` redirigen a la cuenta en distribución. La edición de textos ya no tiene una pantalla ni una ruta de guardado.

## Plan y vigencia

ChatGPT conserva un mes pagado y un mes de gracia; Claude conserva un mes pagado. Al terminar la vigencia, el estado calculado pasa a **Gratis** y bloquea las ventas para todos los roles. El administrador también puede marcar Gratis antes del vencimiento si la cuenta pierde el plan.

**Restaurar plan vigente** elimina esa marca manual únicamente si las fechas siguen vigentes. Después del vencimiento se requiere un nuevo pago. Los cupos se mantienen incluso cuando la cuenta está Gratis o inactiva.

## Instalación y verificación

La migración compartida crea `cuentas_distribucion`, `cuentas_ciclos` y la marca `plan_gratis`. Se incluye en las migraciones de ambos servicios. Para instalaciones sin migraciones automáticas:

```sh
php deploy/migrate_chatgpt.php
```

Las pruebas de integración crean bases temporales con nombres restringidos y las eliminan al terminar:

```sh
php tests/chatgpt.php
php tests/claude.php
php tests/chatgpt_existentes.php
php tests/chatgpt_textos.php
php tests/run.php
```

`tests/claude.php` incluye los casos de distribución y entregas de ambos servicios y las pruebas de permisos. La selección, las ventas y los nuevos ciclos bloquean primero la fila de su servicio para mantener una selección única y respetar el último cupo en operaciones concurrentes.
