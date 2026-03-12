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

        $updateData = [
            'hora_fin'          => $ahora,
            'duracion_minutos'  => $duracionMinutos,
            'estado'            => 'finalizada',
        ];

        // Actualizar descripción si el usuario la editó al terminar
        $nuevaDescripcion = $this->request->getPost('descripcion');
        if ($nuevaDescripcion !== null && trim($nuevaDescripcion) !== '') {
            $updateData['descripcion'] = trim($nuevaDescripcion);
        }

        $this->bitacoraModel->update($id, $updateData);

        $totalMinutos = $this->bitacoraModel->getTotalMinutosDia($userId, $actividad['fecha']);

        return $this->response->setJSON([
            'ok'               => true,
            'hora_fin'         => $ahora,
            'duracion_minutos' => $duracionMinutos,
            'total_minutos'    => $totalMinutos,
        ]);
    }

    /**
     * Descartar actividad en progreso (olvidó detener el tiempo)
     */
    public function descartarActividad($id)
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
            return $this->response->setJSON(['error' => 'Solo se pueden descartar actividades en progreso'])->setStatusCode(400);
        }

        $this->bitacoraModel->delete($id);

        return $this->response->setJSON(['ok' => true]);
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
     * Actividades en progreso de todo el equipo (AJAX)
     */
    public function equipoEnProgreso()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $actividades = $this->bitacoraModel->getEquipoEnProgreso();

        return $this->response->setJSON([
            'ok'           => true,
            'actividades'  => $actividades,
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

    // ================================================================
    // Push Notifications
    // ================================================================

    /**
     * Guardar suscripción push del navegador
     */
    public function guardarPushSubscription()
    {
        $userId = session()->get('id_users');
        if (!$userId) {
            return $this->response->setJSON(['ok' => false, 'error' => 'No autenticado']);
        }

        $endpoint = $this->request->getPost('endpoint');
        $p256dh   = $this->request->getPost('p256dh');
        $auth     = $this->request->getPost('auth');

        if (!$endpoint || !$p256dh || !$auth) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Datos incompletos']);
        }

        $model = new \App\Models\PushSubscriptionModel();
        $ok = $model->guardarSuscripcion((int) $userId, $endpoint, $p256dh, $auth);

        return $this->response->setJSON(['ok' => $ok]);
    }

    /**
     * Retornar la VAPID public key para el cliente JS
     */
    public function vapidPublicKey()
    {
        return $this->response->setJSON([
            'publicKey' => env('VAPID_PUBLIC_KEY', ''),
        ]);
    }

    // ================================================================
    // Liquidación Quincenal
    // ================================================================

    private function esAdminBitacora(): bool
    {
        return (int) (session()->get('admin_bitacora') ?? 0) === 1;
    }

    /**
     * Vista principal de liquidación (solo admin_bitacora)
     */
    public function liquidacion()
    {
        if (!$this->verificarAcceso()) return redirect()->to('/login');
        if (!$this->esAdminBitacora()) return redirect()->to('bitacora');

        $liquidacionModel = new \App\Models\LiquidacionModel();
        $festivoModel     = new \App\Models\DiaFestivoModel();
        $userModel        = new \App\Models\UserModel();

        // Determinar inicio del periodo
        $ultima = $liquidacionModel->getUltimaLiquidacion();
        $fechaInicio = $ultima ? $ultima['fecha_corte'] : env('BITACORA_PRIMERA_QUINCENA', '2026-03-01 00:00:00');
        $fechaHoy = date('Y-m-d H:i:s');

        // Días hábiles transcurridos (hasta hoy)
        $diasTranscurridos = $festivoModel->contarDiasHabiles($fechaInicio, $fechaHoy);

        // Meta fija: días hábiles de la quincena completa (14 días calendario)
        $fechaFinQuincena = date('Y-m-d', strtotime(substr($fechaInicio, 0, 10) . ' +14 days'));
        $diasHabiles = $festivoModel->contarDiasHabiles($fechaInicio, $fechaFinQuincena);

        // Novedades de tiempo
        $novedadColModel = new \App\Models\NovedadColectivaModel();
        $novedadIndModel = new \App\Models\NovedadIndividualModel();
        $horasColectivas = $novedadColModel->getHorasColectivasRango($fechaInicio, $fechaFinQuincena);

        // Preview de cada usuario
        $usuarios = $userModel->where('activo', 1)->where('bitacora_habilitada', 1)->findAll();
        $preview = [];
        foreach ($usuarios as $u) {
            $minutos = $this->bitacoraModel->getTotalMinutosRango((int) $u['id_users'], $fechaInicio, $fechaHoy);
            $horasTrabajadas = round($minutos / 60, 2);

            $jornada = $u['jornada'] ?? 'completa';
            $horasIndividuales = $novedadIndModel->getHorasIndividualesRango((int) $u['id_users'], $fechaInicio, $fechaFinQuincena);
            $horasMeta = calcularMetaHoras($diasHabiles, $jornada, $horasColectivas, $horasIndividuales);

            $porcentaje = $horasMeta > 0 ? round(($horasTrabajadas / $horasMeta) * 100, 2) : 0;

            $preview[] = [
                'id_users'         => $u['id_users'],
                'nombre_completo'  => $u['nombre_completo'],
                'jornada'          => $jornada,
                'horas_trabajadas' => $horasTrabajadas,
                'horas_meta'       => $horasMeta,
                'porcentaje'       => $porcentaje,
            ];
        }

        // Historial
        $historial = $liquidacionModel->getHistorial();

        return view('bitacora/liquidacion', [
            'tab'               => 'liquidacion',
            'fechaInicio'       => $fechaInicio,
            'fechaHoy'          => $fechaHoy,
            'diasHabiles'       => $diasHabiles,
            'diasTranscurridos' => $diasTranscurridos,
            'preview'           => $preview,
            'historial'         => $historial,
        ]);
    }

    /**
     * Ejecutar liquidación (corte de quincena) — AJAX POST
     */
    public function ejecutarLiquidacion()
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $liquidacionModel = new \App\Models\LiquidacionModel();
        $detalleModel     = new \App\Models\DetalleLiquidacionModel();
        $festivoModel     = new \App\Models\DiaFestivoModel();
        $userModel        = new \App\Models\UserModel();

        $ahora = date('Y-m-d H:i:s');
        $notas = $this->request->getPost('notas') ?? '';

        // Inicio del periodo
        $ultima = $liquidacionModel->getUltimaLiquidacion();
        $fechaInicio = $ultima ? $ultima['fecha_corte'] : env('BITACORA_PRIMERA_QUINCENA', '2026-03-01 00:00:00');

        // 1. Cortar actividades en progreso
        $enProgreso = $this->bitacoraModel->getTodasEnProgreso();
        foreach ($enProgreso as $act) {
            $inicio = new \DateTime($act['hora_inicio']);
            $fin    = new \DateTime($ahora);
            $duracion = round(($fin->getTimestamp() - $inicio->getTimestamp()) / 60, 2);

            // Finalizar la actividad actual
            $this->bitacoraModel->update($act['id_bitacora'], [
                'hora_fin'         => $ahora,
                'duracion_minutos' => $duracion,
                'estado'           => 'finalizada',
            ]);

            // Crear continuación para el siguiente periodo
            $this->bitacoraModel->insert([
                'id_usuario'       => $act['id_usuario'],
                'numero_actividad' => $this->bitacoraModel->getNextNumeroActividad((int) $act['id_usuario'], date('Y-m-d')),
                'descripcion'      => '(cont.) ' . $act['descripcion'],
                'id_centro_costo'  => $act['id_centro_costo'],
                'fecha'            => date('Y-m-d'),
                'hora_inicio'      => $ahora,
                'hora_fin'         => null,
                'duracion_minutos' => null,
                'estado'           => 'en_progreso',
            ]);
        }

        // 2. Calcular días hábiles (transcurridos y meta quincenal)
        $diasTranscurridos = $festivoModel->contarDiasHabiles($fechaInicio, $ahora);
        $fechaFinQuincena = date('Y-m-d', strtotime(substr($fechaInicio, 0, 10) . ' +14 days'));
        $diasHabiles = $festivoModel->contarDiasHabiles($fechaInicio, $fechaFinQuincena);

        // 3. Crear registro de liquidación
        $liquidacionModel->insert([
            'fecha_inicio'  => $fechaInicio,
            'fecha_corte'   => $ahora,
            'dias_habiles'  => $diasHabiles,
            'ejecutado_por' => (int) session()->get('id_users'),
            'notas'         => $notas,
        ]);
        $idLiquidacion = $liquidacionModel->getInsertID();

        // 4. Novedades de tiempo
        $novedadColModel = new \App\Models\NovedadColectivaModel();
        $novedadIndModel = new \App\Models\NovedadIndividualModel();
        $horasColectivas = $novedadColModel->getHorasColectivasRango($fechaInicio, $fechaFinQuincena);

        // 5. Calcular por usuario
        $usuarios = $userModel->where('activo', 1)->where('bitacora_habilitada', 1)->findAll();
        $detalles = [];

        foreach ($usuarios as $u) {
            $jornada = $u['jornada'] ?? 'completa';
            $horasIndividuales = $novedadIndModel->getHorasIndividualesRango((int) $u['id_users'], $fechaInicio, $fechaFinQuincena);
            $horasMeta = calcularMetaHoras($diasHabiles, $jornada, $horasColectivas, $horasIndividuales);

            $minutos = $this->bitacoraModel->getTotalMinutosRango((int) $u['id_users'], $fechaInicio, $ahora);
            $horasTrabajadas = round($minutos / 60, 2);

            $porcentaje = $horasMeta > 0 ? round(($horasTrabajadas / $horasMeta) * 100, 2) : 0;

            $detalleModel->insert([
                'id_liquidacion'        => $idLiquidacion,
                'id_usuario'            => $u['id_users'],
                'jornada'               => $jornada,
                'dias_habiles'          => $diasHabiles,
                'horas_meta'            => $horasMeta,
                'horas_trabajadas'      => $horasTrabajadas,
                'porcentaje_cumplimiento' => $porcentaje,
            ]);

            $detalles[] = [
                'usuario'   => $u,
                'jornada'   => $jornada,
                'horas_meta' => $horasMeta,
                'horas_trabajadas' => $horasTrabajadas,
                'porcentaje' => $porcentaje,
            ];
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return $this->response->setJSON(['error' => 'Error en la transacción'])->setStatusCode(500);
        }

        // 5. Enviar emails de liquidación
        $notificador = new \App\Libraries\NotificadorBitacora();
        $emailsConfig = env('BITACORA_REPORT_EMAILS', '');
        $copias = array_filter(array_map('trim', explode(',', $emailsConfig)));

        foreach ($detalles as $d) {
            $html = $this->generarHTMLLiquidacion($d, $fechaInicio, $ahora, $diasHabiles);
            $asunto = "Liquidación Quincenal — {$d['usuario']['nombre_completo']}";
            $notificador->enviarEmail(
                $d['usuario']['correo'],
                $d['usuario']['nombre_completo'],
                $asunto,
                $html,
                $copias
            );
        }

        return $this->response->setJSON([
            'ok'             => true,
            'id_liquidacion' => $idLiquidacion,
            'actividades_cortadas' => count($enProgreso),
        ]);
    }

    /**
     * Detalle de una liquidación pasada (AJAX GET)
     */
    public function detalleLiquidacion($id)
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $liquidacionModel = new \App\Models\LiquidacionModel();
        $detalle = $liquidacionModel->getDetalle((int) $id);

        return $this->response->setJSON(['ok' => true, 'detalle' => $detalle]);
    }

    /**
     * HTML del email de liquidación
     */
    private function generarHTMLLiquidacion(array $d, string $inicio, string $corte, int $diasHabiles): string
    {
        $inicioFmt = date('d/m/Y h:i A', strtotime($inicio));
        $corteFmt  = date('d/m/Y h:i A', strtotime($corte));
        $jornadaTxt = $d['jornada'] === 'media' ? 'Media jornada (4h/día, 90%)' : 'Jornada completa (8h/día, 80%)';

        $color = '#dc3545'; // rojo
        if ($d['porcentaje'] >= 100) $color = '#198754'; // verde
        elseif ($d['porcentaje'] >= 80) $color = '#ffc107'; // amarillo

        $nombre = htmlspecialchars($d['usuario']['nombre_completo']);
        $cargo  = htmlspecialchars($d['usuario']['cargo'] ?? '');

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #2c3e50; padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 22px;'>Liquidación Quincenal</h1>
                <p style='color: rgba(255,255,255,0.7); margin: 5px 0 0 0; font-size: 13px;'>Periodo: {$inicioFmt} — {$corteFmt}</p>
            </div>
            <div style='padding: 25px; background: #f8f9fa;'>
                <div style='background: white; border-radius: 8px; padding: 15px; margin-bottom: 20px;'>
                    <table style='width: 100%; font-size: 14px;'>
                        <tr><td style='color: #6c757d;'>Usuario:</td><td style='font-weight: bold;'>{$nombre}</td></tr>
                        <tr><td style='color: #6c757d;'>Cargo:</td><td>{$cargo}</td></tr>
                        <tr><td style='color: #6c757d;'>Jornada:</td><td>{$jornadaTxt}</td></tr>
                        <tr><td style='color: #6c757d;'>Días hábiles:</td><td>{$diasHabiles}</td></tr>
                    </table>
                </div>
                <div style='background: white; border-radius: 8px; padding: 20px; text-align: center;'>
                    <table style='width: 100%; font-size: 14px; margin-bottom: 15px;'>
                        <tr>
                            <td style='text-align: center;'>
                                <div style='font-size: 12px; color: #6c757d;'>Horas Meta</div>
                                <div style='font-size: 24px; font-weight: bold;'>{$d['horas_meta']}h</div>
                            </td>
                            <td style='text-align: center;'>
                                <div style='font-size: 12px; color: #6c757d;'>Horas Trabajadas</div>
                                <div style='font-size: 24px; font-weight: bold;'>{$d['horas_trabajadas']}h</div>
                            </td>
                        </tr>
                    </table>
                    <div style='font-size: 14px; color: #6c757d; margin-bottom: 8px;'>Porcentaje de Pago</div>
                    <div style='font-size: 48px; font-weight: 900; color: {$color};'>{$d['porcentaje']}%</div>
                    <div style='background: #e9ecef; border-radius: 10px; height: 20px; margin-top: 15px; overflow: hidden;'>
                        <div style='background: {$color}; height: 100%; width: " . min($d['porcentaje'], 100) . "%; border-radius: 10px;'></div>
                    </div>
                </div>
            </div>
            <div style='padding: 15px; background: #e9ecef; text-align: center; font-size: 11px; color: #6c757d;'>
                Generado por Bitácora Cycloid
            </div>
        </div>";
    }

    // ================================================================
    // Festivos
    // ================================================================

    /**
     * CRUD de días festivos
     */
    public function festivos($anio = null)
    {
        if (!$this->verificarAcceso()) return redirect()->to('/login');
        if (!$this->esAdminBitacora()) return redirect()->to('bitacora');

        $anio = $anio ?: (int) date('Y');
        $festivoModel = new \App\Models\DiaFestivoModel();

        return view('bitacora/festivos', [
            'tab'      => 'liquidacion',
            'anio'     => $anio,
            'festivos' => $festivoModel->getFestivosAnio($anio),
        ]);
    }

    public function guardarFestivo()
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $fecha = $this->request->getPost('fecha');
        $descripcion = $this->request->getPost('descripcion');

        if (!$fecha || !$descripcion) {
            return $this->response->setJSON(['error' => 'Fecha y descripción son requeridas'])->setStatusCode(400);
        }

        $festivoModel = new \App\Models\DiaFestivoModel();
        $anio = (int) date('Y', strtotime($fecha));

        $festivoModel->insert([
            'fecha'       => $fecha,
            'descripcion' => $descripcion,
            'anio'        => $anio,
        ]);

        return $this->response->setJSON(['ok' => true, 'id' => $festivoModel->getInsertID()]);
    }

    public function eliminarFestivo($id)
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $festivoModel = new \App\Models\DiaFestivoModel();
        $festivoModel->delete($id);

        return $this->response->setJSON(['ok' => true]);
    }

    // ========================================
    // NOVEDADES DE TIEMPO
    // ========================================

    /**
     * Novedades Colectivas (Fechas Especiales) — GET vista CRUD
     */
    public function novedadesColectivas($anio = null)
    {
        if (!$this->verificarAcceso()) return redirect()->to('/login');
        if (!$this->esAdminBitacora()) return redirect()->to('bitacora');

        $anio = $anio ?: (int) date('Y');
        $model = new \App\Models\NovedadColectivaModel();

        return view('bitacora/novedades_colectivas', [
            'tab'       => 'liquidacion',
            'anio'      => $anio,
            'novedades' => $model->getNovedadesAnio($anio),
        ]);
    }

    /**
     * Guardar novedad colectiva — POST AJAX
     */
    public function guardarNovedadColectiva()
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $fecha = $this->request->getPost('fecha');
        $descripcion = $this->request->getPost('descripcion');
        $horasReduccion = (float) $this->request->getPost('horas_reduccion');

        if (!$fecha || !$descripcion || $horasReduccion <= 0) {
            return $this->response->setJSON(['error' => 'Todos los campos son requeridos'])->setStatusCode(400);
        }

        $model = new \App\Models\NovedadColectivaModel();
        $model->insert([
            'fecha'           => $fecha,
            'descripcion'     => $descripcion,
            'horas_reduccion' => $horasReduccion,
            'anio'            => (int) date('Y', strtotime($fecha)),
            'created_by'      => (int) session()->get('id_users'),
        ]);

        return $this->response->setJSON(['ok' => true, 'id' => $model->getInsertID()]);
    }

    /**
     * Eliminar novedad colectiva — POST AJAX
     */
    public function eliminarNovedadColectiva($id)
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $model = new \App\Models\NovedadColectivaModel();
        $model->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Novedades Individuales — GET vista CRUD
     */
    public function novedadesIndividuales()
    {
        if (!$this->verificarAcceso()) return redirect()->to('/login');
        if (!$this->esAdminBitacora()) return redirect()->to('bitacora');

        $userModel = new \App\Models\UserModel();
        $model = new \App\Models\NovedadIndividualModel();

        // Mostrar novedades del año actual
        $desde = date('Y') . '-01-01';
        $hasta = date('Y') . '-12-31';

        return view('bitacora/novedades_individuales', [
            'tab'       => 'liquidacion',
            'usuarios'  => $userModel->where('activo', 1)->where('bitacora_habilitada', 1)->orderBy('nombre_completo')->findAll(),
            'novedades' => $model->getNovedadesRango($desde, $hasta),
        ]);
    }

    /**
     * Guardar novedad individual — POST AJAX
     */
    public function guardarNovedadIndividual()
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $idUsuario = (int) $this->request->getPost('id_usuario');
        $fecha = $this->request->getPost('fecha');
        $horasReduccion = (float) $this->request->getPost('horas_reduccion');
        $motivo = $this->request->getPost('motivo');

        if (!$idUsuario || !$fecha || $horasReduccion <= 0 || !$motivo) {
            return $this->response->setJSON(['error' => 'Todos los campos son requeridos'])->setStatusCode(400);
        }

        $model = new \App\Models\NovedadIndividualModel();
        $model->insert([
            'id_usuario'      => $idUsuario,
            'fecha'           => $fecha,
            'horas_reduccion' => $horasReduccion,
            'motivo'          => $motivo,
            'created_by'      => (int) session()->get('id_users'),
        ]);

        return $this->response->setJSON(['ok' => true, 'id' => $model->getInsertID()]);
    }

    /**
     * Eliminar novedad individual — POST AJAX
     */
    public function eliminarNovedadIndividual($id)
    {
        if (!$this->verificarAcceso() || !$this->esAdminBitacora()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $model = new \App\Models\NovedadIndividualModel();
        $model->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    // ========================================
    // MÓDULO DE CORRECCIONES
    // ========================================

    /**
     * Solicitar corrección de hora_fin — POST AJAX (autenticado)
     */
    public function solicitarCorreccion()
    {
        if (!$this->verificarAcceso()) {
            return $this->response->setJSON(['error' => 'Sin acceso'])->setStatusCode(403);
        }

        $idBitacora = (int) $this->request->getPost('id_bitacora');
        $horaFinNueva = $this->request->getPost('hora_fin_nueva');
        $motivo = $this->request->getPost('motivo') ?? '';

        if (!$idBitacora || !$horaFinNueva) {
            return $this->response->setJSON(['error' => 'Datos incompletos'])->setStatusCode(400);
        }

        // Verificar que la actividad exista, sea del usuario y esté finalizada
        $actividad = $this->bitacoraModel
            ->select('bitacora_actividades.*, centros_costo.nombre AS centro_costo_nombre')
            ->join('centros_costo', 'centros_costo.id_centro_costo = bitacora_actividades.id_centro_costo', 'left')
            ->find($idBitacora);
        if (!$actividad) {
            return $this->response->setJSON(['error' => 'Actividad no encontrada'])->setStatusCode(404);
        }

        $idUsuario = (int) session()->get('id_users');
        $esAdmin = $this->esAdminBitacora();

        if ((int) $actividad['id_usuario'] !== $idUsuario && !$esAdmin) {
            return $this->response->setJSON(['error' => 'No tienes permiso'])->setStatusCode(403);
        }

        if ($actividad['estado'] !== 'finalizada') {
            return $this->response->setJSON(['error' => 'Solo se pueden corregir actividades finalizadas'])->setStatusCode(400);
        }

        $correccionModel = new \App\Models\BitacoraCorreccionModel();

        if ($correccionModel->tienePendiente($idBitacora)) {
            return $this->response->setJSON(['error' => 'Ya existe una corrección pendiente para esta actividad'])->setStatusCode(400);
        }

        // Construir la hora_fin completa (misma fecha que la actividad + hora nueva)
        // Si la hora nueva es tipo "18:15" la combinamos con la fecha
        $valorAnterior = $actividad['hora_fin'];
        $fechaBase = $actividad['fecha'];

        // Aceptar formato "HH:MM" o datetime completo
        if (strlen($horaFinNueva) <= 5) {
            $valorNuevo = $fechaBase . ' ' . $horaFinNueva . ':00';
        } else {
            $valorNuevo = $horaFinNueva;
        }

        // Validar que la nueva hora sea posterior a hora_inicio
        if (strtotime($valorNuevo) <= strtotime($actividad['hora_inicio'])) {
            return $this->response->setJSON(['error' => 'La hora de fin debe ser posterior a la hora de inicio'])->setStatusCode(400);
        }

        $token = bin2hex(random_bytes(32));

        $correccionModel->insert([
            'id_bitacora'    => $idBitacora,
            'id_usuario'     => (int) $actividad['id_usuario'],
            'campo'          => 'hora_fin',
            'valor_anterior' => $valorAnterior,
            'valor_nuevo'    => $valorNuevo,
            'motivo'         => $motivo,
            'token'          => $token,
            'token_expira'   => date('Y-m-d H:i:s', strtotime('+48 hours')),
        ]);

        // Enviar email al admin
        $this->enviarEmailCorreccion($actividad, $valorAnterior, $valorNuevo, $motivo, $token);

        return $this->response->setJSON(['ok' => true, 'mensaje' => 'Solicitud enviada para aprobación']);
    }

    /**
     * Ver corrección pendiente por token — GET público (sin login)
     */
    public function verCorreccion(string $token)
    {
        $correccionModel = new \App\Models\BitacoraCorreccionModel();
        $correccion = $correccionModel->getByToken($token);

        if (!$correccion) {
            return view('bitacora/correcciones/error', [
                'mensaje' => 'El enlace de corrección es inválido, ya fue procesado o ha expirado.'
            ]);
        }

        $detalle = $correccionModel->getDetalleConActividad((int) $correccion['id_correccion']);

        // Calcular duraciones
        $duracionAnterior = round((strtotime($detalle['valor_anterior']) - strtotime($detalle['hora_inicio'])) / 60, 2);
        $duracionNueva = round((strtotime($detalle['valor_nuevo']) - strtotime($detalle['hora_inicio'])) / 60, 2);
        $diferencia = $duracionNueva - $duracionAnterior;

        return view('bitacora/correcciones/ver', [
            'correccion'       => $detalle,
            'token'            => $token,
            'duracion_anterior' => $duracionAnterior,
            'duracion_nueva'   => $duracionNueva,
            'diferencia'       => $diferencia,
        ]);
    }

    /**
     * Aprobar corrección — POST público (token)
     */
    public function aprobarCorreccion(string $token)
    {
        $correccionModel = new \App\Models\BitacoraCorreccionModel();
        $correccion = $correccionModel->getByToken($token);

        if (!$correccion) {
            return view('bitacora/correcciones/error', [
                'mensaje' => 'El enlace es inválido, ya fue procesado o ha expirado.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $actividad = $this->bitacoraModel->find((int) $correccion['id_bitacora']);

        // 1. Actualizar la actividad
        $nuevaHoraFin = $correccion['valor_nuevo'];
        $nuevaDuracion = round((strtotime($nuevaHoraFin) - strtotime($actividad['hora_inicio'])) / 60, 2);

        $this->bitacoraModel->update($correccion['id_bitacora'], [
            'hora_fin'         => $nuevaHoraFin,
            'duracion_minutos' => $nuevaDuracion,
        ]);

        // 2. Marcar corrección como aprobada
        $correccionModel->update($correccion['id_correccion'], [
            'estado'           => 'aprobada',
            'aprobado_por'     => 'token',
            'fecha_resolucion' => date('Y-m-d H:i:s'),
        ]);

        // 3. Re-liquidar si la actividad cae en un periodo ya liquidado
        $this->reliquidarSiNecesario($actividad);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return view('bitacora/correcciones/error', [
                'mensaje' => 'Error al procesar la corrección. Intente de nuevo.'
            ]);
        }

        $detalle = $correccionModel->getDetalleConActividad((int) $correccion['id_correccion']);

        return view('bitacora/correcciones/resultado', [
            'tipo'       => 'aprobada',
            'correccion' => $detalle,
            'duracion_nueva' => $nuevaDuracion,
        ]);
    }

    /**
     * Rechazar corrección — POST público (token)
     */
    public function rechazarCorreccion(string $token)
    {
        $correccionModel = new \App\Models\BitacoraCorreccionModel();
        $correccion = $correccionModel->getByToken($token);

        if (!$correccion) {
            return view('bitacora/correcciones/error', [
                'mensaje' => 'El enlace es inválido, ya fue procesado o ha expirado.'
            ]);
        }

        $correccionModel->update($correccion['id_correccion'], [
            'estado'           => 'rechazada',
            'aprobado_por'     => 'token',
            'fecha_resolucion' => date('Y-m-d H:i:s'),
        ]);

        $detalle = $correccionModel->getDetalleConActividad((int) $correccion['id_correccion']);

        return view('bitacora/correcciones/resultado', [
            'tipo'       => 'rechazada',
            'correccion' => $detalle,
        ]);
    }

    /**
     * Re-liquida el detalle de un usuario si la actividad cae en un periodo ya liquidado
     */
    protected function reliquidarSiNecesario(array $actividad): void
    {
        $db = \Config\Database::connect();

        // Buscar liquidación que contenga esta actividad
        $liquidacion = $db->query("
            SELECT * FROM liquidaciones_bitacora
            WHERE fecha_inicio <= ? AND fecha_corte >= ?
            ORDER BY id_liquidacion DESC LIMIT 1
        ", [$actividad['hora_inicio'], $actividad['hora_inicio']])->getRowArray();

        if (!$liquidacion) return;

        $userModel = new \App\Models\UserModel();
        $novedadColModel = new \App\Models\NovedadColectivaModel();
        $novedadIndModel = new \App\Models\NovedadIndividualModel();

        $usuario = $userModel->find((int) $actividad['id_usuario']);
        if (!$usuario) return;

        // Recalcular horas trabajadas del usuario en ese periodo
        $minutos = $this->bitacoraModel->getTotalMinutosRango(
            (int) $actividad['id_usuario'],
            $liquidacion['fecha_inicio'],
            $liquidacion['fecha_corte']
        );
        $horasTrabajadas = round($minutos / 60, 2);

        // Recalcular meta con novedades de tiempo
        $jornada = $usuario['jornada'] ?? 'completa';
        $fechaFinQ = date('Y-m-d', strtotime(substr($liquidacion['fecha_inicio'], 0, 10) . ' +14 days'));
        $horasColectivas = $novedadColModel->getHorasColectivasRango($liquidacion['fecha_inicio'], $fechaFinQ);
        $horasIndividuales = $novedadIndModel->getHorasIndividualesRango((int) $actividad['id_usuario'], $liquidacion['fecha_inicio'], $fechaFinQ);
        $horasMeta = calcularMetaHoras((int) $liquidacion['dias_habiles'], $jornada, $horasColectivas, $horasIndividuales);
        $porcentaje = $horasMeta > 0 ? round(($horasTrabajadas / $horasMeta) * 100, 2) : 0;

        // Actualizar el detalle de liquidación
        $db->query("
            UPDATE detalle_liquidacion
            SET horas_trabajadas = ?, porcentaje_cumplimiento = ?
            WHERE id_liquidacion = ? AND id_usuario = ?
        ", [$horasTrabajadas, $porcentaje, $liquidacion['id_liquidacion'], $actividad['id_usuario']]);
    }

    /**
     * Envía email de solicitud de corrección al admin
     */
    protected function enviarEmailCorreccion(array $actividad, string $valorAnterior, string $valorNuevo, string $motivo, string $token): void
    {
        $userModel = new \App\Models\UserModel();
        $usuario = $userModel->find((int) $actividad['id_usuario']);

        $durAnterior = round((strtotime($valorAnterior) - strtotime($actividad['hora_inicio'])) / 60);
        $durNueva = round((strtotime($valorNuevo) - strtotime($actividad['hora_inicio'])) / 60);

        $hAnterior = floor($durAnterior / 60) . 'h ' . ($durAnterior % 60) . 'min';
        $hNueva = floor($durNueva / 60) . 'h ' . ($durNueva % 60) . 'min';

        $enlace = base_url("bitacora-correccion/{$token}");
        $motivoHTML = $motivo ? '<p style="margin: 10px 0; padding: 10px; background: #fff3cd; border-radius: 6px; font-size: 13px;"><strong>Motivo:</strong> ' . htmlspecialchars($motivo) . '</p>' : '';

        $cc = htmlspecialchars($actividad['centro_costo_nombre'] ?? $actividad['id_centro_costo'] ?? '-');

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #2c3e50; padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 20px;'>Solicitud de Corrección</h1>
                <p style='color: rgba(255,255,255,0.7); margin: 5px 0 0 0; font-size: 13px;'>Bitácora Cycloid</p>
            </div>

            <div style='padding: 25px; background: #f8f9fa;'>
                <div style='background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px;'>
                    <table style='width: 100%; font-size: 14px;'>
                        <tr>
                            <td style='color: #6c757d; padding: 4px 0;'>Usuario:</td>
                            <td style='font-weight: bold;'>{$usuario['nombre_completo']}</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d; padding: 4px 0;'>Actividad:</td>
                            <td>" . htmlspecialchars($actividad['descripcion']) . "</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d; padding: 4px 0;'>Centro de costo:</td>
                            <td>{$cc}</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d; padding: 4px 0;'>Fecha:</td>
                            <td>" . date('d/m/Y', strtotime($actividad['fecha'])) . "</td>
                        </tr>
                    </table>
                </div>

                <div style='background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px;'>
                    <h3 style='margin: 0 0 10px 0; font-size: 15px; color: #2c3e50;'>Cambio solicitado</h3>
                    <table style='width: 100%; font-size: 14px; border-collapse: collapse;'>
                        <tr style='background: #f1f3f5;'>
                            <th style='padding: 8px; text-align: left;'></th>
                            <th style='padding: 8px; text-align: center;'>Hora Fin</th>
                            <th style='padding: 8px; text-align: center;'>Duración</th>
                        </tr>
                        <tr>
                            <td style='padding: 8px; font-weight: bold; color: #dc3545;'>Antes</td>
                            <td style='padding: 8px; text-align: center;'>" . date('h:i A', strtotime($valorAnterior)) . "</td>
                            <td style='padding: 8px; text-align: center;'>{$hAnterior}</td>
                        </tr>
                        <tr style='background: #d4edda;'>
                            <td style='padding: 8px; font-weight: bold; color: #198754;'>Después</td>
                            <td style='padding: 8px; text-align: center; font-weight: bold;'>" . date('h:i A', strtotime($valorNuevo)) . "</td>
                            <td style='padding: 8px; text-align: center; font-weight: bold;'>{$hNueva}</td>
                        </tr>
                    </table>
                </div>

                {$motivoHTML}

                <div style='text-align: center; margin-top: 20px;'>
                    <a href='{$enlace}' style='display: inline-block; background: #198754; color: white; padding: 14px 40px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px;'>
                        Revisar y Aprobar
                    </a>
                </div>

                <p style='text-align: center; color: #6c757d; font-size: 12px; margin-top: 15px;'>
                    Este enlace expira en 48 horas.
                </p>
            </div>

            <div style='padding: 15px; background: #e9ecef; text-align: center; font-size: 11px; color: #6c757d;'>
                <p style='margin: 0;'>Bitácora Cycloid — Módulo de Correcciones</p>
            </div>
        </div>";

        $adminEmail = env('BITACORA_ADMIN_CORRECCIONES', 'edison.cuervo@cycloidtalent.com');
        $notificador = new \App\Libraries\NotificadorBitacora();
        $notificador->enviarEmail(
            $adminEmail,
            'Edison Cuervo',
            "Corrección solicitada — {$usuario['nombre_completo']} — " . date('d/m/Y', strtotime($actividad['fecha'])),
            $html
        );
    }
}
