<?php
declare(strict_types=1);

// Comparte únicamente la base efímera de modals.php.
$test('UI: todos los estados de Suscripciones conservan su código y filtran los resultados', static function () use ($request, $pdo, $check): void {
    foreach (App\Models\Suscripcion::ESTADOS as $state) {
        [$code, $html] = $request('/suscripciones?' . http_build_query(['estado' => $state, 'q' => 'Modal']));
        $check($code === 200, 'No abrió el filtro ' . $state);
        $check((bool) preg_match('/<option value="' . $state . '"\s+selected>/', $html), 'No conservó ' . $state);
        preg_match_all('/data-record-key="suscripcion-(\d+)"/', $html, $matches);
        foreach ($matches[1] as $id) $check($pdo->query('SELECT estado FROM suscripciones WHERE id=' . (int) $id)->fetchColumn() === $state, 'El filtro incluye otro estado.');
    }
});
$test('UI: cuentas filtran y paginan sin cambiar la selección ni renumerar la cola', static function () use ($pdo, $request, $check, $hidden, $csrf): void {
    foreach (['chatgpt' => App\Models\ChatgptCuenta::class, 'claude' => App\Models\ClaudeCuenta::class] as $service => $class) {
        $model = new $class($pdo);
        $current = $model->currentDistribution()['id'] ?? null;
        $prefix = 'UIUX-' . $service . '-';
        $ids = [];
        for ($i = 1; $i <= 15; ++$i) $ids[] = $model->create(['etiqueta' => $prefix . $i,
            'correo' => $prefix . $i . '@example.test', 'password_cuenta' => 'fixture-ui-only',
            'fecha_pago' => date('Y-m-d'), 'monto_bs' => '200.50', 'notas' => 'Datos ficticios de prueba']);
        $pdo->exec('UPDATE cuentas_principales SET activo=0 WHERE id=' . $ids[0]);
        $model->setFreePlan($ids[1], true);
        $pdo->exec("UPDATE cuentas_principales SET password_cuenta='' WHERE id=" . $ids[2]);
        $first = $model->listing($prefix, '', 1, 10);
        $second = $model->listing($prefix, '', 2, 10);
        $check(count($first['rows']) === 10 && count($second['rows']) === 5 && $first['totalRows'] === 15, 'Paginación incorrecta.');
        $check(count(array_intersect(array_column($first['rows'], 'id'), array_column($second['rows'], 'id'))) === 0, 'Repite cuentas entre páginas.');
        foreach (['inactivas' => $ids[0], 'gratis' => $ids[1], 'pendientes' => $ids[2]] as $filter => $id) {
            $result = $model->listing($prefix, $filter);
            $check($result['totalRows'] === 1 && (int) $result['rows'][0]['id'] === $id, 'Filtro incorrecto: ' . $filter);
        }
        $full = $model->listing('', 'listas', 1, 100);
        $page = $model->listing('', 'listas', 2, 10);
        $positions = array_column($full['rows'], 'turno_distribucion', 'id');
        foreach ($page['rows'] as $row) $check($positions[$row['id']] === $row['turno_distribucion'], 'Se renumeró la cola al paginar.');
        $invalid = $model->listing($prefix, 'inexistente', 999, 3);
        $check($invalid['filter'] === '' && $invalid['perPage'] === 25 && $invalid['page'] === 1, 'No normalizó parámetros inválidos.');
        $check(($model->currentDistribution()['id'] ?? null) === $current, 'Consultar el listado cambió la selección.');
        [$code, $html] = $request('/' . $service . '?' . http_build_query(['q' => $prefix, 'por_pagina' => 10, 'page' => 2]));
        $check($code === 200 && str_contains($html, 'Página 2 de 2') && str_contains($html, 'name="page" value="2"'), 'El HTML perdió la página al distribuir.');
        $check(str_contains($html, 'name="por_pagina" value="10"') && str_contains($html, 'gs-action-menu'), 'Falta el contexto o las acciones compactas.');
        $context = ['q' => $prefix, 'filtro' => 'listas', 'page' => 2, 'por_pagina' => 10];
        [,$html] = $request('/' . $service . '?' . http_build_query($context));
        $next = $model->listing($prefix, 'listas', 2, 10)['rows'][0];
        try {
            [$code,,$headers] = $request('/' . $service . '/' . (int) $next['id'] . '/distribuir', $context + [
                '_csrf' => $csrf, 'origen' => 'listado', 'token_operacion' => $hidden($html, 'token_operacion')]);
            $location = implode('', preg_grep('/^Location:/i', $headers));
            parse_str((string) parse_url(trim(substr($location, 9)), PHP_URL_QUERY), $returned);
            $check($code === 302 && $returned == $context, 'Distribuir no conservó búsqueda, filtro, página y tamaño.');
            $check((int) $model->currentDistribution()['id'] === (int) $next['id'], 'No seleccionó la cuenta solicitada.');
        } finally {
            if ($current !== null) $model->selectDistribution((int) $current);
        }
    }
});
$test('UI: el panel abre pendientes y mantiene acceso a los vigentes y a las entregas', static function () use ($request, $makeSubscription, $check): void {
    $id = $makeSubscription(1, date('Y-m-d'));
    [$code, $html] = $request('/dashboard');
    $check($code === 200 && str_contains($html, 'name="estado" value="PENDIENTES"'), 'El panel no prioriza pendientes.');
    $check(str_contains($html, 'data-record-key="suscripcion-' . $id . '"'), 'No muestra la suscripción que vence hoy.');
    $check(str_contains($html, 'Entregar ChatGPT') && str_contains($html, 'Entregar Claude') && str_contains($html, 'Resumen financiero de los resultados'), 'Faltan accesos o resumen.');
});
