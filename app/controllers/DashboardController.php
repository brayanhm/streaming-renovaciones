<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Suscripcion;
use DateTimeImmutable;

class DashboardController extends Controller
{
    private Suscripcion $suscripciones;

    public function __construct()
    {
        $this->suscripciones = new Suscripcion();
    }

    public function index(): void
    {
        $this->suscripciones->recalculateStates(RECUP_DAYS);

        $search = trim((string) ($_GET['q'] ?? ''));
        $selectedStatus = strtoupper(trim((string) ($_GET['estado'] ?? 'TODOS')));
        if ($selectedStatus !== 'TODOS' && !in_array($selectedStatus, Suscripcion::ESTADOS, true)) {
            $selectedStatus = 'TODOS';
        }

        $allRows = $this->suscripciones->all($search);
        $activeRows = [];
        $noRenewRows = [];
        foreach ($allRows as $row) {
            $row['contact_type_sugerido'] = $this->suscripciones->inferContactType($row);

            if ((int) ($row['flag_no_renovo'] ?? 0) === 1) {
                $noRenewRows[] = $row;
                continue;
            }

            $activeRows[] = $row;
        }
        $this->sortRowsForMessaging($activeRows);
        $this->sortNoRenewRows($noRenewRows);

        $rows = $activeRows;
        if ($selectedStatus !== 'TODOS') {
            $rows = array_values(array_filter(
                $activeRows,
                static fn (array $item): bool => (string) ($item['estado'] ?? '') === $selectedStatus
            ));
        }

        $totals = [
            'costo' => 0.0,
            'venta' => 0.0,
            'ganancia' => 0.0,
        ];
        foreach ($rows as $item) {
            $cost = (float) ($item['costo_final'] ?? $item['modalidad_costo'] ?? 0);
            $sale = (float) ($item['precio_final'] ?? $item['modalidad_precio'] ?? 0);
            $totals['costo'] += $cost;
            $totals['venta'] += $sale;
            $totals['ganancia'] += ($sale - $cost);
        }

        $counts = ['TODOS' => count($activeRows)];
        foreach (Suscripcion::ESTADOS as $estado) {
            $counts[$estado] = 0;
        }
        foreach ($activeRows as $item) {
            $state = (string) ($item['estado'] ?? '');
            if (isset($counts[$state])) {
                $counts[$state]++;
            }
        }
        foreach ($noRenewRows as $item) {
            $state = (string) ($item['estado'] ?? '');
            if (isset($counts[$state])) {
                $counts[$state]++;
            }
        }

        $this->render('dashboard/index', [
            'pageTitle' => 'Ghost Panel',
            'rows' => $rows,
            'counts' => $counts,
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'totals' => $totals,
            'today' => new DateTimeImmutable('today'),
            'noRenewRows' => $noRenewRows,
            'noRenewCount' => count($noRenewRows),
        ]);
    }

    public function whatsapp(int $id): void
    {
        $subscription = $this->suscripciones->findWithRelations($id);
        if ($subscription === null) {
            flash('danger', 'Suscripcion no encontrada.');
            $this->redirect('/dashboard');
        }

        $paramType = strtoupper(trim((string) ($_GET['tipo'] ?? '')));
        $contactType = in_array($paramType, Suscripcion::CONTACTOS, true)
            ? $paramType
            : $this->inferContactTypeFromDueDate((string) ($subscription['fecha_vencimiento'] ?? ''));

        $link = $this->suscripciones->buildWhatsAppLink($id, $contactType);
        if ($link === null) {
            flash('danger', 'No se pudo generar el enlace de WhatsApp. Verifica que el telefono sea celular de Bolivia (8 digitos, inicia con 6 o 7).');
            $this->redirect('/dashboard');
        }

        $this->suscripciones->markWhatsappContact($id, $contactType);
        header('Location: ' . $link);
        exit;
    }

    public function renovar(int $id): void
    {
        $months = (int) ($_POST['meses'] ?? 0);
        if ($months <= 0) {
            flash('danger', 'El periodo de renovacion no es valido.');
            $this->redirect('/dashboard');
        }

        try {
            $newDue = $this->suscripciones->renovar($id, $months);
            if ($newDue === null) {
                flash('danger', 'No se pudo renovar la suscripcion. Verifica los meses permitidos para esa plataforma.');
                $this->redirect('/dashboard');
            }

            flash('success', 'Renovacion aplicada. Nueva fecha de vencimiento: ' . $newDue);
        } catch (\Throwable $exception) {
            flash('danger', 'Error al renovar: ' . $exception->getMessage());
        }

        $this->redirect('/dashboard');
    }

    public function noRenovo(int $id): void
    {
        $ok = $this->suscripciones->markNoRenovo($id);
        if ($ok) {
            flash('warning', 'Suscripcion marcada como no renovada.');
        } else {
            flash('danger', 'No se pudo actualizar el estado de renovacion.');
        }

        $this->redirect('/dashboard');
    }

    private function inferContactTypeFromDueDate(string $dueDate): string
    {
        $today = new DateTimeImmutable('today');
        $due = new DateTimeImmutable($dueDate);
        $days = (int) $today->diff($due)->format('%r%a');

        if ($days <= -15) {
            return 'REC_15';
        }
        if ($days <= -RECUP_DAYS) {
            return 'REC_7';
        }
        if ($days <= 0) {
            return 'MENOS_1';
        }
        if ($days <= 3) {
            return 'MENOS_2';
        }

        return 'MENOS_2';
    }

    private function sortRowsForMessaging(array &$rows): void
    {
        $statePriority = [
            'REENVIAR_1D' => 0,
            'CONTACTAR_2D' => 1,
            'VENCIDO' => 2,
            'RECUP' => 3,
            'ESPERA' => 4,
            'ACTIVO' => 5,
        ];

        usort($rows, static function (array $left, array $right) use ($statePriority): int {
            $leftState = (string) ($left['estado'] ?? 'ACTIVO');
            $rightState = (string) ($right['estado'] ?? 'ACTIVO');
            $leftPriority = $statePriority[$leftState] ?? 99;
            $rightPriority = $statePriority[$rightState] ?? 99;

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            $leftDays = (int) ($left['dias_para_vencer'] ?? 0);
            $rightDays = (int) ($right['dias_para_vencer'] ?? 0);
            $leftAbs = abs($leftDays);
            $rightAbs = abs($rightDays);
            if ($leftAbs !== $rightAbs) {
                return $leftAbs <=> $rightAbs;
            }

            $leftPast = $leftDays < 0;
            $rightPast = $rightDays < 0;
            if ($leftPast !== $rightPast) {
                return $leftPast ? -1 : 1;
            }

            $leftDue = (string) ($left['fecha_vencimiento'] ?? '');
            $rightDue = (string) ($right['fecha_vencimiento'] ?? '');
            if ($leftDue !== $rightDue) {
                return strcmp($leftDue, $rightDue);
            }

            return (int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0);
        });
    }

    private function sortNoRenewRows(array &$rows): void
    {
        usort($rows, static function (array $left, array $right): int {
            $leftDue = (string) ($left['fecha_vencimiento'] ?? '');
            $rightDue = (string) ($right['fecha_vencimiento'] ?? '');
            if ($leftDue !== $rightDue) {
                return strcmp($rightDue, $leftDue);
            }

            return (int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0);
        });
    }
}
