<?php

namespace App\Controllers;

use App\Models\BitacoraActividadModel;
use App\Models\CentroCostoModel;
use App\Models\UserModel;
use App\Libraries\OpenAIService;

class BitacoraController extends BaseController
{
    protected $bitacoraModel;
    protected $centroCostoModel;

    public function __construct()
    {
        helper('bitacora');
        $this->bitacoraModel    = new BitacoraActividadModel();
        $this->centroCostoModel = new CentroCostoModel();
    }

    /**
     * Verificar que el usuario tiene bitácora habilitada
     */
    private function verificarAcceso(): bool
    {
        $userId = session()->get('id_users');
        if (!$userId) return false;

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        return $user && $user['bitacora_habilitada'] == 1;
    }

    // ====================================
    // VISTAS
    // ====================================

    public function index()
    {
        if (!$this->verificarAcceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo de bitácora.');
        }

        $userId = session()->get('id_users');
        $hoy    = date('Y-m-d');

        $data = [
            'centrosCosto'     => $this->centroCostoModel->getActivos(),
            'actividadesHoy'   => $this->bitacoraModel->getActividadesDelDia($userId, $hoy),
            'actividadActiva'  => $this->bitacoraModel->getActividadEnProgreso($userId),
            'totalMinutos'     => $this->bitacoraModel->getTotalMinutosDia($userId, $hoy),
            'tab'              => 'bitacora',
        ];

        return view('bitacora/index', $data);
    }

    public function centrosCosto()
    {
        if (!$this->verificarAcceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo de bitácora.');
        }

        $data = [
            'centrosCosto' => $this->centroCostoModel->orderBy('nombre', 'ASC')->findAll(),
            'tab'          => 'centros',
        ];

        return view('bitacora/centros_costo', $data);
    }

    public function historial($fecha = null)
    {
        if (!$this->verificarAcceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo de bitácora.');
        }

        $userId = session()->get('id_users');
        $fecha  = $fecha ?? date('Y-m-d');

        $data = [
            'fecha'        => $fecha,
            'actividades'  => $this->bitacoraModel->getActividadesDelDia($userId, $fecha),
            'totalMinutos' => $this->bitacoraModel->getTotalMinutosDia($userId, $fecha),
            'tab'          => 'resumen',
        ];

        return view('bitacora/historial', $data);
    }

    /**
     * Resumen mensual de productividad propia
     */
    public function resumen($anio = null, $mes = null)
    {
        if (!$this->verificarAcceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo de bitácora.');
        }

        $userId = session()->get('id_users');
        $anio   = $anio ? (int)$anio : (int)date('Y');
        $mes    = $mes ? (int)$mes : (int)date('n');

        $data = [
            'anio'    => $anio,
            'mes'     => $mes,
            'resumen' => $this->bitacoraModel->getResumenMensual($userId, $anio, $mes),
            'tab'     => 'resumen',
        ];

        return view('bitacora/resumen', $data);
    }

    /**
     * Vista equipo — resumen mensual de todos los usuarios (jefe/superadmin)
     */
    public function equipo($anio = null, $mes = null)
    {
        if (!$this->verificarAcceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo de bitácora.');
        }

        $rolId = (int) session()->get('id_roles');
        if (!in_array($rolId, [1, 2, 3])) {
            return redirect()->to('bitacora');
        }

        $anio = $anio ? (int)$anio : (int)date('Y');
        $mes  = $mes ? (int)$mes : (int)date('n');

        $data = [
            'anio'   => $anio,
            'mes'    => $mes,
            'equipo' => $this->bitacoraModel->getResumenEquipoMensual($anio, $mes),
            'tab'    => 'equipo',
        ];

        return view('bitacora/equipo', $data);
    }

    /**
     * Detalle mensual de un usuario especifico (vista jefe)
     */
    public function equipoDetalle($idUsuario, $anio = null, $mes = null)
    {
        if (!$this->verificarAcceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo de bitácora.');
        }

        $rolId = (int) session()->get('id_roles');
        if (!in_array($rolId, [1, 2, 3])) {
            return redirect()->to('bitacora');
        }

        $anio = $anio ? (int)$anio : (int)date('Y');
        $mes  = $mes ? (int)$mes : (int)date('n');

        $userModel = new UserModel();
        $usuario = $userModel->find($idUsuario);

        $data = [
            'anio'          => $anio,
            'mes'           => $mes,
            'idUsuario'     => $idUsuario,
            'nombreUsuario' => $usuario ? $usuario['nombre_completo'] : 'Usuario',
            'resumen'       => $this->bitacoraModel->getResumenMensual((int)$idUsuario, $anio, $mes),
            'tab'           => 'equipo',
        ];

        return view('bitacora/equipo_detalle', $data);
    }

    // ====================================
    // ENDPOINTS AJAX
    // ====================================

    /**
     * Iniciar una nueva actividad
     */
    public function iniciarActividad()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $userId      = session()->get('id_users');
        $descripcion = trim($this->request->getPost('descripcion') ?? '');
        $centroCosto = (int) $this->request->getPost('id_centro_costo');

        if (empty($descripcion) || $centroCosto <= 0) {
            return $this->response->setJSON(['error' => 'Descripción y centro de costo son obligatorios'])->setStatusCode(400);
        }

        // Verificar que no hay actividad en progreso
        $activa = $this->bitacoraModel->getActividadEnProgreso($userId);
        if ($activa) {
            return $this->response->setJSON(['error' => 'Ya tienes una actividad en progreso. Termínala primero.'])->setStatusCode(400);
        }

        $hoy       = date('Y-m-d');
        $ahora     = date('Y-m-d H:i:s');
        $numActiv  = $this->bitacoraModel->getNextNumeroActividad($userId, $hoy);

