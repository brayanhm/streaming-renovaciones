<?php
declare(strict_types=1);

// Las credenciales pertenecen a cada cuenta; la plantilla conserva solo variables.
$entrega = <<<'MENSAJE'
📢 *LEER OBLIGATORIO*
⚠️ *Reglas básicas de uso*
1️⃣ *Cómo iniciar sesión:*
🖥️ En PC: Abre tu navegador y entra a ChatGPT
📱 En celular: Abre la aplicación de ChatGPT
👉 Selecciona "Iniciar sesión"
👉 Escribe el correo y contraseña *manualmente*
❌ *No selecciones "Continuar con Google"*
📧 Correo: {CORREO}
🔑 Contraseña: {PASSWORD}
2️⃣ Luego de ingresar el correo y contraseña, ChatGPT te pedirá un código de acceso toma una captura o foto  de esa pantalla. Escríbenos la palabra  *CÓDIGO* con la captura o foto que tomaste, te lo enviamos en ese momento.
⏱️ Tienes *60 segundos* para usarlo desde que te lo enviamos, *úsalo de inmediato*.
⚠️ *Sin esa captura no podremos restaurarte el acceso* si se cierra la sesión.
⚠️ *ChatGPT Compartida  {DISPOSITIVOS} {DISPOSITIVO_LABEL} | 1 mes*
🚨 *Importante — Lee antes de usar:*
❌ *No cambies configuraciones ni elimines nada* → la cuenta se bloqueará automáticamente
❌ *No modifiques ni borres proyectos o chats de otros usuarios*
❌ *No hay reembolsos ni cambios de dispositivo* una vez iniciada la sesión
✅ *Crea tu propia carpeta o proyecto* para mantener tu información separada y segura
⚠️ El mal uso puede ocasionar *bloqueo permanente o pérdida de acceso sin derecho a reembolso*
MENSAJE;

return ['entrega_1' => $entrega, 'entrega_2' => $entrega];
