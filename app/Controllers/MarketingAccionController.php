<?php

namespace App\Controllers;

use App\Models\MarketingAccionModel;
use App\Models\MarketingTipoAccionModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MarketingAccionController extends BaseController
{
    protected $accionModel;
    protected $tipoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form', 'marketing']);
        $this->accionModel = new MarketingAccionModel();
        $this->tipoModel   = new MarketingTipoAccionModel();
    }

    private function chequearAcceso()
    {
        if (!marketing_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo Marketing.');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $userModel = new UserModel();
        $filtros = [
            'desde'          => $this->request->getGet('desde'),
            'hasta'          => $this->request->getGet('hasta'),
            'id_tipo_accion' => $this->request->getGet('tipo'),
            'id_responsable' => $this->request->getGet('responsable'),
        ];
        return view('marketing/acciones/list', [
            'acciones' => $this->accionModel->getListado(array_filter($filtros)),
            'tipos'    => $this->tipoModel->getActivos(),
            'usuarios' => $userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll(),
            'filtros'  => $filtros,
        ]);
    }

    public function crear()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $userModel = new UserModel();
        return view('marketing/acciones/add', [
            'tipos'    => $this->tipoModel->getActivos(),
            'usuarios' => $userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll(),
        ]);
    }

    public function crearPost()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $datos = $this->extraerForm();
        $errores = $this->validar($datos);
        if (!empty($errores)) return redirect()->back()->withInput()->with('errors', $errores);

        $this->accionModel->insert($datos);
        return redirect()->to('/marketing/acciones')->with('success', 'Acción registrada.');
    }

    public function editar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $accion = $this->accionModel->find($id);
        if (!$accion) return redirect()->to('/marketing/acciones')->with('errors', ['No encontrada.']);

        $userModel = new UserModel();
        return view('marketing/acciones/edit', [
            'accion'   => $accion,
            'tipos'    => $this->tipoModel->getActivos(),
            'usuarios' => $userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll(),
        ]);
    }

    public function editarPost($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        if (!$this->accionModel->find($id)) return redirect()->to('/marketing/acciones')->with('errors', ['No encontrada.']);

        $datos = $this->extraerForm();
        $errores = $this->validar($datos);
        if (!empty($errores)) return redirect()->back()->withInput()->with('errors', $errores);

        $this->accionModel->update($id, $datos);
        return redirect()->to('/marketing/acciones')->with('success', 'Acción actualizada.');
    }

    public function eliminar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $this->accionModel->delete((int) $id);
        return redirect()->to('/marketing/acciones')->with('success', 'Acción eliminada.');
    }

    // ─────────────── Config de tipos (solo admin) ───────────────
    public function tipos()
    {
        if (!marketing_es_admin()) return redirect()->to('/marketing')->with('errors', ['Solo admin.']);
        return view('marketing/config_tipos', [
            'tipos' => $this->tipoModel->orderBy('nombre', 'ASC')->findAll(),
        ]);
    }

    public function guardarTipo()
    {
        if (!marketing_es_admin()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin permiso'])->setStatusCode(403);
        }
        $id = (int) $this->request->getPost('id_tipo_accion');
        $datos = [
            'nombre' => trim((string) $this->request->getPost('nombre')),
            'color'  => $this->request->getPost('color') ?: '#6c757d',
            'activa' => (int) ($this->request->getPost('activa') ?? 1),
        ];
        if ($datos['nombre'] === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Nombre requerido'])->setStatusCode(400);
        }
        if ($id > 0) {
            $this->tipoModel->update($id, $datos);
            return $this->response->setJSON(['ok' => true, 'id' => $id]);
        }
        $newId = $this->tipoModel->insert($datos, true);
        return $this->response->setJSON(['ok' => true, 'id' => $newId]);
    }

    public function eliminarTipo($id)
    {
        if (!marketing_es_admin()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin permiso'])->setStatusCode(403);
        }
        $id = (int) $id;
        $db = \Config\Database::connect();
        $count = $db->table('tbl_marketing_accion')->where('id_tipo_accion', $id)->countAllResults();
        if ($count > 0) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => "Hay {$count} acción(es) con este tipo. Inactívalo en su lugar.",
            ])->setStatusCode(400);
        }
        $this->tipoModel->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    // ─────────────── helpers ───────────────
    private function extraerForm(): array
    {
        $req = $this->request;
        return [
            'fecha'           => $req->getPost('fecha') ?: date('Y-m-d'),
            'id_tipo_accion'  => (int) $req->getPost('id_tipo_accion'),
            'descripcion'     => trim((string) $req->getPost('descripcion')),
            'costo'           => $req->getPost('costo') !== '' && $req->getPost('costo') !== null
                                 ? (float) preg_replace('/[^\d.]/', '', (string) $req->getPost('costo')) : null,
            'leads_generados' => $req->getPost('leads_generados') !== '' && $req->getPost('leads_generados') !== null
                                 ? (int) $req->getPost('leads_generados') : null,
            'notas'           => trim((string) $req->getPost('notas')) ?: null,
            'id_responsable'  => (int) $req->getPost('id_responsable') ?: (int) session()->get('id_users'),
        ];
    }

    private function validar(array $d): array
    {
        $errores = [];
        if ($d['descripcion'] === '')   $errores[] = 'La descripción es obligatoria.';
        if ($d['id_tipo_accion'] <= 0)  $errores[] = 'Selecciona un tipo de acción.';
        if (empty($d['fecha']))         $errores[] = 'La fecha es obligatoria.';
        return $errores;
    }
}
