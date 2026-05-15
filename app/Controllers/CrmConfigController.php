<?php

namespace App\Controllers;

use App\Models\CrmEtapaModel;
use App\Models\CrmFuenteModel;
use App\Models\CrmMotivoPerdidaModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmConfigController extends BaseController
{
    protected $etapaModel;
    protected $fuenteModel;
    protected $motivoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'crm']);
        $this->etapaModel  = new CrmEtapaModel();
        $this->fuenteModel = new CrmFuenteModel();
        $this->motivoModel = new CrmMotivoPerdidaModel();
    }

    private function chequearAdmin()
    {
        if (!crm_es_admin()) {
            return redirect()->to('/crm/oportunidades/kanban')
                ->with('errors', ['Solo administradores CRM pueden configurar.']);
        }
        return null;
    }

    private function chequearAdminJson()
    {
        if (!crm_es_admin()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin permiso'])->setStatusCode(403);
        }
        return null;
    }

    // ═══════════════════════════════ ETAPAS ═══════════════════════════════
    public function etapas()
    {
        if ($r = $this->chequearAdmin()) return $r;
        return view('crm/config/etapas', [
            'etapas' => $this->etapaModel->getOrdenadas(false),
        ]);
    }

    public function guardarEtapa()
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $req = $this->request;
        $id = (int) $req->getPost('id_etapa');
        $datos = [
            'nombre'               => trim((string) $req->getPost('nombre')),
            'orden'                => (int) $req->getPost('orden'),
            'probabilidad_default' => max(0, min(100, (int) $req->getPost('probabilidad_default'))),
            'color'                => $req->getPost('color') ?: '#6c757d',
            'tipo'                 => in_array($req->getPost('tipo'), ['abierta','ganada','perdida'], true) ? $req->getPost('tipo') : 'abierta',
            'activa'               => (int) ($req->getPost('activa') ?? 1),
        ];
        if ($datos['nombre'] === '') return $this->response->setJSON(['ok' => false, 'error' => 'Nombre requerido'])->setStatusCode(400);

        if ($id > 0) {
            $this->etapaModel->update($id, $datos);
            return $this->response->setJSON(['ok' => true, 'id' => $id]);
        }
        $newId = $this->etapaModel->insert($datos, true);
        return $this->response->setJSON(['ok' => true, 'id' => $newId]);
    }

    public function eliminarEtapa($id)
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $id = (int) $id;
        // No borrar si tiene oportunidades asociadas
        $db = \Config\Database::connect();
        $count = (int) $db->table('tbl_crm_oportunidad')->where('id_etapa', $id)->countAllResults();
        if ($count > 0) {
            return $this->response->setJSON(['ok' => false, 'error' => "Hay {$count} oportunidad(es) en esta etapa. Mueve antes de eliminar."])->setStatusCode(400);
        }
        $this->etapaModel->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    public function reordenarEtapas()
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $ids = $this->request->getPost('ids'); // array de ids en el orden deseado
        if (!is_array($ids)) return $this->response->setJSON(['ok' => false, 'error' => 'Datos inválidos'])->setStatusCode(400);
        foreach ($ids as $i => $idEtapa) {
            $this->etapaModel->update((int) $idEtapa, ['orden' => ($i + 1) * 10]);
        }
        return $this->response->setJSON(['ok' => true]);
    }

    // ═══════════════════════════════ FUENTES ══════════════════════════════
    public function fuentes()
    {
        if ($r = $this->chequearAdmin()) return $r;
        return view('crm/config/fuentes', [
            'fuentes' => $this->fuenteModel->orderBy('nombre', 'ASC')->findAll(),
        ]);
    }

    public function guardarFuente()
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $id = (int) $this->request->getPost('id_fuente');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $activa = (int) ($this->request->getPost('activa') ?? 1);
        if ($nombre === '') return $this->response->setJSON(['ok' => false, 'error' => 'Nombre requerido'])->setStatusCode(400);

        if ($id > 0) {
            $this->fuenteModel->update($id, ['nombre' => $nombre, 'activa' => $activa]);
            return $this->response->setJSON(['ok' => true, 'id' => $id]);
        }
        $newId = $this->fuenteModel->insert(['nombre' => $nombre, 'activa' => $activa], true);
        return $this->response->setJSON(['ok' => true, 'id' => $newId]);
    }

    public function eliminarFuente($id)
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $this->fuenteModel->delete((int) $id);
        return $this->response->setJSON(['ok' => true]);
    }

    // ═══════════════════════════ MOTIVOS PÉRDIDA ══════════════════════════
    public function motivos()
    {
        if ($r = $this->chequearAdmin()) return $r;
        return view('crm/config/motivos', [
            'motivos' => $this->motivoModel->orderBy('nombre', 'ASC')->findAll(),
        ]);
    }

    public function guardarMotivo()
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $id = (int) $this->request->getPost('id_motivo_perdida');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $activa = (int) ($this->request->getPost('activa') ?? 1);
        if ($nombre === '') return $this->response->setJSON(['ok' => false, 'error' => 'Nombre requerido'])->setStatusCode(400);

        if ($id > 0) {
            $this->motivoModel->update($id, ['nombre' => $nombre, 'activa' => $activa]);
            return $this->response->setJSON(['ok' => true, 'id' => $id]);
        }
        $newId = $this->motivoModel->insert(['nombre' => $nombre, 'activa' => $activa], true);
        return $this->response->setJSON(['ok' => true, 'id' => $newId]);
    }

    public function eliminarMotivo($id)
    {
        if ($r = $this->chequearAdminJson()) return $r;
        $this->motivoModel->delete((int) $id);
        return $this->response->setJSON(['ok' => true]);
    }
}
