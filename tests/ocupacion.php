<?php
declare(strict_types=1);

/** Se ejecuta desde claude.php, en la base efímera de la suite. */
function runOccupationTests(PDO $pdo, callable $test): void
{
    $token = static fn(): string => bin2hex(random_bytes(32));
    $today = date('Y-m-d');
    $create = static function ($model) use ($today): int {
        return $model->create(['etiqueta' => 'Ocupación fixture', 'correo' => bin2hex(random_bytes(8)) . '@example.invalid',
            'password_cuenta' => 'fixture-only', 'capacidad' => 3, 'fecha_pago' => $today, 'monto_bs' => '200.00']);
    };
    foreach ([\App\Models\ChatgptCuenta::class, \App\Models\ClaudeCuenta::class] as $class) {
        $label = $class === \App\Models\ChatgptCuenta::class ? 'ChatGPT' : 'Claude';
        $test("ocupación {$label}: subir, bajar y vender conserva ventas y pagos y actualiza todos los contadores", static function () use ($pdo, $class, $create, $token, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            $model->selectDistribution($id);
            $model->sell($id, 1, $today, $token());
            $sales = $model->sales($id);
            $payments = $model->payments($id);
            $model->adjustOccupation($id, '4', '1', 'Conteo de dispositivos', 'fixture-admin', $token());
            clEq($model->find($id)['ocupados'], 4, 'Debe permitir una ocupación superior a la capacidad.');
            clEq($model->find($id)['estado_distribucion'], 'En distribución', 'La cuenta debe seguir seleccionada al alcanzar la referencia.');
            clEq($model->currentDistribution()['libres'], 0, 'No debe mostrar cupos negativos.');
            clEq($model->find($id)['ventas_habilitadas'], true, 'La ocupación ajustada no bloquea la cuenta seleccionada.');
            $model->adjustOccupation($id, 0, 4, '', 'fixture-admin', $token());
            clEq($model->find($id)['ocupados'], 0, 'Debe poder reducir a cero.');
            clEq($model->find($id)['estado_distribucion'], 'En distribución', 'Reducir la ocupación vuelve a abrir cupos.');
            clEq($model->sales($id), $sales, 'Ajustar no modifica ni libera ventas históricas.');
            clEq($model->payments($id), $payments, 'Ajustar no crea ni modifica pagos.');
            $quantity = $class === \App\Models\ChatgptCuenta::class ? 2 : 1;
            $model->sell($id, $quantity, $today, $token());
            clEq($model->find($id)['ocupados'], $quantity, 'La próxima entrega se suma al contador ajustado.');
            $rows = array_column($model->all(), null, 'id');
            clEq($rows[$id]['ocupados'], $quantity, 'La lista debe coincidir con el detalle y la distribución.');
            $adjustments = $model->occupationAdjustments($id);
            clEq(count($adjustments), 2, 'Debe registrar ambos ajustes.');
            clEq($adjustments[0]['usuario'], 'fixture-admin', 'Debe conservar quién realizó el ajuste.');
            clEq($adjustments[1]['motivo'], 'Conteo de dispositivos', 'Debe conservar el motivo.');
            clEq($model->startCycle($id, true, 'fixture-admin', $token(), $model->currentCycleId($id)), $quantity, 'El ciclo libera la ocupación ajustada.');
            clEq($model->find($id)['ocupados'], 0, 'El nuevo ciclo debe empezar en cero.');
            clEq($model->find($id)['ajuste_ocupacion'], 0, 'El ciclo elimina el ajuste del contador.');
            clEq(count($model->occupationAdjustments($id)), 2, 'El ciclo conserva el historial de ajustes.');
        });
        $test("ocupación {$label}: rechaza valores inválidos y formularios anteriores a una venta", static function () use ($pdo, $class, $create, $token, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            $model->selectDistribution($id);
            foreach ([-1, '1.5', '1e2', '100000', '', 'abc', [], true] as $invalid) {
                clReject(static fn() => $model->adjustOccupation($id, $invalid, 0, '', 'fixture', $token()), 'Aceptó una ocupación inválida.');
            }
            clReject(static fn() => $model->adjustOccupation($id, 1, [], '', 'fixture', $token()), 'Aceptó un contador anterior inválido.');
            clReject(static fn() => $model->adjustOccupation($id, 1, 0, str_repeat('x', 256), 'fixture', $token()), 'Aceptó un motivo demasiado largo.');
            $model->sell($id, 1, $today, $token());
            clReject(static fn() => $model->adjustOccupation($id, 2, 0, '', 'fixture', $token()), 'Un formulario antiguo no puede sobreescribir una venta nueva.');
            clEq($model->find($id)['ocupados'], 1, 'Los rechazos no deben alterar el contador.');
            clEq(count($model->occupationAdjustments($id)), 0, 'Los rechazos no deben dejar ajustes parciales.');
        });
        $test("ocupación {$label}: reintentar un ajuste no borra entregas posteriores ni cruza cuentas", static function () use ($pdo, $class, $create, $token, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            $other = $create($model);
            $model->selectDistribution($id);
            $adjustmentToken = $token();
            $model->adjustOccupation($id, 1, 0, '', 'fixture', $adjustmentToken);
            $model->sell($id, 1, $today, $token());
            $model->adjustOccupation($id, 1, 0, '', 'fixture', $adjustmentToken);
            clEq($model->find($id)['ocupados'], 2, 'Reintentar no debe perder la entrega posterior.');
            clEq(count($model->occupationAdjustments($id)), 1, 'El token no puede duplicar el ajuste.');
            clReject(static fn() => $model->adjustOccupation($id, 2, 0, '', 'fixture', $adjustmentToken), 'No se puede reutilizar el token con otro total.');
            clReject(static fn() => $model->adjustOccupation($other, 1, 0, '', 'fixture', $adjustmentToken), 'No se puede reutilizar el token en otra cuenta.');
            $model->setFreePlan($id, true);
            $model->adjustOccupation($id, 1, 2, '', 'fixture', $token());
            clEq($model->find($id)['estado_periodo'], 'GRATIS', 'Ajustar no debe restaurar un plan Gratis.');
            clReject(static fn() => $model->sell($id, 1, $today, $token()), 'Ajustar no puede habilitar ventas en Gratis.');
        });
        $test("ocupación {$label}: ajustes negativos y retirada de accesos heredados mantienen el contador y la próxima entrega", static function () use ($pdo, $class, $create, $token, $today): void {
            $model = new $class($pdo);
            $id = $create($model);
            $model->selectDistribution($id);
            $pdo->exec("INSERT INTO modalidades (nombre_modalidad, dispositivos) VALUES ('Fixture dos', 2)");
            $mode = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO suscripciones (modalidad_id, cuenta_principal_id, flag_no_renovo) VALUES (:mode, :id, 0)')->execute(['mode' => $mode, 'id' => $id]);
            $legacy = (int) $pdo->lastInsertId();
            $model->adjustOccupation($id, 0, $class === \App\Models\ChatgptCuenta::class ? 2 : 1, '', 'fixture', $token());
            $pdo->exec('UPDATE suscripciones SET flag_no_renovo = 1 WHERE id = ' . $legacy);
            clEq($model->find($id)['ocupados'], 0, 'Retirar accesos heredados no debe volver negativo el contador.');
            $model->sell($id, 1, $today, $token());
            clEq($model->find($id)['ocupados'], 1, 'La próxima entrega debe consumir exactamente un cupo.');
            $model->adjustOccupation($id, 3, 1, '', 'fixture', $token());
            $before = $model->find($id);
            run_distribucion_schema_migrations($pdo);
            clEq($model->find($id), $before, 'Repetir la migración conserva el ajuste.');
        });
    }
    $test('ocupación: controlador exige administrador actual y token del formulario para ambas plataformas', static function () use ($pdo, $create): void {
        $pdo->exec("INSERT INTO usuarios (username, rol) VALUES ('ajuste-fixture', 'admin')");
        $user = (int) $pdo->lastInsertId();
        foreach ([[\App\Models\ChatgptCuenta::class, DistributionChatgptController::class], [\App\Models\ClaudeCuenta::class, DistributionClaudeController::class]] as [$modelClass, $controllerClass]) {
            $model = new $modelClass($pdo);
            $id = $create($model);
            $_SESSION['auth'] = ['id' => $user, 'username' => 'ajuste-fixture', 'rol' => 'admin'];
            $controller = new $controllerClass();
            $post = ['ocupados' => '2', 'ocupados_antes' => '0', 'motivo' => 'Verificación'];
            $_POST = $post + ['token_operacion' => 'invalid'];
            try { $controller->adjustOccupation($id); } catch (DistributionRedirect $redirect) {}
            clEq($model->find($id)['ocupados'], 0, 'Un token inválido no puede ajustar.');
            $_POST = $post + ['token_operacion' => $controller->formToken('ocupacion', $id)];
            try { $controller->adjustOccupation($id); } catch (DistributionRedirect $redirect) {}
            clEq($model->find($id)['ocupados'], 2, 'El administrador con token válido puede ajustar.');
            $pdo->exec("UPDATE usuarios SET rol = 'operador' WHERE id = " . $user);
            $_SESSION['auth']['rol'] = 'admin';
            $_POST = ['ocupados' => '0', 'ocupados_antes' => '2', 'token_operacion' => $controller->formToken('ocupacion', $id)];
            try { $controller->adjustOccupation($id); } catch (DistributionRedirect $redirect) {}
            clEq($model->find($id)['ocupados'], 2, 'Retirar el rol de administrador bloquea también un formulario abierto.');
            clEq(count($model->occupationAdjustments($id)), 1, 'Las operaciones rechazadas no crean ajustes.');
            $pdo->exec("UPDATE usuarios SET rol = 'admin' WHERE id = " . $user);
        }
        $_POST = [];
        unset($_SESSION['auth']);
    });
    $test('ocupación: dos entregas concurrentes se suman al contador ajustado aunque crucen la referencia', static function () use ($pdo, $create, $token, $today): void {
        $model = new \App\Models\ClaudeCuenta($pdo);
        $id = $create($model);
        $model->selectDistribution($id);
        $model->adjustOccupation($id, 2, 0, '', 'fixture', $token());
        clEq(clConcurrentSales($id, $today, [$token(), $token()]), ['SOLD', 'SOLD'], 'Ambas entregas se permiten en la cuenta seleccionada.');
        clEq($model->find($id)['ocupados'], 4, 'Debe sumar ambas entregas al ajuste sin perder cupos.');
    });
}
