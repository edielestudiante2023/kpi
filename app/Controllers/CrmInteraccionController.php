<?php

namespace App\Controllers;

use App\Models\CrmInteraccionModel;
use App\Models\CrmOportunidadModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmInteraccionController extends BaseController
{
    protected $interaccionModel;
    protected $oportunidadModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'crm']);
        $this->interaccionModel = new CrmInteraccionModel();
        $this->oportunidadModel = new CrmOportunidadModel();
    }

    private function chequearAccesoJson()
    {
        if (!crm_tiene_acceso()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin acceso'])->setStatusCode(403);
        }
        return null;
    }

    /**
     * Crear una interacción (AJAX desde modal en vista de oportunidad o empresa).
     */
    public function agregarAjax()
    {
        if ($r = $this->chequearAccesoJson()) return $r;

        $req = $this->request;
        $idOportunidad = (int) $req->getPost('id_oportunidad') ?: null;
        $idEmpresa     = (int) $req->getPost('id_empresa') ?: null;

        // Si viene id_oportunidad y no id_empresa, deducir id_empresa de la oportunidad
        if ($idOportunidad && !$idEmpresa) {
            $op = $this->oportunidadModel->find($idOportunidad);
            if ($op) $idEmpresa = (int) $op['id_empresa'];
        }

        $datos = [
            'id_oportunidad'    => $idOportunidad,
            'id_empresa'        => $idEmpresa,
            'id_contacto'       => (int) $req->getPost('id_contacto') ?: null,
            'tipo'              => $req->getPost('tipo'),
            'asunto'            => trim((string) $req->getPost('asunto')),
            'detalle'           => trim((string) $req->getPost('detalle')) ?: null,
            'fecha_programada'  => $req->getPost('fecha_programada') ?: null,
            'fecha_completada'  => $req->getPost('fecha_completada') ?: null,
            'estado'            => $req->getPost('estado') ?: 'completada',
            'recordatorio_at'   => $req->getPost('recordatorio_at') ?: null,
            'id_usuario'        => (int) session()->get('id_users'),
        ];

        $tiposValidos = ['llamada','reunion','correo','nota','tarea','propuesta_enviada','whatsapp'];
        $errores = [];
        if (!$idOportunidad && !$idEmpresa) $errores[] = 'Falta id_oportunidad o id_empresa.';
        if ($datos['asunto'] === '')        $errores[] = 'Asunto requerido.';
        if (!in_array($datos['tipo'], $tiposValidos, true)) $errores[] = 'Tipo inválido.';
        if (!empty($errores)) {
            return $this->response->setJSON(['ok' => false, 'errors' => $errores])->setStatusCode(400);
        }

        // Si es completada y no llegó fecha, ponerla en ahora
        if ($datos['estado'] === 'completada' && empty($datos['fecha_completada'])) {
            $datos['fecha_completada'] = date('Y-m-d H:i:s');
        }

        $id = $this->interaccionModel->insert($datos, true);

        // Actualizar timestamp de ultima_actividad en la oportunidad
        if ($idOportunidad) {
            $this->oportunidadModel->tocarUltimaActividad($idOportunidad);
        }

        return $this->response->setJSON(['ok' => true, 'id' => $id]);
    }

    /**
     * Marcar una tarea pendiente como completada.
     */
    public function completarAjax($id)
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $id = (int) $id;
        $i = $this->interaccionModel->find($id);
        if (!$i) return $this->response->setJSON(['ok' => false, 'error' => 'No encontrada'])->setStatusCode(404);

        $this->interaccionModel->update($id, [
            'estado'           => 'completada',
            'fecha_completada' => date('Y-m-d H:i:s'),
        ]);

        if (!empty($i['id_oportunidad'])) {
            $this->oportunidadModel->tocarUltimaActividad((int) $i['id_oportunidad']);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function eliminarAjax($id)
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $id = (int) $id;
        if (!$this->interaccionModel->find($id)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'No encontrada'])->setStatusCode(404);
        }
        $this->interaccionModel->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Devuelve el timeline (HTML) de una oportunidad — para refrescar tras agregar/eliminar.
     */
    public function listarPorOportunidadAjax($idOportunidad)
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $items = $this->interaccionModel->getTimeline((int) $idOportunidad);
        return $this->response->setJSON(['ok' => true, 'items' => $items]);
    }
}
