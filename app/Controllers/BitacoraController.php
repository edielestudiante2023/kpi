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

        // Días hábiles del periodo
        $diasHabiles = $festivoModel->contarDiasHabiles($fechaInicio, $fechaHoy);

        // Preview de cada usuario
        $usuarios = $userModel->where('activo', 1)->where('bitacora_habilitada', 1)->findAll();
        $preview = [];
        foreach ($usuarios as $u) {
            $minutos = $this->bitacoraModel->getTotalMinutosRango((int) $u['id_users'], $fechaInicio, $fechaHoy);
            $horasTrabajadas = round($minutos / 60, 2);

            $jornada = $u['jornada'] ?? 'completa';
            $horasDia = $jornada === 'media' ? 4 : 8;
            $eficiencia = $jornada === 'media' ? 0.90 : 0.80;
            $horasMeta = round($diasHabiles * $horasDia * $eficiencia, 2);

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
            'tab'          => 'liquidacion',
            'fechaInicio'  => $fechaInicio,
            'fechaHoy'     => $fechaHoy,
            'diasHabiles'  => $diasHabiles,
            'preview'      => $preview,
            'historial'    => $historial,
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

        // 2. Calcular días hábiles
        $diasHabiles = $festivoModel->contarDiasHabiles($fechaInicio, $ahora);

        // 3. Crear registro de liquidación
        $liquidacionModel->insert([
            'fecha_inicio'  => $fechaInicio,
            'fecha_corte'   => $ahora,
            'dias_habiles'  => $diasHabiles,
            'ejecutado_por' => (int) session()->get('id_users'),
            'notas'         => $notas,
        ]);
        $idLiquidacion = $liquidacionModel->getInsertID();

        // 4. Calcular por usuario
        $usuarios = $userModel->where('activo', 1)->where('bitacora_habilitada', 1)->findAll();
        $detalles = [];

        foreach ($usuarios as $u) {
            $jornada = $u['jornada'] ?? 'completa';
            $horasDia = $jornada === 'media' ? 4 : 8;
            $eficiencia = $jornada === 'media' ? 0.90 : 0.80;
            $horasMeta = round($diasHabiles * $horasDia * $eficiencia, 2);

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
}
