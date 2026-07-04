<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Suscripcion;
use DateTimeImmutable;

class ImportacionesController extends Controller
{
    private Suscripcion $suscripciones;
    private Cliente $clientes;

    public function __construct()
    {
        $this->suscripciones = new Suscripcion();
        $this->clientes = new Cliente();
    }

    public function hub(): void
    {
        $this->render('importaciones/index', [
            'pageTitle' => 'Importaciones',
        ]);
    }

    public function flujotvForm(): void
    {
        $this->render('importaciones/flujotv_cc', [
            'pageTitle' => 'Importar FlujoTV (Cuenta Completa)',
        ]);
    }

    // ───────────────────────── FlujoTV Por dispositivos (solo crear) ─────────────────────────

    public function disposForm(): void
    {
        $this->render('importaciones/flujotv_disp', [
            'pageTitle' => 'Importar FlujoTV (Por dispositivos)',
        ]);
    }

    public function disposPreview(): void
    {
        $raw = $this->readUploadedCsv('/importar/flujotv-dispositivos');

        $target = $this->resolveDisposTarget();
        if ($target === null) {
            flash('danger', 'No se encontró la plataforma "FlujoTV por dispositivos" con planes de 1 dispositivo. Revisa el catálogo.');
            $this->redirect('/importar/flujotv-dispositivos');
        }

        $parsed = self::parseDisposCsv($raw);
        if ($parsed['valid'] === []) {
            flash('danger', 'El archivo no contiene filas válidas (usuario + fecha de finalización).');
            $this->redirect('/importar/flujotv-dispositivos');
        }

        $existing = $this->existingDisposKeys($target['plataformaId']);
        $plan = self::categorizeDispos($parsed['valid'], $existing, $target['modalidades']);

        $token = bin2hex(random_bytes(16));
        $_SESSION['import_flujo_disp'] = ['token' => $token, 'csv' => $raw];

        $this->render('importaciones/flujotv_disp_preview', [
            'pageTitle' => 'Vista previa de importación',
            'plan' => $plan,
            'skipped' => $parsed['skipped'],
            'token' => $token,
            'target' => $target,
        ]);
    }

    public function disposApply(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $stored = $_SESSION['import_flujo_disp'] ?? null;

        if (!is_array($stored) || $token === '' || !hash_equals((string) ($stored['token'] ?? ''), $token)) {
            flash('danger', 'La sesión de importación expiró o no es válida. Vuelve a subir el archivo.');
            $this->redirect('/importar/flujotv-dispositivos');
        }

        $raw = (string) ($stored['csv'] ?? '');
        unset($_SESSION['import_flujo_disp']);

        $target = $this->resolveDisposTarget();
        if ($target === null) {
            flash('danger', 'No se encontró la plataforma destino. Importación cancelada.');
            $this->redirect('/importar/flujotv-dispositivos');
        }

        $parsed = self::parseDisposCsv($raw);
        $existing = $this->existingDisposKeys($target['plataformaId']);
        $plan = self::categorizeDispos($parsed['valid'], $existing, $target['modalidades']);

        $pdo = \db();
        $created = 0;
        $backup = backup_database('import_flujotv_disp');

        try {
            $pdo->beginTransaction();

            foreach ($plan['create'] as $c) {
                $clienteId = $this->clientes->create([
                    'nombre' => $c['user'],
                    'telefono' => '',
                    'notas' => null,
                ]);
                $this->suscripciones->create([
                    'cliente_id' => $clienteId,
                    'plataforma_id' => $target['plataformaId'],
                    'modalidad_id' => (int) $c['mod']['id'],
                    'precio_venta' => $c['mod']['precio'], // precio explícito del plan
                    'costo_base' => $c['mod']['costo'],
                    'fecha_inicio' => (string) $c['inicio'],
                    'fecha_vencimiento' => (string) $c['fin'],
                    'estado' => 'ACTIVO',
                    'usuario_proveedor' => $c['user'],
                    'password_cuenta' => (string) ($c['password'] ?? ''),
                    'notas' => 'Importado desde CSV FlujoTV (Por dispositivos)',
                    'flag_no_renovo' => 0,
                ]);
                $created++;
            }

            $this->suscripciones->recalculateStates(RECUP_DAYS);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Error al importar (no se aplicó ningún cambio): ' . $exception->getMessage());
            $this->redirect('/importar/flujotv-dispositivos');
        }

        audit('importar.flujotv_disp', "{$created} creadas" . ($backup ? ' | respaldo previo ok' : ''));
        flash('success', "Importación aplicada: {$created} cuenta(s) por dispositivos creada(s).");
        $this->redirect('/dashboard');
    }

