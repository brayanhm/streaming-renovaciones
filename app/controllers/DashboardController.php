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
        foreach ($allRows as &$row) {
            $row['contact_type_sugerido'] = $this->suscripciones->inferContactType($row);
        }
        unset($row);

        $rows = $allRows;
        if ($selectedStatus !== 'TODOS') {
            $rows = array_values(array_filter(
                $allRows,
                static fn (array $item): bool => (string) ($item['estado'] ?? '') === $selectedStatus
            ));
        }

        $counts = ['TODOS' => count($allRows)];
        foreach (Suscripcion::ESTADOS as $estado) {
            $counts[$estado] = 0;
        }
        foreach ($allRows as $item) {
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
            'today' => new DateTimeImmutable('today'),
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
            flash('danger', 'No se pudo generar el enlace de WhatsApp. Verifica el telefono del cliente.');
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
        if ($days <= 0) {
            return 'REC_7';
        }

        return $days === 1 ? 'MENOS_1' : 'MENOS_2';
    }
}
