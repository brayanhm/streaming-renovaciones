<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ChatgptCuenta;

class ChatgptController extends CuentasCompartidasController
{
    protected const MODEL = ChatgptCuenta::class;
    protected const SERVICE = 'chatgpt';
    protected const LABEL = 'ChatGPT';
    protected const UNIT = 'dispositivos';

    public function vencimientos(): void
    {
        $this->render('chatgpt/vencimientos', [
            'pageTitle' => 'Vencimientos de ChatGPT',
            'avisosChatgpt' => (new \App\Models\ChatgptAvisos())->resumen(),
            'canAdmin' => $this->canAdmin(),
            'alertsToken' => $this->issueToken('avisos', 0),
        ]);
    }

    public function saveAlerts(): void
    {
        $this->requireAdmin();
        $token = $this->validateToken('avisos', 0);
        $input = ['aviso_fin_pagado' => $_POST['aviso_fin_pagado'] ?? '', 'aviso_fin_gracia' => $_POST['aviso_fin_gracia'] ?? ''];
        try {
            (new \App\Models\ChatgptAvisos())->saveConfig($input);
            unset($_SESSION['chatgpt_operaciones'][$token]);
            clear_old();
            audit('chatgpt.configurar_avisos', 'Fin pagado: ' . $input['aviso_fin_pagado'] . ' días / fin gracia: ' . $input['aviso_fin_gracia'] . ' días');
            flash('success', 'Anticipación de los avisos actualizada.');
        } catch (\Throwable $error) {
            set_old($input);
            $this->operationError($error);
        }
        $this->redirect('/chatgpt/vencimientos#configurar-avisos');
    }

    public function importPending(): void
    {
        $this->requireAdmin();
        $token = $this->validateToken('importar', 0);
        $raw = trim((string) ($_POST['correos'] ?? ''));
        try {
            if ($raw === '' || strlen($raw) > 40000) {
                throw new \InvalidArgumentException('Introduce hasta 200 correos, uno por línea.');
            }
            $emails = array_values(array_filter(preg_split('/\R/u', $raw) ?: [], static fn (string $email): bool => trim($email) !== ''));
            $result = $this->cuentas->importPendingEmails($emails);
            unset($_SESSION['chatgpt_operaciones'][$token]);
            audit('chatgpt.importar_cuentas', $result['creadas'] . ' creadas / ' . $result['existentes'] . ' existentes');
            flash('success', $result['creadas'] . ' cuentas registradas y ' . $result['existentes'] . ' ya existentes. Revisa las fechas y las credenciales de las nuevas cuentas antes de vender.');
        } catch (\Throwable $error) {
            set_old(['correos' => $raw]);
            $this->operationError($error);
        }
        $this->redirect('/chatgpt');
    }

    public function configurePeriod(int $id): void
    {
        $this->requireAdmin();
        $this->account($id);
        $token = $this->validateToken('vigencia', $id);
        try {
            $origin = strtoupper(trim((string) ($_POST['origen_fecha'] ?? '')));
            $date = trim((string) ($_POST['fecha_referencia'] ?? ''));
            $this->validateDate($date);
            $this->cuentas->configureExistingPeriod($id, $origin, $date);
            unset($_SESSION['chatgpt_operaciones'][$token]);
            audit('chatgpt.revisar_vigencia', 'Cuenta ' . $id . ' / ' . $origin . ' / ' . $date);
            flash('success', 'Vigencia existente revisada. Se conservaron los dispositivos y el historial; esta revisión no registra un pago nuevo.');
        } catch (\Throwable $error) {
            $this->operationError($error);
        }
        $this->redirect('/chatgpt/' . $id);
    }
}
