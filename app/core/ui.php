<?php
declare(strict_types=1);
require_once __DIR__ . '/ValidationException.php';

function status_label(string $state): string
{
    return ['CONTACTAR_2D' => 'Por vencer', 'REENVIAR_1D' => 'Vence hoy', 'ESPERA' => 'En espera',
        'ACTIVO' => 'Al día', 'VENCIDO' => 'Vencida', 'RECUP' => 'Recuperación'][$state] ?? $state;
}

function display_date(?string $value): string
{
    $date = parse_ymd_date(substr((string) $value, 0, 10));
    return $date !== null ? $date->format('d/m/Y') : '—';
}

/** Solo datos no sensibles; el formulario original conserva su contexto al rechazar una edición. */
function safe_form_input(array $input): array
{
    return array_filter($input, static fn ($value, $key): bool => is_scalar($value)
        && !preg_match('/password|contrase|token|csrf/i', (string) $key), ARRAY_FILTER_USE_BOTH);
}

function set_field_errors(array $errors): void
{
    $_SESSION['_form_feedback'] = ['action' => url(request_path()), 'fields' => $errors];
}

function form_value(string $action, string $key, string $default = ''): string
{
    return ($_SESSION['_old_action'] ?? '') === $action ? old($key, $default) : $default;
}

function restore_form_values(array $row, string $action): array
{
    if (($_SESSION['_old_action'] ?? '') !== $action) return $row;
    return array_replace($row, array_intersect_key(safe_form_input($_SESSION['_old'] ?? []), $row));
}

function duration_label(int $months): string
{
    return $months . ($months === 1 ? ' mes' : ' meses');
}

function service_type_label(string $type): string
{
    return ['RENOVABLE' => 'Renovable', 'DESECHABLE' => 'No renovable', 'USUARIO' => 'Usuario',
        'CORREO' => 'Correo', 'NO_APLICA' => 'No aplica'][$type] ?? $type;
}
