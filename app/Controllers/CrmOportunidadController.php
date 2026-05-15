<?php

namespace App\Controllers;

use App\Models\CrmOportunidadModel;
use App\Models\CrmEmpresaModel;
use App\Models\CrmContactoModel;
use App\Models\CrmEtapaModel;
use App\Models\CrmMotivoPerdidaModel;
use App\Models\CrmOportunidadHistorialModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmOportunidadController extends BaseController
{
    protected $oportunidadModel;
    protected $empresaModel;
    protected $contactoModel;
    protected $etapaModel;
    protected $motivoModel;
    protected $historialModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form', 'crm']);
        $this->oportunidadModel = new CrmOportunidadModel();
        $this->empresaModel     = new CrmEmpresaModel();
        $this->contactoModel    = new CrmContactoModel();
        $this->etapaModel       = new CrmEtapaModel();
        $this->motivoModel      = new CrmMotivoPerdidaModel();
        $this->historialModel   = new CrmOportunidadHistorialModel();
    }

    private function chequearAcceso()
    {
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }
        return null;
    }

    private function chequearAccesoJson()
    {
        if (!crm_tiene_acceso()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin acceso'])->setStatusCode(403);
        }
        return null;
    }

    /**
     * Kanban del pipeline.
     */
    public function kanban()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $idUsuario = (int) session()->get('id_users');
        $esAdmin = crm_es_admin();

        return view('crm/oportunidades/kanban', [
            'etapas'       => $this->etapaModel->getOrdenadas(),
            'porEtapa'     => $this->oportunidadModel->getKanban($idUsuario, $esAdmin),
            'motivos'      => $this->motivoModel->getActivos(),
        ]);
    }

    /**
     * Listado tipo DataTable con filtros.
     */
    public function lista()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $idUsuario = (int) session()->get('id_users');
        $esAdmin = crm_es_admin();

        $filtros = [
            'id_etapa'       => $this->request->getGet('etapa'),
            'id_responsable' => $this->request->getGet('responsable'),
        ];
        $oportunidades = $this->oportunidadModel->getListadoVisible($idUsuario, $esAdmin, array_filter($filtros));

        $userModel = new UserModel();
        return view('crm/oportunidades/list', [
            'oportunidades' => $oportunidades,
            'etapas'        => $this->etapaModel->getOrdenadas(),
            'usuariosCrm'   => $userModel->where('activo', 1)
                                         ->groupStart()
                                            ->where('crm_habilitado', 1)->orWhere('crm_admin', 1)
                                         ->groupEnd()
                                         ->orderBy('nombre_completo', 'ASC')->findAll(),
            'filtroEtapa'       => $filtros['id_etapa'],
            'filtroResponsable' => $filtros['id_responsable'],
        ]);
    }

    public function crear()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $idEmpresaPre = (int) $this->request->getGet('id_empresa');
        $empresaPre = $idEmpresaPre > 0 ? $this->empresaModel->find($idEmpresaPre) : null;

        $userModel = new UserModel();
        return view('crm/oportunidades/add', [
            'etapas'       => $this->etapaModel->getAbiertas(),
            'usuariosCrm'  => $userModel->where('activo', 1)
                                        ->groupStart()
                                           ->where('crm_habilitado', 1)->orWhere('crm_admin', 1)
                                        ->groupEnd()
                                        ->orderBy('nombre_completo', 'ASC')->findAll(),
            'empresaPre'   => $empresaPre,
        ]);
    }

    public function crearPost()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $datos = $this->extraerForm();
        $errores = $this->validar($datos);
        if (!empty($errores)) {
            return redirect()->back()->withInput()->with('errors', $errores);
        }

        $datos['codigo']     = $this->oportunidadModel->generarCodigo();
        $datos['id_creador'] = (int) session()->get('id_users');
        $id = $this->oportunidadModel->insert($datos, true);

        // Registrar primera entrada en historial
        $this->historialModel->insert([
            'id_oportunidad'    => $id,
            'id_etapa_anterior' => null,
            'id_etapa_nueva'    => (int) $datos['id_etapa'],
            'id_usuario'        => (int) session()->get('id_users'),
            'comentario'        => 'Oportunidad creada',
        ]);

        return redirect()->to("/crm/oportunidades/ver/{$id}")
            ->with('success', "Oportunidad creada: {$datos['codigo']}");
    }

    public function ver($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $op = $this->oportunidadModel->getConJoins($id);
        if (!$op) return redirect()->to('/crm/oportunidades/kanban')->with('errors', ['Oportunidad no encontrada.']);
        if (!crm_puede_ver_oportunidad($op)) {
            return redirect()->to('/crm/oportunidades/kanban')->with('errors', ['No tienes permiso para ver esta oportunidad.']);
        }

        return view('crm/oportunidades/view', [
            'oportunidad' => $op,
            'historial'   => $this->historialModel->getDeOportunidad($id),
            'contactos'   => $this->contactoModel->getDeEmpresa((int) $op['id_empresa'], false),
            'motivos'     => $this->motivoModel->getActivos(),
        ]);
    }

    public function editar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $op = $this->oportunidadModel->find($id);
        if (!$op) return redirect()->to('/crm/oportunidades/kanban')->with('errors', ['No encontrada.']);
        if (!crm_puede_editar_oportunidad($op)) {
            return redirect()->to('/crm/oportunidades/kanban')->with('errors', ['No tienes permiso para editar.']);
        }

        $empresa = $this->empresaModel->find((int) $op['id_empresa']);
        $contactos = $this->contactoModel->getDeEmpresa((int) $op['id_empresa'], false);

        $userModel = new UserModel();
        return view('crm/oportunidades/edit', [
            'oportunidad'  => $op,
            'empresa'      => $empresa,
            'contactos'    => $contactos,
            'etapas'       => $this->etapaModel->getOrdenadas(),
            'motivos'      => $this->motivoModel->getActivos(),
            'usuariosCrm'  => $userModel->where('activo', 1)
                                        ->groupStart()
                                           ->where('crm_habilitado', 1)->orWhere('crm_admin', 1)
                                        ->groupEnd()
                                        ->orderBy('nombre_completo', 'ASC')->findAll(),
        ]);
    }

    public function editarPost($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $op = $this->oportunidadModel->find($id);
        if (!$op) return redirect()->to('/crm/oportunidades/kanban')->with('errors', ['No encontrada.']);
        if (!crm_puede_editar_oportunidad($op)) {
            return redirect()->to('/crm/oportunidades/kanban')->with('errors', ['No tienes permiso para editar.']);
        }

        $datos = $this->extraerForm();
        $errores = $this->validar($datos);
        if (!empty($errores)) {
            return redirect()->back()->withInput()->with('errors', $errores);
        }

        $etapaAnterior = (int) $op['id_etapa'];
        $etapaNueva    = (int) $datos['id_etapa'];

        // Update general (sin tocar id_etapa — eso se hace por cambiarEtapa para registrar historial)
        $datosSinEtapa = $datos;
        unset($datosSinEtapa['id_etapa']);
        $this->oportunidadModel->update($id, $datosSinEtapa);

        // Si cambió la etapa, usar cambiarEtapa() para registrar historial y ajustar fecha_cierre_real
        if ($etapaAnterior !== $etapaNueva) {
            $this->oportunidadModel->cambiarEtapa($id, $etapaNueva, (int) session()->get('id_users'), 'Cambio desde edición');
        }

        return redirect()->to("/crm/oportunidades/ver/{$id}")->with('success', 'Oportunidad actualizada.');
    }

    public function eliminar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $op = $this->oportunidadModel->find($id);
        if (!$op) return redirect()->back()->with('errors', ['No encontrada.']);
        if (!crm_puede_editar_oportunidad($op)) {
            return redirect()->back()->with('errors', ['No tienes permiso.']);
        }
        $this->oportunidadModel->delete($id);
        return redirect()->to('/crm/oportunidades/kanban')->with('success', "Oportunidad eliminada.");
    }

    /**
     * AJAX: mover una oportunidad a otra etapa (drag-drop del Kanban).
     */
    public function cambiarEtapaAjax()
    {
        if ($r = $this->chequearAccesoJson()) return $r;

        $id    = (int) $this->request->getPost('id_oportunidad');
        $etapa = (int) $this->request->getPost('id_etapa');
        $motivo = (int) $this->request->getPost('id_motivo_perdida') ?: null;
        $comentario = trim((string) $this->request->getPost('comentario')) ?: null;

        $op = $this->oportunidadModel->find($id);
        if (!$op) return $this->response->setJSON(['ok' => false, 'error' => 'No encontrada'])->setStatusCode(404);
        if (!crm_puede_editar_oportunidad($op)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin permiso'])->setStatusCode(403);
        }

        // Validar etapa destino
        $etapaDest = $this->etapaModel->find($etapa);
        if (!$etapaDest) return $this->response->setJSON(['ok' => false, 'error' => 'Etapa inválida'])->setStatusCode(400);

        // Si la etapa destino es 'perdida', exigir motivo
        if ($etapaDest['tipo'] === 'perdida' && !$motivo) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'Para marcar perdida se requiere un motivo.',
                'requiere_motivo' => true,
            ])->setStatusCode(400);
        }

        $cambio = $this->oportunidadModel->cambiarEtapa($id, $etapa, (int) session()->get('id_users'), $comentario);

        if (!$cambio) {
            return $this->response->setJSON(['ok' => true, 'sin_cambio' => true]);
        }

        // Si es perdida, guardar el motivo (cambiarEtapa lo limpia, lo seteamos aparte)
        if ($etapaDest['tipo'] === 'perdida' && $motivo) {
            $this->oportunidadModel->update($id, ['id_motivo_perdida' => $motivo]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'oportunidad' => $this->oportunidadModel->getConJoins($id),
        ]);
    }

    /**
     * Marcar ganada — atajo desde la vista detalle.
     */
    public function marcarGanada($id)
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $id = (int) $id;
        $op = $this->oportunidadModel->find($id);
        if (!$op) return $this->response->setJSON(['ok' => false, 'error' => 'No encontrada'])->setStatusCode(404);
        if (!crm_puede_editar_oportunidad($op)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin permiso'])->setStatusCode(403);
        }

        $etapaGanada = $this->etapaModel->where('tipo', 'ganada')->first();
        if (!$etapaGanada) return $this->response->setJSON(['ok' => false, 'error' => 'No hay etapa ganada configurada'])->setStatusCode(500);

        $this->oportunidadModel->cambiarEtapa($id, (int) $etapaGanada['id_etapa'], (int) session()->get('id_users'), 'Marcada como ganada');
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Marcar perdida con motivo — desde la vista detalle.
     */
    public function marcarPerdida($id)
    {
        if ($r = $this->chequearAccesoJson()) return $r;
        $id = (int) $id;
        $motivo = (int) $this->request->getPost('id_motivo_perdida');
        $comentario = trim((string) $this->request->getPost('comentario')) ?: null;

        if (!$motivo) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Motivo requerido'])->setStatusCode(400);
        }

        $op = $this->oportunidadModel->find($id);
        if (!$op) return $this->response->setJSON(['ok' => false, 'error' => 'No encontrada'])->setStatusCode(404);
        if (!crm_puede_editar_oportunidad($op)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin permiso'])->setStatusCode(403);
        }

        $etapaPerdida = $this->etapaModel->where('tipo', 'perdida')->first();
        if (!$etapaPerdida) return $this->response->setJSON(['ok' => false, 'error' => 'No hay etapa perdida configurada'])->setStatusCode(500);

        $this->oportunidadModel->cambiarEtapa($id, (int) $etapaPerdida['id_etapa'], (int) session()->get('id_users'), $comentario);
        $this->oportunidadModel->update($id, ['id_motivo_perdida' => $motivo]);
        return $this->response->setJSON(['ok' => true]);
    }

    // ─────────────────────────────── HELPERS ───────────────────────────────

    private function extraerForm(): array
    {
        $req = $this->request;
        return [
            'id_empresa'            => (int) $req->getPost('id_empresa'),
            'id_contacto_principal' => (int) $req->getPost('id_contacto_principal') ?: null,
            'titulo'                => trim((string) $req->getPost('titulo')),
            'descripcion'           => trim((string) $req->getPost('descripcion')) ?: null,
            'valor'                 => $this->parseMonto($req->getPost('valor')),
            'moneda'                => $req->getPost('moneda') ?: 'COP',
            'id_etapa'              => (int) $req->getPost('id_etapa'),
            'probabilidad'          => (int) $req->getPost('probabilidad'),
            'fecha_cierre_estimada' => $req->getPost('fecha_cierre_estimada') ?: null,
            'id_responsable'        => (int) $req->getPost('id_responsable') ?: (int) session()->get('id_users'),
            'notas'                 => trim((string) $req->getPost('notas')) ?: null,
        ];
    }

    private function validar(array $d): array
    {
        $errores = [];
        if ($d['id_empresa'] <= 0)     $errores[] = 'Empresa requerida.';
        if ($d['titulo'] === '')       $errores[] = 'Título requerido.';
        if ($d['id_etapa'] <= 0)       $errores[] = 'Etapa requerida.';
        if ($d['valor'] < 0)           $errores[] = 'El valor no puede ser negativo.';
        if ($d['probabilidad'] < 0 || $d['probabilidad'] > 100) $errores[] = 'Probabilidad debe estar entre 0 y 100.';
        return $errores;
    }

    private function parseMonto($val): float
    {
        if ($val === null || $val === '') return 0.0;
        $str = preg_replace('/[\$\s]/', '', (string) $val);
        if (strpos($str, ',') !== false) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace('.', '', $str);
        }
        return is_numeric($str) ? (float) $str : 0.0;
    }
}
