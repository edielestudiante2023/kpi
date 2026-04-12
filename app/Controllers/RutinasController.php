<?php

namespace App\Controllers;

use App\Models\RutinaActividadModel;
use App\Models\RutinaAsignacionModel;
use App\Models\RutinaRegistroModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class RutinasController extends BaseController
{
    protected $actividadModel;
    protected $asignacionModel;
    protected $registroModel;
    protected $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->actividadModel  = new RutinaActividadModel();
        $this->asignacionModel = new RutinaAsignacionModel();
        $this->registroModel   = new RutinaRegistroModel();
        $this->userModel       = new UserModel();
    }

    // =========================================================
    // CRUD ACTIVIDADES (tabla maestra)
    // =========================================================

    public function listActividades()
    {
        $data['actividades'] = $this->actividadModel->orderBy('nombre', 'ASC')->findAll();
        return view('rutinas/list_actividades', $data);
    }

    public function addActividad()
    {
        return view('rutinas/add_actividad');
    }

    public function addActividadPost()
    {
        $rules = [
            'nombre'    => 'required|max_length[255]',
            'descripcion' => 'permit_empty',
            'frecuencia'  => 'required|in_list[L-V,diaria]',
            'peso'        => 'required|decimal',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }
        $this->actividadModel->insert([
            'nombre'      => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'frecuencia'  => $this->request->getPost('frecuencia'),
            'peso'        => $this->request->getPost('peso'),
            'activa'      => 1,
        ]);
        return redirect()->to('/rutinas/actividades')->with('success', 'Actividad creada.');
    }

    public function editActividad($id)
    {
        $actividad = $this->actividadModel->find($id);
        if (! $actividad) {
            throw new PageNotFoundException("Actividad con ID $id no existe");
        }
        return view('rutinas/edit_actividad', ['actividad' => $actividad]);
    }

    public function editActividadPost($id)
    {
        $rules = [
            'nombre'    => 'required|max_length[255]',
            'descripcion' => 'permit_empty',
            'frecuencia'  => 'required|in_list[L-V,diaria]',
            'peso'        => 'required|decimal',
            'activa'      => 'required|in_list[0,1]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }
        $this->actividadModel->update($id, [
            'nombre'      => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'frecuencia'  => $this->request->getPost('frecuencia'),
            'peso'        => $this->request->getPost('peso'),
            'activa'      => $this->request->getPost('activa'),
        ]);
        return redirect()->to('/rutinas/actividades')->with('success', 'Actividad actualizada.');
    }

    public function deleteActividad($id)
    {
        $this->actividadModel->delete($id);
        return redirect()->to('/rutinas/actividades')->with('success', 'Actividad eliminada.');
    }

    // =========================================================
    // CRUD ASIGNACIONES (quién hace qué)
    // =========================================================

    public function listAsignaciones()
    {
        $db = \Config\Database::connect();
        $data['asignaciones'] = $db->query("
            SELECT ra.id_asignacion, ra.activa,
                   u.id_users, u.nombre_completo, u.correo,
                   a.id_actividad, a.nombre AS actividad_nombre
            FROM rutinas_asignaciones ra
            JOIN users u ON u.id_users = ra.id_users
            JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
            ORDER BY u.nombre_completo, a.nombre
        ")->getResultArray();

        $data['usuarios']    = $this->userModel->where('activo', 1)->orderBy('nombre_completo')->findAll();
        $data['actividades'] = $this->actividadModel->where('activa', 1)->orderBy('nombre')->findAll();

        return view('rutinas/list_asignaciones', $data);
    }

    public function addAsignacionPost()
    {
        $idUsers     = $this->request->getPost('id_users');
        $actividades = $this->request->getPost('actividades'); // array de ids

        if (empty($idUsers) || empty($actividades)) {
            return redirect()->back()->with('error', 'Selecciona usuario y al menos una actividad.');
        }

        $insertadas = 0;
        foreach ($actividades as $idAct) {
            $existe = $this->asignacionModel
                ->where('id_users', $idUsers)
                ->where('id_actividad', $idAct)
                ->first();
            if (! $existe) {
                $this->asignacionModel->insert([
                    'id_users'     => $idUsers,
                    'id_actividad' => $idAct,
                    'activa'       => 1,
                ]);
                $insertadas++;
            }
        }

        return redirect()->to('/rutinas/asignaciones')
            ->with('success', "$insertadas asignación(es) creada(s).");
    }

    public function deleteAsignacion($id)
    {
        $this->asignacionModel->delete($id);
        return redirect()->to('/rutinas/asignaciones')->with('success', 'Asignación eliminada.');
    }

    // =========================================================
    // CALENDARIO (vista interna, autenticada)
    // =========================================================

    public function calendario()
    {
        $mes  = (int) ($this->request->getGet('mes')  ?: date('n'));
        $anio = (int) ($this->request->getGet('anio') ?: date('Y'));
        $idUser = (int) ($this->request->getGet('usuario') ?: 0);

        // Días hábiles (L-V) del mes
        $diasHabiles = [];
        $totalDias = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        for ($d = 1; $d <= $totalDias; $d++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
            $dow = (int) date('N', strtotime($fecha)); // 1=Lun .. 5=Vie
            if ($dow <= 5) {
                $diasHabiles[] = [
                    'fecha'     => $fecha,
                    'dia'       => $d,
                    'dia_letra' => ['L','M','X','J','V'][$dow - 1],
                ];
            }
        }

        // Usuarios con asignaciones activas
        $db = \Config\Database::connect();
        $data['usuariosConRutinas'] = $db->query("
            SELECT DISTINCT u.id_users, u.nombre_completo
            FROM rutinas_asignaciones ra
            JOIN users u ON u.id_users = ra.id_users
            WHERE ra.activa = 1
            ORDER BY u.nombre_completo
        ")->getResultArray();

        // Si no hay usuario seleccionado, tomar el primero
        if (! $idUser && ! empty($data['usuariosConRutinas'])) {
            $idUser = (int) $data['usuariosConRutinas'][0]['id_users'];
        }

        // Actividades asignadas al usuario
        $actividades = [];
        if ($idUser) {
            $actividades = $db->query("
                SELECT a.id_actividad, a.nombre, a.peso
                FROM rutinas_asignaciones ra
                JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
                WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
                ORDER BY a.nombre
            ", [$idUser])->getResultArray();
        }

        // Registros del mes
        $registros = [];
        if ($idUser) {
            $rows = $this->registroModel
                ->where('id_users', $idUser)
                ->where('fecha >=', sprintf('%04d-%02d-01', $anio, $mes))
                ->where('fecha <=', sprintf('%04d-%02d-%02d', $anio, $mes, $totalDias))
                ->where('completada', 1)
                ->findAll();
            foreach ($rows as $r) {
                $registros[$r['id_actividad'] . '_' . $r['fecha']] = true;
            }
        }

        // Puntajes diarios
        $puntajeDiario = [];
        $totalPeso = array_sum(array_column($actividades, 'peso'));
        foreach ($diasHabiles as $dh) {
            $sumaPeso = 0;
            foreach ($actividades as $act) {
                $key = $act['id_actividad'] . '_' . $dh['fecha'];
                if (isset($registros[$key])) {
                    $sumaPeso += $act['peso'];
                }
            }
            $puntajeDiario[$dh['fecha']] = $totalPeso > 0
                ? round(($sumaPeso / $totalPeso) * 100)
                : 0;
        }

        // Acumulados semanales
        $semanales = [];
        $semanaActual = null;
        $semanaSum = 0;
        $semanaDias = 0;
        foreach ($diasHabiles as $dh) {
            $semana = (int) date('W', strtotime($dh['fecha']));
            if ($semanaActual !== null && $semana !== $semanaActual) {
                $semanales[$semanaActual] = $semanaDias > 0
                    ? round($semanaSum / $semanaDias)
                    : 0;
                $semanaSum = 0;
                $semanaDias = 0;
            }
            $semanaActual = $semana;
            $semanaSum += $puntajeDiario[$dh['fecha']];
            $semanaDias++;
        }
        if ($semanaActual !== null) {
            $semanales[$semanaActual] = $semanaDias > 0
                ? round($semanaSum / $semanaDias)
                : 0;
        }

        // Acumulado mensual
        $totalPuntaje = array_sum($puntajeDiario);
        $cantDias = count(array_filter($puntajeDiario, fn($v) => $v > 0));
        // Solo promediar los días que ya pasaron (hasta hoy)
        $hoy = date('Y-m-d');
        $diasPasados = 0;
        $sumaPasados = 0;
        foreach ($diasHabiles as $dh) {
            if ($dh['fecha'] <= $hoy) {
                $diasPasados++;
                $sumaPasados += $puntajeDiario[$dh['fecha']];
            }
        }
        $acumuladoMensual = $diasPasados > 0 ? round($sumaPasados / $diasPasados) : 0;

        $data['diasHabiles']      = $diasHabiles;
        $data['actividades']      = $actividades;
        $data['registros']        = $registros;
        $data['puntajeDiario']    = $puntajeDiario;
        $data['semanales']        = $semanales;
        $data['acumuladoMensual'] = $acumuladoMensual;
        $data['mes']              = $mes;
        $data['anio']             = $anio;
        $data['idUser']           = $idUser;
        $data['hoy']              = $hoy;

        return view('rutinas/calendario', $data);
    }

    // =========================================================
    // VISTA PÚBLICA (token, sin auth) — checklist diario
    // =========================================================

    private function generarTokenRutina(int $userId, string $fecha): string
    {
        return substr(hash('sha256', $userId . '|' . $fecha . '|rutinas2026'), 0, 24);
    }

    public function checklistPublico(int $userId, string $fecha, string $token)
    {
        // Validar token
        $esperado = $this->generarTokenRutina($userId, $fecha);
        if (! hash_equals($esperado, $token)) {
            return view('rutinas/checklist_error', ['mensaje' => 'Enlace no válido.']);
        }

        // Validar que sea día hábil
        $dow = (int) date('N', strtotime($fecha));
        if ($dow > 5) {
            return view('rutinas/checklist_error', ['mensaje' => 'No es un día hábil.']);
        }

        // Obtener usuario
        $usuario = $this->userModel->find($userId);
        if (! $usuario) {
            return view('rutinas/checklist_error', ['mensaje' => 'Usuario no encontrado.']);
        }

        // Actividades asignadas
        $db = \Config\Database::connect();
        $actividades = $db->query("
            SELECT a.id_actividad, a.nombre, a.descripcion, a.peso
            FROM rutinas_asignaciones ra
            JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
            WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
            ORDER BY a.nombre
        ", [$userId])->getResultArray();

        // Registros ya completados hoy
        $completados = [];
        $rows = $this->registroModel
            ->where('id_users', $userId)
            ->where('fecha', $fecha)
            ->where('completada', 1)
            ->findAll();
        foreach ($rows as $r) {
            $completados[$r['id_actividad']] = true;
        }

        return view('rutinas/checklist_publico', [
            'usuario'     => $usuario,
            'fecha'       => $fecha,
            'token'       => $token,
            'actividades' => $actividades,
            'completados' => $completados,
        ]);
    }

    public function updateChecklistPublico()
    {
        $userId = (int) $this->request->getPost('user_id');
        $fecha  = $this->request->getPost('fecha');
        $token  = $this->request->getPost('token');
        $idAct  = (int) $this->request->getPost('id_actividad');

        // Validar token
        $esperado = $this->generarTokenRutina($userId, $fecha);
        if (! hash_equals($esperado, $token)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Token inválido']);
        }

        // Validar que la actividad pertenece al usuario
        $asignacion = $this->asignacionModel
            ->where('id_users', $userId)
            ->where('id_actividad', $idAct)
            ->where('activa', 1)
            ->first();
        if (! $asignacion) {
            return $this->response->setJSON(['success' => false, 'message' => 'Actividad no asignada']);
        }

        // Verificar si ya existe registro
        $existe = $this->registroModel
            ->where('id_users', $userId)
            ->where('id_actividad', $idAct)
            ->where('fecha', $fecha)
            ->first();

        if ($existe) {
            // Ya marcado, no hacer nada
            return $this->response->setJSON(['success' => true]);
        }

        // Insertar registro
        $this->registroModel->insert([
            'id_users'         => $userId,
            'id_actividad'     => $idAct,
            'fecha'            => $fecha,
            'completada'       => 1,
            'hora_completado'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true]);
    }
}
