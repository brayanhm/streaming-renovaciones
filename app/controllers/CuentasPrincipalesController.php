<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\CuentaPrincipal;
use App\Models\Plataforma;
use App\Models\Suscripcion;
use DateTimeImmutable;

class CuentasPrincipalesController extends Controller
{
    private CuentaPrincipal $cuentas;
    private Plataforma $plataformas;
    private Cliente $clientes;
    private Suscripcion $suscripciones;

    public function __construct()
    {
        $this->cuentas = new CuentaPrincipal();
        $this->plataformas = new Plataforma();
        $this->clientes = new Cliente();
        $this->suscripciones = new Suscripcion();
    }

    public function index(): void
    {
        $this->render('cuentas_principales/index', [
            'pageTitle' => 'Cuentas principales',
            'rows' => $this->cuentas->all(),
            'plataformas' => $this->plataformasConCuentas(),
        ]);
    }

    public function store(): void
    {
        $plataformaId = (int) ($_POST['plataforma_id'] ?? 0);
        $etiqueta = trim((string) ($_POST['etiqueta'] ?? ''));
        $capacidad = (int) ($_POST['capacidad'] ?? 0);

        if ($etiqueta === '' || $capacidad < 1 || !$this->esPlataformaConCuentas($plataformaId)) {
            flash('danger', 'Completa la plataforma (que use cuentas principales), la etiqueta y una capacidad válida (≥ 1).');
            $this->redirect('/cuentas-principales');
        }

        [$inicio, $venc] = $this->resolveFechasPago();
        $this->cuentas->create([
            'plataforma_id' => $plataformaId,
            'etiqueta' => $etiqueta,
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'password_cuenta' => (string) ($_POST['password_cuenta'] ?? ''),
            'capacidad' => $capacidad,
            'fecha_inicio' => $inicio,
            'fecha_vencimiento' => $venc,
            'activo' => 1,
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ]);
        audit('cuenta_principal.crear', $etiqueta);
        flash('success', 'Cuenta principal creada.');
        $this->redirect('/cuentas-principales');
    }

    public function show(int $id): void
    {
        $cuenta = $this->cuentas->find($id);
        if ($cuenta === null) {
            flash('danger', 'Cuenta principal no encontrada.');
            $this->redirect('/cuentas-principales');
        }

        $this->render('cuentas_principales/show', [
            'pageTitle' => 'Cuenta: ' . ($cuenta['etiqueta'] ?? ''),
            'cuenta' => $cuenta,
            'asignados' => $this->cuentas->asignados($id),
            'departamentos' => CuentaPrincipal::DEPARTAMENTOS,
            'password' => decrypt_secret((string) ($cuenta['password_cuenta'] ?? '')),
        ]);
    }

    public function edit(int $id): void
    {
        $cuenta = $this->cuentas->find($id);
        if ($cuenta === null) {
            flash('danger', 'Cuenta principal no encontrada.');
            $this->redirect('/cuentas-principales');
        }

        $this->render('cuentas_principales/edit', [
            'pageTitle' => 'Editar cuenta principal',
            'cuenta' => $cuenta,
            'password' => decrypt_secret((string) ($cuenta['password_cuenta'] ?? '')),
        ]);
    }

    public function update(int $id): void
    {
        $cuenta = $this->cuentas->find($id);
        if ($cuenta === null) {
            flash('danger', 'Cuenta principal no encontrada.');
            $this->redirect('/cuentas-principales');
        }

        $etiqueta = trim((string) ($_POST['etiqueta'] ?? ''));
        $capacidad = (int) ($_POST['capacidad'] ?? 0);
        $ocupados = $this->cuentas->countAsignados($id);
        if ($etiqueta === '' || $capacidad < 1) {
            flash('danger', 'La etiqueta es obligatoria y la capacidad debe ser ≥ 1.');
            $this->redirect('/cuentas-principales/editar/' . $id);
        }
        if ($capacidad < $ocupados) {
            flash('danger', "No puedes fijar una capacidad ($capacidad) menor a los usuarios ya asignados ($ocupados).");
            $this->redirect('/cuentas-principales/editar/' . $id);
        }

        [$inicio, $venc] = $this->resolveFechasPago();
        $this->cuentas->update($id, [
            'etiqueta' => $etiqueta,
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'password_cuenta' => (string) ($_POST['password_cuenta'] ?? ''),
            'capacidad' => $capacidad,
            'fecha_inicio' => $inicio,
            'fecha_vencimiento' => $venc,
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ]);
        audit('cuenta_principal.editar', 'ID ' . $id);
        flash('success', 'Cuenta principal actualizada.');
        $this->redirect('/cuentas-principales/' . $id);
    }

    public function destroy(int $id): void
    {
        $cuenta = $this->cuentas->find($id);
        if ($cuenta === null) {
            flash('danger', 'Cuenta principal no encontrada.');
            $this->redirect('/cuentas-principales');
        }
        if ($this->cuentas->countAsignados($id) > 0) {
            flash('danger', 'No puedes eliminar una cuenta con usuarios asignados. Quítalos o márcalos como no renovados primero.');
            $this->redirect('/cuentas-principales/' . $id);
        }

        try {
            $this->cuentas->delete($id);
            audit('cuenta_principal.eliminar', 'ID ' . $id . ' (' . (string) $cuenta['etiqueta'] . ')');
            flash('success', 'Cuenta principal eliminada.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar: ' . $exception->getMessage());
        }
        $this->redirect('/cuentas-principales');
    }