        $data = [
            'id_usuario'        => $userId,
            'numero_actividad'  => $numActiv,
            'descripcion'       => $descripcion,
            'id_centro_costo'   => $centroCosto,
            'fecha'             => $hoy,
            'hora_inicio'       => $ahora,
            'estado'            => 'en_progreso',
        ];

        $this->bitacoraModel->insert($data);
        $id = $this->bitacoraModel->getInsertID();

        return $this->response->setJSON([
            'ok'          => true,
            'id_bitacora' => $id,
            'hora_inicio' => $ahora,
            'numero'      => $numActiv,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Terminar una actividad en progreso
     */
    public function terminarActividad($id)
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $userId    = session()->get('id_users');
        $actividad = $this->bitacoraModel->find($id);

        if (!$actividad || $actividad['id_usuario'] != $userId) {
            return $this->response->setJSON(['error' => 'Actividad no encontrada'])->setStatusCode(404);
        }

        if ($actividad['estado'] !== 'en_progreso') {
            return $this->response->setJSON(['error' => 'La actividad ya fue finalizada'])->setStatusCode(400);
        }

        $ahora = date('Y-m-d H:i:s');
        $inicio = new \DateTime($actividad['hora_inicio']);
        $fin    = new \DateTime($ahora);
        $diff   = $fin->getTimestamp() - $inicio->getTimestamp();
        $duracionMinutos = round($diff / 60, 2);

        $this->bitacoraModel->update($id, [
            'hora_fin'          => $ahora,
            'duracion_minutos'  => $duracionMinutos,
            'estado'            => 'finalizada',
        ]);

        $totalMinutos = $this->bitacoraModel->getTotalMinutosDia($userId, $actividad['fecha']);

        return $this->response->setJSON([
            'ok'               => true,
            'hora_fin'         => $ahora,
            'duracion_minutos' => $duracionMinutos,
            'total_minutos'    => $totalMinutos,
        ]);
    }

    /**
     * Obtener actividad activa actual (AJAX)
     */
    public function actividadActiva()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $userId  = session()->get('id_users');
        $activa  = $this->bitacoraModel->getActividadEnProgreso($userId);

        return $this->response->setJSON([
            'ok'       => true,
            'actividad' => $activa,
        ]);
    }

    /**
     * Lista de actividades de hoy (AJAX)
     */
    public function actividadesHoy()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $userId = session()->get('id_users');
        $hoy    = date('Y-m-d');

        return $this->response->setJSON([
            'ok'           => true,
            'actividades'  => $this->bitacoraModel->getActividadesDelDia($userId, $hoy),
            'totalMinutos' => $this->bitacoraModel->getTotalMinutosDia($userId, $hoy),
        ]);
    }

    // ====================================
    // CENTROS DE COSTO
    // ====================================

    public function guardarCentroCosto()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $nombre      = trim($this->request->getPost('nombre') ?? '');
        $descripcion = trim($this->request->getPost('descripcion') ?? '');
        $id          = (int) $this->request->getPost('id_centro_costo');

        if (empty($nombre)) {
            return $this->response->setJSON(['error' => 'El nombre es obligatorio'])->setStatusCode(400);
        }

        $data = [
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
        ];

        if ($id > 0) {
            $this->centroCostoModel->update($id, $data);
        } else {
            $data['created_by'] = session()->get('id_users');
            $this->centroCostoModel->insert($data);
            $id = $this->centroCostoModel->getInsertID();
        }

        return $this->response->setJSON(['ok' => true, 'id' => $id]);
    }

    /**
     * Verificar duplicados de centro de costo con IA
     */
    public function verificarDuplicadoCC()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $nombre = trim($this->request->getPost('nombre') ?? '');
        if (empty($nombre)) {
            return $this->response->setJSON(['similares' => []]);
        }

        // Obtener todos los centros de costo activos
        $centros = $this->centroCostoModel->getActivos();
        if (empty($centros)) {
            return $this->response->setJSON(['similares' => []]);
        }

        $listaNombres = array_map(function($c) {
            return ['id' => $c['id_centro_costo'], 'nombre' => $c['nombre']];
        }, $centros);

        $listaJSON = json_encode($listaNombres, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Eres un asistente que detecta duplicados en una lista de centros de costo.

El usuario quiere crear un nuevo centro de costo llamado: "$nombre"

Lista de centros de costo existentes:
$listaJSON

Analiza si el nombre nuevo es similar, duplicado o equivalente a alguno existente. Considera:
- Nombres iguales con diferente mayuscula/minuscula
- Abreviaciones (ej: "Dpto" vs "Departamento")
- Sinonimos o variaciones (ej: "Oficina Central" vs "Sede Principal")
- Errores tipograficos menores

Responde SOLO con un JSON valido (sin markdown):
{
    "similares": [
        {"id": 123, "nombre": "Nombre existente", "razon": "motivo breve de similitud"}
    ]
}

Si NO hay similares, responde: {"similares": []}
Maximo 3 resultados. Solo incluye los realmente similares.
PROMPT;

        $openai = new OpenAIService();
        if (!$openai->isConfigured()) {
            return $this->response->setJSON(['similares' => []]);
        }

        $resultado = $openai->makeRequestRaw($prompt);

        if ($resultado['success'] && isset($resultado['data']['similares'])) {
            return $this->response->setJSON(['similares' => $resultado['data']['similares']]);
        }

        return $this->response->setJSON(['similares' => []]);
    }

    public function eliminarCentroCosto($id)
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $this->centroCostoModel->update($id, ['activo' => 0]);

        return $this->response->setJSON(['ok' => true]);
    }

}
