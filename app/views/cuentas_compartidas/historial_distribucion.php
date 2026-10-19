<?php declare(strict_types=1); ?>
<details class="mt-3" <?= !empty($historyExpanded) ? 'open' : '' ?>>
    <summary class="fw-semibold">Historial de distribución</summary>
    <p class="small text-secondary mt-2 mb-2">Se conserva el primer inicio y el primer llenado de cada ciclo. Las fechas anteriores a este registro quedan sin registrar. Hora de Bolivia.</p>
    <?php if (empty($historialDistribucion)): ?>
        <p class="small text-secondary mb-0">Sin fechas de distribución registradas.</p>
    <?php else: ?>
        <div class="table-responsive"><table data-responsive-cards class="table table-sm align-middle mb-0">
            <thead><tr><th>Ciclo</th><th>Inicio de distribución</th><th>Primer llenado</th><th>Ocupación al llenarse</th></tr></thead>
            <tbody><?php foreach ($historialDistribucion as $distribucionHistorica): ?>
                <tr>
                    <td><?= (int) $distribucionHistorica['numero_ciclo'] ?><?= (int) $distribucionHistorica['ciclo_id'] === (int) $cuenta['ciclo_actual'] ? ' (actual)' : '' ?></td>
                    <td><?= e(display_datetime($distribucionHistorica['inicio_distribucion'])) ?></td>
                    <td><?= e(display_datetime($distribucionHistorica['cupos_completados_at'])) ?></td>
                    <td><?php if ($distribucionHistorica['capacidad_al_completar'] !== null): ?><?= (int) $distribucionHistorica['ocupados_al_completar'] ?> / <?= (int) $distribucionHistorica['capacidad_al_completar'] ?><?php else: ?>—<?php endif; ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</details>
