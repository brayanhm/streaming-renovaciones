<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;

class ClientesController extends Controller
{
    private Cliente $clientes;

    public function __construct()
    {
        $this->clientes = new Cliente();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $this->clientes->all($search);

        $this->render('clientes/index', [
            'pageTitle' => 'Clientes',
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    public function store(): void
    {
        $payload = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ];

        if ($payload['nombre'] === '' || $payload['telefono'] === '') {
            set_old($payload);
            flash('danger', 'Nombre y telefono son obligatorios.');
            $this->redirect('/clientes');
        }

        $this->clientes->create($payload);
        clear_old();
        flash('success', 'Cliente creado correctamente.');
        $this->redirect('/clientes');
    }

    public function edit(int $id): void
    {
        $item = $this->clientes->find($id);
        if ($item === null) {
            flash('danger', 'Cliente no encontrado.');
            $this->redirect('/clientes');
        }

        $this->render('clientes/edit', [
            'pageTitle' => 'Editar cliente',
            'item' => $item,
        ]);
    }

    public function update(int $id): void
    {
        $payload = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ];

        if ($payload['nombre'] === '' || $payload['telefono'] === '') {
            flash('danger', 'Nombre y telefono son obligatorios.');
            $this->redirect('/clientes/editar/' . $id);
        }

        $this->clientes->update($id, $payload);
        flash('success', 'Cliente actualizado.');
        $this->redirect('/clientes');
    }

    public function destroy(int $id): void
    {
        try {
            $this->clientes->delete($id);
            flash('success', 'Cliente eliminado.');
        } catch (\Throwable $exception) {
            flash('danger', 'No se pudo eliminar el cliente: ' . $exception->getMessage());
        }

        $this->redirect('/clientes');
    }
}
