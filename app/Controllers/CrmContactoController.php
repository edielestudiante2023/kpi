<?php

namespace App\Controllers;

use App\Models\CrmContactoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmContactoController extends BaseController
{
    protected $contactoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'crm']);
        $this->contactoModel = new CrmContactoModel();
    }

    private function chequearAccesoJson()
    {
        if (!crm_tiene_acceso()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin acceso'])->setStatusCode(403);
        }
        return null;
    }

    /**
     * Crear/editar contacto (AJAX desde la vista de empresa).
     */
    public function guardar()
    {
        if ($r = $this->chequearAccesoJson()) return $r;

        $idContacto = (int) $this->request->getPost('id_contacto');
        $datos = [
            'id_empresa' => (int) $this->request->getPost('id_empresa'),
            'nombre'     => trim((string) $this->request->getPost('nombre')),
            'cargo'      => trim((string) $this->request->getPost('cargo')) ?: null,
            'email'      => trim((string) $this->request->getPost('email')) ?: null,
            'telefono'   => trim((string) $this->request->getPost('telefono')) ?: null,
            'es_decisor' => (int) ($this->request->getPost('es_decisor') ?? 0),
            'notas'      => trim((string) $this->request->getPost('notas')) ?: null,
            'activo'     => (int) ($this->request->getPost('activo') ?? 1),
        ];

        $errores = [];
        if ($datos['id_empresa'] <= 0) $errores[] = 'Empresa requerida.';
        if ($datos['nombre'] === '')   $errores[] = 'El nombre es obligatorio.';
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Email inválido.';
        }
        if (!empty($errores)) {
            return $this->response->setJSON(['ok' => false, 'errors' => $errores])->setStatusCode(400);
        }

        if ($idContacto > 0) {
            $this->contactoModel->update($idContacto, $datos);
            return $this->response->setJSON(['ok' => true, 'id' => $idContacto]);
        }
        $newId = $this->contactoModel->insert($datos, true);
        return $this->response->setJSON(['ok' => true, 'id' => $newId]);
    }

    /**
     * Eliminar contacto (AJAX).
     */
    public function eliminar($id)
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $id = (int) $id;
        if (!$this->contactoModel->find($id)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'No encontrado'])->setStatusCode(404);
        }
        $this->contactoModel->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Búsqueda AJAX para selects de contacto (filtrada por empresa).
     */
    public function buscarAjax()
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $idEmpresa = (int) $this->request->getGet('id_empresa');
        if ($idEmpresa <= 0) return $this->response->setJSON(['items' => []]);
        $items = $this->contactoModel
            ->select('id_contacto, nombre, cargo, email')
            ->where('id_empresa', $idEmpresa)
            ->where('activo', 1)
            ->orderBy('es_decisor', 'DESC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
        return $this->response->setJSON(['items' => $items]);
    }
}