    public function flujotvPreview(): void
    {
        $raw = $this->readUploadedCsv('/importar/flujotv');

        $target = $this->resolveTarget();
        if ($target === null) {
            flash('danger', 'No se encontró la plataforma "FlujoTV cuenta completa" con un plan de 1 mes. Revisa el catálogo.');
            $this->redirect('/importar/flujotv');
        }

        $parsed = self::parseCsv($raw);
        if ($parsed['valid'] === []) {
            flash('danger', 'El archivo no contiene filas válidas (usuario + fecha de finalización).');
            $this->redirect('/importar/flujotv');
        }

        $existing = $this->existingByUser($target['plataformaId']);
        $plan = self::categorize($parsed['valid'], $existing);

        $token = bin2hex(random_bytes(16));
        $_SESSION['import_flujo_cc'] = ['token' => $token, 'csv' => $raw];

        $this->render('importaciones/flujotv_cc_preview', [
            'pageTitle' => 'Vista previa de importación',
            'plan' => $plan,
            'skipped' => $parsed['skipped'],
            'duplicates' => $parsed['duplicates'],
            'token' => $token,
            'target' => $target,
        ]);
    }

    public function flujotvApply(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $stored = $_SESSION['import_flujo_cc'] ?? null;

        if (!is_array($stored) || $token === '' || !hash_equals((string) ($stored['token'] ?? ''), $token)) {
            flash('danger', 'La sesión de importación expiró o no es válida. Vuelve a subir el archivo.');
            $this->redirect('/importar/flujotv');
        }

        $raw = (string) ($stored['csv'] ?? '');
        unset($_SESSION['import_flujo_cc']); // un solo uso: evita reaplicar por error

        $target = $this->resolveTarget();
        if ($target === null) {
            flash('danger', 'No se encontró la plataforma destino. Importación cancelada.');
            $this->redirect('/importar/flujotv');
        }

        $parsed = self::parseCsv($raw);
        $existing = $this->existingByUser($target['plataformaId']);
        $plan = self::categorize($parsed['valid'], $existing);

        $pdo = \db();
        // El inicio se deriva del vencimiento (siempre presente) restando la duración
        // del plan, para garantizar fecha_inicio <= fecha_vencimiento incluso con
        // cuentas cuya finalización ya pasó.
        $planDuracion = max(1, (int) ($target['modalidad']['duracion_meses'] ?? 1));
        $updated = 0;
        $created = 0;
        $backup = backup_database('import_flujotv_cc');

        try {
            $pdo->beginTransaction();

            foreach ($plan['update'] as $u) {
                if ($this->suscripciones->updateDueDate((int) $u['id'], (string) $u['fin'])) {
                    $updated++;
                }
            }

            foreach ($plan['create'] as $c) {
                $inicio = \shift_months_clamped(
                    new DateTimeImmutable((string) $c['fin']),
                    -$planDuracion
                )->format('Y-m-d');

                $clienteId = $this->clientes->create([
                    'nombre' => $c['user'],
                    'telefono' => '', // vacío: aparece en "Completar contactos"
                    'notas' => null,
                ]);
                $this->suscripciones->create([
                    'cliente_id' => $clienteId,
                    'plataforma_id' => $target['plataformaId'],
                    'modalidad_id' => (int) $target['modalidad']['id'],
                    'precio_venta' => null, // hereda del plan
                    'costo_base' => null,
                    'fecha_inicio' => $inicio,
                    'fecha_vencimiento' => (string) $c['fin'],
                    'estado' => 'ACTIVO',
                    'usuario_proveedor' => $c['user'],
                    'notas' => 'Importado desde CSV FlujoTV (Cuenta Completa)',
                    'flag_no_renovo' => 0,
                ]);
                $created++;
            }

            // Normaliza estados (CONTACTAR_2D/REENVIAR_1D/ACTIVO/VENCIDO/RECUP) segun las fechas.
            $this->suscripciones->recalculateStates(RECUP_DAYS);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Error al importar (no se aplicó ningún cambio): ' . $exception->getMessage());
            $this->redirect('/importar/flujotv');
        }

        audit('importar.flujotv_cc', "{$updated} actualizadas, {$created} creadas" . ($backup ? ' | respaldo previo ok' : ''));
        flash('success', "Importación aplicada: {$updated} actualizada(s) y {$created} creada(s).");
        $this->redirect('/dashboard');
    }

