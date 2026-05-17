<?php

namespace App\Controllers;

use App\Models\MarketingLeadModel;
use App\Models\CrmFuenteModel;
use App\Models\CrmEmpresaModel;
use App\Models\CrmContactoModel;
use App\Models\CrmOportunidadModel;
use App\Models\CrmEtapaModel;
use App\Models\CrmOportunidadHistorialModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MarketingLeadController extends BaseController
{
    protected $leadModel;
    protected $fuenteModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form', 'marketing']);
        $this->leadModel   = new MarketingLeadModel();
        $this->fuenteModel = new CrmFuenteModel();
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
            'estado'         => $this->request->getGet('estado'),
            'id_fuente'      => $this->request->getGet('fuente'),
            'id_responsable' => $this->request->getGet('responsable'),
            'busqueda'       => trim($this->request->getGet('busqueda') ?? ''),
        ];
        return view('marketing/leads/list', [
            'leads'        => $this->leadModel->getListado(array_filter($filtros)),
            'fuentes'      => $this->fuenteModel->getActivas(),
            'usuarios'     => $userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll(),
            'filtros'      => $filtros,
        ]);
    }

    public function crear()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $userModel = new UserModel();
        return view('marketing/leads/add', [
            'fuentes'  => $this->fuenteModel->getActivas(),
            'usuarios' => $userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll(),
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
        $id = $this->leadModel->insert($datos, true);
        return redirect()->to("/marketing/leads/ver/{$id}")->with('success', 'Lead creado.');
    }

    public function ver($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $rows = $this->leadModel->getListado([]);
        $lead = null;
        foreach ($rows as $r2) {
            if ((int) $r2['id_lead'] === $id) { $lead = $r2; break; }
        }
        if (!$lead) {
            return redirect()->to('/marketing/leads')->with('errors', ['Lead no encontrado.']);
        }

        // Tipos de acción para mostrar contexto
        return view('marketing/leads/view', [
            'lead' => $lead,
        ]);
    }

    public function editar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        $lead = $this->leadModel->find($id);
        if (!$lead) return redirect()->to('/marketing/leads')->with('errors', ['No encontrado.']);

        $userModel = new UserModel();
        return view('marketing/leads/edit', [
            'lead'     => $lead,
            'fuentes'  => $this->fuenteModel->getActivas(),
            'usuarios' => $userModel->where('activo', 1)->orderBy('nombre_completo', 'ASC')->findAll(),
        ]);
    }

    public function editarPost($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $id = (int) $id;
        if (!$this->leadModel->find($id)) return redirect()->to('/marketing/leads')->with('errors', ['No encontrado.']);

        $datos = $this->extraerForm();
        $errores = $this->validar($datos);
        if (!empty($errores)) return redirect()->back()->withInput()->with('errors', $errores);

        $this->leadModel->update($id, $datos);
        return redirect()->to("/marketing/leads/ver/{$id}")->with('success', 'Lead actualizado.');
    }

    public function eliminar($id)
    {
        if ($r = $this->chequearAcceso()) return $r;
        $this->leadModel->delete((int) $id);
        return redirect()->to('/marketing/leads')->with('success', 'Lead eliminado.');
    }

    /**
     * Cambia el estado del lead (AJAX desde lista o ficha).
     */
    public function cambiarEstadoAjax($id)
    {
        if (!marketing_tiene_acceso()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin acceso'])->setStatusCode(403);
        }
        $id = (int) $id;
        $lead = $this->leadModel->find($id);
        if (!$lead) return $this->response->setJSON(['ok' => false, 'error' => 'No encontrado'])->setStatusCode(404);

        $nuevoEstado = $this->request->getPost('estado');
        $validos = ['nuevo', 'contactado', 'calificado', 'descartado'];
        if (!in_array($nuevoEstado, $validos, true)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Estado inválido'])->setStatusCode(400);
        }

        $update = ['estado' => $nuevoEstado];
        if ($nuevoEstado === 'calificado') {
            $update['fecha_calificacion'] = date('Y-m-d H:i:s');
        }
        if ($nuevoEstado === 'descartado') {
            $update['fecha_descartado'] = date('Y-m-d H:i:s');
            $update['motivo_descarte'] = trim((string) $this->request->getPost('motivo_descarte')) ?: null;
        }
        $this->leadModel->update($id, $update);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Convierte un lead en empresa + contacto + oportunidad (botón one-click).
     * Chunk C — el "puente" con el CRM.
     */
    public function convertirAOportunidad($id)
    {
        if (!marketing_tiene_acceso()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Sin acceso'])->setStatusCode(403);
        }
        $id = (int) $id;
        $lead = $this->leadModel->find($id);
        if (!$lead) return $this->response->setJSON(['ok' => false, 'error' => 'Lead no encontrado'])->setStatusCode(404);

        if (!empty($lead['id_empresa_convertida']) && !empty($lead['id_oportunidad_convertida'])) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'Este lead ya fue convertido.',
                'id_oportunidad' => (int) $lead['id_oportunidad_convertida'],
            ])->setStatusCode(400);
        }

        $razonSocial = trim((string) $this->request->getPost('razon_social')) ?: ($lead['empresa_text'] ?: $lead['nombre']);
        $titulo      = trim((string) $this->request->getPost('titulo_oportunidad')) ?: 'Oportunidad inicial — ' . $razonSocial;
        $valor       = (float) preg_replace('/[^\d.]/', '', (string) $this->request->getPost('valor'));
        $idEtapaInicial = (int) $this->request->getPost('id_etapa') ?: null;

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Crear empresa
            $empresaModel = new CrmEmpresaModel();
            $idEmpresa = $empresaModel->insert([
                'razon_social'    => $razonSocial,
                'email_principal' => $lead['email'] ?: null,
                'telefono'        => $lead['telefono'] ?: null,
                'id_fuente'       => $lead['id_fuente'] ?: null,
                'id_responsable'  => $lead['id_responsable'] ?: (int) session()->get('id_users'),
                'notas'           => "Convertido del lead #{$lead['id_lead']}: {$lead['nombre']}",
                'activo'          => 1,
                'created_by'      => (int) session()->get('id_users'),
            ], true);

            // 2. Crear contacto (si hay datos suficientes)
            $idContacto = null;
            if (!empty($lead['nombre'])) {
                $contactoModel = new CrmContactoModel();
                $idContacto = $contactoModel->insert([
                    'id_empresa' => $idEmpresa,
                    'nombre'     => $lead['nombre'],
                    'cargo'      => $lead['cargo'] ?: null,
                    'email'      => $lead['email'] ?: null,
                    'telefono'   => $lead['telefono'] ?: null,
                    'activo'     => 1,
                ], true);
            }

            // 3. Crear oportunidad
            $oportunidadModel = new CrmOportunidadModel();
            $etapaModel = new CrmEtapaModel();

            // Si no llegó etapa explícita, usar la primera abierta por orden
            if (!$idEtapaInicial) {
                $etapaProspecto = $etapaModel->where('tipo', 'abierta')->orderBy('orden', 'ASC')->first();
                $idEtapaInicial = $etapaProspecto ? (int) $etapaProspecto['id_etapa'] : null;
            }
            $etapa = $etapaModel->find($idEtapaInicial);
            $probabilidad = $etapa ? (int) $etapa['probabilidad_default'] : 0;

            $codigo = $oportunidadModel->generarCodigo();
            $idOportunidad = $oportunidadModel->insert([
                'codigo'                => $codigo,
                'id_empresa'            => $idEmpresa,
                'id_contacto_principal' => $idContacto,
                'titulo'                => $titulo,
                'descripcion'           => $lead['notas'] ?: null,
                'valor'                 => $valor,
                'moneda'                => 'COP',
                'id_etapa'              => $idEtapaInicial,
                'probabilidad'          => $probabilidad,
                'id_responsable'        => $lead['id_responsable'] ?: (int) session()->get('id_users'),
                'id_creador'            => (int) session()->get('id_users'),
                'notas'                 => "Origen: lead de marketing #{$lead['id_lead']}",
            ], true);

            // Historial inicial
            $historialModel = new CrmOportunidadHistorialModel();
            $historialModel->insert([
                'id_oportunidad'    => $idOportunidad,
                'id_etapa_anterior' => null,
                'id_etapa_nueva'    => $idEtapaInicial,
                'id_usuario'        => (int) session()->get('id_users'),
                'comentario'        => 'Creada al convertir lead de marketing',
            ]);

            // 4. Actualizar lead: marcarlo calificado y enlazar
            $this->leadModel->update($id, [
                'estado'                    => 'calificado',
                'fecha_calificacion'        => date('Y-m-d H:i:s'),
                'id_empresa_convertida'     => $idEmpresa,
                'id_oportunidad_convertida' => $idOportunidad,
            ]);

            $db->transComplete();
            if (!$db->transStatus()) {
                return $this->response->setJSON(['ok' => false, 'error' => 'Error en la transacción'])->setStatusCode(500);
            }

            return $this->response->setJSON([
                'ok' => true,
                'id_empresa' => $idEmpresa,
                'id_oportunidad' => $idOportunidad,
                'codigo' => $codigo,
                'redirect' => "/crm/oportunidades/ver/{$idOportunidad}",
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['ok' => false, 'error' => 'Error: ' . $e->getMessage()])->setStatusCode(500);
        }
    }

    // ─────────────────────────── HELPERS ───────────────────────────

    private function extraerForm(): array
    {
        $req = $this->request;
        return [
            'nombre'         => trim((string) $req->getPost('nombre')),
            'empresa_text'   => trim((string) $req->getPost('empresa_text')) ?: null,
            'cargo'          => trim((string) $req->getPost('cargo')) ?: null,
            'email'          => trim((string) $req->getPost('email')) ?: null,
            'telefono'       => trim((string) $req->getPost('telefono')) ?: null,
            'id_fuente'      => (int) $req->getPost('id_fuente') ?: null,
            'estado'         => $req->getPost('estado') ?: 'nuevo',
            'id_responsable' => (int) $req->getPost('id_responsable') ?: null,
            'notas'          => trim((string) $req->getPost('notas')) ?: null,
        ];
    }

    private function validar(array $d): array
    {
        $errores = [];
        if ($d['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Email inválido.';
        }
        return $errores;
    }
}