    /**
     * Asigna un usuario nuevo a la cuenta principal: crea cliente + suscripción (1 mes).
     */
    public function asignar(int $id): void
    {
        $cuenta = $this->cuentas->find($id);
        if ($cuenta === null) {
            flash('danger', 'Cuenta principal no encontrada.');
            $this->redirect('/cuentas-principales');
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $numero = local_whatsapp_phone_bolivia(trim((string) ($_POST['numero'] ?? '')));
        $departamento = trim((string) ($_POST['departamento'] ?? ''));
        $fechaInicioRaw = trim((string) ($_POST['fecha_inicio'] ?? ''));

        if ($nombre === '') {
            flash('danger', 'Indica el nombre del usuario asignado.');
            $this->redirect('/cuentas-principales/' . $id);
        }
        if ($numero === '' || !is_valid_local_whatsapp_phone_bolivia($numero)) {
            flash('danger', 'Número inválido. Escribe solo el celular de 8 dígitos (inicia con 6 o 7); el +591 se agrega automáticamente.');
            $this->redirect('/cuentas-principales/' . $id);
        }
        if (!in_array($departamento, CuentaPrincipal::DEPARTAMENTOS, true)) {
            flash('danger', 'Selecciona un departamento válido.');
            $this->redirect('/cuentas-principales/' . $id);
        }
        $fechaInicio = parse_ymd_date($fechaInicioRaw) ?? new DateTimeImmutable('today');

        // Control de cupos.
        if ($this->cuentas->countAsignados($id) >= (int) $cuenta['capacidad']) {
            flash('danger', 'Cupo lleno: esta cuenta ya tiene ' . (int) $cuenta['capacidad'] . ' usuario(s) asignado(s).');
            $this->redirect('/cuentas-principales/' . $id);
        }

        $modalidadId = $this->resolveModalidadMensual((int) $cuenta['plataforma_id']);
        $fechaVenc = shift_months_clamped($fechaInicio, 1)->format('Y-m-d');

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $clienteId = $this->clientes->create([
                'nombre' => $nombre,
                'telefono' => $numero,
                'notas' => null,
            ]);
            $this->suscripciones->create([
                'cliente_id' => $clienteId,
                'plataforma_id' => (int) $cuenta['plataforma_id'],
                'modalidad_id' => $modalidadId,
                'cuenta_principal_id' => $id,
                'precio_venta' => null,
                'costo_base' => null,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_vencimiento' => $fechaVenc,
                'estado' => 'ACTIVO',
                'usuario_proveedor' => '',
                'departamento' => $departamento,
                'notas' => '',
                'flag_no_renovo' => 0,
            ]);
            $pdo->commit();
            audit('cuenta_principal.asignar', 'CP ' . $id . ' -> ' . $nombre);
            flash('success', 'Usuario "' . $nombre . '" asignado (vence ' . $fechaVenc . ').');
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'No se pudo asignar: ' . $exception->getMessage());
        }

        $this->redirect('/cuentas-principales/' . $id);
    }

    // ── helpers ──

    /**
     * Fechas de la cuenta principal (activación y pago). Si no se indica el
     * vencimiento, se calcula un mes desde la activación.
     *
     * @return array{0:string,1:string} [fecha_inicio, fecha_vencimiento] en Y-m-d o ''
     */
    private function resolveFechasPago(): array
    {
        $inicio = parse_ymd_date(trim((string) ($_POST['fecha_inicio'] ?? '')));
        $venc = parse_ymd_date(trim((string) ($_POST['fecha_vencimiento'] ?? '')));
        if ($inicio !== null && $venc === null) {
            $venc = shift_months_clamped($inicio, 1);
        }

        return [
            $inicio !== null ? $inicio->format('Y-m-d') : '',
            $venc !== null ? $venc->format('Y-m-d') : '',
        ];
    }

    private function plataformasConCuentas(): array
    {
        return array_values(array_filter(
            $this->plataformas->all(),
            static fn (array $p): bool => (int) ($p['usa_cuentas_principales'] ?? 0) === 1
        ));
    }

    private function esPlataformaConCuentas(int $plataformaId): bool
    {
        foreach ($this->plataformasConCuentas() as $p) {
            if ((int) $p['id'] === $plataformaId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Modalidad de 1 mes de la plataforma (la crea sin precio si no existe).
     */
    private function resolveModalidadMensual(int $plataformaId): int
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT id FROM modalidades WHERE plataforma_id = :pid ORDER BY duracion_meses = 1 DESC, id ASC LIMIT 1'
        );
        $stmt->execute(['pid' => $plataformaId]);
        $existing = $stmt->fetch();
        if ($existing) {
            return (int) $existing['id'];
        }

        $ins = $pdo->prepare(
            "INSERT INTO modalidades (plataforma_id, nombre_modalidad, tipo_cuenta, duracion_meses, dispositivos, precio, costo)
             VALUES (:pid, 'Asignación', 'CUENTA_COMPLETA', 1, NULL, 0, 0)"
        );
        $ins->execute(['pid' => $plataformaId]);

        return (int) $pdo->lastInsertId();
    }
}