    private function readUploadedCsv(string $redirectTo): string
    {
        $file = $_FILES['archivo'] ?? null;
        $error = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

        if (!is_array($file) || $error === UPLOAD_ERR_NO_FILE) {
            flash('danger', 'Selecciona un archivo CSV para importar.');
            $this->redirect($redirectTo);
        }
        if ($error !== UPLOAD_ERR_OK) {
            flash('danger', 'No se pudo subir el archivo (código de error ' . $error . '). Intenta de nuevo.');
            $this->redirect($redirectTo);
        }

        $name = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            flash('danger', 'El archivo debe ser .csv. Si tienes un Excel, usa "Guardar como CSV".');
            $this->redirect($redirectTo);
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            flash('danger', 'El archivo está vacío o supera el límite de 2 MB.');
            $this->redirect($redirectTo);
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            flash('danger', 'La subida no es válida. Intenta de nuevo.');
            $this->redirect($redirectTo);
        }

        return (string) file_get_contents($tmp);
    }

    /**
     * Resuelve la plataforma FlujoTV (Cuenta Completa) y su plan de 1 mes sin
     * hardcodear ids: busca por nombre y por el plan cuenta completa de 1 mes.
     *
     * @return array{plataformaId:int,plataformaNombre:string,modalidad:array}|null
     */
    private function resolveTarget(): ?array
    {
        $pdo = \db();

        $plat = $pdo->query(
            "SELECT id, nombre
             FROM plataformas
             WHERE LOWER(nombre) LIKE '%flujo%'
               AND LOWER(nombre) NOT LIKE '%dispositivo%'
             ORDER BY id ASC
             LIMIT 1"
        )->fetch();
        if (!$plat) {
            return null;
        }

        $stmt = $pdo->prepare(
            "SELECT id, duracion_meses, precio, costo
             FROM modalidades
             WHERE plataforma_id = :pid
               AND tipo_cuenta = 'CUENTA_COMPLETA'
               AND duracion_meses = 1
             ORDER BY (dispositivos IS NULL) DESC, id ASC
             LIMIT 1"
        );
        $stmt->execute(['pid' => (int) $plat['id']]);
        $mod = $stmt->fetch();
        if (!$mod) {
            return null;
        }

        return [
            'plataformaId' => (int) $plat['id'],
            'plataformaNombre' => (string) $plat['nombre'],
            'modalidad' => $mod,
        ];
    }

    /**
     * Suscripciones cuenta completa existentes de la plataforma, indexadas por
     * usuario (minúsculas). Conserva la de vencimiento más reciente por usuario.
     */
    private function existingByUser(int $plataformaId): array
    {
        $stmt = \db()->prepare(
            "SELECT s.id, s.usuario_proveedor, s.fecha_vencimiento
             FROM suscripciones s
             INNER JOIN modalidades m ON m.id = s.modalidad_id
             WHERE s.plataforma_id = :pid AND m.tipo_cuenta = 'CUENTA_COMPLETA'"
        );
        $stmt->execute(['pid' => $plataformaId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $lc = mb_strtolower(trim((string) $row['usuario_proveedor']));
            if ($lc === '') {
                continue;
            }
            $fin = (string) $row['fecha_vencimiento'];
            if (!isset($map[$lc]) || strcmp($fin, (string) $map[$lc]['fecha_vencimiento']) > 0) {
                $map[$lc] = [
                    'id' => (int) $row['id'],
                    'usuario' => (string) $row['usuario_proveedor'],
                    'fecha_vencimiento' => $fin,
                ];
            }
        }

        return $map;
    }

    /**
     * Parsea el CSV del proveedor (delimitado por ; o ,, con BOM y filas de
     * cabecera). Devuelve filas válidas indexadas por usuario en minúsculas.
     *
     * @return array{valid:array<string,array{user:string,fin:string}>,skipped:array<int,array{user:string,reason:string}>,duplicates:int}
     */
    public static function parseCsv(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $valid = [];
        $skipped = [];
        $duplicates = 0;

        foreach ($lines as $idx => $line) {
            if ($idx === 0) {
                $line = ltrim($line, "\xEF\xBB\xBF"); // quita BOM
            }
            if (trim($line) === '') {
                continue;
            }

            $delimiter = substr_count($line, ';') >= substr_count($line, ',') ? ';' : ',';
            $cols = str_getcsv($line, $delimiter, '"', '');
            $user = trim((string) ($cols[0] ?? ''));
            $finRaw = trim((string) ($cols[1] ?? ''));

            if ($user === '') {
                continue; // p. ej. la fila "Datos del usuario final;"
            }

            $fin = self::parseDate($finRaw);
            if ($fin === null) {
                $lower = mb_strtolower($user);
                // Cabeceras conocidas: se ignoran en silencio.
                if ($lower === 'nombre de usuario' || str_starts_with($lower, 'datos del usuario')) {
                    continue;
                }
                $skipped[] = ['user' => $user, 'reason' => 'sin fecha de finalización válida'];
                continue;
            }

            $lc = mb_strtolower($user);
            if (isset($valid[$lc])) {
                $duplicates++;
            }
            // Ante duplicados dentro del CSV, conserva la fecha más lejana.
            if (!isset($valid[$lc]) || strcmp($fin, $valid[$lc]['fin']) > 0) {
                $valid[$lc] = ['user' => $user, 'fin' => $fin];
            }
        }

        return ['valid' => $valid, 'skipped' => $skipped, 'duplicates' => $duplicates];
    }

    private static function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? "{$m[1]}-{$m[2]}-{$m[3]}" : null;
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $value, $m)) {
            $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            return checkdate((int) $mo, (int) $d, (int) $m[3]) ? "{$m[3]}-{$mo}-{$d}" : null;
        }

        return null;
    }

    /**
     * Clasifica cada fila del CSV contra lo existente en el sistema.
     */
    public static function categorize(array $valid, array $existing): array
    {
        $update = [];
        $create = [];
        $ignore = [];
        $csvKeys = [];

        foreach ($valid as $lc => $row) {
            $csvKeys[$lc] = true;
            if (isset($existing[$lc])) {
                $cur = $existing[$lc];
                if ((string) $cur['fecha_vencimiento'] === $row['fin']) {
                    $ignore[] = ['user' => $cur['usuario'], 'fin' => $row['fin']];
                } else {
                    $update[] = [
                        'id' => (int) $cur['id'],
                        'user' => (string) $cur['usuario'],
                        'old' => (string) $cur['fecha_vencimiento'],
                        'fin' => $row['fin'],
                        'delta' => (int) ((strtotime($row['fin']) - strtotime((string) $cur['fecha_vencimiento'])) / 86400),
                    ];
                }
            } else {
                $create[] = ['user' => $row['user'], 'fin' => $row['fin']];
            }
        }

        $notInCsv = [];
        foreach ($existing as $lc => $cur) {
            if (!isset($csvKeys[$lc])) {
                $notInCsv[] = $cur;
            }
        }

        return ['update' => $update, 'create' => $create, 'ignore' => $ignore, 'notInCsv' => $notInCsv];
    }

    // ───────────────────────── Helpers Por dispositivos ─────────────────────────

    /**
     * Resuelve la plataforma FlujoTV por dispositivos y sus planes de 1 dispositivo.
     * (Decisión del negocio: estas cuentas se asumen siempre de 1 dispositivo.)
     *
     * @return array{plataformaId:int,plataformaNombre:string,modalidades:array}|null
     */
    private function resolveDisposTarget(): ?array
    {
        $pdo = \db();

        $plat = $pdo->query(
            "SELECT id, nombre
             FROM plataformas
             WHERE LOWER(nombre) LIKE '%flujo%'
               AND LOWER(nombre) LIKE '%dispositivo%'
             ORDER BY id ASC
             LIMIT 1"
        )->fetch();
        if (!$plat) {
            return null;
        }

        $stmt = $pdo->prepare(
            "SELECT id, dispositivos, duracion_meses, precio, costo
             FROM modalidades
             WHERE plataforma_id = :pid
               AND tipo_cuenta = 'POR_DISPOSITIVOS'
               AND dispositivos = 1
             ORDER BY duracion_meses ASC"
        );
        $stmt->execute(['pid' => (int) $plat['id']]);
        $mods = $stmt->fetchAll();
        if ($mods === []) {
            return null;
        }

        return [
            'plataformaId' => (int) $plat['id'],
            'plataformaNombre' => (string) $plat['nombre'],
            'modalidades' => $mods,
        ];
    }

    /**
     * Claves de suscripciones existentes de la plataforma: usuario|inicio|fin (minúsculas),
     * para omitir duplicados exactos (mismo usuario Y mismas fechas).
     */
    private function existingDisposKeys(int $plataformaId): array
    {
        $stmt = \db()->prepare(
            'SELECT usuario_proveedor, fecha_inicio, fecha_vencimiento
             FROM suscripciones WHERE plataforma_id = :pid'
        );
        $stmt->execute(['pid' => $plataformaId]);

        $keys = [];
        foreach ($stmt->fetchAll() as $row) {
            $user = mb_strtolower(trim((string) $row['usuario_proveedor']));
            if ($user === '') {
                continue;
            }
            $keys[$user . '|' . (string) $row['fecha_inicio'] . '|' . (string) $row['fecha_vencimiento']] = true;
        }

        return $keys;
    }

    /**
     * Parsea el CSV del proveedor de cuentas por dispositivos.
     * Columnas: usuario;contraseña;crédito;fecha inicio;fecha finalización;smartTv;estado
     *
     * @return array{valid:array<int,array{user:string,credito:int,inicio:?string,fin:string}>,skipped:array<int,array{user:string,reason:string}>}
     */
    public static function parseDisposCsv(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $valid = [];
        $skipped = [];

        foreach ($lines as $idx => $line) {
            if ($idx === 0) {
                $line = ltrim($line, "\xEF\xBB\xBF");
            }
            if (trim($line) === '') {
                continue;
            }

            $delimiter = substr_count($line, ';') >= substr_count($line, ',') ? ';' : ',';
            $cols = str_getcsv($line, $delimiter, '"', '');
            $user = trim((string) ($cols[0] ?? ''));
            if ($user === '') {
                continue;
            }

            $fin = self::parseDate((string) ($cols[4] ?? ''));
            if ($fin === null) {
                $lower = mb_strtolower($user);
                if ($lower === 'nombre de usuario' || str_starts_with($lower, 'datos del usuario')) {
                    continue;
                }
                $skipped[] = ['user' => $user, 'reason' => 'sin fecha de finalización válida'];
                continue;
            }

            $inicio = self::parseDate((string) ($cols[3] ?? ''));
            $credito = (int) trim((string) ($cols[2] ?? ''));
            if ($credito <= 0) {
                $credito = 1;
            }
            $password = trim((string) ($cols[1] ?? ''));

            $valid[] = ['user' => $user, 'password' => $password, 'credito' => $credito, 'inicio' => $inicio, 'fin' => $fin];
        }

        return ['valid' => $valid, 'skipped' => $skipped];
    }

    /**
     * Elige el plan cuya duración se acerca más al crédito (empate → duración mayor).
     */
    private static function pickModalidadByCredito(array $mods, int $credito): array
    {
        usort($mods, static function (array $a, array $b) use ($credito): int {
            $da = abs((int) $a['duracion_meses'] - $credito);
            $db = abs((int) $b['duracion_meses'] - $credito);
            if ($da === $db) {
                return (int) $b['duracion_meses'] <=> (int) $a['duracion_meses'];
            }

            return $da <=> $db;
        });

        return $mods[0];
    }

    /**
     * Clasifica las filas: crea las nuevas, omite duplicados exactos (usuario+fechas)
     * ya existentes en el sistema o repetidos dentro del propio archivo.
     */
    public static function categorizeDispos(array $valid, array $existingKeys, array $mods): array
    {
        $create = [];
        $skipExisting = [];
        $dupFile = 0;
        $seen = [];

        foreach ($valid as $row) {
            $mod = self::pickModalidadByCredito($mods, (int) $row['credito']);

            $inicio = $row['inicio'];
            // Sin inicio en el CSV, o inicio posterior al vencimiento (dato inconsistente):
            // se deriva restando la duración del plan al vencimiento (siempre presente).
            if ($inicio === null || $inicio > (string) $row['fin']) {
                $inicio = \shift_months_clamped(
                    new DateTimeImmutable((string) $row['fin']),
                    -((int) $mod['duracion_meses'])
                )->format('Y-m-d');
            }

            $key = mb_strtolower(trim((string) $row['user'])) . '|' . $inicio . '|' . (string) $row['fin'];

            if (isset($existingKeys[$key])) {
                $skipExisting[] = ['user' => $row['user'], 'inicio' => $inicio, 'fin' => $row['fin']];
                continue;
            }
            if (isset($seen[$key])) {
                $dupFile++;
                continue;
            }
            $seen[$key] = true;

            $create[] = [
                'user' => $row['user'],
                'password' => (string) ($row['password'] ?? ''),
                'inicio' => $inicio,
                'fin' => $row['fin'],
                'credito' => (int) $row['credito'],
                'mod' => $mod,
            ];
        }

        return ['create' => $create, 'skipExisting' => $skipExisting, 'dupFile' => $dupFile];
    }
}
