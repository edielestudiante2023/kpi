<?php

namespace App\Controllers;

use App\Models\CrmEmpresaModel;
use App\Models\CrmContactoModel;
use App\Models\CrmFuenteModel;
use App\Models\CrmInteraccionModel;
use App\Models\CrmOportunidadModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmEmpresaController extends BaseController
{
    protected $empresaModel;
    protected $contactoModel;
    protected $fuenteModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form', 'crm']);
        $this->empresaModel  = new CrmEmpresaModel();
        $this->contactoModel = new CrmContactoModel();
        $this->fuenteModel   = new CrmFuenteModel();
    }

    private function chequearAcceso()
    {
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }
        return null;
    }

    /**
     * Listado de empresas (DataTable con filtro de búsqueda).
     */
    public function index()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $busqueda = trim($this->request->getGet('busqueda') ?? '');
        return view('crm/empresas/list', [
            'empresas'      => $this->empresaModel->getListado($busqueda ?: null, false),
            'filtroBusqueda'=> $busqueda,
        ]);
    }

    public function crear()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $userModel = new UserModel();
        return view('crm/empresas/add', [
            'fuentes'       => $this->fuenteModel->getActivas(),
            'usuariosCrm'   => $userModel->where('activo', 1)
                                         ->groupStart()
                                            ->where('crm_habilitado', 1)
                                            ->orWhere('crm_admin', 1)
                                         ->groupEnd()
                                         ->orderBy('nombre_completo', 'ASC')->findAll(),
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
        $datos['created_by'] = (int) session()->get('id_users');
        $id = $this->empresaModel->insert($datos, true);
        return redirect()->to("/crm/empresas/ver/{$id}")
            ->with('success', 'Empresa creada correctamente.');
    }

    public function ver($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $emp = $this->empresaModel->find($id);
        if (!$emp) {
            return redirect()->to('/crm/empresas')->with('errors', ['Empresa no encontrada.']);
        }

        // Datos relacionados
        $db = \Config\Database::connect();
        $emp['fuente_nombre'] = $emp['id_fuente']
            ? ($db->table('tbl_crm_fuente')->where('id_fuente', $emp['id_fuente'])->get()->getRow()->nombre ?? '')
            : '';
        $emp['responsable_nombre'] = $emp['id_responsable']
            ? ($db->table('users')->where('id_users', $emp['id_responsable'])->get()->getRow()->nombre_completo ?? '')
            : '';

        $contactos = $this->contactoModel->getDeEmpresa($id, false);

        // Oportunidades asociadas — filtradas por visibilidad del usuario
        $oportunidadModel = new CrmOportunidadModel();
        $oportunidades = $oportunidadModel->getListadoVisible(
            (int) session()->get('id_users'),
            crm_es_admin(),
            ['id_empresa' => $id]
        );

        // Interacciones recientes
        $interaccionModel = new CrmInteraccionModel();
        $interacciones = $interaccionModel->getDeEmpresa($id, 20);

        return view('crm/empresas/view', [
            'empresa'       => $emp,
            'contactos'     => $contactos,
            'oportunidades' => $oportunidades,
            'interacciones' => $interacciones,
        ]);
    }

    public function editar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $emp = $this->empresaModel->find($id);
        if (!$emp) return redirect()->to('/crm/empresas')->with('errors', ['Empresa no encontrada.']);

        $userModel = new UserModel();
        return view('crm/empresas/edit', [
            'empresa'     => $emp,
            'fuentes'     => $this->fuenteModel->getActivas(),
            'usuariosCrm' => $userModel->where('activo', 1)
                                       ->groupStart()
                                          ->where('crm_habilitado', 1)
                                          ->orWhere('crm_admin', 1)
                                       ->groupEnd()
                                       ->orderBy('nombre_completo', 'ASC')->findAll(),
        ]);
    }

    public function editarPost($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        if (!$this->empresaModel->find($id)) {
            return redirect()->to('/crm/empresas')->with('errors', ['Empresa no encontrada.']);
        }
        $datos = $this->extraerForm();
        $errores = $this->validar($datos);
        if (!empty($errores)) {
            return redirect()->back()->withInput()->with('errors', $errores);
        }
        $this->empresaModel->update($id, $datos);
        return redirect()->to("/crm/empresas/ver/{$id}")->with('success', 'Empresa actualizada.');
    }

    public function eliminar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $emp = $this->empresaModel->find($id);
        if (!$emp) return redirect()->back()->with('errors', ['Empresa no encontrada.']);

        if ($this->empresaModel->contarOportunidades($id) > 0) {
            return redirect()->back()->with('errors', [
                'No se puede eliminar: la empresa tiene oportunidades asociadas. Inactívala en su lugar.',
            ]);
        }
        $this->empresaModel->delete($id);
        return redirect()->to('/crm/empresas')->with('success', "Empresa #{$id} eliminada.");
    }

    /**
     * Búsqueda AJAX para Select2 desde el form de nueva oportunidad.
     */
    public function buscarAjax()
    {
        if (!crm_tiene_acceso()) {
            return $this->response->setJSON(['items' => []])->setStatusCode(403);
        }
        $q = trim($this->request->getGet('q') ?? '');
        return $this->response->setJSON(['items' => $this->empresaModel->buscarAjax($q)]);
    }

    // ─────────────────────────────── HELPERS ───────────────────────────────

    private function extraerForm(): array
    {
        $req = $this->request;
        return [
            'razon_social'    => trim((string) $req->getPost('razon_social')),
            'nit'             => trim((string) $req->getPost('nit')) ?: null,
            'sector'          => trim((string) $req->getPost('sector')) ?: null,
            'tamano'          => $req->getPost('tamano') ?: null,
            'ciudad'          => trim((string) $req->getPost('ciudad')) ?: null,
            'telefono'        => trim((string) $req->getPost('telefono')) ?: null,
            'email_principal' => trim((string) $req->getPost('email_principal')) ?: null,
            'sitio_web'       => trim((string) $req->getPost('sitio_web')) ?: null,
            'id_fuente'       => (int) $req->getPost('id_fuente') ?: null,
            'id_responsable'  => (int) $req->getPost('id_responsable') ?: null,
            'notas'           => trim((string) $req->getPost('notas')) ?: null,
            'activo'          => (int) ($req->getPost('activo') ?? 1),
        ];
    }

    private function validar(array $d): array
    {
        $errores = [];
        if ($d['razon_social'] === '') $errores[] = 'La razón social es obligatoria.';
        if (!empty($d['email_principal']) && !filter_var($d['email_principal'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email principal no es válido.';
        }
        return $errores;
    }
}
