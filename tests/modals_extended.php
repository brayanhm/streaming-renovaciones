<?php
declare(strict_types=1);

// Se ejecuta dentro de modals.php y comparte únicamente su base efímera y servidor local.
$hidden = static function (string $html, string $name) use ($check): string {
    $check((bool) preg_match('/name="'.preg_quote($name, '/').'" value="([^"]*)"/', $html, $match), 'Falta el campo '.$name);
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
};
$makeSubscription = static function (int $client = 1, string $due = '2028-01-31') use ($pdo,$platform,$plan): int {
    $stmt = $pdo->prepare("INSERT INTO suscripciones (cliente_id,plataforma_id,modalidad_id,fecha_inicio,fecha_vencimiento,usuario_proveedor,precio_venta,costo_base)
        VALUES (?,?,?,'2026-01-01',?,'usuario_modal',45.50,10.20)");
    $stmt->execute([$client,$platform,$plan,$due]);
    return (int) $pdo->lastInsertId();
};
$test('renovación: vista previa respeta importes personalizados y fin de mes bisiesto', static function () use ($makeSubscription,$pdo,$request,$check): void {
    $id = $makeSubscription();
    $options = (new App\Models\Suscripcion($pdo))->renewalOptions($id);
    $check($options[1]['precio'] === '45.50' && $options[1]['costo'] === '10.20','La vista previa perdió los importes acordados.');
    $check($options[1]['nuevo_vencimiento'] === '2028-02-29','El calendario no respeta el último día del mes.');
    [$code,$html] = $request('/suscripciones/renovar/'.$id);
    $check($code === 200 && str_contains($html,'data-form-behavior="renewal"') && str_contains($html,'29/02/2028'),'Falta la vista previa remota.');
});
$test('renovación: confirmar registra un ingreso y reenviar el mismo formulario no lo duplica', static function () use ($makeSubscription,$request,$hidden,$csrf,$pdo,$check): void {
    $id = $makeSubscription();
    [,$html] = $request('/suscripciones/renovar/'.$id);
    $post = ['_csrf'=>$csrf,'meses'=>'1','token_renovacion'=>$hidden($html,'token_renovacion')];
    [$code,$body] = $request('/suscripciones/renovar/'.$id,$post,true);
    $check($code === 200 && json_decode($body,true)['ok'],'No confirmó la renovación.');
    $check($pdo->query('SELECT fecha_vencimiento FROM suscripciones WHERE id='.$id)->fetchColumn() === '2028-02-29','Vencimiento incorrecto.');
    [$code,$body] = $request('/suscripciones/renovar/'.$id,$post,true);
    $check($code === 422 && !json_decode($body,true)['ok'],'Se aceptó un reenvío.');
    $check((int)$pdo->query('SELECT COUNT(*) FROM movimientos WHERE suscripcion_id='.$id)->fetchColumn() === 1,'Se duplicó el ingreso.');
    $check((float)$pdo->query('SELECT monto FROM movimientos WHERE suscripcion_id='.$id)->fetchColumn() === 45.50,'El importe difiere de la vista previa.');
});
$test('renovación: dos formularios abiertos no pueden cobrar dos veces sobre el mismo vencimiento', static function () use ($makeSubscription,$request,$hidden,$csrf,$pdo,$check): void {
    $id = $makeSubscription();
    [,$first] = $request('/suscripciones/renovar/'.$id); [,$second] = $request('/suscripciones/renovar/'.$id);
    foreach ([$first,$second] as $index=>$html) {
        [$code] = $request('/suscripciones/renovar/'.$id,['_csrf'=>$csrf,'meses'=>'1','token_renovacion'=>$hidden($html,'token_renovacion')],true);
        $check($code === ($index === 0 ? 200 : 422),'No rechazó el vencimiento anterior.');
    }
    $check((int)$pdo->query('SELECT COUNT(*) FROM movimientos WHERE suscripcion_id='.$id)->fetchColumn() === 1,'Se registró más de un ingreso.');
});
$test('renovación: un cambio de precio posterior a abrir el modal exige revisar antes de cobrar', static function () use ($makeSubscription,$request,$hidden,$csrf,$pdo,$platform,$check): void {
    $id = $makeSubscription();
    [,$html] = $request('/suscripciones/renovar/'.$id);
    $price = $pdo->query('SELECT precio FROM modalidades WHERE plataforma_id='.$platform.' AND duracion_meses=3')->fetchColumn();
    try {
        $pdo->exec('UPDATE modalidades SET precio=999 WHERE plataforma_id='.$platform.' AND duracion_meses=3');
        [$code,$body] = $request('/suscripciones/renovar/'.$id,['_csrf'=>$csrf,'meses'=>'3','token_renovacion'=>$hidden($html,'token_renovacion')],true);
        $check($code === 422 && !json_decode($body,true)['ok'],'Aceptó un importe diferente al mostrado.');
        $check((int)$pdo->query('SELECT COUNT(*) FROM movimientos WHERE suscripcion_id='.$id)->fetchColumn() === 0,'Registró un cobro rechazado.');
    } finally { $pdo->exec('UPDATE modalidades SET precio='.(float)$price.' WHERE plataforma_id='.$platform.' AND duracion_meses=3'); }
});
$test('vencimiento: reactivar y corregir fechas no crea movimientos financieros', static function () use ($request,$makeSubscription,$csrf,$pdo,$check): void {
    $id = $makeSubscription();
    [$code,$html] = $request('/suscripciones/finalizacion/'.$id.'?reactivar=1');
    $check($code === 200 && str_contains($html,'Este cambio no registra un ingreso.'),'Falta la explicación de la operación.');
    [$code,$body] = $request('/suscripciones/finalizacion/'.$id,['_csrf'=>$csrf,'fecha_vencimiento'=>'2028-03-15'],true);
    $check($code === 200 && json_decode($body,true)['ok'],'No actualizó la fecha.');
    [$code] = $request('/suscripciones/finalizacion/'.$id,['_csrf'=>$csrf,'fecha_vencimiento'=>'2025-01-01'],true);
    $check($code === 422,'Aceptó una fecha anterior al inicio.');
    $check((int)$pdo->query('SELECT COUNT(*) FROM movimientos WHERE suscripcion_id='.$id)->fetchColumn() === 0,'Cambiar la fecha registró un cobro.');
});
$test('historial de renovaciones: consulta amplia sin modificar datos ni mostrar formularios', static function () use ($request,$subscription,$pdo,$check): void {
    $before = (int)$pdo->query('SELECT COUNT(*) FROM movimientos')->fetchColumn();
    [$code,$html] = $request('/suscripciones/historial/'.$subscription);
    $check($code === 200 && str_contains($html,'data-modal-size="wide"') && str_contains($html,'Historial de renovaciones'),'No está disponible el historial modal.');
    $check(!str_contains($html,'<form method="post"'),'El historial contiene una operación de escritura.');
    $check((int)$pdo->query('SELECT COUNT(*) FROM movimientos')->fetchColumn() === $before,'Consultar alteró el historial.');
});
$test('cupos: formulario independiente para ChatGPT y Claude con validación y guardado', static function () use ($request,$hidden,$csrf,$pdo,$check): void {
    foreach ($pdo->query("SELECT id,servicio FROM cuentas_principales WHERE servicio IN ('CHATGPT','CLAUDE')")->fetchAll() as $account) {
        $path = '/'.strtolower($account['servicio']).'/'.$account['id'];
        [$code,$html] = $request($path.'/ocupacion');
        $check($code === 200 && str_contains($html,'data-standalone-occupation'),'No abrió el ajuste independiente.');
        $post = ['_csrf'=>$csrf,'ocupados'=>'2','ocupados_antes'=>'0','motivo'=>'Prueba del modal','token_operacion'=>$hidden($html,'token_operacion')];
        [$code,$body] = $request($path.'/ocupacion',$post,true);
        $check($code === 200 && json_decode($body,true)['ok'],'Falló el ajuste independiente.');
        [$code,$edit] = $request($path.'/editar');
        $check($code === 200 && !str_contains($edit,'name="ocupados"') && str_contains($edit,$path.'/ocupacion'),'La edición general aún mezcla el formulario de cupos.');
    }
});
$test('historial de distribución: acceso directo desde los dos índices', static function () use ($request,$pdo,$check): void {
    foreach ($pdo->query("SELECT id,servicio FROM cuentas_principales WHERE servicio IN ('CHATGPT','CLAUDE')")->fetchAll() as $account) {
        $service = strtolower($account['servicio']); $path = '/'.$service.'/'.$account['id'].'/historial-distribucion';
        [$code,$index] = $request('/'.$service); $check($code === 200 && str_contains($index,$path),'Falta el acceso en el índice.');
        [$code,$html] = $request($path); $check($code === 200 && str_contains($html,'data-modal-size="wide"') && str_contains($html,'Primer llenado'),'Falta el historial de distribución.');
    }
});
$test('permisos: un operador consulta historial pero no puede abrir ni aplicar ajustes de cupos', static function () use ($request,$pdo,$csrf,$check): void {
    $id = (int)$pdo->query("SELECT id FROM cuentas_principales WHERE servicio='CLAUDE' LIMIT 1")->fetchColumn();
    try {
        $pdo->exec("UPDATE usuarios SET rol='operador' WHERE id=1");
        [$code] = $request('/claude/'.$id.'/ocupacion'); $check($code === 302,'Se permitió abrir el ajuste sin rol.');
        [$code,$body] = $request('/claude/'.$id.'/ocupacion',['_csrf'=>$csrf,'ocupados'=>'9','ocupados_antes'=>'2','token_operacion'=>'invalido'],true);
        $check($code === 422 && !json_decode($body,true)['ok'],'Se permitió escribir sin rol.');
        [$code,$html] = $request('/claude/'.$id.'/historial-distribucion');
        $check($code === 200 && str_contains($html,'Primer llenado'),'El operador perdió la consulta de historial.');
    } finally { $pdo->exec("UPDATE usuarios SET rol='admin' WHERE id=1"); $request('/claude'); }
});
$pending = [];
foreach (['Pendiente uno','Pendiente dos','Pendiente tres'] as $name) {
    $stmt = $pdo->prepare("INSERT INTO clientes (nombre,telefono,notas) VALUES (?,'','Datos ficticios pendientes')"); $stmt->execute([$name]);
    $pending[] = $id = (int)$pdo->lastInsertId(); $makeSubscription($id);
}
$test('completar contactos: conserva filtro y ofrece guardar y siguiente con contexto de la cuenta', static function () use ($request,$pending,$platform,$check): void {
    [$code,$html] = $request('/clientes/completar?q=Pendiente&plataforma_id='.$platform.'&tipo_cuenta=CUENTA_COMPLETA');
    $check($code === 200 && substr_count($html,'data-contact-complete') === 3,'No conservó los pendientes filtrados.');
    [$code,$html] = $request('/clientes/completar/'.$pending[0]);
    $check($code === 200 && str_contains($html,'usuario_modal') && str_contains($html,'Guardar y siguiente'),'Faltan contexto o acción para continuar.');
});
$test('completar contactos: rechaza errores y al guardar retira únicamente al cliente completado', static function () use ($request,$pending,$csrf,$pdo,$check): void {
    [$code,$body] = $request('/clientes/completar/actualizar/'.$pending[0],['_csrf'=>$csrf,'contacto'=>'','numero'=>'71234567'],true);
    $check($code === 422 && isset(json_decode($body,true)['fields']['contacto']),'Aceptó el contacto vacío.');
    [$code,$body] = $request('/clientes/completar/actualizar/'.$pending[0],['_csrf'=>$csrf,'contacto'=>'Pendiente completado','numero'=>'123'],true);
    $check($code === 422 && isset(json_decode($body,true)['fields']['numero']),'Aceptó un teléfono inválido.');
    [$code,$body] = $request('/clientes/completar/actualizar/'.$pending[0],['_csrf'=>$csrf,'contacto'=>'Pendiente completado','numero'=>'71234567','continuar'=>'1'],true);
    $check($code === 200 && json_decode($body,true)['ok'],'No guardó el contacto.');
    [,$html] = $request('/clientes/completar?q=Pendiente');
    $check(substr_count($html,'data-contact-complete') === 2 && !str_contains($html,'/clientes/completar/'.$pending[0].'"'),'No actualizó correctamente la cola.');
    $check($pdo->query('SELECT notas FROM clientes WHERE id='.$pending[0])->fetchColumn() === 'Datos ficticios pendientes','Se perdieron notas al completar.');
});
$test('perfil: usuario y contraseña se cargan por separado y la validación se queda en su campo', static function () use ($request,$csrf,$check): void {
    [$code,$html] = $request('/perfil');
    $check($code === 200 && str_contains($html,'data-modal-page') && str_contains($html,'name="username"') && !str_contains($html,'name="current_password"'),'El perfil no separó las operaciones.');
    [$code,$html] = $request('/perfil?seccion=password');
    $check($code === 200 && str_contains($html,'name="current_password"') && !str_contains($html,'name="username"'),'No cargó el cambio de contraseña.');
    [$code,$body] = $request('/perfil',['_csrf'=>$csrf,'_action'=>'change_username','username'=>'x'],true);
    $check($code === 422 && isset(json_decode($body,true)['fields']['username']),'No señaló el usuario inválido.');
    [$code,$body] = $request('/perfil',['_csrf'=>$csrf,'_action'=>'change_password','current_password'=>'no-valida','new_password'=>'prueba-nueva','confirm_password'=>'prueba-nueva'],true);
    $check($code === 422 && isset(json_decode($body,true)['fields']['current_password']) && !str_contains($body,'prueba-nueva'),'La validación de contraseña filtró datos o no señaló el campo.');
});
$test('perfil: guarda usuario y contraseña únicamente del usuario autenticado', static function () use ($request,$csrf,$pdo,$check): void {
    [$code,$body] = $request('/perfil',['_csrf'=>$csrf,'_action'=>'change_username','username'=>'demo_modal_actualizado'],true);
    $check($code === 200 && json_decode($body,true)['ok'],'No guardó el nombre de usuario.');
    $stmt = $pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id=1'); $stmt->execute([password_hash('fixture-actual',PASSWORD_DEFAULT)]);
    [$code,$body] = $request('/perfil',['_csrf'=>$csrf,'_action'=>'change_password','current_password'=>'fixture-actual','new_password'=>'fixture-nueva','confirm_password'=>'fixture-nueva'],true);
    $check($code === 200 && json_decode($body,true)['ok'],'No guardó la contraseña de prueba.');
    $check(password_verify('fixture-nueva',$pdo->query('SELECT password_hash FROM usuarios WHERE id=1')->fetchColumn()),'La contraseña no quedó cifrada correctamente.');
});
